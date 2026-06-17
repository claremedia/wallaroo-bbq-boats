<?php
/**
 * WBB_Shortcode — registers [wbb_booking_form] and outputs the 5-step form.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Shortcode {

	public static function init() {
		add_shortcode( 'wbb_booking_form', array( __CLASS__, 'render' ) );
	}

	// ── Shortcode output ───────────────────────────────────────────────────
	public static function render( $atts ) {
		self::enqueue_assets();

		$min_group   = (int) wbb_setting( 'min_group_size', 2 );
		$max_per_boat = (int) wbb_setting( 'max_per_boat', 6 );
		$show_pricing = wbb_setting( 'show_pricing', '1' );
		$phone = function_exists( 'wallaroo_option' ) ? wallaroo_option( 'phone' ) : get_bloginfo( 'admin_email' );

		ob_start();
		?>
		<div id="wbb-booking-form" class="wbb-form-wrap" data-min-group="<?php echo esc_attr( $min_group ); ?>" data-max-per-boat="<?php echo esc_attr( $max_per_boat ); ?>">

			<!-- ── Step indicator ────────────────────────────────────────── -->
			<nav class="wbb-steps-nav" aria-label="<?php esc_attr_e( 'Booking steps', 'wbb-bookings' ); ?>">
				<ol class="wbb-steps" role="list">
					<?php
					$step_labels = array(
						1 => 'Date',
						2 => 'Time',
						3 => 'Group',
						4 => 'Details',
						5 => 'Review',
					);
					foreach ( $step_labels as $num => $label ) :
					?>
					<li class="wbb-step <?php echo 1 === $num ? 'wbb-step--active' : 'wbb-step--upcoming'; ?>" data-step="<?php echo esc_attr( $num ); ?>" aria-current="<?php echo 1 === $num ? 'step' : 'false'; ?>">
						<div class="wbb-step__circle"><span><?php echo esc_html( $num ); ?></span></div>
						<span class="wbb-step__label"><?php echo esc_html( $label ); ?></span>
					</li>
					<?php if ( $num < 5 ) : ?>
					<li class="wbb-step__connector" aria-hidden="true"></li>
					<?php endif; ?>
					<?php endforeach; ?>
				</ol>
			</nav>

			<!-- ── Step 1: Choose date ───────────────────────────────────── -->
			<section class="wbb-panel" id="wbb-panel-1" data-step="1">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'Choose a date', 'wbb-bookings' ); ?></h2>
				<p class="wbb-loading-msg" id="wbb-dates-loading"><?php esc_html_e( 'Loading available dates…', 'wbb-bookings' ); ?></p>
				<div id="wbb-calendar-container" aria-label="<?php esc_attr_e( 'Availability calendar', 'wbb-bookings' ); ?>" role="region"></div>
				<p class="wbb-error wbb-hidden" id="wbb-date-error" role="alert"></p>
			</section>

			<!-- ── Step 2: Choose time ───────────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-2" data-step="2">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'Choose a time slot', 'wbb-bookings' ); ?></h2>
				<p class="wbb-selected-date" id="wbb-date-display"></p>
				<p class="wbb-loading-msg wbb-hidden" id="wbb-slots-loading"><?php esc_html_e( 'Loading time slots…', 'wbb-bookings' ); ?></p>
				<div id="wbb-slots-container" role="group" aria-label="<?php esc_attr_e( 'Available time slots', 'wbb-bookings' ); ?>"></div>
				<p class="wbb-error wbb-hidden" id="wbb-slots-error" role="alert"></p>
				<div class="wbb-actions">
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="1"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
				</div>
			</section>

			<!-- ── Step 3: Group size ────────────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-3" data-step="3">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'How many people?', 'wbb-bookings' ); ?></h2>
				<div class="wbb-form-group">
					<label for="wbb-group-size" class="wbb-label">
						<?php esc_html_e( 'Group size', 'wbb-bookings' ); ?>
					</label>
					<div class="wbb-number-wrap">
						<button type="button" class="wbb-num-btn" id="wbb-group-minus" aria-label="<?php esc_attr_e( 'Decrease group size', 'wbb-bookings' ); ?>">&#8722;</button>
						<input type="number"
							id="wbb-group-size"
							class="wbb-input wbb-input--number"
							value="<?php echo esc_attr( $min_group ); ?>"
							min="<?php echo esc_attr( $min_group ); ?>"
							step="1"
							aria-label="<?php esc_attr_e( 'Group size', 'wbb-bookings' ); ?>">
						<button type="button" class="wbb-num-btn" id="wbb-group-plus" aria-label="<?php esc_attr_e( 'Increase group size', 'wbb-bookings' ); ?>">&#43;</button>
					</div>
				</div>
				<div class="wbb-boat-calc" id="wbb-boat-calc">
					<div class="wbb-boat-row">
						<span><?php esc_html_e( 'Boats you\'ll need:', 'wbb-bookings' ); ?></span>
						<strong id="wbb-boats-needed">1</strong>
					</div>
					<div class="wbb-boat-row">
						<span><?php esc_html_e( 'Boats available:', 'wbb-bookings' ); ?></span>
						<strong id="wbb-boats-avail-count">—</strong>
					</div>
					<?php if ( $show_pricing ) : ?>
					<div class="wbb-boat-row wbb-price-row" id="wbb-price-row">
						<span id="wbb-price-label"><?php echo esc_html( wbb_setting( 'price_label', 'Estimated total' ) ); ?>:</span>
						<strong id="wbb-est-price">—</strong>
					</div>
					<?php endif; ?>
				</div>
				<p class="wbb-error wbb-hidden" id="wbb-boats-error" role="alert"></p>
				<div class="wbb-actions">
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="2"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
					<button type="button" class="wbb-btn-primary" id="wbb-next-3" disabled><?php esc_html_e( 'Next', 'wbb-bookings' ); ?></button>
				</div>
			</section>

			<!-- ── Step 4: Your details ──────────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-4" data-step="4">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'Your details', 'wbb-bookings' ); ?></h2>

				<div class="wbb-form-group">
					<label for="wbb-name" class="wbb-label"><?php esc_html_e( 'Full name', 'wbb-bookings' ); ?> <span class="wbb-required" aria-hidden="true">*</span></label>
					<input type="text" id="wbb-name" class="wbb-input" autocomplete="name" required>
					<span class="wbb-field-error" id="wbb-name-error" role="alert" aria-live="polite"></span>
				</div>

				<div class="wbb-form-group">
					<label for="wbb-email" class="wbb-label"><?php esc_html_e( 'Email address', 'wbb-bookings' ); ?> <span class="wbb-required" aria-hidden="true">*</span></label>
					<input type="email" id="wbb-email" class="wbb-input" autocomplete="email" required>
					<span class="wbb-field-error" id="wbb-email-error" role="alert" aria-live="polite"></span>
				</div>

				<div class="wbb-form-group">
					<label for="wbb-phone" class="wbb-label"><?php esc_html_e( 'Phone number', 'wbb-bookings' ); ?> <span class="wbb-required" aria-hidden="true">*</span></label>
					<input type="tel" id="wbb-phone" class="wbb-input" autocomplete="tel"
						placeholder="<?php esc_attr_e( '0400 000 000', 'wbb-bookings' ); ?>" required>
					<span class="wbb-field-error" id="wbb-phone-error" role="alert" aria-live="polite"></span>
				</div>

				<div class="wbb-form-group">
					<label for="wbb-notes" class="wbb-label">
						<?php esc_html_e( 'Notes or special requests', 'wbb-bookings' ); ?>
						<span class="wbb-optional"><?php esc_html_e( '(optional)', 'wbb-bookings' ); ?></span>
					</label>
					<textarea id="wbb-notes" class="wbb-textarea" rows="3"
						placeholder="<?php esc_attr_e( 'Dietary requirements, special occasions, questions…', 'wbb-bookings' ); ?>"></textarea>
				</div>

				<div class="wbb-actions">
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="3"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
					<button type="button" class="wbb-btn-primary" id="wbb-next-4"><?php esc_html_e( 'Next', 'wbb-bookings' ); ?></button>
				</div>
			</section>

			<!-- ── Step 5: Review & submit ───────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-5" data-step="5">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'Review your request', 'wbb-bookings' ); ?></h2>
				<div id="wbb-summary-card" class="wbb-summary-card" role="region" aria-label="<?php esc_attr_e( 'Booking summary', 'wbb-bookings' ); ?>"></div>
				<div class="wbb-confirm-wrap">
					<label class="wbb-confirm-label">
						<input type="checkbox" id="wbb-confirm-cb" class="wbb-checkbox">
						<span><?php echo esc_html( wbb_setting( 'confirm_checkbox_text', 'I understand this is a booking request. Wallaroo BBQ Boats will contact me within 24 hours to confirm.' ) ); ?></span>
					</label>
				</div>
				<p class="wbb-error wbb-hidden" id="wbb-submit-error" role="alert"></p>
				<div class="wbb-actions">
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="4"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
					<button type="button" class="wbb-btn-submit" id="wbb-submit-btn" disabled>
						<?php esc_html_e( 'Send Booking Request', 'wbb-bookings' ); ?>
					</button>
				</div>
			</section>

			<!-- ── Success panel ─────────────────────────────────────────── -->
			<div id="wbb-success-panel" class="wbb-success-panel wbb-hidden" role="status" aria-live="polite"></div>

		</div><!-- /.wbb-form-wrap -->
		<?php
		return ob_get_clean();
	}

	// ── Asset enqueuing ────────────────────────────────────────────────────
	public static function enqueue_assets() {
		$css_file = WBB_PLUGIN_DIR . 'assets/css/booking-form.css';
		$js_file  = WBB_PLUGIN_DIR . 'assets/js/booking-form.js';

		wp_enqueue_style(
			'wbb-booking-form',
			WBB_PLUGIN_URL . 'assets/css/booking-form.css',
			array(),
			file_exists( $css_file ) ? filemtime( $css_file ) : WBB_VERSION
		);

		wp_enqueue_script(
			'wbb-booking-form',
			WBB_PLUGIN_URL . 'assets/js/booking-form.js',
			array(),
			file_exists( $js_file ) ? filemtime( $js_file ) : WBB_VERSION,
			true
		);

		$phone = function_exists( 'wallaroo_option' ) ? wallaroo_option( 'phone' ) : get_bloginfo( 'admin_email' );

		wp_localize_script( 'wbb-booking-form', 'wbbData', array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'wbb_front_nonce' ),
			'bookingNonce' => wp_create_nonce( 'wbb_booking_nonce' ),
			'minGroup'     => (int) wbb_setting( 'min_group_size', 2 ),
			'maxPerBoat'   => (int) wbb_setting( 'max_per_boat', 6 ),
			'showPricing'  => (bool) wbb_setting( 'show_pricing', '1' ),
			'currency'     => wbb_setting( 'currency_symbol', '$' ),
			'priceLabel'   => wbb_setting( 'price_label', 'Estimated total' ),
			'sitePhone'    => $phone,
			'strings'      => array(
				'loadingDates'   => __( 'Loading available dates…', 'wbb-bookings' ),
				'loadingSlots'   => __( 'Loading time slots…', 'wbb-bookings' ),
				'noSlots'        => __( 'No time slots available for this date.', 'wbb-bookings' ),
				'boatsFull'      => __( 'Fully booked', 'wbb-bookings' ),
				'boatsRemain'    => __( 'boats available', 'wbb-bookings' ),
				'hoursLabel'     => __( 'hours', 'wbb-bookings' ),
				'boatsNeeded'    => __( 'Boats you\'ll need', 'wbb-bookings' ),
				'boatsAvail'     => __( 'Boats available', 'wbb-bookings' ),
				'notEnoughBoats' => __( 'Sorry, we do not have enough boats available for a group of that size on this date. Please choose a different date or reduce your group size.', 'wbb-bookings' ),
				'slotTaken'      => __( 'Sorry, this slot has just been taken. Please go back and choose a different time.', 'wbb-bookings' ),
				'serverError'    => sprintf( __( 'Something went wrong. Please try again or call us on %s.', 'wbb-bookings' ), $phone ),
				'nameRequired'   => __( 'Please enter your full name.', 'wbb-bookings' ),
				'emailInvalid'   => __( 'Please enter a valid email address.', 'wbb-bookings' ),
				'phoneRequired'  => __( 'Please enter your phone number.', 'wbb-bookings' ),
				'submitting'     => __( 'Sending request…', 'wbb-bookings' ),
			),
		) );
	}
}
