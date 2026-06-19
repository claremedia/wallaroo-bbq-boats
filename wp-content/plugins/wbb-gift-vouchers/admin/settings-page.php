<?php
/**
 * Renders the Gift Voucher settings page.
 */

defined( 'ABSPATH' ) || exit;

function wbb_gv_render_settings_page() {
	if ( ! current_user_can( 'wbb_manage' ) ) {
		return;
	}
	$s = wp_parse_args( get_option( 'wbb_gv_settings', array() ), WBB_GV_Settings::get_defaults() );
	?>
	<div class="wrap wbb-gv-adminwrap">
		<h1 class="wbb-gv-title">
			<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Gift Voucher Settings', 'wbb-gift-vouchers' ); ?>
		</h1>

		<?php settings_errors( 'wbb_gv_settings_group' ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'wbb_gv_settings_group' ); ?>

			<div class="wbb-gv-settingcard">
				<h2><?php esc_html_e( 'Voucher Rules', 'wbb-gift-vouchers' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gv_min"><?php esc_html_e( 'Minimum amount', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="number" min="0" id="gv_min" name="wbb_gv_settings[min_amount]" value="<?php echo esc_attr( $s['min_amount'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_cur"><?php esc_html_e( 'Currency symbol', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="text" id="gv_cur" name="wbb_gv_settings[currency_symbol]" value="<?php echo esc_attr( $s['currency_symbol'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_exp"><?php esc_html_e( 'Expiry (months)', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="number" min="0" id="gv_exp" name="wbb_gv_settings[expiry_months]" value="<?php echo esc_attr( $s['expiry_months'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Australian gift cards must be valid at least 36 months.', 'wbb-gift-vouchers' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_prefix"><?php esc_html_e( 'Code prefix', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="text" id="gv_prefix" name="wbb_gv_settings[code_prefix]" value="<?php echo esc_attr( $s['code_prefix'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'e.g. WBB → codes like WBB-A1B2-C3D4', 'wbb-gift-vouchers' ); ?></p></td>
					</tr>
				</table>
			</div>

			<div class="wbb-gv-settingcard">
				<h2><?php esc_html_e( 'Notifications', 'wbb-gift-vouchers' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gv_fromname"><?php esc_html_e( 'From name', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="text" id="gv_fromname" name="wbb_gv_settings[from_name]" value="<?php echo esc_attr( $s['from_name'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_fromemail"><?php esc_html_e( 'From email', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="email" id="gv_fromemail" name="wbb_gv_settings[from_email]" value="<?php echo esc_attr( $s['from_email'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_adminemail"><?php esc_html_e( 'Admin notification email', 'wbb-gift-vouchers' ); ?></label></th>
						<td><input type="email" id="gv_adminemail" name="wbb_gv_settings[admin_notification_email]" value="<?php echo esc_attr( $s['admin_notification_email'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email admin on new voucher', 'wbb-gift-vouchers' ); ?></th>
						<td><label><input type="checkbox" name="wbb_gv_settings[email_admin_on_create]" value="1" <?php checked( $s['email_admin_on_create'], '1' ); ?>> <?php esc_html_e( 'Send an internal notification when a voucher is created', 'wbb-gift-vouchers' ); ?></label></td>
					</tr>
				</table>
			</div>

			<div class="wbb-gv-settingcard">
				<h2><?php esc_html_e( 'Form &amp; Voucher Text', 'wbb-gift-vouchers' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gv_intro"><?php esc_html_e( 'Form intro text', 'wbb-gift-vouchers' ); ?></label></th>
						<td><textarea id="gv_intro" name="wbb_gv_settings[form_intro_text]" rows="2" class="large-text"><?php echo esc_textarea( $s['form_intro_text'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_success"><?php esc_html_e( 'Success message', 'wbb-gift-vouchers' ); ?></label></th>
						<td><textarea id="gv_success" name="wbb_gv_settings[success_message]" rows="2" class="large-text"><?php echo esc_textarea( $s['success_message'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Tags: {purchaser_name} {recipient_name} {voucher_code} {amount} {expiry}', 'wbb-gift-vouchers' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_confirm"><?php esc_html_e( 'Confirm checkbox text', 'wbb-gift-vouchers' ); ?></label></th>
						<td><textarea id="gv_confirm" name="wbb_gv_settings[confirm_checkbox_text]" rows="2" class="large-text"><?php echo esc_textarea( $s['confirm_checkbox_text'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="gv_terms"><?php esc_html_e( 'Terms (printed on PDF)', 'wbb-gift-vouchers' ); ?></label></th>
						<td><textarea id="gv_terms" name="wbb_gv_settings[terms_text]" rows="2" class="large-text"><?php echo esc_textarea( $s['terms_text'] ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<div class="wbb-gv-settingcard">
				<h2><?php esc_html_e( 'Advanced', 'wbb-gift-vouchers' ); ?></h2>
				<p class="description" style="margin-bottom:8px;"><?php esc_html_e( 'Square payment and emailed delivery will be added in a later phase.', 'wbb-gift-vouchers' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'wbb-gift-vouchers' ); ?></th>
						<td><label><input type="checkbox" name="wbb_gv_settings[delete_on_uninstall]" value="1" <?php checked( $s['delete_on_uninstall'], '1' ); ?>> <?php esc_html_e( 'Drop the vouchers table and settings when the plugin is deleted', 'wbb-gift-vouchers' ); ?></label></td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Save Settings', 'wbb-gift-vouchers' ), 'primary large' ); ?>
		</form>
	</div>
	<?php
}
