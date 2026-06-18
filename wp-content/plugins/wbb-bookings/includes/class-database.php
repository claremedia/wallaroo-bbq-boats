<?php
/**
 * WBB_Database — creates and manages the plugin's custom database tables.
 *
 * DB_VERSION 2.0.0: adds wp_wbb_boats, wp_wbb_boat_schedules,
 * wp_wbb_schedule_exceptions; adds boat_id + price_per_boat columns
 * to wp_wbb_bookings.
 * DB_VERSION 2.1.0: adds wp_wbb_menu_items (Food & Drink); adds
 * inclusions + inclusions_total columns to wp_wbb_bookings.
 * DB_VERSION 2.2.0: adds hire_total column to wp_wbb_bookings (tiered hire pricing).
 */

defined( 'ABSPATH' ) || exit;

class WBB_Database {

	const DB_VERSION        = '2.2.0';
	const DB_VERSION_OPTION = 'wbb_db_version';

	// ── Activation: create / upgrade tables ────────────────────────────────
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── Legacy availability table (kept for data preservation) ─────────
		$av = $wpdb->prefix . 'wbb_availability';
		dbDelta( "CREATE TABLE {$av} (
			id              int(11)        NOT NULL AUTO_INCREMENT,
			date            date           NOT NULL,
			time_slot       varchar(20)    NOT NULL DEFAULT '',
			duration_hours  decimal(3,1)   NOT NULL DEFAULT '2.0',
			boats_available int(11)        NOT NULL DEFAULT '0',
			boats_booked    int(11)        NOT NULL DEFAULT '0',
			price_per_boat  decimal(8,2)   NOT NULL DEFAULT '0.00',
			is_blocked      tinyint(1)     NOT NULL DEFAULT '0',
			created_at      datetime       NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY date            (date),
			KEY date_time_slot  (date, time_slot)
		) {$charset_collate};" );

		// ── Bookings table (boat_id + price_per_boat added in v2.0.0) ──────
		$bk = $wpdb->prefix . 'wbb_bookings';
		dbDelta( "CREATE TABLE {$bk} (
			id              int(11)                                NOT NULL AUTO_INCREMENT,
			booking_ref     varchar(20)                            NOT NULL DEFAULT '',
			availability_id int(11)                                NOT NULL DEFAULT '0',
			boat_id         int(11)                                NOT NULL DEFAULT '0',
			booking_date    date                                   NOT NULL,
			time_slot       varchar(20)                            NOT NULL DEFAULT '',
			duration_hours  decimal(3,1)                           NOT NULL DEFAULT '2.0',
			boats_requested int(11)                                NOT NULL DEFAULT '1',
			group_size      int(11)                                NOT NULL DEFAULT '1',
			price_per_boat  decimal(8,2)                           NOT NULL DEFAULT '0.00',
			customer_name   varchar(100)                           NOT NULL DEFAULT '',
			customer_email  varchar(100)                           NOT NULL DEFAULT '',
			customer_phone  varchar(30)                            NOT NULL DEFAULT '',
			notes           text,
			staff_notes     text,
			inclusions       longtext,
			inclusions_total decimal(8,2)                          NOT NULL DEFAULT '0.00',
			hire_total       decimal(8,2)                          NOT NULL DEFAULT '0.00',
			status          enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
			created_at      datetime                               NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at      datetime                               NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY booking_ref   (booking_ref),
			KEY status              (status),
			KEY booking_date        (booking_date),
			KEY availability_id     (availability_id),
			KEY boat_id             (boat_id)
		) {$charset_collate};" );

		// ── Fleet (boats) ──────────────────────────────────────────────────
		$boats = $wpdb->prefix . 'wbb_boats';
		dbDelta( "CREATE TABLE {$boats} (
			id         int(11)      NOT NULL AUTO_INCREMENT,
			name       varchar(100) NOT NULL DEFAULT '',
			active     tinyint(1)   NOT NULL DEFAULT '1',
			sort_order int(11)      NOT NULL DEFAULT '0',
			created_at datetime     NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		) {$charset_collate};" );

		// ── Per-boat weekly schedule ────────────────────────────────────────
		$schedules = $wpdb->prefix . 'wbb_boat_schedules';
		dbDelta( "CREATE TABLE {$schedules} (
			id             int(11)      NOT NULL AUTO_INCREMENT,
			boat_id        int(11)      NOT NULL DEFAULT '0',
			day_of_week    tinyint(1)   NOT NULL DEFAULT '0',
			time_slot      varchar(20)  NOT NULL DEFAULT '',
			duration_hours decimal(3,1) NOT NULL DEFAULT '2.0',
			price          decimal(8,2) NOT NULL DEFAULT '0.00',
			active         tinyint(1)   NOT NULL DEFAULT '1',
			PRIMARY KEY  (id),
			KEY boat_day (boat_id, day_of_week)
		) {$charset_collate};" );

		// ── Per-date session exceptions (blocks) ───────────────────────────
		$exceptions = $wpdb->prefix . 'wbb_schedule_exceptions';
		dbDelta( "CREATE TABLE {$exceptions} (
			id             int(11)     NOT NULL AUTO_INCREMENT,
			boat_id        int(11)     NOT NULL DEFAULT '0',
			exception_date date        NOT NULL,
			time_slot      varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY boat_date_slot (boat_id, exception_date, time_slot),
			KEY exception_date (exception_date)
		) {$charset_collate};" );

		// ── Food & Drink menu items ────────────────────────────────────────
		$menu = $wpdb->prefix . 'wbb_menu_items';
		dbDelta( "CREATE TABLE {$menu} (
			id          int(11)       NOT NULL AUTO_INCREMENT,
			category    varchar(20)   NOT NULL DEFAULT 'food',
			title       varchar(150)  NOT NULL DEFAULT '',
			description text,
			price       decimal(8,2)  NOT NULL DEFAULT '0.00',
			image_id    int(11)       NOT NULL DEFAULT '0',
			active      tinyint(1)    NOT NULL DEFAULT '1',
			sort_order  int(11)       NOT NULL DEFAULT '0',
			created_at  datetime      NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY category_sort (category, sort_order)
		) {$charset_collate};" );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	// ── Deactivation: nothing to do (preserve data) ────────────────────────
	public static function deactivate() {}

	// ── Uninstall: optionally remove all data ──────────────────────────────
	public static function uninstall() {
		$settings = get_option( 'wbb_settings', array() );

		// Guard: only wipe data if the admin explicitly enabled it.
		if ( empty( $settings['delete_on_uninstall'] ) ) {
			return;
		}

		global $wpdb;

		// Drop in reverse FK order.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_menu_items" );           // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_schedule_exceptions" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_boat_schedules" );      // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_boats" );               // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_availability" );        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbb_bookings" );            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		delete_option( 'wbb_settings' );
		delete_option( self::DB_VERSION_OPTION );

		// Clean up per-year booking counters.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wbb_booking_counter_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Clean up rate-limit transients.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wbb_rate_%' OR option_name LIKE '_transient_timeout_wbb_rate_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
