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

			// Pricing tab — hire price per boat, by number of people on that boat.
			// Overflow boats re-price from the 1–2 tier (each boat priced by its own count).
			'hire_price_1_2'             => '220',
			'hire_price_3'               => '250',
			'hire_price_4'               => '280',
			'hire_price_5'               => '300',
			'hire_price_6'               => '320',

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
	// The settings screen is tabbed and only submits the active tab's fields,
	// so we merge onto the existing option (rather than replacing it) and only
	// reset checkboxes/durations that belong to the tab being saved.
	public static function sanitize( $input ) {
		$existing = get_option( 'wbb_settings', array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		if ( ! is_array( $input ) ) {
			return $existing;
		}

		$clean  = $existing;
		$active = isset( $input['_active_tab'] ) ? sanitize_key( $input['_active_tab'] ) : '';

		// Text fields
		foreach ( array( 'from_name', 'currency_symbol', 'price_label' ) as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_text_field( $input[ $f ] );
			}
		}

		// Email fields
		foreach ( array( 'from_email', 'reply_to_email', 'admin_notification_email' ) as $f ) {
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

		// Price fields (decimal)
		$price_fields = array( 'hire_price_1_2', 'hire_price_3', 'hire_price_4', 'hire_price_5', 'hire_price_6' );
		foreach ( $price_fields as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = (string) round( (float) $input[ $f ], 2 );
			}
		}

		// Checkboxes — only reset those belonging to the tab being saved
		// (an unchecked box is simply absent from $input).
		$tab_checkboxes = array(
			'email'    => array( 'email_customer_on_request', 'email_admin_on_request', 'email_customer_on_confirm', 'email_customer_on_cancel' ),
			'rules'    => array( 'auto_confirm' ),
			'display'  => array( 'show_pricing' ),
			'business' => array( 'delete_on_uninstall' ),
		);
		if ( isset( $tab_checkboxes[ $active ] ) ) {
			foreach ( $tab_checkboxes[ $active ] as $f ) {
				$clean[ $f ] = ! empty( $input[ $f ] ) ? '1' : '0';
			}
		}

		// Durations — only on the Business tab
		if ( 'business' === $active ) {
			$allowed_durations = array( '1', '1.5', '2', '2.5', '3', '3.5', '4' );
			if ( ! empty( $input['durations'] ) && is_array( $input['durations'] ) ) {
				$clean['durations'] = array_values(
					array_intersect( array_map( 'sanitize_text_field', $input['durations'] ), $allowed_durations )
				);
			} else {
				$clean['durations'] = array();
			}
		}

		unset( $clean['_active_tab'] );
		return $clean;
	}
}

// ── Pricing helpers ──────────────────────────────────────────────────────────

if ( ! function_exists( 'wbb_boat_price_for_people' ) ) {
	/** Hire price for a single boat carrying $people people. */
	function wbb_boat_price_for_people( $people ) {
		$people = (int) $people;
		if ( $people <= 2 ) {
			$key = 'hire_price_1_2';
		} elseif ( $people === 3 ) {
			$key = 'hire_price_3';
		} elseif ( $people === 4 ) {
			$key = 'hire_price_4';
		} elseif ( $people === 5 ) {
			$key = 'hire_price_5';
		} else {
			$key = 'hire_price_6';
		}
		// No explicit default — let wbb_setting() fall back to get_defaults().
		return (float) wbb_setting( $key );
	}
}

if ( ! function_exists( 'wbb_calc_hire_total' ) ) {
	/**
	 * Total hire price for a group. Boats fill to capacity; each boat is priced
	 * by how many people are on it, so an overflow boat re-prices from the 1–2 tier.
	 */
	function wbb_calc_hire_total( $group_size, $max_per_boat = null ) {
		$group = max( 0, (int) $group_size );
		$max   = $max_per_boat ? (int) $max_per_boat : (int) wbb_setting( 'max_per_boat', 6 );
		if ( $max < 1 ) {
			$max = 6;
		}
		$total = 0;
		$remaining = $group;
		while ( $remaining > 0 ) {
			$on_boat    = min( $remaining, $max );
			$total     += wbb_boat_price_for_people( $on_boat );
			$remaining -= $on_boat;
		}
		return round( $total, 2 );
	}
}
