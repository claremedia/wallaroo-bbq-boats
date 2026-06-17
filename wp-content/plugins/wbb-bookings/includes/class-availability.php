<?php
/**
 * WBB_Availability — front-end availability AJAX handlers.
 *
 * Availability is now computed on-the-fly from wp_wbb_boats,
 * wp_wbb_boat_schedules and wp_wbb_schedule_exceptions by WBB_Schedule.
 * This class contains only the public-facing AJAX endpoints.
 *
 * Admin calendar and day-session data are handled by WBB_Schedule.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Availability {

	// ── AJAX registration ──────────────────────────────────────────────────
	public static function register_ajax() {
		// Front-end (public, unauthenticated)
		add_action( 'wp_ajax_nopriv_wbb_get_available_dates', array( __CLASS__, 'ajax_get_available_dates' ) );
		add_action( 'wp_ajax_wbb_get_available_dates',        array( __CLASS__, 'ajax_get_available_dates' ) );

		add_action( 'wp_ajax_nopriv_wbb_get_time_slots', array( __CLASS__, 'ajax_get_time_slots' ) );
		add_action( 'wp_ajax_wbb_get_time_slots',        array( __CLASS__, 'ajax_get_time_slots' ) );
	}

	// ── Front-end: available dates ─────────────────────────────────────────
	public static function ajax_get_available_dates() {
		check_ajax_referer( 'wbb_front_nonce', 'nonce' );

		$min_advance = (int) wbb_setting( 'min_advance_hours', 24 );
		$max_days    = (int) wbb_setting( 'max_booking_days',  365 );

		$min_date = date( 'Y-m-d', strtotime( "+{$min_advance} hours" ) );
		$max_date = date( 'Y-m-d', strtotime( "+{$max_days} days" ) );

		$available = WBB_Schedule::get_available_dates( $min_date, $max_date );

		wp_send_json_success( array(
			'available' => $available,
			'min_date'  => $min_date,
			'max_date'  => $max_date,
		) );
	}

	// ── Front-end: time slots for a date ──────────────────────────────────
	public static function ajax_get_time_slots() {
		check_ajax_referer( 'wbb_front_nonce', 'nonce' );

		$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
		if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date.' ) );
		}

		$slots = WBB_Schedule::get_computed_slots_for_date( $date );

		$show_pricing = wbb_setting( 'show_pricing', '1' );
		$currency     = wbb_setting( 'currency_symbol', '$' );
		$price_label  = wbb_setting( 'price_label', 'Estimated total' );

		$formatted = array();
		foreach ( $slots as $s ) {
			$formatted[] = array(
				'time_slot'       => $s['time_slot'],
				'duration_hours'  => (float) $s['duration_hours'],
				'boats_available' => (int)   $s['boats_available'],
				'boats_booked'    => (int)   $s['boats_booked'],
				'boats_remaining' => (int)   $s['boats_remaining'],
				'price_per_boat'  => $show_pricing ? (float) $s['price_per_boat'] : null,
				'currency'        => $show_pricing ? $currency : '',
				'price_label'     => $show_pricing ? $price_label : '',
				'is_full'         => $s['is_full'],
			);
		}

		wp_send_json_success( array( 'slots' => $formatted ) );
	}
}
