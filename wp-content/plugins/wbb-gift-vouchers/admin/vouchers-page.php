<?php
/**
 * Renders the Gift Vouchers admin list.
 */

defined( 'ABSPATH' ) || exit;

function wbb_gv_render_vouchers_page() {
	if ( ! current_user_can( 'wbb_manage' ) ) {
		return;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wbb_gift_vouchers';

	$filter_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	$filter_search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
	$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$per_page      = 20;

	$where  = '1=1';
	$params = array();
	if ( $filter_status && in_array( $filter_status, array( 'pending', 'issued', 'redeemed', 'cancelled' ), true ) ) {
		$where   .= ' AND status = %s';
		$params[] = $filter_status;
	}
	if ( $filter_search ) {
		$like     = '%' . $wpdb->esc_like( $filter_search ) . '%';
		$where   .= ' AND (purchaser_name LIKE %s OR purchaser_email LIKE %s OR recipient_name LIKE %s OR voucher_code LIKE %s)';
		$params   = array_merge( $params, array( $like, $like, $like, $like ) );
	}

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}"; // phpcs:ignore
	$total     = $params ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : (int) $wpdb->get_var( $count_sql ); // phpcs:ignore
	$total_pages = max( 1, ceil( $total / $per_page ) );
	$offset      = ( $current_page - 1 ) * $per_page;

	$data_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d"; // phpcs:ignore
	$vouchers = $wpdb->get_results( $wpdb->prepare( $data_sql, array_merge( $params, array( $per_page, $offset ) ) ) ); // phpcs:ignore

	$currency = wbb_gv_setting( 'currency_symbol', '$' );

	$export_url = wp_nonce_url(
		add_query_arg( array_filter( array( 'action' => 'wbb_gv_export', 'status' => $filter_status, 'search' => $filter_search ) ), admin_url( 'admin-post.php' ) ),
		'wbb_gv_export'
	);
	?>
	<div class="wrap wbb-gv-adminwrap">
		<h1 class="wbb-gv-title">
			<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Gift Vouchers', 'wbb-gift-vouchers' ); ?>
		</h1>
		<p class="wbb-gv-note"><?php esc_html_e( 'Records created from the website form. Payment and emailed delivery are handled in a later phase — new vouchers start as "pending".', 'wbb-gift-vouchers' ); ?></p>

		<form method="get" class="wbb-gv-filters">
			<input type="hidden" name="page" value="wbb-gv-vouchers">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'wbb-gift-vouchers' ); ?></option>
				<?php foreach ( array( 'pending', 'issued', 'redeemed', 'cancelled' ) as $st ) : ?>
				<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $filter_status, $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="search" name="search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email, code…', 'wbb-gift-vouchers' ); ?>" style="min-width:220px;">
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wbb-gift-vouchers' ); ?></button>
			<?php if ( $filter_status || $filter_search ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbb-gv-vouchers' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'wbb-gift-vouchers' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-left:auto;">&#8595; <?php esc_html_e( 'Export CSV', 'wbb-gift-vouchers' ); ?></a>
		</form>

		<p class="wbb-gv-results"><?php printf( esc_html( _n( '%s voucher', '%s vouchers', $total, 'wbb-gift-vouchers' ) ), esc_html( number_format_i18n( $total ) ) ); ?></p>

		<?php if ( empty( $vouchers ) ) : ?>
		<div class="wbb-gv-empty"><p><?php esc_html_e( 'No gift vouchers yet.', 'wbb-gift-vouchers' ); ?></p></div>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped wbb-gv-table">
			<thead>
				<tr>
					<th style="width:13%;"><?php esc_html_e( 'Code', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:9%;"><?php esc_html_e( 'Amount', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:20%;"><?php esc_html_e( 'Purchaser', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:18%;"><?php esc_html_e( 'Recipient', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:10%;"><?php esc_html_e( 'Status', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:10%;"><?php esc_html_e( 'Created', 'wbb-gift-vouchers' ); ?></th>
					<th style="width:20%;"><?php esc_html_e( 'Actions', 'wbb-gift-vouchers' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $vouchers as $v ) : ?>
				<tr data-id="<?php echo esc_attr( $v->id ); ?>">
					<td><strong><?php echo esc_html( $v->voucher_code ); ?></strong></td>
					<td><?php echo esc_html( $currency . number_format( (float) $v->amount, 2 ) ); ?></td>
					<td><?php echo esc_html( $v->purchaser_name ); ?><br><small><?php echo esc_html( $v->purchaser_email ); ?></small></td>
					<td><?php echo esc_html( $v->recipient_name ); ?></td>
					<td><span class="wbb-gv-badge wbb-gv-badge--<?php echo esc_attr( $v->status ); ?>"><?php echo esc_html( ucfirst( $v->status ) ); ?></span></td>
					<td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $v->created_at ) ) ); ?></td>
					<td class="wbb-gv-rowactions">
						<a href="#" class="wbb-gv-view" data-id="<?php echo esc_attr( $v->id ); ?>"><?php esc_html_e( 'View', 'wbb-gift-vouchers' ); ?></a>
						<?php if ( 'issued' !== $v->status && 'cancelled' !== $v->status ) : ?>
						<span class="wbb-gv-sep">|</span>
						<a href="#" class="wbb-gv-issue" data-id="<?php echo esc_attr( $v->id ); ?>"><?php esc_html_e( 'Mark issued', 'wbb-gift-vouchers' ); ?></a>
						<?php endif; ?>
						<?php if ( 'cancelled' !== $v->status ) : ?>
						<span class="wbb-gv-sep">|</span>
						<a href="#" class="wbb-gv-cancel" data-id="<?php echo esc_attr( $v->id ); ?>"><?php esc_html_e( 'Cancel', 'wbb-gift-vouchers' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<tr class="wbb-gv-detailrow wbb-gv-hidden" id="wbb-gv-detail-<?php echo esc_attr( $v->id ); ?>">
					<td colspan="7"><div class="wbb-gv-detail" data-id="<?php echo esc_attr( $v->id ); ?>"><em><?php esc_html_e( 'Loading…', 'wbb-gift-vouchers' ); ?></em></div></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) :
			$base = add_query_arg( array_filter( array( 'page' => 'wbb-gv-vouchers', 'status' => $filter_status, 'search' => $filter_search ) ), admin_url( 'admin.php' ) );
		?>
		<div class="wbb-gv-pagination tablenav"><div class="tablenav-pages">
			<?php echo paginate_links( array( 'base' => $base . '&paged=%#%', 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => $total_pages, 'current' => $current_page ) ); ?>
		</div></div>
		<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
