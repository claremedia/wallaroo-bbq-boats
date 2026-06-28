<?php
/**
 * Template Name: What's On Board
 */
get_header();

function wbb_inner_field( $name, $fallback = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( $name );
        return ( $val !== false && $val !== '' && $val !== null ) ? $val : $fallback;
    }
    return $fallback;
}

$booking_url  = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
$icon_dir     = get_template_directory_uri() . '/assets/icons/';
$headline     = wbb_inner_field( 'wob_hero_headline',   "WHAT'S ON BOARD" );
$subheading   = wbb_inner_field( 'wob_hero_subheading', 'Everything you need for a great day on the water.' );
$hero_image   = wbb_inner_field( 'wob_hero_image',      [] );
$hero_img_url = ! empty( $hero_image['url'] ) ? $hero_image['url'] : '';
$hero_img_alt = ! empty( $hero_image['alt'] ) ? $hero_image['alt'] : '';
$boat_image   = wbb_inner_field( 'wob_boat_image',      [] );
$food_copy    = wbb_inner_field( 'wob_food_copy',       'Bring a packed lunch, a snag pack, or order a grazing platter. The BBQ is fired up and ready to go.' );
$drinks_copy  = wbb_inner_field( 'wob_drinks_copy',     'Cold drinks available to purchase on board. No BYO alcohol — keep it safe and legal on the water.' );
$boat_img_url = ! empty( $boat_image['url'] ) ? $boat_image['url'] : 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=800&q=80';
$boat_img_alt = ! empty( $boat_image['alt'] ) ? $boat_image['alt'] : 'BBQ boat on the water';

// Included / Not included — editable per-row in the page editor (ACF), with
// the client-supplied copy as defaults. Up to 8 rows each; blank rows are skipped.
$included_defaults = [
    'Use of your own BBQ Boat for 2 hours, self-driven.',
    'Maximum capacity is 6 participants per boat. Bookings of 7 to 12 people will need to hire two BBQ Boats.',
    'A Bluetooth speaker is available.',
    'Esky and ice provided at no charge.',
    'No BYO alcohol — feel free to bring your own soft drinks or water, or purchase them from us.',
    'Cutlery, plates, cups, napkins etc. provided.',
    'BBQ provided if requested, or keep it as a table for platters or other food.',
];

$not_included_defaults = [
    'We can provide platters if requested.',
    'We prefer that you BYO your own meat for the BBQ. We have limited food options, but you can order these from us with plenty of notice from our price list.',
    'Ask us about our Spencer Gulf King prawn and wine deal.',
    'Drinks — BYO alcohol is not permitted. You may order drinks through the price list on our website; this is a liquor licensing obligation.',
    'You are encouraged to pre-order your drinks from Wallaroo Marina BBQ Boats up to 48 hours before your trip. Please reach out if you have difficulty and we can help with the purchase — ordering on the day will reduce your cruise time.',
    'We will assist you wherever we can to provide you with what you want for your own personalised trip.',
];

$included = [];
$not_included = [];
for ( $i = 1; $i <= 8; $i++ ) {
    $inc = wbb_inner_field( 'wob_included_' . $i, $included_defaults[ $i - 1 ] ?? '' );
    if ( $inc !== '' ) {
        $included[] = $inc;
    }
    $ninc = wbb_inner_field( 'wob_notincluded_' . $i, $not_included_defaults[ $i - 1 ] ?? '' );
    if ( $ninc !== '' ) {
        $not_included[] = $ninc;
    }
}
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<?php if ( $hero_img_url ) : ?>
<section class="relative overflow-hidden bg-brand-navy" style="min-height:460px;" aria-label="Page hero">
  <img
    src="<?php echo esc_url( $hero_img_url ); ?>"
    alt="<?php echo esc_attr( $hero_img_alt ); ?>"
    class="absolute inset-0 w-full h-full object-cover"
    loading="eager" fetchpriority="high" decoding="async" width="1400" height="600"
  >
  <div class="absolute inset-0 bg-brand-navy/65" aria-hidden="true"></div>
  <img src="<?php echo esc_url( $icon_dir ); ?>boat-porthole.png" alt="" width="260" height="260" class="absolute -bottom-6 -right-6 w-56 h-56 object-contain pointer-events-none select-none" style="opacity:0.07;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="relative z-10 py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center">
      <p class="section-subheading text-white/80 mb-3">Wallaroo BBQ Boats</p>
      <h1 class="wbb-hero__title font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
        <?php echo esc_html( $headline ); ?>
      </h1>
      <p class="wbb-hero__sub font-body text-white/90 text-lg sm:text-xl leading-relaxed">
        <?php echo esc_html( $subheading ); ?>
      </p>
    </div>
  </div>
