<?php
/**
 * Renders the single-booking edit screen (post-editor style).
 */

defined( 'ABSPATH' ) || exit;

function wbb_render_booking_edit_page( $id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wbb_bookings';
	$b     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

	if ( ! $b ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Booking not found', 'wbb-bookings' ) . '</h1><p><a href="' . esc_url( admin_url( 'admin.php?page=wbb-bookings' ) ) . '">' . esc_html__( '← Back to bookings', 'wbb-bookings' ) . '</a></p></div>';
		return;
	}

	$currency  = wbb_setting( 'currency_symbol', '$' );
	$list_url  = admin_url( 'admin.php?page=wbb-bookings' );
	$durations = wbb_setting( 'durations', array( '2', '2.5', '3' ) );

	// Current inclusions keyed by item id.
	$current = array();
	if ( ! empty( $b->inclusions ) ) {
		$decoded = json_decode( $b->inclusions, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $line ) {
				if ( ! empty( $line['id'] ) ) {
					$current[ (int) $line['id'] ] = $line;
				}
			}
		}
	}

	// Active menu items grouped by category.
	$menu_by_cat = array();
	if ( class_exists( 'WBB_Menu' ) ) {
		foreach ( WBB_Menu::get_items( null, true ) as $mi ) {
			$menu_by_cat[ $mi->category ][] = $mi;
		}
	}

	$auto_hire = function_exists( 'wbb_calc_hire_total' ) ? wbb_calc_hire_total( (int) $b->group_size ) : 0;
	?>
	<div class="wrap wbb-bk-edit wbb-bk-wrap">

		<h1 class="wbb-page-title">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<?php printf( esc_html__( 'Edit Booking %s', 'wbb-bookings' ), esc_html( $b->booking_ref ) ); ?>
			<span class="wbb-status-badge wbb-status-badge--<?php echo esc_attr( $b->status ); ?>" style="margin-left:8px;"><?php echo esc_html( ucfirst( $b->status ) ); ?></span>
		</h1>
		<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to bookings', 'wbb-bookings' ); ?></a></p>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking updated.', 'wbb-bookings' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wbb_save_booking' ); ?>
			<input type="hidden" name="action" value="wbb_admin_save_booking">
			<input type="hidden" name="booking_id" value="<?php echo esc_attr( $b->id ); ?>">

			<!-- Booking -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Booking', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_e_status"><?php esc_html_e( 'Status', 'wbb-bookings' ); ?></label></th>
						<td>
							<select id="wbb_e_status" name="status">
								<?php foreach ( array( 'pending', 'confirmed', 'cancelled' ) as $st ) : ?>
								<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $b->status, $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Note: changing status here does not send confirmation/cancellation emails — use the Confirm/Cancel actions on the list for that.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_e_date"><?php esc_html_e( 'Date', 'wbb-bookings' ); ?></label></th>
						<td><input type="date" id="wbb_e_date" name="booking_date" value="<?php echo esc_attr( $b->booking_date ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_time"><?php esc_html_e( 'Time slot', 'wbb-bookings' ); ?></label></th>
						<td><input type="text" id="wbb_e_time" name="time_slot" class="regular-text" value="<?php echo esc_attr( $b->time_slot ); ?>" placeholder="e.g. 10:00 AM"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_duration"><?php esc_html_e( 'Duration (hours)', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_e_duration" name="duration_hours" class="small-text" step="0.5" min="0" value="<?php echo esc_attr( (float) $b->duration_hours ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_group"><?php esc_html_e( 'Group size', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_e_group" name="group_size" class="small-text" min="0" value="<?php echo esc_attr( (int) $b->group_size ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_boats"><?php esc_html_e( 'Boats', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_e_boats" name="boats_requested" class="small-text" min="0" value="<?php echo esc_attr( (int) $b->boats_requested ); ?>"></td>
					</tr>
				</table>
			</div>

			<!-- Customer -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Customer', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_e_name"><?php esc_html_e( 'Name', 'wbb-bookings' ); ?></label></th>
						<td><input type="text" id="wbb_e_name" name="customer_name" class="regular-text" value="<?php echo esc_attr( $b->customer_name ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_email"><?php esc_html_e( 'Email', 'wbb-bookings' ); ?></label></th>
						<td><input type="email" id="wbb_e_email" name="customer_email" class="regular-text" value="<?php echo esc_attr( $b->customer_email ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_phone"><?php esc_html_e( 'Phone', 'wbb-bookings' ); ?></label></th>
						<td><input type="text" id="wbb_e_phone" name="customer_phone" class="regular-text" value="<?php echo esc_attr( $b->customer_phone ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_e_notes"><?php esc_html_e( 'Customer notes', 'wbb-bookings' ); ?></label></th>
						<td><textarea id="wbb_e_notes" name="notes" class="large-text" rows="3"><?php echo esc_textarea( $b->notes ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<!-- Food & Drink -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Food &amp; Drink', 'wbb-bookings' ); ?></h2>
				<p class="description" style="margin-top:0;"><?php esc_html_e( 'Set a quantity for each item. The extras total recalculates on save.', 'wbb-bookings' ); ?></p>

				<?php
				$rendered_ids = array();
				if ( class_exists( 'WBB_Menu' ) && ! empty( $menu_by_cat ) ) :
					foreach ( WBB_Menu::CATEGORIES as $cat ) :
						if ( empty( $menu_by_cat[ $cat ] ) ) {
							continue;
						}
						?>
						<h4 style="margin:14px 0 6px;"><?php echo esc_html( WBB_Menu::category_label( $cat ) ); ?></h4>
						<?php foreach ( $menu_by_cat[ $cat ] as $mi ) :
							$rendered_ids[] = (int) $mi->id;
							$qty = isset( $current[ (int) $mi->id ] ) ? (int) $current[ (int) $mi->id ]['qty'] : 0;
						?>
						<div class="wbb-incl-row" style="display:flex;align-items:center;gap:10px;padding:5px 0;border-bottom:1px solid #f0f0f1;max-width:620px;">
							<input type="number" min="0" name="incl_qty[<?php echo (int) $mi->id; ?>]" value="<?php echo esc_attr( $qty ); ?>" class="small-text" style="width:64px;">
							<span style="flex:1;"><?php echo esc_html( $mi->title ); ?></span>
							<span style="color:#646970;white-space:nowrap;"><?php echo esc_html( $currency . number_format( (float) $mi->price, 2 ) ); ?></span>
							<input type="hidden" name="incl_title[<?php echo (int) $mi->id; ?>]" value="<?php echo esc_attr( $mi->title ); ?>">
							<input type="hidden" name="incl_price[<?php echo (int) $mi->id; ?>]" value="<?php echo esc_attr( $mi->price ); ?>">
						</div>
						<?php endforeach; ?>
						<?php
					endforeach;
				endif;

				// Any current inclusions whose menu item no longer exists — keep them editable.
				$orphans = array();
				foreach ( $current as $iid => $line ) {
					if ( ! in_array( (int) $iid, $rendered_ids, true ) ) {
						$orphans[ $iid ] = $line;
					}
				}
				if ( $orphans ) :
					?>
					<h4 style="margin:14px 0 6px;"><?php esc_html_e( 'Other (removed from menu)', 'wbb-bookings' ); ?></h4>
					<?php foreach ( $orphans as $iid => $line ) : ?>
					<div class="wbb-incl-row" style="display:flex;align-items:center;gap:10px;padding:5px 0;border-bottom:1px solid #f0f0f1;max-width:620px;">
						<input type="number" min="0" name="incl_qty[<?php echo (int) $iid; ?>]" value="<?php echo esc_attr( (int) $line['qty'] ); ?>" class="small-text" style="width:64px;">
						<span style="flex:1;"><?php echo esc_html( $line['title'] ); ?></span>
						<span style="color:#646970;white-space:nowrap;"><?php echo esc_html( $currency . number_format( (float) $line['unit_price'], 2 ) ); ?></span>
						<input type="hidden" name="incl_title[<?php echo (int) $iid; ?>]" value="<?php echo esc_attr( $line['title'] ); ?>">
						<input type="hidden" name="incl_price[<?php echo (int) $iid; ?>]" value="<?php echo esc_attr( $line['unit_price'] ); ?>">
					</div>
					<?php endforeach; ?>
					<?php
				endif;

				if ( empty( $menu_by_cat ) && empty( $orphans ) ) :
					?>
					<p><?php esc_html_e( 'No menu items available. Add items under Food & Drink.', 'wbb-bookings' ); ?></p>
					<?php
				endif;
				?>
			</div>

			<!-- Pricing -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Pricing', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_e_hire"><?php esc_html_e( 'Boat hire total', 'wbb-bookings' ); ?></label></th>
						<td>
							<span><?php echo esc_html( $currency ); ?></span>
							<input type="number" id="wbb_e_hire" name="hire_total" class="small-text" step="0.01" min="0" value="<?php echo esc_attr( number_format( (float) $b->hire_total, 2, '.', '' ) ); ?>">
							<p class="description"><?php printf( esc_html__( 'Auto-calculated from the current group size: %s. Edit if you need to override.', 'wbb-bookings' ), esc_html( $currency . number_format( (float) $auto_hire, 2 ) ) ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Extras total', 'wbb-bookings' ); ?></th>
						<td><strong><?php echo esc_html( $currency . number_format( (float) $b->inclusions_total, 2 ) ); ?></strong> <span class="description">(<?php esc_html_e( 'recalculated on save', 'wbb-bookings' ); ?>)</span></td>
					</tr>
				</table>
			</div>

			<!-- Staff notes -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Staff notes', 'wbb-bookings' ); ?></h2>
				<textarea name="staff_notes" class="large-text" rows="3"><?php echo esc_textarea( $b->staff_notes ); ?></textarea>
			</div>

			<p class="submit">
				<?php submit_button( __( 'Save Booking', 'wbb-bookings' ), 'primary', 'submit', false ); ?>
				&nbsp;
				<a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wbb-bookings' ); ?></a>
			</p>
		</form>
	</div>
	<?php
}
