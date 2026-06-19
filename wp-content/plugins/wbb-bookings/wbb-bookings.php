<?php
/**
 * Plugin Name:  WBB Bookings
 * Plugin URI:   https://wallaroobbqboats.com.au
 * Description:  Booking request management for Wallaroo BBQ Boats. Handles availability, booking requests, admin management, and email notifications.
 * Version:      2.0.0
 * Author:       Wallaroo BBQ Boats
 * Author URI:   https://wallaroobbqboats.com.au
 * Text Domain:  wbb-bookings
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────
define( 'WBB_VERSION',     '2.0.0' );
define( 'WBB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WBB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WBB_PLUGIN_FILE', __FILE__ );

// ── Core includes ──────────────────────────────────────────────────────────
require_once WBB_PLUGIN_DIR . 'includes/class-database.php';
require_once WBB_PLUGIN_DIR . 'includes/class-settings.php';
require_once WBB_PLUGIN_DIR . 'includes/class-emails.php';
require_once WBB_PLUGIN_DIR . 'includes/class-schedule.php';
require_once WBB_PLUGIN_DIR . 'includes/class-availability.php';
require_once WBB_PLUGIN_DIR . 'includes/class-bookings.php';
require_once WBB_PLUGIN_DIR . 'includes/class-menu.php';
require_once WBB_PLUGIN_DIR . 'includes/class-manifest.php';
require_once WBB_PLUGIN_DIR . 'includes/class-shortcode.php';

// ── Admin only ─────────────────────────────────────────────────────────────
if ( is_admin() ) {
	require_once WBB_PLUGIN_DIR . 'admin/class-admin.php';
	WBB_Admin::init();
}

// ── Lifecycle hooks ────────────────────────────────────────────────────────
register_activation_hook(   __FILE__, array( 'WBB_Database', 'activate'   ) );
register_deactivation_hook( __FILE__, array( 'WBB_Database', 'deactivate' ) );
register_uninstall_hook(    __FILE__, array( 'WBB_Database', 'uninstall'  ) );

// ── DB upgrade check ───────────────────────────────────────────────────────
// Runs dbDelta when the stored schema version is behind the plugin. dbDelta is
// safe to re-run: it adds the new table/columns without touching existing data.
add_action( 'plugins_loaded', function () {
	if ( get_option( WBB_Database::DB_VERSION_OPTION ) !== WBB_Database::DB_VERSION ) {
		WBB_Database::activate();
	}
} );

// ── Plugin row "Settings" link ─────────────────────────────────────────────
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$url  = admin_url( 'admin.php?page=wbb-settings' );
	$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wbb-bookings' ) . '</a>';
	array_unshift( $links, $link );
	return $links;
} );

// ── Bootstrap ──────────────────────────────────────────────────────────────
add_action( 'init', function () {
	WBB_Shortcode::init();
} );

add_action( 'admin_init', function () {
	WBB_Settings::register();
} );

// ── AJAX registration ──────────────────────────────────────────────────────
// Registered immediately so the hooks are available on all requests.
WBB_Schedule::register_ajax();
WBB_Availability::register_ajax();
WBB_Bookings::register_ajax();
WBB_Menu::register_ajax();

// ── CSV export via admin-post ──────────────────────────────────────────────
add_action( 'admin_post_wbb_export_bookings', array( 'WBB_Bookings', 'export_csv' ) );

// ── Printable manifests (daily run sheet + food & drink) via admin-post ─────
WBB_Manifest::register();

// ── Full booking edit save (admin) ─────────────────────────────────────────
add_action( 'admin_post_wbb_admin_save_booking', array( 'WBB_Bookings', 'save_booking_full' ) );

// ── "View Book Now Page" opens in new tab ─────────────────────────────────
add_action( 'admin_footer', function () {
	?>
	<script>
	(function () {
		var links = document.querySelectorAll( '#adminmenu a' );
		for ( var i = 0; i < links.length; i++ ) {
			if ( links[ i ].textContent.trim() === 'View Book Now Page' ) {
				links[ i ].setAttribute( 'target', '_blank' );
				links[ i ].setAttribute( 'rel', 'noopener noreferrer' );
			}
		}
	})();
	</script>
	<?php
} );
