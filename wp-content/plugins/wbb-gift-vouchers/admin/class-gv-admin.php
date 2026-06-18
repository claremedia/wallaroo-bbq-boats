<?php
/**
 * WBB_GV_Admin — admin menus + asset enqueuing.
 */

defined( 'ABSPATH' ) || exit;

class WBB_GV_Admin {

	public static function init() {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_menus() {
		add_menu_page(
			__( 'Gift Vouchers', 'wbb-gift-vouchers' ),
			__( 'Gift Vouchers', 'wbb-gift-vouchers' ),
			'manage_options',
			'wbb-gv-vouchers',
			array( __CLASS__, 'page_vouchers' ),
			'dashicons-tickets-alt',
			2
		);
		add_submenu_page( 'wbb-gv-vouchers', __( 'Vouchers', 'wbb-gift-vouchers' ), __( 'Vouchers', 'wbb-gift-vouchers' ), 'manage_options', 'wbb-gv-vouchers', array( __CLASS__, 'page_vouchers' ) );
		add_submenu_page( 'wbb-gv-vouchers', __( 'Gift Voucher Settings', 'wbb-gift-vouchers' ), __( 'Settings', 'wbb-gift-vouchers' ), 'manage_options', 'wbb-gv-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function page_vouchers() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_GV_DIR . 'admin/vouchers-page.php';
		wbb_gv_render_vouchers_page();
	}

	public static function page_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once WBB_GV_DIR . 'admin/settings-page.php';
		wbb_gv_render_settings_page();
	}

	public static function enqueue_assets( $hook ) {
		$pages = array( 'toplevel_page_wbb-gv-vouchers', 'gift-vouchers_page_wbb-gv-settings' );
		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}

		$css = WBB_GV_DIR . 'assets/css/gv-admin.css';
		$js  = WBB_GV_DIR . 'assets/js/gv-admin.js';

		wp_enqueue_style( 'wbb-gv-admin', WBB_GV_URL . 'assets/css/gv-admin.css', array(), file_exists( $css ) ? filemtime( $css ) : WBB_GV_VERSION );
		wp_enqueue_script( 'wbb-gv-admin', WBB_GV_URL . 'assets/js/gv-admin.js', array( 'jquery' ), file_exists( $js ) ? filemtime( $js ) : WBB_GV_VERSION, true );

		wp_localize_script( 'wbb-gv-admin', 'wbbGVAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wbb_gv_admin_nonce' ),
			'strings' => array(
				'confirmIssue'  => __( 'Mark this voucher as issued?', 'wbb-gift-vouchers' ),
				'confirmCancel' => __( 'Cancel this voucher?', 'wbb-gift-vouchers' ),
				'saving'        => __( 'Saving…', 'wbb-gift-vouchers' ),
				'saved'         => __( 'Saved.', 'wbb-gift-vouchers' ),
				'error'         => __( 'Something went wrong.', 'wbb-gift-vouchers' ),
				'currency'      => wbb_gv_setting( 'currency_symbol', '$' ),
			),
		) );
	}
}
