<?php
/**
 * WBB_GV_Shortcode — [wbb_gift_voucher_form] front-end form.
 */

defined( 'ABSPATH' ) || exit;

class WBB_GV_Shortcode {

	public static function init() {
		add_shortcode( 'wbb_gift_voucher_form', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		self::enqueue_assets();

		$currency   = wbb_gv_setting( 'currency_symbol', '$' );
		$min        = (int) wbb_gv_setting( 'min_amount', 25 );
		$intro      = wbb_gv_setting( 'form_intro_text', '' );
		$confirm    = wbb_gv_setting( 'confirm_checkbox_text' );

		ob_start();
		?>
		<div id="wbb-gv-form" class="wbb-gv-wrap" data-min="<?php echo esc_attr( $min ); ?>">

			<?php if ( $intro ) : ?>
			<p class="wbb-gv-intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<form class="wbb-gv-card" novalidate>

				<!-- Amount -->
				<fieldset class="wbb-gv-section">
					<legend><?php esc_html_e( 'Voucher amount', 'wbb-gift-vouchers' ); ?></legend>
					<div class="wbb-gv-field">
						<label for="wbb-gv-amount" class="wbb-gv-label">
							<?php esc_html_e( 'Amount', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-req" aria-hidden="true">*</span>
						</label>
						<div class="wbb-gv-amount-wrap">
							<span class="wbb-gv-currency"><?php echo esc_html( $currency ); ?></span>
							<input type="number" id="wbb-gv-amount" class="wbb-gv-input wbb-gv-input--amount" min="<?php echo esc_attr( $min ); ?>" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( $min ); ?>">
						</div>
						<p class="wbb-gv-hint"><?php printf( esc_html__( 'Minimum %s.', 'wbb-gift-vouchers' ), esc_html( $currency . number_format( $min, 2 ) ) ); ?></p>
						<span class="wbb-gv-error" data-error-for="amount" role="alert"></span>
					</div>
				</fieldset>

				<!-- Purchaser -->
				<fieldset class="wbb-gv-section">
					<legend><?php esc_html_e( 'Your details', 'wbb-gift-vouchers' ); ?></legend>
					<div class="wbb-gv-field">
						<label for="wbb-gv-pname" class="wbb-gv-label"><?php esc_html_e( 'Your name', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-req" aria-hidden="true">*</span></label>
						<input type="text" id="wbb-gv-pname" class="wbb-gv-input" autocomplete="name">
						<span class="wbb-gv-error" data-error-for="pname" role="alert"></span>
					</div>
					<div class="wbb-gv-field">
						<label for="wbb-gv-pemail" class="wbb-gv-label"><?php esc_html_e( 'Your email', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-req" aria-hidden="true">*</span></label>
						<input type="email" id="wbb-gv-pemail" class="wbb-gv-input" autocomplete="email">
						<span class="wbb-gv-error" data-error-for="pemail" role="alert"></span>
					</div>
					<div class="wbb-gv-field">
						<label for="wbb-gv-pphone" class="wbb-gv-label"><?php esc_html_e( 'Your phone', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-optional"><?php esc_html_e( '(optional)', 'wbb-gift-vouchers' ); ?></span></label>
						<input type="tel" id="wbb-gv-pphone" class="wbb-gv-input" autocomplete="tel">
					</div>
				</fieldset>

				<!-- Recipient -->
				<fieldset class="wbb-gv-section">
					<legend><?php esc_html_e( 'Who is it for?', 'wbb-gift-vouchers' ); ?></legend>
					<div class="wbb-gv-field">
						<label for="wbb-gv-rname" class="wbb-gv-label"><?php esc_html_e( 'Recipient name', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-req" aria-hidden="true">*</span></label>
						<input type="text" id="wbb-gv-rname" class="wbb-gv-input">
						<span class="wbb-gv-error" data-error-for="rname" role="alert"></span>
					</div>
					<div class="wbb-gv-field">
						<label for="wbb-gv-remail" class="wbb-gv-label"><?php esc_html_e( 'Recipient email', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-optional"><?php esc_html_e( '(optional)', 'wbb-gift-vouchers' ); ?></span></label>
						<input type="email" id="wbb-gv-remail" class="wbb-gv-input">
						<span class="wbb-gv-error" data-error-for="remail" role="alert"></span>
					</div>
					<div class="wbb-gv-field">
						<label for="wbb-gv-rmsg" class="wbb-gv-label"><?php esc_html_e( 'Personal message', 'wbb-gift-vouchers' ); ?> <span class="wbb-gv-optional"><?php esc_html_e( '(optional)', 'wbb-gift-vouchers' ); ?></span></label>
						<textarea id="wbb-gv-rmsg" class="wbb-gv-textarea" rows="3" maxlength="300" placeholder="<?php esc_attr_e( 'Happy birthday! Enjoy a day on the water.', 'wbb-gift-vouchers' ); ?>"></textarea>
					</div>
				</fieldset>

				<!-- Confirm + submit -->
				<div class="wbb-gv-confirm">
					<label class="wbb-gv-confirm-label">
						<input type="checkbox" id="wbb-gv-confirm" class="wbb-gv-checkbox">
						<span><?php echo esc_html( $confirm ); ?></span>
					</label>
				</div>

				<p class="wbb-gv-error wbb-gv-submit-error" role="alert"></p>

				<button type="submit" class="wbb-gv-submit" disabled>
					<?php esc_html_e( 'Create Gift Voucher', 'wbb-gift-vouchers' ); ?>
				</button>
			</form>

			<!-- Success -->
			<div class="wbb-gv-success wbb-gv-hidden" role="status" aria-live="polite"></div>

		</div>
		<?php
		return ob_get_clean();
	}

	public static function enqueue_assets() {
		$css = WBB_GV_DIR . 'assets/css/gv-form.css';
		$js  = WBB_GV_DIR . 'assets/js/gv-form.js';

		wp_enqueue_style( 'wbb-gv-form', WBB_GV_URL . 'assets/css/gv-form.css', array(), file_exists( $css ) ? filemtime( $css ) : WBB_GV_VERSION );
		wp_enqueue_script( 'wbb-gv-form', WBB_GV_URL . 'assets/js/gv-form.js', array(), file_exists( $js ) ? filemtime( $js ) : WBB_GV_VERSION, true );

		wp_localize_script( 'wbb-gv-form', 'wbbGV', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wbb_gv_nonce' ),
			'currency' => wbb_gv_setting( 'currency_symbol', '$' ),
			'minAmount' => (float) wbb_gv_setting( 'min_amount', 25 ),
			'strings'  => array(
				'amountMin'     => __( 'Please enter an amount of at least the minimum.', 'wbb-gift-vouchers' ),
				'nameRequired'  => __( 'Please enter your name.', 'wbb-gift-vouchers' ),
				'emailInvalid'  => __( 'Please enter a valid email address.', 'wbb-gift-vouchers' ),
				'rnameRequired' => __( 'Please enter the recipient\'s name.', 'wbb-gift-vouchers' ),
				'remailInvalid' => __( 'Please enter a valid recipient email.', 'wbb-gift-vouchers' ),
				'submitting'    => __( 'Creating voucher…', 'wbb-gift-vouchers' ),
				'submit'        => __( 'Create Gift Voucher', 'wbb-gift-vouchers' ),
				'serverError'   => __( 'Something went wrong. Please try again.', 'wbb-gift-vouchers' ),
				'downloadPdf'   => __( 'Download voucher (PDF)', 'wbb-gift-vouchers' ),
				'successTitle'  => __( 'Voucher created!', 'wbb-gift-vouchers' ),
			),
		) );
	}
}
