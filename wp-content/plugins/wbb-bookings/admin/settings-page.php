<?php
/**
 * Renders the WBB Settings admin page.
 */

defined( 'ABSPATH' ) || exit;

function wbb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'email';
	$tabs = array(
		'email'    => __( 'Email', 'wbb-bookings' ),
		'rules'    => __( 'Booking Rules', 'wbb-bookings' ),
		'display'  => __( 'Display', 'wbb-bookings' ),
		'business' => __( 'Business', 'wbb-bookings' ),
	);

	$months = array(
		1  => 'January',   2  => 'February', 3  => 'March',
		4  => 'April',     5  => 'May',      6  => 'June',
		7  => 'July',      8  => 'August',   9  => 'September',
		10 => 'October',   11 => 'November', 12 => 'December',
	);

	$duration_options = array(
		'1'   => '1 hour',
		'1.5' => '1.5 hours',
		'2'   => '2 hours',
		'2.5' => '2.5 hours',
		'3'   => '3 hours',
		'3.5' => '3.5 hours',
		'4'   => '4 hours',
	);

	$selected_durations = wbb_setting( 'durations', array() );
	if ( ! is_array( $selected_durations ) ) {
		$selected_durations = array();
	}

	$reset_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=wbb_admin_reset_settings' ), 'wbb_admin_nonce', '_ajax_nonce' );
	?>
	<div class="wrap wbb-settings-wrap">

		<h1 class="wbb-page-title">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Booking Settings', 'wbb-bookings' ); ?>
		</h1>

		<?php settings_errors( 'wbb_settings_group' ); ?>

		<!-- Tabs -->
		<nav class="nav-tab-wrapper wbb-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'wbb-bookings' ); ?>">
			<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbb-settings&tab=' . $slug ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>"
			   <?php echo $active_tab === $slug ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="options.php" class="wbb-settings-form">
			<?php settings_fields( 'wbb_settings_group' ); ?>
			<!-- Hidden field to preserve other tab values -->
			<input type="hidden" name="wbb_settings[_active_tab]" value="<?php echo esc_attr( $active_tab ); ?>">

			<?php if ( 'email' === $active_tab ) : ?>
			<!-- ── Email Tab ──────────────────────────────────────────────── -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Email Sender', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_from_name"><?php esc_html_e( 'From name', 'wbb-bookings' ); ?></label></th>
						<td><input type="text" id="wbb_from_name" name="wbb_settings[from_name]" class="regular-text" value="<?php echo esc_attr( wbb_setting( 'from_name', 'Wallaroo BBQ Boats' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_from_email"><?php esc_html_e( 'From email', 'wbb-bookings' ); ?></label></th>
						<td><input type="email" id="wbb_from_email" name="wbb_settings[from_email]" class="regular-text" value="<?php echo esc_attr( wbb_setting( 'from_email' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_reply_to"><?php esc_html_e( 'Reply-to email', 'wbb-bookings' ); ?></label></th>
						<td><input type="email" id="wbb_reply_to" name="wbb_settings[reply_to_email]" class="regular-text" value="<?php echo esc_attr( wbb_setting( 'reply_to_email' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_admin_email"><?php esc_html_e( 'Admin notification email', 'wbb-bookings' ); ?></label></th>
						<td>
							<input type="email" id="wbb_admin_email" name="wbb_settings[admin_notification_email]" class="regular-text" value="<?php echo esc_attr( wbb_setting( 'admin_notification_email' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Where new booking requests are sent. May differ from your public contact email.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="wbb-card">
				<h2><?php esc_html_e( 'Email Toggles', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$toggles = array(
						'email_customer_on_request' => __( 'Email customer when request is submitted', 'wbb-bookings' ),
						'email_admin_on_request'    => __( 'Email admin when request is submitted', 'wbb-bookings' ),
						'email_customer_on_confirm' => __( 'Email customer when booking is confirmed', 'wbb-bookings' ),
						'email_customer_on_cancel'  => __( 'Email customer when booking is cancelled', 'wbb-bookings' ),
					);
					foreach ( $toggles as $key => $label ) :
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wbb_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( wbb_setting( $key, '1' ), '1' ); ?>>
								<?php esc_html_e( 'Enabled', 'wbb-bookings' ); ?>
							</label>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>

			<?php
			$merge_tags_note = '<code>{customer_name}</code> <code>{booking_ref}</code> <code>{date}</code> <code>{time}</code> <code>{duration}</code> <code>{group_size}</code> <code>{boats}</code> <code>{estimated_price}</code> <code>{customer_email}</code> <code>{customer_phone}</code> <code>{notes}</code> <code>{site_phone}</code> <code>{site_email}</code>';

			$templates = array(
				'template_customer_request'   => __( 'Customer: request received', 'wbb-bookings' ),
				'template_admin_notification' => __( 'Admin: new booking notification', 'wbb-bookings' ),
				'template_confirmed'          => __( 'Customer: booking confirmed', 'wbb-bookings' ),
				'template_cancelled'          => __( 'Customer: booking cancelled', 'wbb-bookings' ),
			);
			?>
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Email Templates', 'wbb-bookings' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Available merge tags:', 'wbb-bookings' ); ?> <?php echo $merge_tags_note; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<table class="form-table" role="presentation">
					<?php foreach ( $templates as $key => $label ) : ?>
					<tr>
						<th><label for="wbb_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<textarea id="wbb_<?php echo esc_attr( $key ); ?>" name="wbb_settings[<?php echo esc_attr( $key ); ?>]" class="large-text" rows="5"><?php echo esc_textarea( wbb_setting( $key ) ); ?></textarea>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>

			<?php elseif ( 'rules' === $active_tab ) : ?>
			<!-- ── Booking Rules Tab ──────────────────────────────────────── -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Booking Rules', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_min_group"><?php esc_html_e( 'Minimum group size', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_min_group" name="wbb_settings[min_group_size]" class="small-text" min="1" value="<?php echo esc_attr( wbb_setting( 'min_group_size', '2' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_max_per_boat"><?php esc_html_e( 'Maximum people per boat', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_max_per_boat" name="wbb_settings[max_per_boat]" class="small-text" min="1" value="<?php echo esc_attr( wbb_setting( 'max_per_boat', '6' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_min_advance"><?php esc_html_e( 'Minimum advance notice (hours)', 'wbb-bookings' ); ?></label></th>
						<td>
							<input type="number" id="wbb_min_advance" name="wbb_settings[min_advance_hours]" class="small-text" min="0" value="<?php echo esc_attr( wbb_setting( 'min_advance_hours', '24' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Customers cannot book slots within this window. Default: 24.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_max_days"><?php esc_html_e( 'Maximum booking window (days)', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_max_days" name="wbb_settings[max_booking_days]" class="small-text" min="1" value="<?php echo esc_attr( wbb_setting( 'max_booking_days', '365' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_flag_threshold"><?php esc_html_e( 'Pending flag threshold (hours)', 'wbb-bookings' ); ?></label></th>
						<td>
							<input type="number" id="wbb_flag_threshold" name="wbb_settings[flag_threshold_hours]" class="small-text" min="1" value="<?php echo esc_attr( wbb_setting( 'flag_threshold_hours', '48' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Pending requests older than this are visually flagged in the bookings list. No auto-cancel.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Auto-confirm bookings', 'wbb-bookings' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wbb_settings[auto_confirm]" value="1" <?php checked( wbb_setting( 'auto_confirm', '0' ), '1' ); ?>>
								<?php esc_html_e( 'Confirm bookings immediately on submission (no manual approval required)', 'wbb-bookings' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<?php elseif ( 'display' === $active_tab ) : ?>
			<!-- ── Display Tab ───────────────────────────────────────────── -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Display Settings', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_currency"><?php esc_html_e( 'Currency symbol', 'wbb-bookings' ); ?></label></th>
						<td><input type="text" id="wbb_currency" name="wbb_settings[currency_symbol]" class="small-text" value="<?php echo esc_attr( wbb_setting( 'currency_symbol', '$' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="wbb_price_label"><?php esc_html_e( 'Price label', 'wbb-bookings' ); ?></label></th>
						<td>
							<input type="text" id="wbb_price_label" name="wbb_settings[price_label]" class="regular-text" value="<?php echo esc_attr( wbb_setting( 'price_label', 'Estimated total' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Shown before the price on the booking summary step.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Show pricing on booking form', 'wbb-bookings' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wbb_settings[show_pricing]" value="1" <?php checked( wbb_setting( 'show_pricing', '1' ), '1' ); ?>>
								<?php esc_html_e( 'Show price estimates on the front-end booking form', 'wbb-bookings' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_form_intro"><?php esc_html_e( 'Booking form intro text', 'wbb-bookings' ); ?></label></th>
						<td>
							<textarea id="wbb_form_intro" name="wbb_settings[form_intro_text]" class="large-text" rows="4"><?php echo esc_textarea( wbb_setting( 'form_intro_text' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Displayed above the booking form on the Book Now page.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_success_msg"><?php esc_html_e( 'Success message', 'wbb-bookings' ); ?></label></th>
						<td>
							<textarea id="wbb_success_msg" name="wbb_settings[success_message]" class="large-text" rows="4"><?php echo esc_textarea( wbb_setting( 'success_message' ) ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Shown after successful form submission. Merge tags:', 'wbb-bookings' ); ?>
								<code>{customer_name}</code> <code>{booking_ref}</code> <code>{date}</code> <code>{time}</code> <code>{site_phone}</code>
							</p>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_confirm_checkbox"><?php esc_html_e( 'Confirmation checkbox text', 'wbb-bookings' ); ?></label></th>
						<td>
							<textarea id="wbb_confirm_checkbox" name="wbb_settings[confirm_checkbox_text]" class="large-text" rows="3"><?php echo esc_textarea( wbb_setting( 'confirm_checkbox_text' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'The checkbox label shown on the final step of the booking form before submitting.', 'wbb-bookings' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<?php elseif ( 'business' === $active_tab ) : ?>
			<!-- ── Business Tab ──────────────────────────────────────────── -->
			<div class="wbb-card">
				<h2><?php esc_html_e( 'Trading Season', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wbb_season_start"><?php esc_html_e( 'Default season start month', 'wbb-bookings' ); ?></label></th>
						<td>
							<select id="wbb_season_start" name="wbb_settings[season_start_month]">
								<?php foreach ( $months as $num => $name ) : ?>
								<option value="<?php echo esc_attr( $num ); ?>" <?php selected( wbb_setting( 'season_start_month', '9' ), (string) $num ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_season_end"><?php esc_html_e( 'Default season end month', 'wbb-bookings' ); ?></label></th>
						<td>
							<select id="wbb_season_end" name="wbb_settings[season_end_month]">
								<?php foreach ( $months as $num => $name ) : ?>
								<option value="<?php echo esc_attr( $num ); ?>" <?php selected( wbb_setting( 'season_end_month', '5' ), (string) $num ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_default_boats"><?php esc_html_e( 'Default boats available', 'wbb-bookings' ); ?></label></th>
						<td><input type="number" id="wbb_default_boats" name="wbb_settings[default_boats_available]" class="small-text" min="1" value="<?php echo esc_attr( wbb_setting( 'default_boats_available', '3' ) ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Available session durations', 'wbb-bookings' ); ?></th>
						<td>
							<?php foreach ( $duration_options as $val => $label ) : ?>
							<label class="wbb-duration-check">
								<input type="checkbox" name="wbb_settings[durations][]" value="<?php echo esc_attr( $val ); ?>" <?php checked( in_array( (string) $val, $selected_durations, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label><br>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th><label for="wbb_slot_interval"><?php esc_html_e( 'Time slot interval', 'wbb-bookings' ); ?></label></th>
						<td>
							<select id="wbb_slot_interval" name="wbb_settings[time_slot_interval]">
								<option value="30"  <?php selected( wbb_setting( 'time_slot_interval', '60' ), '30' ); ?>><?php esc_html_e( 'Every 30 minutes', 'wbb-bookings' ); ?></option>
								<option value="60"  <?php selected( wbb_setting( 'time_slot_interval', '60' ), '60' ); ?>><?php esc_html_e( 'Every hour', 'wbb-bookings' ); ?></option>
								<option value="90"  <?php selected( wbb_setting( 'time_slot_interval', '60' ), '90' ); ?>><?php esc_html_e( 'Every 90 minutes', 'wbb-bookings' ); ?></option>
								<option value="120" <?php selected( wbb_setting( 'time_slot_interval', '60' ), '120' ); ?>><?php esc_html_e( 'Every 2 hours', 'wbb-bookings' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div class="wbb-card wbb-card--danger">
				<h2><?php esc_html_e( 'Data', 'wbb-bookings' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Delete all data on uninstall', 'wbb-bookings' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wbb_settings[delete_on_uninstall]" value="1" <?php checked( wbb_setting( 'delete_on_uninstall', '0' ), '1' ); ?>>
								<?php esc_html_e( 'Remove all database tables and plugin settings when the plugin is deleted. Default: off.', 'wbb-bookings' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<?php endif; ?>

			<div class="wbb-settings-footer">
				<?php submit_button( __( 'Save Settings', 'wbb-bookings' ), 'primary', 'submit', false ); ?>
				&nbsp;&nbsp;
				<button type="button" id="wbb-reset-defaults" class="button button-secondary">
					<?php esc_html_e( 'Reset to Defaults', 'wbb-bookings' ); ?>
				</button>
			</div>

		</form>
	</div>
	<?php
}
