<?php
/**
 * Template Name: Food & Drink
 *
 * Lists menu items managed in the WBB Bookings plugin (Food / Drinks / Platters).
 * Uses the compact row layout from the booking "Extras" step.
 */
get_header();

if ( ! function_exists( 'wbb_inner_field' ) ) {
    function wbb_inner_field( $name, $fallback = '' ) {
        if ( function_exists( 'get_field' ) ) {
            $val = get_field( $name );
            return ( $val !== false && $val !== '' && $val !== null ) ? $val : $fallback;
        }
        return $fallback;
    }
}

$booking_url  = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
$phone        = wallaroo_option( 'phone' );
$tel_href     = 'tel:' . preg_replace( '/\s+/', '', $phone );
$icon_dir     = get_template_directory_uri() . '/assets/icons/';

$headline     = wbb_inner_field( 'fd_hero_headline',   'FOOD &amp; DRINK' );
$subheading   = wbb_inner_field( 'fd_hero_subheading', 'Sort the food before you get on the water. Add it to your booking or grab it on the day.' );
$hero_image   = wbb_inner_field( 'fd_hero_image',      [] );
$hero_img_url = ! empty( $hero_image['url'] ) ? $hero_image['url'] : '';
$hero_img_alt = ! empty( $hero_image['alt'] ) ? $hero_image['alt'] : '';

$currency = function_exists( 'wbb_setting' ) ? wbb_setting( 'currency_symbol', '$' ) : '$';

$cat_subtitles = [
    'food'     => 'Cook it up on the BBQ',
    'drinks'   => 'Cold drinks on board',
    'platters' => 'Ready to graze',
];
?>

<!-- ── Hero (condensed) ─────────────────────────────────────── -->
<?php if ( $hero_img_url ) : ?>
<section class="relative overflow-hidden bg-brand-navy" style="min-height:300px;" aria-label="Page hero">
  <img
    src="<?php echo esc_url( $hero_img_url ); ?>"
    alt="<?php echo esc_attr( $hero_img_alt ); ?>"
    class="absolute inset-0 w-full h-full object-cover"
    loading="eager" fetchpriority="high" decoding="async" width="1400" height="500"
  >
  <div class="absolute inset-0 bg-brand-navy/65" aria-hidden="true"></div>
  <div class="relative z-10 py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center">
      <p class="section-subheading text-white/80 mb-2">Wallaroo BBQ Boats</p>
      <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl leading-tight mb-3">
        <?php echo wp_kses_post( $headline ); ?>
      </h1>
      <p class="font-body text-white/90 text-base sm:text-lg leading-relaxed">
        <?php echo esc_html( $subheading ); ?>
      </p>
    </div>
  </div>
</section>
<?php else : ?>
<section class="bg-brand-sky py-14 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Page hero">
  <img src="<?php echo esc_url( $icon_dir ); ?>fish-facing-right.png" alt="" width="200" height="200" class="absolute -bottom-6 -right-6 w-44 h-44 object-contain pointer-events-none select-none" style="opacity:0.07;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-white/80 mb-2">Wallaroo BBQ Boats</p>
    <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl leading-tight mb-3">
      <?php echo wp_kses_post( $headline ); ?>
    </h1>
    <p class="font-body text-white/90 text-base sm:text-lg leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- ── Menu (compact row list) ──────────────────────────────── -->
<section class="bg-white py-14 px-4 sm:px-6 lg:px-8" aria-label="Menu">
  <div class="max-w-3xl mx-auto">
    <?php
    $has_any = false;
    if ( class_exists( 'WBB_Menu' ) ) :
        foreach ( WBB_Menu::CATEGORIES as $cat ) :
            $items = WBB_Menu::get_items( $cat, true );
            if ( empty( $items ) ) {
                continue;
            }
            $has_any      = true;
            $cat_label    = WBB_Menu::category_label( $cat );
            $cat_subtitle = $cat_subtitles[ $cat ] ?? '';
    ?>
    <div class="mb-10 last:mb-0">

      <div class="flex items-baseline justify-between gap-4 border-b-2 border-brand-cream pb-2 mb-3">
        <h2 id="cat-<?php echo esc_attr( $cat ); ?>-heading" class="section-heading text-2xl lg:text-3xl"><?php echo esc_html( $cat_label ); ?></h2>
        <?php if ( $cat_subtitle ) : ?>
        <span class="font-body text-sm text-gray-500"><?php echo esc_html( $cat_subtitle ); ?></span>
        <?php endif; ?>
      </div>

      <ul class="flex flex-col list-none m-0 p-0" role="list">
        <?php foreach ( $items as $item ) :
          $img_url = WBB_Menu::get_item_image_url( $item, 'thumbnail' );
        ?>
        <li class="flex items-center gap-4 py-3 border-b border-gray-100 last:border-0">
          <?php if ( $img_url ) : ?>
          <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>" class="w-14 h-14 rounded-xl object-cover flex-shrink-0 bg-brand-cream" loading="lazy" decoding="async" width="56" height="56">
          <?php else : ?>
          <span class="w-14 h-14 rounded-xl flex-shrink-0 bg-brand-cream" aria-hidden="true"></span>
          <?php endif; ?>

          <div class="flex-1 min-w-0">
            <h3 class="font-heading text-brand-navy uppercase text-base tracking-wide leading-tight"><?php echo esc_html( $item->title ); ?></h3>
            <?php if ( ! empty( $item->description ) ) : ?>
            <p class="font-body text-gray-600 text-sm leading-snug mt-0.5"><?php echo esc_html( $item->description ); ?></p>
            <?php endif; ?>
          </div>

          <?php if ( (float) $item->price > 0 ) : ?>
          <span class="font-heading text-brand-red text-base whitespace-nowrap"><?php echo esc_html( $currency . number_format( (float) $item->price, 2 ) ); ?></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>

    </div>
    <?php
        endforeach;
    endif;

    if ( ! $has_any ) :
    ?>
    <p class="font-body text-gray-600 text-base leading-relaxed text-center">
      Our food &amp; drink menu is on its way. In the meantime, BYO food is welcome and cold drinks are available on board. Call us on <?php echo esc_html( $phone ); ?> with any questions.
    </p>
    <?php endif; ?>
  </div>
</section>

<!-- ── CTA strip (condensed) ────────────────────────────────── -->
<section class="bg-brand-navy py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Call to action">
  <img src="<?php echo esc_url( $icon_dir ); ?>round-sailboat.png" alt="" width="180" height="180" class="absolute -bottom-4 -right-4 w-40 h-40 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-heading text-white uppercase text-2xl lg:text-3xl mb-3">Add It To Your Booking</h2>
    <p class="font-body text-blue-100 text-base mb-6">Pick your extras when you book — it'll be ready when you arrive.</p>
    <div class="flex flex-wrap items-center justify-center gap-4">
      <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-8 py-3">Book Now</a>
      <a href="<?php echo esc_attr( $tel_href ); ?>" class="btn-outline text-base px-8 py-3">Call <?php echo esc_html( $phone ); ?></a>
    </div>
  </div>
</section>

<?php get_footer();
