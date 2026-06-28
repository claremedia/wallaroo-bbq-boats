<?php
/**
 * Template Name: Book Now
 * Placeholder booking page until booking system is wired up.
 */
get_header();

function wbb_inner_field( $name, $fallback = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( $name );
        return ( $val !== false && $val !== '' && $val !== null ) ? $val : $fallback;
    }
    return $fallback;
}

$phone       = wallaroo_option( 'phone' );
$email       = wallaroo_option( 'email' );
$tel_href    = 'tel:' . preg_replace( '/\s+/', '', $phone );
$booking_url = wallaroo_option( 'booking_url' ) ?: '#';

$headline    = wbb_inner_field( 'bn_hero_headline',       'BOOK YOUR BOAT' );
$subheading  = wbb_inner_field( 'bn_hero_subheading',     'Pick a date, grab the crew, and head down the marina.' );
$placeholder = wbb_inner_field( 'bn_placeholder_message', 'Online booking coming soon. Call us or email to check availability and lock in your date.' );
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="bg-brand-navy py-20 px-4 sm:px-6 lg:px-8" aria-label="Page hero">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-brand-sky mb-3">Wallaroo BBQ Boats</p>
    <h1 class="wbb-hero__title font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="wbb-hero__sub font-body text-blue-100 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>

<!-- ── Booking form ─────────────────────────────────────────── -->
<section class="bg-gray-50 py-16 px-4 sm:px-6 lg:px-8" aria-label="Booking">
  <div class="max-w-2xl mx-auto">

    <?php
    // Intro text from plugin settings (if plugin is active).
    $intro_text = function_exists( 'wbb_setting' ) ? wbb_setting( 'form_intro_text', '' ) : '';
    if ( $intro_text ) : ?>
    <div class="font-body text-gray-600 text-base lg:text-lg leading-relaxed mb-8">
      <?php echo wp_kses_post( wpautop( $intro_text ) ); ?>
    </div>
    <?php endif; ?>

    <?php
    // Render the booking form if the plugin shortcode is available,
    // otherwise fall back to the original contact card.
    if ( shortcode_exists( 'wbb_booking_form' ) ) :
        echo do_shortcode( '[wbb_booking_form]' );
    else : ?>
    <div class="bg-white rounded-3xl shadow-card-hover p-8 lg:p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-cream rounded-2xl mb-6">
        <svg class="w-8 h-8 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8"  y1="2" x2="8"  y2="6"/>
          <line x1="3"  y1="10" x2="21" y2="10"/>
        </svg>
      </div>
      <h2 class="font-heading text-brand-navy uppercase text-2xl lg:text-3xl mb-4">Check Availability</h2>
      <p class="font-body text-gray-600 text-base lg:text-lg leading-relaxed mb-8 max-w-lg mx-auto">
        Online booking coming soon. Call us or email to check availability and lock in your date.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo esc_url( $tel_href ); ?>" class="btn-primary text-base px-8 py-4">
          Call <?php echo esc_html( $phone ); ?>
        </a>
        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="btn-outline-navy text-base px-8 py-4">
          Email Us
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- ── How It Works reminder ────────────────────────────────── -->
<section class="bg-white py-16 px-4 sm:px-6 lg:px-8 border-t border-gray-100" aria-labelledby="how-it-works-heading">
  <div class="max-w-4xl mx-auto">

    <h2 id="how-it-works-heading" class="wbb-section-title font-heading text-brand-navy uppercase text-center text-2xl lg:text-3xl mb-10">How It Works</h2>

    <ol class="grid grid-cols-1 sm:grid-cols-3 gap-8 list-none m-0 p-0" role="list">

      <?php
      $steps = [
          [ 'number' => '01', 'heading' => 'Book online',                            'body' => 'Pick your date and group size. Confirm your booking in minutes.' ],
          [ 'number' => '02', 'heading' => 'Turn up 15 minutes early',               'body' => 'We\'ll run you through the quick safety briefing and show you the ropes.' ],
          [ 'number' => '03', 'heading' => 'We hand you the keys — sort yourselves out', 'body' => 'Head out onto Spencer Gulf, fire up the BBQ, and enjoy your session on the water.' ],
      ];
      foreach ( $steps as $step ) : ?>
      <li class="wbb-card flex flex-col items-center text-center gap-3">
        <span class="font-heading text-brand-red text-5xl leading-none"><?php echo esc_html( $step['number'] ); ?></span>
        <h3 class="font-heading text-brand-navy uppercase text-base"><?php echo esc_html( $step['heading'] ); ?></h3>
        <p class="font-body text-gray-600 text-sm leading-relaxed"><?php echo esc_html( $step['body'] ); ?></p>
      </li>
      <?php endforeach; ?>

    </ol>

  </div>
</section>

<?php get_footer();
