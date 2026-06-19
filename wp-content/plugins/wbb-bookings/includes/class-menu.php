<?php
/**
 * WBB_Menu — Food & Drink menu items (Food / Drinks / Platters).
 *
 * Stores items in wp_wbb_menu_items. Provides public read helpers used by both
 * the front-end Food & Drink page and the booking form's Extras step, plus admin
 * AJAX handlers for the tabbed, drag-to-reorder management screen.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Menu {

	/** Allowed categories, in display order. */
	const CATEGORIES = array( 'food', 'drinks', 'platters' );

	/** Human labels for each category. */
	public static function category_label( $key ) {
		$labels = array(
			'food'     => __( 'Food', 'wbb-bookings' ),
			'drinks'   => __( 'Drinks', 'wbb-bookings' ),
			'platters' => __( 'Platters', 'wbb-bookings' ),
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( $key );
	}

	// ── AJAX registration ──────────────────────────────────────────────────
	public static function register_ajax() {
		add_action( 'wp_ajax_wbb_menu_get_items', array( __CLASS__, 'ajax_get_items' ) );
		add_action( 'wp_ajax_wbb_menu_save_item', array( __CLASS__, 'ajax_save_item' ) );
		add_action( 'wp_ajax_wbb_menu_delete_item', array( __CLASS__, 'ajax_delete_item' ) );
		add_action( 'wp_ajax_wbb_menu_reorder', array( __CLASS__, 'ajax_reorder' ) );
	}

	// ── Public read helpers ─────────────────────────────────────────────────

	/**
	 * Get menu items, optionally filtered by category.
	 *
	 * @param string|null $category One of CATEGORIES, or null for all.
	 * @param bool        $active_only Only return active items.
	 * @return array Array of row objects.
	 */
	public static function get_items( $category = null, $active_only = true ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wbb_menu_items';

		$where  = '1=1';
		$params = array();

		if ( $category && in_array( $category, self::CATEGORIES, true ) ) {
			$where   .= ' AND category = %s';
			$params[] = $category;
		}
		if ( $active_only ) {
			$where .= ' AND active = 1';
		}

		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY category ASC, sort_order ASC, id ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Fetch a single item row by id.
	 */
	public static function get_item( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wbb_menu_items';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Image URL for an item row, or '' if none.
	 */
	public static function get_item_image_url( $row, $size = 'medium' ) {
		if ( empty( $row->image_id ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( (int) $row->image_id, $size );
		return $url ? $url : '';
	}

	// ── Admin AJAX handlers ─────────────────────────────────────────────────

	private static function guard() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'wbb_manage' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'wbb-bookings' ) ) );
		}
	}

	/**
	 * All items (active + inactive) decorated with a thumbnail URL, for the
	 * admin screen.
	 */
	private static function admin_items() {
		$items = self::get_items( null, false );
		foreach ( $items as $row ) {
			$row->image_url = self::get_item_image_url( $row, 'thumbnail' );
		}
		return $items;
	}

	/**
	 * Return all items (active and inactive) for the admin screen.
	 */
	public static function ajax_get_items() {
		self::guard();
		wp_send_json_success( array( 'items' => self::admin_items() ) );
	}

	/**
	 * Create or update an item.
	 */
	public static function ajax_save_item() {
		self::guard();

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_menu_items';

		$id          = absint( $_POST['item_id'] ?? 0 );
		$category    = sanitize_text_field( $_POST['category'] ?? 'food' );
		$title       = sanitize_text_field( $_POST['title'] ?? '' );
		$description = sanitize_textarea_field( $_POST['description'] ?? '' );
		$price       = isset( $_POST['price'] ) ? round( (float) $_POST['price'], 2 ) : 0.0;
		$image_id    = absint( $_POST['image_id'] ?? 0 );
		$active      = ! empty( $_POST['active'] ) ? 1 : 0;

		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			$category = 'food';
		}
		if ( '' === $title ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a title.', 'wbb-bookings' ) ) );
		}

		$data    = array(
			'category'    => $category,
			'title'       => $title,
			'description' => $description,
			'price'       => $price,
			'image_id'    => $image_id,
			'active'      => $active,
		);
		$formats = array( '%s', '%s', '%s', '%f', '%d', '%d' );

		if ( $id ) {
			$wpdb->update( $table, $data, array( 'id' => $id ), $formats, array( '%d' ) );
		} else {
			// New item sorts to the end of its category.
			$max = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT MAX(sort_order) FROM {$table} WHERE category = %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$category
			) );
			$data['sort_order'] = $max + 1;
			$data['created_at'] = current_time( 'mysql' );
			$formats[]          = '%d';
			$formats[]          = '%s';
			$wpdb->insert( $table, $data, $formats );
		}

		wp_send_json_success( array( 'items' => self::admin_items() ) );
	}

	/**
	 * Delete an item.
	 */
	public static function ajax_delete_item() {
		self::guard();

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_menu_items';
		$id    = absint( $_POST['item_id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item.', 'wbb-bookings' ) ) );
		}

		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		wp_send_json_success( array( 'items' => self::admin_items() ) );
	}

	/**
	 * Persist a new sort order. Expects ordered_ids[] = array of item IDs in the
	 * new display order (for a single category).
	 */
	public static function ajax_reorder() {
		self::guard();

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_menu_items';

		$ids = isset( $_POST['ordered_ids'] ) && is_array( $_POST['ordered_ids'] )
			? array_map( 'absint', $_POST['ordered_ids'] )
			: array();

		$order = 0;
		foreach ( $ids as $id ) {
			if ( ! $id ) {
				continue;
			}
			$wpdb->update(
				$table,
				array( 'sort_order' => $order ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
			$order++;
		}

		wp_send_json_success( array( 'items' => self::admin_items() ) );
	}
}