</section>
<?php else : ?>
<section class="bg-brand-sky py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Page hero">
  <img src="<?php echo esc_url( $icon_dir ); ?>boat-porthole.png" alt="" width="260" height="260" class="absolute -bottom-6 -right-6 w-56 h-56 object-contain pointer-events-none select-none" style="opacity:0.07;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-white/80 mb-3">Wallaroo BBQ Boats</p>
    <h1 class="wbb-hero__title font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="wbb-hero__sub font-body text-white/90 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- ── Included / Not Included (two columns) ────────────────── -->
<section class="bg-brand-cream py-20 px-4 sm:px-6 lg:px-8" aria-label="What's included">
  <div class="max-w-6xl mx-auto">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-start">

      <!-- Included (left, and first on mobile) -->
      <div class="wbb-card bg-white rounded-3xl shadow-card p-8">
        <h3 class="font-heading text-brand-navy uppercase text-xl lg:text-2xl mb-6">Included</h3>
        <ul class="flex flex-col gap-4 list-none m-0 p-0" role="list">
          <?php foreach ( $included as $item ) : ?>
          <li class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-navy flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span class="font-body text-gray-700 text-base leading-relaxed"><?php echo esc_html( $item ); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Not Included (right, second on mobile) -->
      <div class="wbb-card bg-white rounded-3xl shadow-card p-8">
        <h3 class="font-heading text-brand-navy uppercase text-xl lg:text-2xl mb-6">Not Included</h3>
        <ul class="flex flex-col gap-4 list-none m-0 p-0" role="list">
          <?php foreach ( $not_included as $item ) : ?>
          <li class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-navy flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            <span class="font-body text-gray-700 text-base leading-relaxed"><?php echo esc_html( $item ); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>
</section>

<!-- ── The Boats — stats over the boat image (no overlay) ───── -->
<section class="relative overflow-hidden bg-brand-navy px-4 sm:px-6 lg:px-8" aria-labelledby="boats-heading">

  <?php if ( $boat_img_url ) : ?>
  <img
    src="<?php echo esc_url( $boat_img_url ); ?>"
    alt="<?php echo esc_attr( $boat_img_alt ); ?>"
    class="absolute inset-0 w-full h-full object-cover"
    width="1400" height="600" loading="lazy" decoding="async"
  >
  <?php endif; ?>

  <!-- Content aligned to the bottom of the image -->
  <div class="relative z-10 max-w-4xl mx-auto flex flex-col justify-end min-h-[360px] lg:min-h-[520px] py-10 lg:py-12">
    <h2 id="boats-heading" class="wbb-section-title section-heading text-white text-shadow-hero text-center text-3xl lg:text-4xl mb-8">The Boats</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">

      <?php
      $stats = [
          [ 'number' => wbb_inner_field( 'wob_stat_1_number', '2 to 6' ), 'label' => wbb_inner_field( 'wob_stat_1_label', 'per boat' ) ],
          [ 'number' => wbb_inner_field( 'wob_stat_2_number', 'GAS' ), 'label' => wbb_inner_field( 'wob_stat_2_label', 'BBQ + gas cooker' ) ],
          [ 'number' => wbb_inner_field( 'wob_stat_3_number', '0' ),   'label' => wbb_inner_field( 'wob_stat_3_label', 'Licence required' ) ],
      ];
      foreach ( $stats as $stat ) : ?>
      <div class="wbb-card bg-white rounded-3xl shadow-card p-6">
        <p class="font-heading text-brand-navy text-5xl lg:text-6xl uppercase mb-1"><?php echo esc_html( $stat['number'] ); ?></p>
        <p class="font-body text-gray-600 text-sm uppercase tracking-wide"><?php echo esc_html( $stat['label'] ); ?></p>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- ── Food & Drinks ─────────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="food-heading">
  <div class="max-w-2xl mx-auto text-center">
    <h2 id="food-heading" class="wbb-section-title section-heading text-3xl lg:text-4xl mb-4">Food &amp; Drinks</h2>
    <p class="font-body text-gray-700 text-base lg:text-lg leading-relaxed mb-8">
      BYO your food and fire up the BBQ, or add platters and food from our menu. No BYO alcohol — soft drinks and water are welcome, or pre-order drinks from us.
    </p>
    <a href="<?php echo esc_url( home_url( '/food-drink/' ) ); ?>" class="btn-outline-navy text-base px-8 py-4">
      See the Food &amp; Drink Menu
    </a>
  </div>
</section>

<!-- ── CTA strip ─────────────────────────────────────────────── -->
<section class="bg-brand-navy py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Call to action">
  <img src="<?php echo esc_url( $icon_dir ); ?>big-starfish.png" alt="" width="200" height="200" class="absolute -bottom-4 -right-4 w-48 h-48 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="wbb-section-title font-heading text-white uppercase text-3xl lg:text-4xl mb-4">Ready to Get Out on the Water?</h2>
    <p class="font-body text-blue-100 text-lg mb-8">Book a session and we'll take care of the rest.</p>
    <a href="<?php echo esc_url( $booking_url ); ?>" class="wbb-booking-cta btn-primary text-base px-10 py-4">Book Now</a>
  </div>
</section>

<?php get_footer();
