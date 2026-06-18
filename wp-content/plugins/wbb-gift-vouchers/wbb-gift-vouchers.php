<?php
/**
 * Plugin Name:  WBB Gift Vouchers
 * Plugin URI:   https://wallaroobbqboats.com.au
 * Description:  Gift voucher purchasing for Wallaroo BBQ Boats — front-end form, voucher records, and auto-generated branded PDF. Square payment and email delivery are added in a later phase.
 * Version:      1.0.0
 * Author:       Wallaroo BBQ Boats
 * Author URI:   https://wallaroobbqboats.com.au
 * Text Domain:  wbb-gift-vouchers
 * License:      GPL v2 or later
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────
define( 'WBB_GV_VERSION',  '1.0.0' );
define( 'WBB_GV_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WBB_GV_URL',      plugin_dir_url( __FILE__ ) );
define( 'WBB_GV_FILE',     __FILE__ );

// ── Core includes ──────────────────────────────────────────────────────────
require_once WBB_GV_DIR . 'includes/class-gv-database.php';
require_once WBB_GV_DIR . 'includes/class-gv-settings.php';
require_once WBB_GV_DIR . 'includes/class-gv-pdf.php';
require_once WBB_GV_DIR . 'includes/class-gv-vouchers.php';
require_once WBB_GV_DIR . 'includes/class-gv-shortcode.php';

// ── Admin only ─────────────────────────────────────────────────────────────
if ( is_admin() ) {
	require_once WBB_GV_DIR . 'admin/class-gv-admin.php';
	WBB_GV_Admin::init();
}

// ── Lifecycle ──────────────────────────────────────────────────────────────
register_activation_hook(   __FILE__, array( 'WBB_GV_Database', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'WBB_GV_Database', 'deactivate' ) );
register_uninstall_hook(    __FILE__, array( 'WBB_GV_Database', 'uninstall'  ) );

// DB upgrade check — re-run dbDelta when the schema version is behind.
add_action( 'plugins_loaded', function () {
	if ( get_option( WBB_GV_Database::DB_VERSION_OPTION ) !== WBB_GV_Database::DB_VERSION ) {
		WBB_GV_Database::activate();
	}
} );

// Plugin row "Settings" link.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$url = admin_url( 'admin.php?page=wbb-gv-settings' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wbb-gift-vouchers' ) . '</a>' );
	return $links;
} );

// ── Bootstrap ──────────────────────────────────────────────────────────────
add_action( 'init',       function () { WBB_GV_Shortcode::init(); } );
add_action( 'admin_init', function () { WBB_GV_Settings::register(); } );

// AJAX + admin-post endpoints.
WBB_GV_Vouchers::register_ajax();
add_action( 'admin_post_nopriv_wbb_gv_pdf', array( 'WBB_GV_Vouchers', 'public_pdf' ) );
add_action( 'admin_post_wbb_gv_pdf',        array( 'WBB_GV_Vouchers', 'public_pdf' ) );
add_action( 'admin_post_wbb_gv_admin_pdf',  array( 'WBB_GV_Vouchers', 'admin_pdf' ) );
add_action( 'admin_post_wbb_gv_export',     array( 'WBB_GV_Vouchers', 'export_csv' ) );
