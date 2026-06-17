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

$features = [
    [ 'type' => 'svg', 'icon' => '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>',                                                       'label' => wbb_inner_field( 'wob_feature_1', 'Gas BBQ and all cooking gear' ) ],
    [ 'type' => 'svg', 'icon' => '<path d="M3 2h18v4H3zM4 6l2 14h12l2-14"/><line x1="9" y1="11" x2="15" y2="11"/>',                                           'label' => wbb_inner_field( 'wob_feature_2', 'Plates and cutlery included' ) ],
    [ 'type' => 'svg', 'icon' => '<path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3"/>',                          'label' => wbb_inner_field( 'wob_feature_3', 'Cold drinks for sale on board' ) ],
    [ 'type' => 'svg', 'icon' => '<path d="M3 11l19-9-9 19-2-8-8-2z"/>',                                                                                       'label' => wbb_inner_field( 'wob_feature_4', 'BYO your own food' ) ],
    [ 'type' => 'png', 'icon' => $icon_dir . 'round-sailboat.png',                                                                                              'label' => wbb_inner_field( 'wob_feature_5', '2 to 6 people per boat' ) ],
    [ 'type' => 'png', 'icon' => $icon_dir . 'rescue-tube.png',                                                                                                 'label' => wbb_inner_field( 'wob_feature_6', 'Life jackets provided' ) ],
    [ 'type' => 'svg', 'icon' => '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',                                    'label' => wbb_inner_field( 'wob_feature_7', 'Safety briefing before you head out' ) ],
];
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
      <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
        <?php echo esc_html( $headline ); ?>
      </h1>
      <p class="font-body text-white/90 text-lg sm:text-xl leading-relaxed">
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
    <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="font-body text-white/90 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- ── Features ─────────────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="features-heading">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

      <!-- Image -->
      <div class="relative rounded-3xl overflow-hidden aspect-[4/3] shadow-card-hover">
        <img
          src="<?php echo esc_url( $boat_img_url ); ?>"
          alt="<?php echo esc_attr( $boat_img_alt ); ?>"
          width="800"
          height="600"
          loading="lazy"
          decoding="async"
          class="w-full h-full object-cover"
        >
      </div>

      <!-- Features list -->
      <div>
        <h2 id="features-heading" class="section-heading text-3xl lg:text-4xl mb-8">Everything Included</h2>
        <ul class="flex flex-col gap-4 list-none m-0 p-0" role="list">
          <?php foreach ( $features as $feature ) : ?>
          <li class="flex items-center gap-4">
            <span class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-brand-cream rounded-xl">
              <?php if ( $feature['type'] === 'png' ) : ?>
                <img src="<?php echo esc_url( $feature['icon'] ); ?>" alt="" width="24" height="24" class="w-6 h-6 object-contain" loading="lazy" aria-hidden="true">
              <?php else : ?>
                <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <?php echo $feature['icon']; ?>
                </svg>
              <?php endif; ?>
            </span>
            <span class="font-body text-gray-700 text-base"><?php echo esc_html( $feature['label'] ); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>
</section>

<!-- ── The Boats — stat tiles ───────────────────────────────── -->
<section class="bg-brand-cream py-16 px-4 sm:px-6 lg:px-8" aria-labelledby="boats-heading">
  <div class="max-w-4xl mx-auto">
    <h2 id="boats-heading" class="section-heading text-center text-3xl lg:text-4xl mb-10">The Boats</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

      <?php
      $stats = [
          [ 'number' => wbb_inner_field( 'wob_stat_1_number', '2 to 6' ), 'label' => wbb_inner_field( 'wob_stat_1_label', 'per boat' ) ],
          [ 'number' => wbb_inner_field( 'wob_stat_2_number', 'GAS' ), 'label' => wbb_inner_field( 'wob_stat_2_label', 'BBQ + gas cooker' ) ],
          [ 'number' => wbb_inner_field( 'wob_stat_3_number', '0' ),   'label' => wbb_inner_field( 'wob_stat_3_label', 'Licence required' ) ],
      ];
      foreach ( $stats as $stat ) : ?>
      <div class="bg-white rounded-3xl shadow-card p-8 text-center">
        <p class="font-heading text-brand-navy text-5xl lg:text-6xl uppercase mb-2"><?php echo esc_html( $stat['number'] ); ?></p>
        <p class="font-body text-gray-600 text-sm"><?php echo esc_html( $stat['label'] ); ?></p>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- ── Food & Drinks ─────────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="food-heading">
  <div class="max-w-4xl mx-auto">
    <h2 id="food-heading" class="section-heading text-center text-3xl lg:text-4xl mb-10">Food &amp; Drinks</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

      <!-- Food card -->
      <div class="bg-brand-cream rounded-3xl p-8">
        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center mb-5 shadow-card">
          <img src="<?php echo esc_url( $icon_dir ); ?>fish-facing-right.png" alt="" width="28" height="28" class="w-7 h-7 object-contain" loading="lazy" aria-hidden="true">
        </div>
        <h3 class="font-heading text-brand-navy uppercase text-xl mb-3">Bring Your Own Food</h3>
        <p class="font-body text-gray-700 text-sm leading-relaxed"><?php echo esc_html( $food_copy ); ?></p>
      </div>

      <!-- Drinks card -->
      <div class="bg-brand-cream rounded-3xl p-8">
        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center mb-5 shadow-card">
          <svg class="w-6 h-6 text-brand-sky" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3"/>
          </svg>
        </div>
        <h3 class="font-heading text-brand-navy uppercase text-xl mb-3">Drinks On Board</h3>
        <p class="font-body text-gray-700 text-sm leading-relaxed"><?php echo esc_html( $drinks_copy ); ?></p>
      </div>

    </div>
  </div>
</section>

<!-- ── CTA strip ─────────────────────────────────────────────── -->
<section class="bg-brand-navy py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Call to action">
  <img src="<?php echo esc_url( $icon_dir ); ?>big-starfish.png" alt="" width="200" height="200" class="absolute -bottom-4 -right-4 w-48 h-48 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-heading text-white uppercase text-3xl lg:text-4xl mb-4">Ready to Get Out on the Water?</h2>
    <p class="font-body text-blue-100 text-lg mb-8">Book a session and we'll take care of the rest.</p>
    <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-10 py-4">Book Now</a>
  </div>
</section>

<?php get_footer();
