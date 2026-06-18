<?php
/**
 * WBB_Admin — registers admin menus and enqueues admin assets.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Admin {

	public static function init() {
		add_action( 'admin_menu',             array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts',  array( __CLASS__, 'enqueue_assets' ) );
	}

	// ── Admin menus ────────────────────────────────────────────────────────
	public static function register_menus() {
		// Top-level menu
		add_menu_page(
			__( 'BBQ Bookings', 'wbb-bookings' ),
			__( 'BBQ Bookings', 'wbb-bookings' ),
			'manage_options',
			'wbb-bookings',
			array( __CLASS__, 'page_bookings' ),
			'dashicons-calendar-alt',
			1
		);

		// Bookings sub-page (same as parent)
		add_submenu_page(
			'wbb-bookings',
			__( 'Bookings', 'wbb-bookings' ),
			__( 'Bookings', 'wbb-bookings' ),
			'manage_options',
			'wbb-bookings',
			array( __CLASS__, 'page_bookings' )
		);

		// Availability
		add_submenu_page(
			'wbb-bookings',
			__( 'Availability', 'wbb-bookings' ),
			__( 'Availability', 'wbb-bookings' ),
			'manage_options',
			'wbb-availability',
			array( __CLASS__, 'page_availability' )
		);

		// Food & Drink
		add_submenu_page(
			'wbb-bookings',
			__( 'Food & Drink', 'wbb-bookings' ),
			__( 'Food & Drink', 'wbb-bookings' ),
			'manage_options',
			'wbb-menu',
			array( __CLASS__, 'page_menu' )
		);

		// Settings
		add_submenu_page(
			'wbb-bookings',
			__( 'Booking Settings', 'wbb-bookings' ),
			__( 'Settings', 'wbb-bookings' ),
			'manage_options',
			'wbb-settings',
			array( __CLASS__, 'page_settings' )
		);

		// "View Book Now Page" — added via $submenu hack for external URL.
		global $submenu;
		$submenu['wbb-bookings'][] = array(
			__( 'View Book Now Page', 'wbb-bookings' ),
			'manage_options',
			home_url( '/book-now/' ),
			__( 'View Book Now Page', 'wbb-bookings' ),
		);
	}

	// ── Page callbacks ─────────────────────────────────────────────────────
	public static function page_bookings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_PLUGIN_DIR . 'admin/bookings-page.php';
		wbb_render_bookings_page();
	}

	public static function page_availability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_PLUGIN_DIR . 'admin/availability-page.php';
		wbb_render_availability_page();
	}

	public static function page_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_PLUGIN_DIR . 'admin/settings-page.php';
		wbb_render_settings_page();
	}

	public static function page_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_PLUGIN_DIR . 'admin/menu-page.php';
		wbb_render_menu_page();
	}

	// ── Asset enqueuing ────────────────────────────────────────────────────
	public static function enqueue_assets( $hook ) {
		// Only load on our plugin pages.
		$wbb_pages = array(
			'toplevel_page_wbb-bookings',
			'bbq-bookings_page_wbb-availability',
			'bbq-bookings_page_wbb-menu',
			'bbq-bookings_page_wbb-settings',
		);
		if ( ! in_array( $hook, $wbb_pages, true ) ) {
			return;
		}

		$is_menu_page = ( 'bbq-bookings_page_wbb-menu' === $hook );

		// Food & Drink page needs the media library + drag-sort.
		if ( $is_menu_page ) {
			wp_enqueue_media();
		}

		$css_file = WBB_PLUGIN_DIR . 'assets/css/admin.css';
		$js_file  = WBB_PLUGIN_DIR . 'assets/js/admin.js';

		wp_enqueue_style(
			'wbb-admin',
			WBB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			file_exists( $css_file ) ? filemtime( $css_file ) : WBB_VERSION
		);

		$js_deps = array( 'jquery' );
		if ( $is_menu_page ) {
			$js_deps[] = 'jquery-ui-sortable';
		}

		wp_enqueue_script(
			'wbb-admin',
			WBB_PLUGIN_URL . 'assets/js/admin.js',
			$js_deps,
			file_exists( $js_file ) ? filemtime( $js_file ) : WBB_VERSION,
			true
		);

		wp_localize_script( 'wbb-admin', 'wbbAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wbb_admin_nonce' ),
			'strings' => array(
				'confirmConfirm'      => __( 'Confirm this booking? A confirmation email will be sent to the customer.', 'wbb-bookings' ),
				'confirmCancel'       => __( 'Cancel this booking? A cancellation email will be sent to the customer.', 'wbb-bookings' ),
				'confirmReset'        => __( 'Reset all settings to their defaults? This cannot be undone.', 'wbb-bookings' ),
				'confirmDeleteBoat'   => __( 'Delete this boat and all its schedule data? This cannot be undone.', 'wbb-bookings' ),
				'confirmDeleteItem'   => __( 'Delete this item? This cannot be undone.', 'wbb-bookings' ),
				'selectImage'         => __( 'Select image', 'wbb-bookings' ),
				'useImage'            => __( 'Use this image', 'wbb-bookings' ),
				'currency'            => function_exists( 'wbb_setting' ) ? wbb_setting( 'currency_symbol', '$' ) : '$',
				'saved'               => __( 'Saved.', 'wbb-bookings' ),
				'saving'              => __( 'Saving…', 'wbb-bookings' ),
				'notesSaved'          => __( 'Notes saved.', 'wbb-bookings' ),
				'errorGeneric'        => __( 'Something went wrong. Please try again.', 'wbb-bookings' ),
				'noScheduledSessions' => __( 'No sessions are scheduled for this date. Set up a weekly schedule above.', 'wbb-bookings' ),
				'selectBoatFirst'     => __( 'Select a boat above to edit its schedule.', 'wbb-bookings' ),
				'scheduleSaved'       => __( 'Schedule saved.', 'wbb-bookings' ),
				'sessionBlocked'      => __( 'Blocked.', 'wbb-bookings' ),
				'sessionUnblocked'    => __( 'Unblocked.', 'wbb-bookings' ),
				'days'                => array(
					__( 'Sunday',    'wbb-bookings' ),
					__( 'Monday',    'wbb-bookings' ),
					__( 'Tuesday',   'wbb-bookings' ),
					__( 'Wednesday', 'wbb-bookings' ),
					__( 'Thursday',  'wbb-bookings' ),
					__( 'Friday',    'wbb-bookings' ),
					__( 'Saturday',  'wbb-bookings' ),
				),
				'durations'           => wbb_setting( 'durations', array( '2', '2.5', '3' ) ),
			),
		) );
	}
}
