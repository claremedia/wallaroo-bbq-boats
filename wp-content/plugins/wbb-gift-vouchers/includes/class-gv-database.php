<?php
/**
 * WBB_GV_Database — creates/upgrades the gift voucher table.
 */

defined( 'ABSPATH' ) || exit;

class WBB_GV_Database {

	const DB_VERSION        = '1.0.0';
	const DB_VERSION_OPTION = 'wbb_gv_db_version';

	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = $wpdb->prefix . 'wbb_gift_vouchers';
		dbDelta( "CREATE TABLE {$table} (
			id                int(11)      NOT NULL AUTO_INCREMENT,
			voucher_code      varchar(32)  NOT NULL DEFAULT '',
			amount            decimal(8,2) NOT NULL DEFAULT '0.00',
			balance           decimal(8,2) NOT NULL DEFAULT '0.00',
			purchaser_name    varchar(100) NOT NULL DEFAULT '',
			purchaser_email   varchar(100) NOT NULL DEFAULT '',
			purchaser_phone   varchar(30)  NOT NULL DEFAULT '',
			recipient_name    varchar(100) NOT NULL DEFAULT '',
			recipient_email   varchar(100) NOT NULL DEFAULT '',
			recipient_message text,
			status            enum('pending','issued','redeemed','cancelled') NOT NULL DEFAULT 'pending',
			download_token    varchar(64)  NOT NULL DEFAULT '',
			expiry_date       date         DEFAULT NULL,
			staff_notes       text,
			created_at        datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at        datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY voucher_code (voucher_code),
			KEY status (status)
		) {$charset_collate};" );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	public static function deactivate() {}

	public static function uninstall() {
		$settings = get_option( 'wbb_gv_settings', array() );
		if ( empty( $settings['delete_on_uninstall'] ) ) {
			return;
		}
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_gift_vouchers" ); // phpcs:ignore
		delete_option( 'wbb_gv_settings' );
		delete_option( self::DB_VERSION_OPTION );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wbb_gv_counter_%'" ); // phpcs:ignore
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wbb_gv_rate_%' OR option_name LIKE '_transient_timeout_wbb_gv_rate_%'" ); // phpcs:ignore
	}
}
