<?php
/**
 * WBB_Schedule — per-boat weekly schedule, exception overrides,
 * and computed availability.
 *
 * Computed availability model:
 *   boats_remaining = boats_scheduled - boats_excepted - boats_booked
 *
 * Replaces the old wp_wbb_availability generation approach.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Schedule {

	// ── AJAX registration ──────────────────────────────────────────────────
	public static function register_ajax() {
		$actions = array(
			'wbb_admin_get_boats'          => 'ajax_get_boats',
			'wbb_admin_save_boat'          => 'ajax_save_boat',
			'wbb_admin_delete_boat'        => 'ajax_delete_boat',
			'wbb_admin_get_boat_schedule'  => 'ajax_get_boat_schedule',
			'wbb_admin_save_boat_schedule' => 'ajax_save_boat_schedule',
			'wbb_admin_get_exceptions'     => 'ajax_get_exceptions',
			'wbb_admin_add_exception'      => 'ajax_add_exception',
			'wbb_admin_remove_exception'   => 'ajax_remove_exception',
			'wbb_admin_get_day_sessions'   => 'ajax_get_day_sessions',
			'wbb_admin_get_month'          => 'ajax_admin_get_month',
			'wbb_admin_get_boat_month'     => 'ajax_admin_get_boat_month',
		);
		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
		}
	}

	// ────────────────────────────────────────────────────────────────────────
	// FLEET MANAGEMENT
	// ────────────────────────────────────────────────────────────────────────

	public static function get_all_boats() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}wbb_boats ORDER BY sort_order ASC, id ASC"
		);
	}

	public static function save_boat( $id, $name, $active, $sort_order ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wbb_boats';

		if ( $id ) {
			$wpdb->update(
				$table,
				array(
					'name'       => $name,
					'active'     => $active,
					'sort_order' => $sort_order,
				),
				array( 'id' => $id ),
				array( '%s', '%d', '%d' ),
				array( '%d' )
			);
			return (int) $id;
		}

		$wpdb->insert(
			$table,
			array(
				'name'       => $name,
				'active'     => $active,
				'sort_order' => $sort_order,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function delete_boat( $boat_id ) {
		global $wpdb;
		$boat_id = (int) $boat_id;
		$wpdb->delete( $wpdb->prefix . 'wbb_boats',                 array( 'id'      => $boat_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'wbb_boat_schedules',        array( 'boat_id' => $boat_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'wbb_schedule_exceptions',   array( 'boat_id' => $boat_id ), array( '%d' ) );
	}

	// ────────────────────────────────────────────────────────────────────────
	// WEEKLY SCHEDULES
	// ────────────────────────────────────────────────────────────────────────

	public static function get_schedule_for_boat( $boat_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wbb_boat_schedules
			  WHERE boat_id = %d
			  ORDER BY day_of_week ASC, time_slot ASC",
			$boat_id
		) );
	}

	/**
	 * Replace the entire schedule for a boat.
	 *
	 * @param int   $boat_id
	 * @param array $sessions Array of associative arrays with keys:
	 *                        day_of_week, time_slot, duration_hours, active
	 */
	public static function save_schedule_for_boat( $boat_id, $sessions ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'wbb_boat_schedules';
		$boat_id = (int) $boat_id;

		// Delete existing and re-insert (simplest idempotent approach).
		$wpdb->delete( $table, array( 'boat_id' => $boat_id ), array( '%d' ) );

		foreach ( $sessions as $s ) {
			$time_slot = sanitize_text_field( $s['time_slot'] ?? '' );
			if ( ! $time_slot ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'boat_id'        => $boat_id,
					'day_of_week'    => absint( $s['day_of_week'] ?? 0 ),
					'time_slot'      => $time_slot,
					'duration_hours' => (float) ( $s['duration_hours'] ?? 2.0 ),
					'active'         => ! empty( $s['active'] ) ? 1 : 0,
				),
				array( '%d', '%d', '%s', '%f', '%d' )
			);
		}
	}

	// ────────────────────────────────────────────────────────────────────────
	// EXCEPTIONS (per-date session blocks)
	// ────────────────────────────────────────────────────────────────────────

	public static function get_exceptions_for_date( $date ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wbb_schedule_exceptions WHERE exception_date = %s",
			$date
		) );
	}

	public static function add_exception( $boat_id, $date, $time_slot ) {
		global $wpdb;
		// REPLACE handles the UNIQUE KEY constraint gracefully.
		$wpdb->replace(
			$wpdb->prefix . 'wbb_schedule_exceptions',
			array(
				'boat_id'        => (int) $boat_id,
				'exception_date' => $date,
				'time_slot'      => $time_slot,
			),
			array( '%d', '%s', '%s' )
		);
	}

	public static function remove_exception( $boat_id, $date, $time_slot ) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'wbb_schedule_exceptions',
			array(
				'boat_id'        => (int) $boat_id,
				'exception_date' => $date,
				'time_slot'      => $time_slot,
			),
			array( '%d', '%s', '%s' )
		);
	}

	// ────────────────────────────────────────────────────────────────────────
	// COMPUTED AVAILABILITY
	// ────────────────────────────────────────────────────────────────────────

	/**
	 * Compute available slot data for a given date.
	 *
	 * Returns an array of slot descriptors, or [] if no slots / out-of-season.
	 */
	public static function get_computed_slots_for_date( $date ) {
		if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return array();
		}
		if ( ! self::is_date_in_season( $date ) ) {
			return array();
		}

		$dow = (int) date( 'w', strtotime( $date ) ); // 0=Sun … 6=Sat

		global $wpdb;

		// Schedules active for this day
		$schedules = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.boat_id, s.time_slot, s.duration_hours
			   FROM {$wpdb->prefix}wbb_boat_schedules s
			   JOIN {$wpdb->prefix}wbb_boats b ON b.id = s.boat_id
			  WHERE s.day_of_week = %d AND s.active = 1 AND b.active = 1
			  ORDER BY s.time_slot ASC, b.sort_order ASC, s.boat_id ASC",
			$dow
		) );

		if ( empty( $schedules ) ) {
			return array();
		}

		// Exceptions for this date
		$exc_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT boat_id, time_slot FROM {$wpdb->prefix}wbb_schedule_exceptions
			  WHERE exception_date = %s",
			$date
		) );
		$exc_set = array();
		foreach ( $exc_rows as $e ) {
			$exc_set[ (int) $e->boat_id . '_' . $e->time_slot ] = true;
		}

		// Bookings already made for this date (non-cancelled)
		$bk_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT time_slot, SUM(boats_requested) AS booked
			   FROM {$wpdb->prefix}wbb_bookings
			  WHERE booking_date = %s AND status != 'cancelled'
			  GROUP BY time_slot",
			$date
		) );
		$booked_map = array();
		foreach ( $bk_rows as $r ) {
			$booked_map[ $r->time_slot ] = (int) $r->booked;
		}

		// Group by time_slot; count non-excepted boats
		$by_slot = array();
		foreach ( $schedules as $s ) {
			$ts = $s->time_slot;
			if ( ! isset( $by_slot[ $ts ] ) ) {
				$by_slot[ $ts ] = array(
					'time_slot'       => $ts,
					'duration_hours'  => (float) $s->duration_hours,
					'boats_scheduled' => 0,
				);
			}
			$exc_key = (int) $s->boat_id . '_' . $ts;
			if ( ! isset( $exc_set[ $exc_key ] ) ) {
				$by_slot[ $ts ]['boats_scheduled']++;
			}
		}

		// Build final output
		$result = array();
		foreach ( $by_slot as $slot ) {
			$ts             = $slot['time_slot'];
			$boats_sched    = $slot['boats_scheduled'];
			$boats_booked   = $booked_map[ $ts ] ?? 0;
			$boats_remaining = max( 0, $boats_sched - $boats_booked );

			$result[] = array(
				'time_slot'       => $ts,
				'duration_hours'  => $slot['duration_hours'],
				'boats_available' => $boats_sched,
				'boats_booked'    => (int) $boats_booked,
				'boats_remaining' => $boats_remaining,
				'is_full'         => $boats_remaining <= 0,
			);
		}

		return $result;
	}

	/**
	 * Return array of 'YYYY-MM-DD' strings that have at least one open slot.
	 * Batches all DB queries to avoid per-day round-trips.
	 */
	public static function get_available_dates( $min_date, $max_date ) {
		global $wpdb;

		// All active schedules
		$all_schedules = $wpdb->get_results(
			"SELECT s.boat_id, s.day_of_week, s.time_slot
			   FROM {$wpdb->prefix}wbb_boat_schedules s
			   JOIN {$wpdb->prefix}wbb_boats b ON b.id = s.boat_id
			  WHERE s.active = 1 AND b.active = 1"
		);

		if ( empty( $all_schedules ) ) {
			return array();
		}

		// Build dow_map: dow => [time_slot => [boat_ids]]
		$dow_map = array();
		foreach ( $all_schedules as $s ) {
			$dow = (int) $s->day_of_week;
			$ts  = $s->time_slot;
			if ( ! isset( $dow_map[ $dow ] ) ) {
				$dow_map[ $dow ] = array();
			}
			if ( ! isset( $dow_map[ $dow ][ $ts ] ) ) {
				$dow_map[ $dow ][ $ts ] = array();
			}
			$dow_map[ $dow ][ $ts ][] = (int) $s->boat_id;
		}

		// Batch-fetch exceptions in range
		$exc_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT boat_id, exception_date, time_slot
			   FROM {$wpdb->prefix}wbb_schedule_exceptions
			  WHERE exception_date >= %s AND exception_date <= %s",
			$min_date,
			$max_date
		) );
		$exc_map = array(); // date => [boat_id_ts => true]
		foreach ( $exc_rows as $e ) {
			$d = $e->exception_date;
			if ( ! isset( $exc_map[ $d ] ) ) {
				$exc_map[ $d ] = array();
			}
			$exc_map[ $d ][ (int) $e->boat_id . '_' . $e->time_slot ] = true;
		}

		// Batch-fetch bookings in range
		$bk_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT booking_date, time_slot, SUM(boats_requested) AS booked
			   FROM {$wpdb->prefix}wbb_bookings
			  WHERE booking_date >= %s AND booking_date <= %s
			    AND status != 'cancelled'
			  GROUP BY booking_date, time_slot",
			$min_date,
			$max_date
		) );
		$bk_map = array(); // date => [time_slot => booked_count]
		foreach ( $bk_rows as $r ) {
			$d = $r->booking_date;
			if ( ! isset( $bk_map[ $d ] ) ) {
				$bk_map[ $d ] = array();
			}
			$bk_map[ $d ][ $r->time_slot ] = (int) $r->booked;
		}

		$available = array();
		$current   = strtotime( $min_date );
		$end       = strtotime( $max_date );

		while ( $current <= $end ) {
			$date = date( 'Y-m-d', $current );
			$dow  = (int) date( 'w', $current );

			if ( isset( $dow_map[ $dow ] ) && self::is_date_in_season( $date ) ) {
				$date_exc = $exc_map[ $date ] ?? array();
				$date_bk  = $bk_map[ $date ]  ?? array();

				if ( self::date_has_availability( $dow, $dow_map, $date_exc, $date_bk ) ) {
					$available[] = $date;
				}
			}

			$current = strtotime( '+1 day', $current );
		}

		return $available;
	}

	// ────────────────────────────────────────────────────────────────────────
	// HELPERS
	// ────────────────────────────────────────────────────────────────────────

	private static function date_has_availability( $dow, $dow_map, $date_exc, $date_bk ) {
		foreach ( $dow_map[ $dow ] as $ts => $boat_ids ) {
			$boats_sched = 0;
			foreach ( $boat_ids as $bid ) {
				if ( ! isset( $date_exc[ $bid . '_' . $ts ] ) ) {
					$boats_sched++;
				}
			}
			$boats_booked = $date_bk[ $ts ] ?? 0;
			if ( $boats_sched - $boats_booked > 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * True if $date falls within the configured operating season.
	 * Supports wrap-around (e.g., Sep–May crosses year boundary).
	 */
	public static function is_date_in_season( $date ) {
		$start = (int) wbb_setting( 'season_start_month', 9 );
		$end   = (int) wbb_setting( 'season_end_month',   5 );
		$month = (int) date( 'm', strtotime( $date ) );

		if ( $start <= $end ) {
			// Non-wrapping season (e.g., March–October).
			return $month >= $start && $month <= $end;
		}
		// Wrapping season (e.g., September–May).
		return $month >= $start || $month <= $end;
	}

	/**
	 * Compute a summary status for a set of slot data.
	 * Used by the admin calendar.
	 */
	public static function compute_day_status( $slots ) {
		if ( empty( $slots ) ) {
			return 'none';
		}

		$any_available = false;
		$any_booked    = false;
		$all_full      = true;

		foreach ( $slots as $s ) {
			if ( $s['boats_remaining'] > 0 ) {
				$any_available = true;
				$all_full      = false;
			}
			if ( $s['boats_booked'] > 0 ) {
				$any_booked = true;
			}
		}

		if ( $all_full ) {
			return 'full';
		}
		if ( $any_booked ) {
			return 'partial';
		}
		if ( $any_available ) {
			return 'available';
		}
		return 'none';
	}

	// ────────────────────────────────────────────────────────────────────────
	// ADMIN AJAX HANDLERS
	// ────────────────────────────────────────────────────────────────────────

	private static function require_admin() {
		check_ajax_referer( 'wbb_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}
	}

	// ── Fleet ──────────────────────────────────────────────────────────────

	public static function ajax_get_boats() {
		self::require_admin();
		wp_send_json_success( array( 'boats' => self::get_all_boats() ) );
	}

	public static function ajax_save_boat() {
		self::require_admin();

		$id         = absint( $_POST['boat_id'] ?? 0 );
		$name       = sanitize_text_field( $_POST['name'] ?? '' );
		$active     = ! empty( $_POST['active'] ) ? 1 : 0;
		$sort_order = absint( $_POST['sort_order'] ?? 0 );

		if ( ! $name ) {
			wp_send_json_error( array( 'message' => 'Boat name is required.' ) );
		}

		$saved_id = self::save_boat( $id, $name, $active, $sort_order );
		wp_send_json_success( array( 'boat_id' => $saved_id, 'boats' => self::get_all_boats() ) );
	}

	public static function ajax_delete_boat() {
		self::require_admin();

		$id = absint( $_POST['boat_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid boat ID.' ) );
		}

		// Refuse if there are active bookings for this boat.
		global $wpdb;
		$has_bookings = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wbb_bookings WHERE boat_id = %d AND status != 'cancelled'",
			$id
		) );
		if ( $has_bookings ) {
			wp_send_json_error( array( 'message' => 'Cannot delete: this boat has active bookings.' ) );
		}

		self::delete_boat( $id );
		wp_send_json_success( array( 'boats' => self::get_all_boats() ) );
	}

	// ── Schedules ──────────────────────────────────────────────────────────

	public static function ajax_get_boat_schedule() {
		self::require_admin();

		$boat_id = absint( $_POST['boat_id'] ?? 0 );
		if ( ! $boat_id ) {
			wp_send_json_error( array( 'message' => 'Invalid boat ID.' ) );
		}

		$rows = self::get_schedule_for_boat( $boat_id );
		wp_send_json_success( array( 'schedule' => $rows ) );
	}

	public static function ajax_save_boat_schedule() {
		self::require_admin();

		$boat_id  = absint( $_POST['boat_id'] ?? 0 );
		$sessions = isset( $_POST['sessions'] ) && is_array( $_POST['sessions'] ) ? $_POST['sessions'] : array();

		if ( ! $boat_id ) {
			wp_send_json_error( array( 'message' => 'Invalid boat ID.' ) );
		}

		self::save_schedule_for_boat( $boat_id, $sessions );
		wp_send_json_success( array( 'message' => 'Schedule saved.' ) );
	}

	// ── Exceptions ─────────────────────────────────────────────────────────

	public static function ajax_get_exceptions() {
		self::require_admin();

		$date = sanitize_text_field( $_POST['date'] ?? '' );
		if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date.' ) );
		}

		wp_send_json_success( array( 'exceptions' => self::get_exceptions_for_date( $date ) ) );
	}

	public static function ajax_add_exception() {
		self::require_admin();

		$boat_id   = absint( $_POST['boat_id'] ?? 0 );
		$date      = sanitize_text_field( $_POST['date'] ?? '' );
		$time_slot = sanitize_text_field( $_POST['time_slot'] ?? '' );

		if ( ! $boat_id || ! $date || ! $time_slot || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid data.' ) );
		}

		self::add_exception( $boat_id, $date, $time_slot );
		wp_send_json_success( array( 'message' => 'Session blocked.' ) );
	}

	public static function ajax_remove_exception() {
		self::require_admin();

		$boat_id   = absint( $_POST['boat_id'] ?? 0 );
		$date      = sanitize_text_field( $_POST['date'] ?? '' );
		$time_slot = sanitize_text_field( $_POST['time_slot'] ?? '' );

		if ( ! $boat_id || ! $date || ! $time_slot || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid data.' ) );
		}

		self::remove_exception( $boat_id, $date, $time_slot );
		wp_send_json_success( array( 'message' => 'Session unblocked.' ) );
	}

	// ── Day sessions (for block UI) ─────────────────────────────────────────

	public static function ajax_get_day_sessions() {
		self::require_admin();

		$date = sanitize_text_field( $_POST['date'] ?? '' );
		if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date.' ) );
		}

		$dow = (int) date( 'w', strtotime( $date ) );

		global $wpdb;

		$schedules = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.boat_id, s.time_slot, s.duration_hours, b.name AS boat_name
			   FROM {$wpdb->prefix}wbb_boat_schedules s
			   JOIN {$wpdb->prefix}wbb_boats b ON b.id = s.boat_id
			  WHERE s.day_of_week = %d AND s.active = 1 AND b.active = 1
			  ORDER BY b.sort_order ASC, b.id ASC, s.time_slot ASC",
			$dow
		) );

		// Exceptions for this date
		$exc_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT boat_id, time_slot FROM {$wpdb->prefix}wbb_schedule_exceptions
			  WHERE exception_date = %s",
			$date
		) );
		$exc_set = array();
		foreach ( $exc_rows as $e ) {
			$exc_set[ (int) $e->boat_id . '_' . $e->time_slot ] = true;
		}

		// Bookings for this date
		$bk_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT time_slot, SUM(boats_requested) AS booked
			   FROM {$wpdb->prefix}wbb_bookings
			  WHERE booking_date = %s AND status != 'cancelled'
			  GROUP BY time_slot",
			$date
		) );
		$booked_map = array();
		foreach ( $bk_rows as $r ) {
			$booked_map[ $r->time_slot ] = (int) $r->booked;
		}

		$result = array();
		foreach ( $schedules as $s ) {
			$key = (int) $s->boat_id . '_' . $s->time_slot;
			$result[] = array(
				'boat_id'        => (int) $s->boat_id,
				'boat_name'      => $s->boat_name,
				'time_slot'      => $s->time_slot,
				'duration_hours' => (float) $s->duration_hours,
				'is_blocked'     => isset( $exc_set[ $key ] ) ? 1 : 0,
				'boats_booked'   => $booked_map[ $s->time_slot ] ?? 0,
			);
		}

		wp_send_json_success( array( 'date' => $date, 'sessions' => $result ) );
	}

	// ── Admin month calendar ────────────────────────────────────────────────

	public static function ajax_admin_get_month() {
		self::require_admin();

		$year  = isset( $_POST['year'] )  ? absint( $_POST['year'] )  : (int) date( 'Y' );
		$month = isset( $_POST['month'] ) ? absint( $_POST['month'] ) : (int) date( 'm' );

		$start = sprintf( '%04d-%02d-01', $year, $month );
		$end   = date( 'Y-m-t', strtotime( $start ) );

		$statuses = array();
		$days     = array();

		$current = strtotime( $start );
		$last    = strtotime( $end );

		while ( $current <= $last ) {
			$date  = date( 'Y-m-d', $current );
			$slots = self::get_computed_slots_for_date( $date );

			if ( ! empty( $slots ) ) {
				$days[ $date ]     = $slots;
				$statuses[ $date ] = self::compute_day_status( $slots );
			} else {
				$statuses[ $date ] = 'none';
			}

			$current = strtotime( '+1 day', $current );
		}

		wp_send_json_success( array(
			'year'     => $year,
			'month'    => $month,
			'days'     => $days,
			'statuses' => $statuses,
		) );
	}

	// ── Per-boat month calendar ─────────────────────────────────────────────

	public static function ajax_admin_get_boat_month() {
		self::require_admin();

		$boat_id = isset( $_POST['boat_id'] ) ? absint( $_POST['boat_id'] ) : 0;
		$year    = isset( $_POST['year'] )    ? absint( $_POST['year'] )    : (int) date( 'Y' );
		$month   = isset( $_POST['month'] )   ? absint( $_POST['month'] )   : (int) date( 'n' );

		if ( ! $boat_id ) {
			wp_send_json_error( array( 'message' => 'Invalid boat.' ) );
		}

		global $wpdb;

		// Weekly schedule for this boat
		$schedules = $wpdb->get_results( $wpdb->prepare(
			"SELECT day_of_week, time_slot, duration_hours
			   FROM {$wpdb->prefix}wbb_boat_schedules
			  WHERE boat_id = %d AND active = 1
			  ORDER BY time_slot ASC",
			$boat_id
		) );

		// Build dow_map: dow -> [ {time_slot, duration_hours}, … ]
		$dow_map = array();
		foreach ( $schedules as $s ) {
			$dow = (int) $s->day_of_week;
			if ( ! isset( $dow_map[ $dow ] ) ) {
				$dow_map[ $dow ] = array();
			}
			$dow_map[ $dow ][] = array(
				'time_slot'      => $s->time_slot,
				'duration_hours' => (float) $s->duration_hours,
			);
		}

		$first_day = sprintf( '%04d-%02d-01', $year, $month );
		$last_day  = date( 'Y-m-t', strtotime( $first_day ) );

		// Exceptions for this boat in this month
		$exceptions = $wpdb->get_results( $wpdb->prepare(
			"SELECT exception_date, time_slot
			   FROM {$wpdb->prefix}wbb_schedule_exceptions
			  WHERE boat_id = %d AND exception_date BETWEEN %s AND %s",
			$boat_id, $first_day, $last_day
		) );

		$exc_map = array();
		foreach ( $exceptions as $e ) {
			$exc_map[ $e->exception_date ][ $e->time_slot ] = true;
		}

		// Bookings per date+slot this month (aggregate across all boats)
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT booking_date, time_slot, COUNT(*) AS booking_count
			   FROM {$wpdb->prefix}wbb_bookings
			  WHERE booking_date BETWEEN %s AND %s AND status != 'cancelled'
			  GROUP BY booking_date, time_slot",
			$first_day, $last_day
		) );

		$bk_map = array();
		foreach ( $bookings as $b ) {
			$bk_map[ $b->booking_date ][ $b->time_slot ] = (int) $b->booking_count;
		}

		$days_in_month = (int) date( 't', strtotime( $first_day ) );
		$days          = array();

		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date_str  = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$dow       = (int) date( 'w', strtotime( $date_str ) );
			$in_season = self::is_date_in_season( $date_str );

			if ( ! $in_season || ! isset( $dow_map[ $dow ] ) ) {
				$days[ $date_str ] = array( 'in_season' => $in_season, 'slots' => array() );
				continue;
			}

			$slots = array();
			foreach ( $dow_map[ $dow ] as $slot ) {
				$ts      = $slot['time_slot'];
				$slots[] = array(
					'time_slot'      => $ts,
					'duration_hours' => $slot['duration_hours'],
					'is_blocked'     => ! empty( $exc_map[ $date_str ][ $ts ] ),
					'boats_booked'   => isset( $bk_map[ $date_str ][ $ts ] ) ? $bk_map[ $date_str ][ $ts ] : 0,
				);
			}

			$days[ $date_str ] = array( 'in_season' => true, 'slots' => $slots );
		}

		wp_send_json_success( array(
			'year'    => $year,
			'month'   => $month,
			'boat_id' => $boat_id,
			'days'    => $days,
		) );
	}
}
