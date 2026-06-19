<?php
/**
 * WBB_Manifest — printable run sheets for the admin.
 *
 * Two outputs, both served via admin-post as a self-contained, print-optimised
 * HTML page (opens in a new tab, auto-triggers the print dialog):
 *
 *   • Daily run sheet  — every non-cancelled booking for one date, grouped by
 *                        session time, with full booking detail per booking.
 *   • Food & Drink     — every food/drink order across a date range, shown as
 *                        individual orders plus an aggregate "kitchen list".
 */

defined( 'ABSPATH' ) || exit;

class WBB_Manifest {

	// ── Hook registration ──────────────────────────────────────────────────
	public static function register() {
		add_action( 'admin_post_wbb_export_manifest',      array( __CLASS__, 'export_day_manifest' ) );
		add_action( 'admin_post_wbb_export_food_manifest', array( __CLASS__, 'export_food_manifest' ) );
	}

	// ── Daily run sheet ─────────────────────────────────────────────────────
	public static function export_day_manifest() {
		if ( ! current_user_can( 'wbb_manage' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wbb_export_manifest' );

		$date = isset( $_GET['manifest_date'] ) ? sanitize_text_field( wp_unslash( $_GET['manifest_date'] ) ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_bookings';

		// Operational sheet: include everything still live (pending + confirmed),
		// drop cancelled. Order by session time, then by who's coming.
		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table}
			  WHERE booking_date = %s AND status != 'cancelled'
			  ORDER BY time_slot ASC, customer_name ASC",
			$date
		) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Group by session time.
		$sessions = array();
		foreach ( $bookings as $b ) {
			$sessions[ $b->time_slot ][] = $b;
		}

		$currency   = wbb_setting( 'currency_symbol', '$' );
		$total_pax  = 0;
		$total_boat = 0;
		foreach ( $bookings as $b ) {
			$total_pax  += (int) $b->group_size;
			$total_boat += (int) $b->boats_requested;
		}

		$title = sprintf( 'Run Sheet — %s', date_i18n( 'l, j F Y', strtotime( $date ) ) );
		self::print_head( $title );
		?>
		<header class="m-head">
			<div>
				<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<p class="m-sub"><?php esc_html_e( 'Daily Run Sheet', 'wbb-bookings' ); ?></p>
			</div>
			<div class="m-head-right">
				<p class="m-date"><?php echo esc_html( date_i18n( 'l, j F Y', strtotime( $date ) ) ); ?></p>
				<p class="m-meta">
					<?php printf(
						/* translators: 1: bookings 2: guests 3: boats */
						esc_html__( '%1$d bookings · %2$d guests · %3$d boats', 'wbb-bookings' ),
						count( $bookings ), $total_pax, $total_boat
					); ?>
				</p>
			</div>
		</header>

		<?php if ( empty( $bookings ) ) : ?>
			<p class="m-empty"><?php esc_html_e( 'No live bookings for this date.', 'wbb-bookings' ); ?></p>
		<?php else : ?>
			<?php foreach ( $sessions as $slot => $rows ) :
				$sess_pax  = 0;
				$sess_boat = 0;
				foreach ( $rows as $r ) {
					$sess_pax  += (int) $r->group_size;
					$sess_boat += (int) $r->boats_requested;
				}
			?>
			<section class="m-session">
				<h2 class="m-session-title">
					<span><?php echo esc_html( $slot ); ?></span>
					<span class="m-session-meta"><?php printf(
						esc_html__( '%1$d bookings · %2$d guests · %3$d boats', 'wbb-bookings' ),
						count( $rows ), $sess_pax, $sess_boat
					); ?></span>
				</h2>

				<?php foreach ( $rows as $b ) :
					$inclusions = self::decode_inclusions( $b->inclusions );
				?>
				<div class="m-card">
					<div class="m-card-head">
						<span class="m-ref"><?php echo esc_html( $b->booking_ref ); ?></span>
						<span class="m-name"><?php echo esc_html( $b->customer_name ); ?></span>
						<span class="m-badge m-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span>
					</div>
					<table class="m-detail">
						<tr>
							<th><?php esc_html_e( 'Group size', 'wbb-bookings' ); ?></th>
							<td><?php echo esc_html( $b->group_size ); ?></td>
							<th><?php esc_html_e( 'Boats', 'wbb-bookings' ); ?></th>
							<td><?php echo esc_html( $b->boats_requested ); ?></td>
							<th><?php esc_html_e( 'Duration', 'wbb-bookings' ); ?></th>
							<td><?php echo esc_html( $b->duration_hours ); ?> hr</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Phone', 'wbb-bookings' ); ?></th>
							<td><?php echo esc_html( $b->customer_phone ); ?></td>
							<th><?php esc_html_e( 'Email', 'wbb-bookings' ); ?></th>
							<td colspan="3"><?php echo esc_html( $b->customer_email ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Hire total', 'wbb-bookings' ); ?></th>
							<td><?php echo esc_html( $currency . number_format( (float) $b->hire_total, 2 ) ); ?></td>
							<th><?php esc_html_e( 'Food &amp; Drink', 'wbb-bookings' ); ?></th>
							<td colspan="3"><?php echo esc_html( $currency . number_format( (float) $b->inclusions_total, 2 ) ); ?></td>
						</tr>
					</table>

					<?php if ( ! empty( $inclusions ) ) : ?>
					<div class="m-incl">
						<strong><?php esc_html_e( 'Food &amp; Drink:', 'wbb-bookings' ); ?></strong>
						<ul>
							<?php foreach ( $inclusions as $line ) : ?>
							<li><span class="m-qty"><?php echo esc_html( $line['qty'] ); ?>&times;</span> <?php echo esc_html( $line['title'] ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $b->notes ) ) : ?>
					<p class="m-notes"><strong><?php esc_html_e( 'Customer notes:', 'wbb-bookings' ); ?></strong> <?php echo esc_html( $b->notes ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $b->staff_notes ) ) : ?>
					<p class="m-notes m-notes--staff"><strong><?php esc_html_e( 'Staff notes:', 'wbb-bookings' ); ?></strong> <?php echo esc_html( $b->staff_notes ); ?></p>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</section>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php
		self::print_foot();
		exit;
	}

