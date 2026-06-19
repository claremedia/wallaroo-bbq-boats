<?php
/**
 * WBB_GV_Settings — single serialised option (wbb_gv_settings).
 *
 * Usage: wbb_gv_setting( 'min_amount', 25 )
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wbb_gv_setting' ) ) {
	function wbb_gv_setting( $key, $default = '' ) {
		$settings = get_option( 'wbb_gv_settings', array() );
		$defaults = WBB_GV_Settings::get_defaults();
		if ( isset( $settings[ $key ] ) && $settings[ $key ] !== '' ) {
			return $settings[ $key ];
		}
		if ( '' !== $default ) {
			return $default;
		}
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}
}

class WBB_GV_Settings {

	public static function get_defaults() {
		return array(
			'min_amount'               => '25',
			'currency_symbol'          => '$',
			'expiry_months'            => '36',
			'code_prefix'              => 'WBB',
			'from_name'                => 'Wallaroo BBQ Boats',
			'from_email'               => get_bloginfo( 'admin_email' ),
			'admin_notification_email' => get_bloginfo( 'admin_email' ),
			'email_admin_on_create'    => '1',
			'form_intro_text'          => '',
			'success_message'          => "Thanks {purchaser_name}! Gift voucher {voucher_code} for {amount} has been created. You can download the voucher PDF below.",
			'terms_text'               => 'Redeemable for boat hire at Copper Cove Marina, Wallaroo SA. Not redeemable for cash.',
			'confirm_checkbox_text'    => 'I understand this creates a gift voucher record. Payment is arranged separately.',
			'delete_on_uninstall'      => '0',
		);
	}

	public static function register() {
		register_setting( 'wbb_gv_settings_group', 'wbb_gv_settings', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize' ),
		) );

		// Allow saving via options.php with the custom wbb_manage capability
		// (instead of the default manage_options) so the Business Manager role
		// can save. Administrators have wbb_manage too.
		add_filter( 'option_page_capability_wbb_gv_settings_group', function () {
			return 'wbb_manage';
		} );
	}

	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$clean = array();

		foreach ( array( 'currency_symbol', 'code_prefix', 'from_name' ) as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_text_field( $input[ $f ] );
			}
		}
		foreach ( array( 'from_email', 'admin_notification_email' ) as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_email( $input[ $f ] );
			}
		}
		foreach ( array( 'min_amount', 'expiry_months' ) as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = (string) absint( $input[ $f ] );
			}
		}
		foreach ( array( 'form_intro_text', 'success_message', 'terms_text', 'confirm_checkbox_text' ) as $f ) {
			if ( isset( $input[ $f ] ) ) {
				$clean[ $f ] = sanitize_textarea_field( $input[ $f ] );
			}
		}
		foreach ( array( 'email_admin_on_create', 'delete_on_uninstall' ) as $f ) {
			$clean[ $f ] = ! empty( $input[ $f ] ) ? '1' : '0';
		}
		return $clean;
	}
}
