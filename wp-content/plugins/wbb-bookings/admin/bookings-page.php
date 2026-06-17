<?php
/**
 * Renders the WBB Bookings admin page.
 *
 * Filterable table, status badges, action links (View/Confirm/Cancel),
 * inline detail panel, CSV export, pagination.
 */

defined( 'ABSPATH' ) || exit;

function wbb_render_bookings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wbb_bookings';

	// ── Read filter params ─────────────────────────────────────────────────
	$filter_status    = isset( $_GET['status'] )    ? sanitize_key( $_GET['status'] )              : '';
	$filter_date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] )    : '';
	$filter_date_to   = isset( $_GET['date_to'] )   ? sanitize_text_field( $_GET['date_to'] )      : '';
	$filter_search    = isset( $_GET['search'] )    ? sanitize_text_field( $_GET['search'] )       : '';
	$current_page     = isset( $_GET['paged'] )     ? max( 1, absint( $_GET['paged'] ) )            : 1;
	$per_page         = 20;

	// ── Build WHERE clause ─────────────────────────────────────────────────
	$where  = '1=1';
	$params = array();

	if ( $filter_status && in_array( $filter_status, array( 'pending', 'confirmed', 'cancelled' ), true ) ) {
		$where   .= ' AND status = %s';
		$params[] = $filter_status;
	}
	if ( $filter_date_from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_from ) ) {
		$where   .= ' AND booking_date >= %s';
		$params[] = $filter_date_from;
	}
	if ( $filter_date_to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_to ) ) {
		$where   .= ' AND booking_date <= %s';
		$params[] = $filter_date_to;
	}
	if ( $filter_search ) {
		$like     = '%' . $wpdb->esc_like( $filter_search ) . '%';
		$where   .= ' AND (customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s OR booking_ref LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	// ── Counts ─────────────────────────────────────────────────────────────
	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$total = ! empty( $params )
		? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) )  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		: (int) $wpdb->get_var( $count_sql );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$total_pages = max( 1, ceil( $total / $per_page ) );
	$offset      = ( $current_page - 1 ) * $per_page;

	// ── Fetch bookings ─────────────────────────────────────────────────────
	$data_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$query_params = array_merge( $params, array( $per_page, $offset ) );
	$bookings = $wpdb->get_results( $wpdb->prepare( $data_sql, $query_params ) );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$flag_hours  = (int) wbb_setting( 'flag_threshold_hours', 48 );
	$flag_cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$flag_hours} hours" ) );

	// ── Export URL ─────────────────────────────────────────────────────────
	$export_args = array(
		'action'    => 'wbb_export_bookings',
		'status'    => $filter_status,
		'date_from' => $filter_date_from,
		'date_to'   => $filter_date_to,
		'search'    => $filter_search,
	);
	$export_url = wp_nonce_url(
		add_query_arg( array_filter( $export_args ), admin_url( 'admin-post.php' ) ),
		'wbb_export_bookings'
	);

	// ── Status counts for badges ───────────────────────────────────────────
	$status_counts = $wpdb->get_results(
		"SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status",
		OBJECT_K
	);

	?>
	<div class="wrap wbb-bk-wrap">

		<h1 class="wbb-page-title">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Bookings', 'wbb-bookings' ); ?>
			<?php if ( isset( $status_counts['pending'] ) && $status_counts['pending']->cnt > 0 ) : ?>
			<span class="wbb-count-badge wbb-badge--pending"><?php echo esc_html( $status_counts['pending']->cnt ); ?> <?php esc_html_e( 'pending', 'wbb-bookings' ); ?></span>
			<?php endif; ?>
		</h1>

		<!-- ── Filters ────────────────────────────────────────────────── -->
		<form method="get" class="wbb-filters" action="">
			<input type="hidden" name="page" value="wbb-bookings">
			<div class="wbb-filter-row">
				<select name="status" id="wbb-filter-status">
					<option value=""><?php esc_html_e( 'All statuses', 'wbb-bookings' ); ?></option>
					<option value="pending"   <?php selected( $filter_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'wbb-bookings' ); ?></option>
					<option value="confirmed" <?php selected( $filter_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'wbb-bookings' ); ?></option>
					<option value="cancelled" <?php selected( $filter_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wbb-bookings' ); ?></option>
				</select>

				<input type="date" name="date_from" value="<?php echo esc_attr( $filter_date_from ); ?>" placeholder="<?php esc_attr_e( 'From date', 'wbb-bookings' ); ?>" title="<?php esc_attr_e( 'Date from', 'wbb-bookings' ); ?>">
				<input type="date" name="date_to"   value="<?php echo esc_attr( $filter_date_to ); ?>"   placeholder="<?php esc_attr_e( 'To date', 'wbb-bookings' ); ?>"   title="<?php esc_attr_e( 'Date to', 'wbb-bookings' ); ?>">

				<input type="search" name="search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email, phone, ref…', 'wbb-bookings' ); ?>" style="min-width:220px;">

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wbb-bookings' ); ?></button>
				<?php if ( $filter_status || $filter_date_from || $filter_date_to || $filter_search ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbb-bookings' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'wbb-bookings' ); ?></a>
				<?php endif; ?>

				<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-left:auto;">
					&#8595; <?php esc_html_e( 'Export CSV', 'wbb-bookings' ); ?>
				</a>
			</div>
		</form>

		<!-- ── Results info ───────────────────────────────────────────── -->
		<p class="wbb-results-info">
			<?php printf(
				/* translators: 1: number of results */
				esc_html( _n( '%s booking', '%s bookings', $total, 'wbb-bookings' ) ),
				esc_html( number_format_i18n( $total ) )
			); ?>
		</p>

		<!-- ── Bookings table ─────────────────────────────────────────── -->
		<?php if ( empty( $bookings ) ) : ?>
		<div class="wbb-empty">
			<p><?php esc_html_e( 'No bookings found.', 'wbb-bookings' ); ?></p>
		</div>
		<?php else : ?>
		<div class="wbb-table-wrap">
			<table class="wp-list-table widefat fixed striped wbb-bookings-table">
				<thead>
					<tr>
						<th style="width:8%;"><?php esc_html_e( 'Ref', 'wbb-bookings' ); ?></th>
						<th style="width:20%;"><?php esc_html_e( 'Customer', 'wbb-bookings' ); ?></th>
						<th style="width:9%;"><?php esc_html_e( 'Date', 'wbb-bookings' ); ?></th>
						<th style="width:7%;"><?php esc_html_e( 'Time', 'wbb-bookings' ); ?></th>
						<th style="width:8%;"><?php esc_html_e( 'Duration', 'wbb-bookings' ); ?></th>
						<th style="width:6%;"><?php esc_html_e( 'Group', 'wbb-bookings' ); ?></th>
						<th style="width:6%;"><?php esc_html_e( 'Boats', 'wbb-bookings' ); ?></th>
						<th style="width:9%;"><?php esc_html_e( 'Status', 'wbb-bookings' ); ?></th>
						<th style="width:9%;"><?php esc_html_e( 'Submitted', 'wbb-bookings' ); ?></th>
						<th style="width:18%;"><?php esc_html_e( 'Actions', 'wbb-bookings' ); ?></th>
					</tr>
				</thead>
				<tbody id="wbb-bookings-tbody">
				<?php foreach ( $bookings as $b ) :
					$is_flagged    = 'pending' === $b->status && $b->created_at < $flag_cutoff;
					$hours_pending = round( ( time() - strtotime( $b->created_at ) ) / 3600 );
					$row_class     = $is_flagged ? 'wbb-row-flagged' : '';
				?>
				<tr class="wbb-booking-row <?php echo esc_attr( $row_class ); ?>" data-id="<?php echo esc_attr( $b->id ); ?>">
					<td>
						<strong><?php echo esc_html( $b->booking_ref ); ?></strong>
						<?php if ( $is_flagged ) : ?>
						<span class="wbb-flag-icon" title="<?php echo esc_attr( sprintf( __( 'Awaiting confirmation for %d hours.', 'wbb-bookings' ), $hours_pending ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Awaiting confirmation for %d hours.', 'wbb-bookings' ), $hours_pending ) ); ?>">&#9873;</span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo esc_html( $b->customer_name ); ?>
						<br><small><?php echo esc_html( $b->customer_email ); ?></small>
					</td>
					<td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $b->booking_date ) ) ); ?></td>
					<td><?php echo esc_html( $b->time_slot ); ?></td>
					<td><?php echo esc_html( $b->duration_hours ); ?> hr</td>
					<td><?php echo esc_html( $b->group_size ); ?></td>
					<td><?php echo esc_html( $b->boats_requested ); ?></td>
					<td>
						<span class="wbb-status-badge wbb-status-badge--<?php echo esc_attr( $b->status ); ?>">
							<?php echo esc_html( ucfirst( $b->status ) ); ?>
						</span>
					</td>
					<td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $b->created_at ) ) ); ?></td>
					<td class="wbb-row-actions">
						<a href="#" class="wbb-view-btn" data-id="<?php echo esc_attr( $b->id ); ?>"><?php esc_html_e( 'View', 'wbb-bookings' ); ?></a>
						<?php if ( 'confirmed' !== $b->status && 'cancelled' !== $b->status ) : ?>
						<span class="wbb-action-sep">|</span>
						<a href="#" class="wbb-confirm-btn" data-id="<?php echo esc_attr( $b->id ); ?>"><?php esc_html_e( 'Confirm', 'wbb-bookings' ); ?></a>
						<?php endif; ?>
						<?php if ( 'cancelled' !== $b->status ) : ?>
						<span class="wbb-action-sep">|</span>
						<a href="#" class="wbb-cancel-btn" data-id="<?php echo esc_attr( $b->id ); ?>"><?php esc_html_e( 'Cancel', 'wbb-bookings' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<!-- Inline detail panel row (hidden by default) -->
				<tr class="wbb-detail-row wbb-hidden" id="wbb-detail-<?php echo esc_attr( $b->id ); ?>">
					<td colspan="10">
						<div class="wbb-detail-panel" data-id="<?php echo esc_attr( $b->id ); ?>">
							<div class="wbb-detail-loading"><?php esc_html_e( 'Loading…', 'wbb-bookings' ); ?></div>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- ── Pagination ─────────────────────────────────────────────── -->
		<?php if ( $total_pages > 1 ) :
			$base_url = add_query_arg( array_filter( array(
				'page'      => 'wbb-bookings',
				'status'    => $filter_status,
				'date_from' => $filter_date_from,
				'date_to'   => $filter_date_to,
				'search'    => $filter_search,
			) ), admin_url( 'admin.php' ) );
		?>
		<div class="wbb-pagination">
			<?php
			echo paginate_links( array(
				'base'      => $base_url . '&paged=%#%',
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => $total_pages,
				'current'   => $current_page,
			) );
			?>
		</div>
		<?php endif; ?>

		<?php endif; ?>

	</div><!-- /.wrap -->
	<?php
}
