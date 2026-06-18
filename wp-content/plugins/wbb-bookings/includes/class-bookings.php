<?php
/**
 * WBB_Bookings — manages the bookings table and related AJAX/admin actions.
 *
 * v2.0.0: submission now uses booking_date + time_slot (not availability_id).
 * Availability is computed by WBB_Schedule — no counter updates needed.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Bookings {

	// ── AJAX registration ──────────────────────────────────────────────────
	public static function register_ajax() {
		// Front-end (public)
		add_action( 'wp_ajax_nopriv_wbb_submit_booking', array( __CLASS__, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_wbb_submit_booking',        array( __CLASS__, 'ajax_submit_booking' ) );

		// Admin only
		add_action( 'wp_ajax_wbb_admin_update_booking_status', array( __CLASS__, 'ajax_update_booking_status' ) );
		add_action( 'wp_ajax_wbb_admin_save_booking_notes',    array( __CLASS__, 'ajax_save_booking_notes' ) );
		add_action( 'wp_ajax_wbb_admin_get_booking',           array( __CLASS__, 'ajax_get_booking' ) );
		add_action( 'wp_ajax_wbb_admin_reset_settings',        array( __CLASS__, 'ajax_reset_settings' ) );
	}

	// ── Front-end: submit booking ──────────────────────────────────────────
	public static function ajax_submit_booking() {
		check_ajax_referer( 'wbb_booking_nonce', 'nonce' );

		// Rate limiting: max 3 per IP per hour.
		if ( ! self::check_rate_limit() ) {
			$phone = function_exists( 'wallaroo_option' ) ? wallaroo_option( 'phone' ) : get_bloginfo( 'admin_email' );
			wp_send_json_error( array(
				'code'    => 'rate_limited',
				'message' => sprintf(
					/* translators: %s: phone number */
					__( 'Too many booking requests from your connection. Please call us directly on %s.', 'wbb-bookings' ),
					$phone
				),
			) );
		}

		// Sanitise inputs.
		$booking_date    = sanitize_text_field( $_POST['booking_date']    ?? '' );
		$time_slot       = sanitize_text_field( $_POST['time_slot']       ?? '' );
		$group_size      = absint( $_POST['group_size']      ?? 0 );
		$boats_requested = absint( $_POST['boats_requested'] ?? 0 );
		$customer_name   = sanitize_text_field( $_POST['customer_name']   ?? '' );
		$customer_email  = sanitize_email( $_POST['customer_email']  ?? '' );
		$customer_phone  = sanitize_text_field( $_POST['customer_phone']  ?? '' );
		$notes           = sanitize_textarea_field( $_POST['notes']    ?? '' );

		// Inclusions (Food & Drink extras): validate every line against the menu
		// table and recompute the total server-side — never trust posted prices.
		$inclusions_json  = '';
		$inclusions_total = 0.0;
		$posted_inclusions = isset( $_POST['inclusions'] ) ? wp_unslash( $_POST['inclusions'] ) : '';
		if ( $posted_inclusions && class_exists( 'WBB_Menu' ) ) {
			$decoded = json_decode( $posted_inclusions, true );
			if ( is_array( $decoded ) ) {
				$clean = array();
				foreach ( $decoded as $line ) {
					$id  = isset( $line['id'] )  ? absint( $line['id'] )  : 0;
					$qty = isset( $line['qty'] ) ? absint( $line['qty'] ) : 0;
					if ( ! $id || $qty < 1 ) {
						continue;
					}
					$item = WBB_Menu::get_item( $id );
					if ( ! $item || (int) $item->active !== 1 ) {
						continue;
					}
					$unit               = (float) $item->price;
					$inclusions_total  += $unit * $qty;
					$clean[]            = array(
						'id'         => $id,
						'title'      => $item->title,
						'qty'        => $qty,
						'unit_price' => $unit,
					);
				}
				if ( ! empty( $clean ) ) {
					$inclusions_json = wp_json_encode( $clean );
				}
			}
		}
		$inclusions_total = round( $inclusions_total, 2 );

		// Validate required fields.
		if ( ! $booking_date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $booking_date ) ||
			 ! $time_slot || ! $group_size || ! $boats_requested ||
			 ! $customer_name || ! $customer_email || ! $customer_phone ) {
			wp_send_json_error( array(
				'code'    => 'validation',
				'message' => __( 'Please fill in all required fields.', 'wbb-bookings' ),
			) );
		}
		if ( ! is_email( $customer_email ) ) {
			wp_send_json_error( array(
				'code'    => 'validation',
				'message' => __( 'Please enter a valid email address.', 'wbb-bookings' ),
			) );
		}

		// Re-verify availability (prevent race conditions).
		$slots = WBB_Schedule::get_computed_slots_for_date( $booking_date );
		$matching_slot = null;
		foreach ( $slots as $s ) {
			if ( $s['time_slot'] === $time_slot ) {
				$matching_slot = $s;
				break;
			}
		}

		if ( ! $matching_slot ) {
			wp_send_json_error( array(
				'code'    => 'unavailable',
				'message' => __( 'Sorry, this slot is no longer available. Please choose a different date or time.', 'wbb-bookings' ),
			) );
		}

		if ( $matching_slot['boats_remaining'] < $boats_requested ) {
			wp_send_json_error( array(
				'code'    => 'taken',
				'message' => __( 'Sorry, not enough boats are available for your group. Please go back and choose a different time.', 'wbb-bookings' ),
			) );
		}

		$duration_hours = (float) $matching_slot['duration_hours'];

		// Determine status.
		$auto_confirm = wbb_setting( 'auto_confirm', '0' );
		$status       = $auto_confirm ? 'confirmed' : 'pending';

		$booking_ref = self::generate_booking_ref();
		$now         = current_time( 'mysql' );

		global $wpdb;
		$bk_table = $wpdb->prefix . 'wbb_bookings';

		// Insert booking record.
		$inserted = $wpdb->insert(
			$bk_table,
			array(
				'booking_ref'     => $booking_ref,
				'availability_id' => 0,
				'boat_id'         => 0,
				'booking_date'    => $booking_date,
				'time_slot'       => $time_slot,
				'duration_hours'  => $duration_hours,
				'boats_requested' => $boats_requested,
				'group_size'      => $group_size,
				'customer_name'   => $customer_name,
				'customer_email'  => $customer_email,
				'customer_phone'   => $customer_phone,
				'notes'            => $notes,
				'inclusions'       => $inclusions_json,
				'inclusions_total' => $inclusions_total,
				'status'           => $status,
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			wp_send_json_error( array(
				'code'    => 'db_error',
				'message' => __( 'Something went wrong. Please try again.', 'wbb-bookings' ),
			) );
		}

		$booking_id = $wpdb->insert_id;

		// Fetch full booking object for emails.
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bk_table} WHERE id = %d",
			$booking_id
		) );

		// Send emails.
		$emails = new WBB_Emails();
		$emails->send_customer_request_received( $booking );
		$emails->send_admin_notification( $booking );
		if ( 'confirmed' === $status ) {
			$emails->send_booking_confirmed( $booking );
		}

		// Build success message.
		$success_template = wbb_setting( 'success_message', '' );
		$success_message  = $emails->process_merge_tags( $success_template, $booking );

		wp_send_json_success( array(
			'booking_ref' => $booking_ref,
			'message'     => $success_message,
		) );
	}

	// ── Admin: update booking status ───────────────────────────────────────
	public static function ajax_update_booking_status() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}

		$id     = absint( $_POST['booking_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );

		if ( ! $id || ! in_array( $status, array( 'confirmed', 'cancelled' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
		}

		global $wpdb;
		$bk_table = $wpdb->prefix . 'wbb_bookings';

		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bk_table} WHERE id = %d",
			$id
		) );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found.' ) );
		}

		// Availability is computed on-the-fly, so no counter updates needed.
		$wpdb->update(
			$bk_table,
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// Re-fetch updated booking.
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bk_table} WHERE id = %d",
			$id
		) );

		// Send email notification.
		$emails = new WBB_Emails();
		if ( 'confirmed' === $status ) {
			$emails->send_booking_confirmed( $booking );
		} elseif ( 'cancelled' === $status ) {
			$emails->send_booking_cancelled( $booking );
		}

		wp_send_json_success( array( 'status' => $status ) );
	}

	// ── Admin: save staff notes ────────────────────────────────────────────
	public static function ajax_save_booking_notes() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}

		$id    = absint( $_POST['booking_id'] ?? 0 );
		$notes = sanitize_textarea_field( $_POST['staff_notes'] ?? '' );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID.' ) );
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wbb_bookings',
			array( 'staff_notes' => $notes, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => 'Notes saved.' ) );
	}

	// ── Admin: get single booking (for inline view) ────────────────────────
	public static function ajax_get_booking() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}

		$id = absint( $_POST['booking_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID.' ) );
		}

		global $wpdb;
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wbb_bookings WHERE id = %d",
			$id
		), ARRAY_A );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Not found.' ) );
		}

		wp_send_json_success( $booking );
	}

	// ── Admin: reset settings to defaults ─────────────────────────────────
	public static function ajax_reset_settings() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}
		update_option( 'wbb_settings', WBB_Settings::get_defaults() );
		wp_send_json_success( array( 'message' => 'Settings reset to defaults.' ) );
	}

	// ── CSV export ─────────────────────────────────────────────────────────
	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wbb_export_bookings' );

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_bookings';

		$where  = '1=1';
		$params = array();

		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		if ( $status && in_array( $status, array( 'pending', 'confirmed', 'cancelled' ), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
		if ( $date_from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$where   .= ' AND booking_date >= %s';
			$params[] = $date_from;
		}

		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
		if ( $date_to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$where   .= ' AND booking_date <= %s';
			$params[] = $date_to;
		}

		$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		if ( $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s OR booking_ref LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC";  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! empty( $params ) ) {
			$bookings = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$bookings = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$filename = 'wbb-bookings-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array(
			'Booking Ref', 'Status', 'Date', 'Time', 'Duration (hrs)', 'Group Size',
			'Boats', 'Price/Boat', 'Customer Name', 'Email', 'Phone', 'Notes',
			'Inclusions', 'Inclusions Total', 'Staff Notes', 'Submitted',
		) );

		foreach ( $bookings as $b ) {
			fputcsv( $out, array(
				$b['booking_ref'],
				$b['status'],
				$b['booking_date'],
				$b['time_slot'],
				$b['duration_hours'],
				$b['group_size'],
				$b['boats_requested'],
				$b['price_per_boat'],
				$b['customer_name'],
				$b['customer_email'],
				$b['customer_phone'],
				$b['notes'],
				self::format_inclusions_text( $b['inclusions'] ?? '' ),
				$b['inclusions_total'] ?? '0.00',
				$b['staff_notes'],
				$b['created_at'],
			) );
		}

		fclose( $out );
		exit;
	}

	// ── Format an inclusions JSON blob as readable plain text ───────────────
	// e.g. "Grazing platter ×2, Soft drinks ×6". Returns '' when none.
	public static function format_inclusions_text( $json ) {
		if ( empty( $json ) ) {
			return '';
		}
		$items = json_decode( $json, true );
		if ( ! is_array( $items ) || empty( $items ) ) {
			return '';
		}
		$parts = array();
		foreach ( $items as $line ) {
			$title = isset( $line['title'] ) ? $line['title'] : '';
			$qty   = isset( $line['qty'] )   ? (int) $line['qty'] : 0;
			if ( '' === $title || $qty < 1 ) {
				continue;
			}
			$parts[] = $title . ' x' . $qty;
		}
		return implode( ', ', $parts );
	}

	// ── Booking reference generator ────────────────────────────────────────
	public static function generate_booking_ref() {
		$year        = date( 'Y' );
		$counter_key = 'wbb_booking_counter_' . $year;
		$count       = (int) get_option( $counter_key, 0 );
		$count++;
		update_option( $counter_key, $count, false );
		return 'WBB-' . $year . '-' . str_pad( $count, 3, '0', STR_PAD_LEFT );
	}

	// ── Rate limiter (IP-based transient) ─────────────────────────────────
	private static function check_rate_limit() {
		$ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
		$key   = 'wbb_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 3 ) {
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}
}
