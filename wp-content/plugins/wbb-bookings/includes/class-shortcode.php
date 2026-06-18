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
		$currency     = wbb_setting( 'currency_symbol', '$' );
		$phone = function_exists( 'wallaroo_option' ) ? wallaroo_option( 'phone' ) : get_bloginfo( 'admin_email' );

		// ── Extras (Food & Drink) ─────────────────────────────────────────
		// Group active menu items by category. When there are none, the Extras
		// step is omitted entirely and the remaining steps shift down.
		$menu_by_cat = array();
		if ( class_exists( 'WBB_Menu' ) ) {
			foreach ( WBB_Menu::get_items( null, true ) as $mi ) {
				$menu_by_cat[ $mi->category ][] = $mi;
			}
		}
		$has_extras = ! empty( $menu_by_cat );

		// Step numbering adapts to whether the Extras step is shown.
		$step_extras  = 4;
		$step_details = $has_extras ? 5 : 4;
		$step_review  = $has_extras ? 6 : 5;
		$total_steps  = $step_review;

		$step_labels = array(
			1 => __( 'Date', 'wbb-bookings' ),
			2 => __( 'Time', 'wbb-bookings' ),
			3 => __( 'Group', 'wbb-bookings' ),
		);
		if ( $has_extras ) {
			$step_labels[ $step_extras ] = __( 'Extras', 'wbb-bookings' );
		}
		$step_labels[ $step_details ] = __( 'Details', 'wbb-bookings' );
		$step_labels[ $step_review ]  = __( 'Review', 'wbb-bookings' );

		ob_start();
		?>
		<div id="wbb-booking-form" class="wbb-form-wrap" data-min-group="<?php echo esc_attr( $min_group ); ?>" data-max-per-boat="<?php echo esc_attr( $max_per_boat ); ?>">

			<!-- ── Step indicator ────────────────────────────────────────── -->
			<nav class="wbb-steps-nav" aria-label="<?php esc_attr_e( 'Booking steps', 'wbb-bookings' ); ?>">
				<ol class="wbb-steps" role="list">
					<?php
					$display_num = 0;
					foreach ( $step_labels as $num => $label ) :
						$display_num++;
					?>
					<li class="wbb-step <?php echo 1 === $num ? 'wbb-step--active' : 'wbb-step--upcoming'; ?>" data-step="<?php echo esc_attr( $num ); ?>" aria-current="<?php echo 1 === $num ? 'step' : 'false'; ?>">
						<div class="wbb-step__circle"><span><?php echo esc_html( $display_num ); ?></span></div>
						<span class="wbb-step__label"><?php echo esc_html( $label ); ?></span>
					</li>
					<?php if ( $num < $total_steps ) : ?>
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

			<?php if ( $has_extras ) : ?>
			<!-- ── Step 4: Extras (Food & Drink) ─────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-<?php echo esc_attr( $step_extras ); ?>" data-step="<?php echo esc_attr( $step_extras ); ?>">
				<h2 class="wbb-panel__heading"><?php esc_html_e( 'Add food &amp; drinks', 'wbb-bookings' ); ?></h2>
				<p class="wbb-panel__intro"><?php esc_html_e( 'Optional. Add platters, food and drinks to your booking and we\'ll have it ready when you arrive.', 'wbb-bookings' ); ?></p>

				<?php foreach ( WBB_Menu::CATEGORIES as $cat ) :
					if ( empty( $menu_by_cat[ $cat ] ) ) {
						continue;
					}
				?>
				<div class="wbb-extras-cat">
					<h3 class="wbb-extras-cat__title"><?php echo esc_html( WBB_Menu::category_label( $cat ) ); ?></h3>
					<?php foreach ( $menu_by_cat[ $cat ] as $mi ) : ?>
					<div class="wbb-extra-row" data-id="<?php echo esc_attr( $mi->id ); ?>" data-price="<?php echo esc_attr( $mi->price ); ?>" data-title="<?php echo esc_attr( $mi->title ); ?>">
						<div class="wbb-extra-row__info">
							<span class="wbb-extra-row__title"><?php echo esc_html( $mi->title ); ?></span>
							<?php if ( ! empty( $mi->description ) ) : ?>
							<span class="wbb-extra-row__desc"><?php echo esc_html( $mi->description ); ?></span>
							<?php endif; ?>
						</div>
						<span class="wbb-extra-row__price"><?php echo esc_html( $currency . number_format( (float) $mi->price, 2 ) ); ?></span>
						<div class="wbb-number-wrap wbb-number-wrap--sm">
							<button type="button" class="wbb-num-btn wbb-extra-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wbb-bookings' ); ?>">&#8722;</button>
							<input type="number" class="wbb-input wbb-input--number wbb-extra-qty" value="0" min="0" step="1" aria-label="<?php echo esc_attr( sprintf( __( 'Quantity of %s', 'wbb-bookings' ), $mi->title ) ); ?>">
							<button type="button" class="wbb-num-btn wbb-extra-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'wbb-bookings' ); ?>">&#43;</button>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endforeach; ?>

				<div class="wbb-extras-subtotal">
					<span><?php esc_html_e( 'Extras total', 'wbb-bookings' ); ?>:</span>
					<strong id="wbb-extras-total"><?php echo esc_html( $currency . '0.00' ); ?></strong>
				</div>

				<div class="wbb-actions">
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="3"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
					<button type="button" class="wbb-btn-primary" id="wbb-next-extras"><?php esc_html_e( 'Next', 'wbb-bookings' ); ?></button>
				</div>
			</section>
			<?php endif; ?>

			<!-- ── Step 4: Your details ──────────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-<?php echo esc_attr( $step_details ); ?>" data-step="<?php echo esc_attr( $step_details ); ?>">
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
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="<?php echo esc_attr( $has_extras ? $step_extras : 3 ); ?>"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
					<button type="button" class="wbb-btn-primary" id="wbb-next-details"><?php esc_html_e( 'Next', 'wbb-bookings' ); ?></button>
				</div>
			</section>

			<!-- ── Step 5: Review & submit ───────────────────────────────── -->
			<section class="wbb-panel wbb-hidden" id="wbb-panel-<?php echo esc_attr( $step_review ); ?>" data-step="<?php echo esc_attr( $step_review ); ?>">
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
					<button type="button" class="wbb-btn-ghost wbb-back-btn" data-go-to="<?php echo esc_attr( $step_details ); ?>"><?php esc_html_e( 'Back', 'wbb-bookings' ); ?></button>
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

		// Recompute whether the Extras step is present so the JS knows the step map.
		$has_extras   = class_exists( 'WBB_Menu' ) && ! empty( WBB_Menu::get_items( null, true ) );
		$step_details = $has_extras ? 5 : 4;
		$step_review  = $has_extras ? 6 : 5;

		// Per-boat hire price by occupancy (1..max), for live front-end pricing.
		$max_per_boat = (int) wbb_setting( 'max_per_boat', 6 );
		$boat_prices  = array();
		for ( $p = 1; $p <= $max_per_boat; $p++ ) {
			$boat_prices[ $p ] = function_exists( 'wbb_boat_price_for_people' ) ? wbb_boat_price_for_people( $p ) : 0;
		}

		wp_localize_script( 'wbb-booking-form', 'wbbData', array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'wbb_front_nonce' ),
			'bookingNonce' => wp_create_nonce( 'wbb_booking_nonce' ),
			'minGroup'     => (int) wbb_setting( 'min_group_size', 2 ),
			'maxPerBoat'   => $max_per_boat,
			'showPricing'  => (bool) wbb_setting( 'show_pricing', '1' ),
			'currency'     => wbb_setting( 'currency_symbol', '$' ),
			'priceLabel'   => wbb_setting( 'price_label', 'Estimated total' ),
			'boatPrices'   => $boat_prices,
			'hasExtras'    => $has_extras,
			'stepDetails'  => $step_details,
			'stepReview'   => $step_review,
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