	// ── Food & Drink manifest (date range) ──────────────────────────────────
	public static function export_food_manifest() {
		if ( ! current_user_can( 'wbb_manage' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wbb_export_food_manifest' );

		$from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$to   = isset( $_GET['date_to'] )   ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )   : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
			$from = current_time( 'Y-m-d' );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$to = $from;
		}
		if ( $to < $from ) {
			$tmp = $from;
			$from = $to;
			$to   = $tmp;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wbb_bookings';

		$bookings = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table}
			  WHERE booking_date BETWEEN %s AND %s
			    AND status != 'cancelled'
			    AND inclusions IS NOT NULL AND inclusions != ''
			  ORDER BY booking_date ASC, time_slot ASC, customer_name ASC",
			$from, $to
		) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$currency = wbb_setting( 'currency_symbol', '$' );

		// Build per-order rows + the aggregate kitchen list in one pass.
		$orders    = array();
		$aggregate = array(); // keyed by item id (0 = ad-hoc/legacy), holds title, qty, category.
		foreach ( $bookings as $b ) {
			$lines = self::decode_inclusions( $b->inclusions );
			if ( empty( $lines ) ) {
				continue;
			}
			$orders[] = array( 'booking' => $b, 'lines' => $lines );

			foreach ( $lines as $line ) {
				$id  = isset( $line['id'] ) ? (int) $line['id'] : 0;
				$key = $id > 0 ? 'i' . $id : 't' . md5( $line['title'] );
				if ( ! isset( $aggregate[ $key ] ) ) {
					$aggregate[ $key ] = array(
						'title'    => $line['title'],
						'qty'      => 0,
						'category' => self::lookup_category( $id ),
					);
				}
				$aggregate[ $key ]['qty'] += (int) $line['qty'];
			}
		}

		// Sort the aggregate by category (menu order), then title.
		$cat_order = array( 'food' => 0, 'drinks' => 1, 'platters' => 2, 'other' => 3 );
		uasort( $aggregate, function ( $a, $b ) use ( $cat_order ) {
			$ca = isset( $cat_order[ $a['category'] ] ) ? $cat_order[ $a['category'] ] : 9;
			$cb = isset( $cat_order[ $b['category'] ] ) ? $cat_order[ $b['category'] ] : 9;
			if ( $ca !== $cb ) {
				return $ca - $cb;
			}
			return strcasecmp( $a['title'], $b['title'] );
		} );

