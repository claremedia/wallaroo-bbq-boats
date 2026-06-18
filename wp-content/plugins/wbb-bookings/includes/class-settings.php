<?php
/**
 * WBB_Settings — stores all plugin settings as a single serialised option.
 *
 * Usage anywhere:
 *   wbb_setting( 'from_name' )
 *   wbb_setting( 'min_group_size', 2 )
 */

defined( 'ABSPATH' ) || exit;

// ── Global helper ──────────────────────────────────────────────────────────
if ( ! function_exists( 'wbb_setting' ) ) {
	function wbb_setting( $key, $default = '' ) {
		$settings = get_option( 'wbb_settings', array() );
		$defaults = WBB_Settings::get_defaults();

		if ( isset( $settings[ $key ] ) && $settings[ $key ] !== '' ) {
			return $settings[ $key ];
		}
		if ( '' !== $default ) {
			return $default;
		}
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}
}

class WBB_Settings {

	// ── All defaults ───────────────────────────────────────────────────────
	public static function get_defaults() {
		return array(
			// Email tab
			'from_name'                  => 'Wallaroo BBQ Boats',
			'from_email'                 => get_bloginfo( 'admin_email' ),
			'reply_to_email'             => get_bloginfo( 'admin_email' ),
			'admin_notification_email'   => get_bloginfo( 'admin_email' ),
			'email_customer_on_request'  => '1',
			'email_admin_on_request'     => '1',
			'email_customer_on_confirm'  => '1',
			'email_customer_on_cancel'   => '1',
			'template_customer_request'  => "Hi {customer_name}, thanks for your booking request. We have received your request for {boats} boat(s) on {date} at {time} for {duration} hours. Your booking reference is {booking_ref}. This is a request only — not a confirmed booking. We will be in touch within 24 hours to confirm. If you need to reach us sooner call {site_phone}.",
			'template_admin_notification' => "New booking request {booking_ref}. Customer: {customer_name}. Email: {customer_email}. Phone: {customer_phone}. Date: {date}. Time: {time}. Duration: {duration} hours. Group size: {group_size}. Boats requested: {boats}. Food & drink extras: {inclusions}. Notes: {notes}. Log in to confirm or cancel this booking.",
			'template_confirmed'         => "Hi {customer_name}, your booking is confirmed. Booking ref: {booking_ref}. Date: {date}. Time: {time}. Duration: {duration} hours. Group size: {group_size}. Boats: {boats}. Please arrive at Copper Cove Marina, Wallaroo 15 minutes before your session. Bring your food, sunscreen, and your people. We will sort the rest. Questions? Call {site_phone}.",
			'template_cancelled'         => "Hi {customer_name}, your booking {booking_ref} for {date} at {time} has been cancelled. We are sorry it did not work out this time. We would love to have you out on the water — head to the website to find another date. Questions? Call {site_phone}.",

			// Booking Rules tab
			'min_group_size'             => '2',
			'max_per_boat'               => '6',
			'min_advance_hours'          => '24',
			'max_booking_days'           => '365',
			'flag_threshold_hours'       => '48',
			'auto_confirm'               => '0',

			// Display tab
			'currency_symbol'            => '$',
			'price_label'                => 'Estimated total',
			'show_pricing'               => '1',
			'form_intro_text'            => '',
			'success_message'            => "Thanks {customer_name}. We have received your booking request for {date} at {time}. Your booking reference is {booking_ref}. We will be in touch within 24 hours to confirm. Need to reach us sooner? Call {site_phone}.",
			'confirm_checkbox_text'      => "I understand this is a booking request. Wallaroo BBQ Boats will contact me within 24 hours to confirm.",

			// Business tab
			'season_start_month'         => '9',
			'season_end_month'           => '5',
			'default_boats_available'    => '3',
			'durations'                  => array( '2', '2.5', '3' ),
			'time_slot_interval'         => '60',
			'delete_on_uninstall'        => '0',
		);
	}

	// ── Register with WordPress Settings API ───────────────────────────────
	public static function register() {
		register_setting(
			'wbb_settings_group',
			'wbb_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	// ── Sanitise before saving ─────────────────────────────────────────────
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();

		// Text fields
		$text_fields = array(
			'from_name', 'currency_symbol', 'price_label',
		);
		foreach ( $text_fields as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_text_field( $input[ $f ] );
			}
		}

		// Email fields
		$email_fields = array(
			'from_email', 'reply_to_email', 'admin_notification_email',
		);
		foreach ( $email_fields as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_email( $input[ $f ] );
			}
		}

		// Textarea fields (plain text)
		$textarea_fields = array(
			'form_intro_text', 'success_message', 'confirm_checkbox_text',
			'template_customer_request', 'template_admin_notification',
			'template_confirmed', 'template_cancelled',
		);
		foreach ( $textarea_fields as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_textarea_field( $input[ $f ] );
			}
		}

		// Integer fields
		$int_fields = array(
			'min_group_size', 'max_per_boat', 'min_advance_hours',
			'max_booking_days', 'flag_threshold_hours', 'default_boats_available',
			'season_start_month', 'season_end_month', 'time_slot_interval',
		);
		foreach ( $int_fields as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = (string) absint( $input[ $f ] );
			}
		}

		// Checkboxes (1 or 0)
		$checkbox_fields = array(
			'email_customer_on_request', 'email_admin_on_request',
			'email_customer_on_confirm', 'email_customer_on_cancel',
			'show_pricing', 'auto_confirm', 'delete_on_uninstall',
		);
		foreach ( $checkbox_fields as $f ) {
			$clean[ $f ] = ! empty( $input[ $f ] ) ? '1' : '0';
		}

		// Durations — array of allowed values
		$allowed_durations = array( '1', '1.5', '2', '2.5', '3', '3.5', '4' );
		if ( ! empty( $input['durations'] ) && is_array( $input['durations'] ) ) {
			$clean['durations'] = array_values(
				array_intersect( array_map( 'sanitize_text_field', $input['durations'] ), $allowed_durations )
			);
		} else {
			$clean['durations'] = array();
		}

		return $clean;
	}
}