		$range_label = ( $from === $to )
			? date_i18n( 'l, j F Y', strtotime( $from ) )
			: date_i18n( 'j M Y', strtotime( $from ) ) . ' – ' . date_i18n( 'j M Y', strtotime( $to ) );

		$title = 'Food & Drink Manifest — ' . $range_label;
		self::print_head( $title );
		?>
		<header class="m-head">
			<div>
				<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<p class="m-sub"><?php esc_html_e( 'Food &amp; Drink Manifest', 'wbb-bookings' ); ?></p>
			</div>
			<div class="m-head-right">
				<p class="m-date"><?php echo esc_html( $range_label ); ?></p>
				<p class="m-meta"><?php printf(
					/* translators: %d: number of orders */
					esc_html( _n( '%d order', '%d orders', count( $orders ), 'wbb-bookings' ) ),
					count( $orders )
				); ?></p>
			</div>
		</header>

		<?php if ( empty( $orders ) ) : ?>
			<p class="m-empty"><?php esc_html_e( 'No food or drink orders in this date range.', 'wbb-bookings' ); ?></p>
		<?php else : ?>

			<!-- ── Aggregate kitchen list ─────────────────────────────────── -->
			<section class="m-session">
				<h2 class="m-session-title"><span><?php esc_html_e( 'Aggregate — total to prepare', 'wbb-bookings' ); ?></span></h2>
				<table class="m-agg">
					<thead>
						<tr>
							<th style="width:14%;"><?php esc_html_e( 'Category', 'wbb-bookings' ); ?></th>
							<th><?php esc_html_e( 'Item', 'wbb-bookings' ); ?></th>
							<th style="width:14%;text-align:right;"><?php esc_html_e( 'Total Qty', 'wbb-bookings' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $aggregate as $row ) : ?>
						<tr>
							<td><?php echo esc_html( self::category_label( $row['category'] ) ); ?></td>
							<td><?php echo esc_html( $row['title'] ); ?></td>
							<td style="text-align:right;"><strong><?php echo esc_html( $row['qty'] ); ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<!-- ── Individual orders ──────────────────────────────────────── -->
			<section class="m-session">
				<h2 class="m-session-title"><span><?php esc_html_e( 'Individual orders', 'wbb-bookings' ); ?></span></h2>
				<?php foreach ( $orders as $order ) :
					$b = $order['booking'];
				?>
				<div class="m-card">
					<div class="m-card-head">
						<span class="m-ref"><?php echo esc_html( $b->booking_ref ); ?></span>
						<span class="m-name"><?php echo esc_html( $b->customer_name ); ?></span>
						<span class="m-order-when"><?php echo esc_html( date_i18n( 'j M Y', strtotime( $b->booking_date ) ) . ' · ' . $b->time_slot ); ?></span>
						<span class="m-badge m-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span>
					</div>
					<ul class="m-order-lines">
						<?php foreach ( $order['lines'] as $line ) : ?>
						<li><span class="m-qty"><?php echo esc_html( $line['qty'] ); ?>&times;</span> <?php echo esc_html( $line['title'] ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( '' !== (string) $b->inclusions_total ) : ?>
					<p class="m-order-total"><?php echo esc_html( $currency . number_format( (float) $b->inclusions_total, 2 ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</section>

		<?php endif; ?>

		<?php
		self::print_foot();
		exit;
	}

	// ── Helpers ─────────────────────────────────────────────────────────────

	/** Decode an inclusions JSON blob to a clean list of {title, qty, id}. */
	private static function decode_inclusions( $json ) {
		if ( empty( $json ) ) {
			return array();
		}
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}
		$out = array();
		foreach ( $decoded as $line ) {
			$title = isset( $line['title'] ) ? (string) $line['title'] : '';
			$qty   = isset( $line['qty'] ) ? (int) $line['qty'] : 0;
			if ( '' === $title || $qty < 1 ) {
				continue;
			}
			$out[] = array(
				'id'    => isset( $line['id'] ) ? (int) $line['id'] : 0,
				'title' => $title,
				'qty'   => $qty,
			);
		}
		return $out;
	}

	/** Resolve a menu item id to its category, falling back to 'other'. */
	private static function lookup_category( $id ) {
		if ( $id > 0 && class_exists( 'WBB_Menu' ) ) {
			$item = WBB_Menu::get_item( $id );
			if ( $item && ! empty( $item->category ) ) {
				return $item->category;
			}
		}
		return 'other';
	}

	/** Human label for an aggregate category. */
	private static function category_label( $key ) {
		if ( 'other' === $key ) {
			return __( 'Other', 'wbb-bookings' );
		}
		return class_exists( 'WBB_Menu' ) ? WBB_Menu::category_label( $key ) : ucfirst( $key );
	}

	// ── Shared print chrome ─────────────────────────────────────────────────
	private static function print_head( $title ) {
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		* { box-sizing: border-box; }
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1d2327; margin: 0; padding: 24px; background: #fff; font-size: 13px; line-height: 1.45; }
		.m-toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; padding: 0 0 16px; }
		.m-toolbar button { font-size: 13px; padding: 7px 16px; border: 1px solid #2271b1; background: #2271b1; color: #fff; border-radius: 3px; cursor: pointer; }
		.m-toolbar button.secondary { background: #fff; color: #2271b1; }
		.m-head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #1d2327; padding-bottom: 10px; margin-bottom: 18px; }
		.m-head h1 { margin: 0; font-size: 20px; }
		.m-sub { margin: 2px 0 0; font-size: 13px; color: #50575e; text-transform: uppercase; letter-spacing: .06em; }
		.m-head-right { text-align: right; }
		.m-date { margin: 0; font-size: 15px; font-weight: 600; }
		.m-meta { margin: 2px 0 0; color: #50575e; }
		.m-empty { padding: 40px; text-align: center; color: #50575e; font-style: italic; }
		.m-session { margin-bottom: 22px; page-break-inside: auto; }
		.m-session-title { display: flex; justify-content: space-between; align-items: baseline; background: #1d2327; color: #fff; padding: 6px 12px; font-size: 15px; margin: 0 0 10px; border-radius: 3px; }
		.m-session-meta { font-size: 12px; font-weight: 400; opacity: .85; }
		.m-card { border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px 12px; margin-bottom: 10px; page-break-inside: avoid; }
		.m-card-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
		.m-ref { font-family: monospace; font-weight: 700; }
		.m-name { font-size: 15px; font-weight: 600; }
		.m-order-when { color: #50575e; }
		.m-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: .04em; margin-left: auto; }
		.m-badge--confirmed { background: #d5e8d4; color: #1e6b2a; }
		.m-badge--pending { background: #fcf0cd; color: #8a6d1a; }
		.m-detail { width: 100%; border-collapse: collapse; }
		.m-detail th { text-align: left; font-weight: 600; color: #50575e; padding: 2px 10px 2px 0; white-space: nowrap; width: 1%; font-size: 12px; }
		.m-detail td { padding: 2px 16px 2px 0; }
		.m-incl { margin-top: 8px; padding-top: 8px; border-top: 1px dashed #dcdcde; }
		.m-incl ul, .m-order-lines { margin: 4px 0 0; padding-left: 4px; list-style: none; }
		.m-incl li, .m-order-lines li { padding: 1px 0; }
		.m-qty { display: inline-block; min-width: 28px; font-weight: 700; font-family: monospace; }
		.m-notes { margin: 8px 0 0; padding: 6px 8px; background: #f6f7f7; border-radius: 3px; }
		.m-notes--staff { background: #fcf0cd; }
		.m-agg { width: 100%; border-collapse: collapse; }
		.m-agg th, .m-agg td { padding: 6px 10px; border-bottom: 1px solid #dcdcde; text-align: left; }
		.m-agg thead th { background: #f0f0f1; border-bottom: 2px solid #c3c4c7; }
		.m-order-total { margin: 6px 0 0; text-align: right; font-weight: 700; }
		@media print {
			body { padding: 0; font-size: 12px; }
			.m-toolbar { display: none; }
			.m-session-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.m-badge, .m-agg thead th, .m-notes--staff { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
		}
	</style>
</head>
<body>
	<div class="m-toolbar">
		<button onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'wbb-bookings' ); ?></button>
		<button class="secondary" onclick="window.close()"><?php esc_html_e( 'Close', 'wbb-bookings' ); ?></button>
	</div>
		<?php
	}

	private static function print_foot() {
		?>
	<script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
		<?php
	}
}
