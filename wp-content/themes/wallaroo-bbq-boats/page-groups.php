<?php
/**
 * Template Name: Groups & Workplaces
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
$booking_url = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
$icon_dir    = get_template_directory_uri() . '/assets/icons/';

$headline      = wbb_inner_field( 'grp_hero_headline',   'BRING YOUR PEOPLE' );
$subheading    = wbb_inner_field( 'grp_hero_subheading', 'Got a big group? Book multiple boats and make a proper day of it. 2 to 6 people per boat.' );
$hire_headline = wbb_inner_field( 'grp_hire_headline',   'THE MORE BOATS THE BETTER' );
$hire_body     = wbb_inner_field( 'grp_hire_body',       'Got more than 6? Book multiple boats and run them side by side. Works brilliantly for workplace days, Christmas parties, birthdays, and anything where you want to split into teams and have a crack at something together. Get in touch and we will sort the logistics.' );
$hero_image    = wbb_inner_field( 'grp_hero_image',      [] );
$hero_img_url  = ! empty( $hero_image['url'] ) ? $hero_image['url'] : '';
$hero_img_alt  = ! empty( $hero_image['alt'] ) ? $hero_image['alt'] : '';
$hire_image    = wbb_inner_field( 'grp_hire_image',      [] );
$hire_img_url  = ! empty( $hire_image['url'] ) ? $hire_image['url'] : '';
$hire_img_alt  = ! empty( $hire_image['alt'] ) ? $hire_image['alt'] : '';

$occasions = [
    [
        'icon'  => $icon_dir . 'big-compass.png',
        'label' => wbb_inner_field( 'grp_occasion_1_heading', 'Workmates' ),
        'body'  => wbb_inner_field( 'grp_occasion_1_body',    'The team day that actually works. Book one boat or several, split into groups and see who can cook the best snag. No agenda, no conference room, just your people on the water.' ),
    ],
    [
        'icon'  => $icon_dir . 'big-flag-waving.png',
        'label' => wbb_inner_field( 'grp_occasion_2_heading', 'Mates' ),
        'body'  => wbb_inner_field( 'grp_occasion_2_body',    'Birthdays, bucks, hens, or just a big Saturday. Book as many boats as your group needs and make a proper day of it.' ),
    ],
    [
        'icon'  => $icon_dir . 'little-submarine.png',
        'label' => wbb_inner_field( 'grp_occasion_3_heading', 'Family' ),
        'body'  => wbb_inner_field( 'grp_occasion_3_body',    'Kids love it, adults love it more. Easy to drive, stable on the water, and genuinely fun for a mixed group of any age.' ),
    ],
    [
        'icon'  => $icon_dir . 'big-binoculars.png',
        'label' => wbb_inner_field( 'grp_occasion_4_heading', 'Visitors' ),
        'body'  => wbb_inner_field( 'grp_occasion_4_body',    'If you are passing through the Copper Coast this is the thing to do. Wallaroo on the water. You will not forget it.' ),
    ],
];

$inclusions = [
    wbb_inner_field( 'grp_inclusion_1', 'Full private boat hire' ),
    wbb_inner_field( 'grp_inclusion_2', 'Gas BBQ set up and ready' ),
    wbb_inner_field( 'grp_inclusion_3', 'Plates, cutlery and cooking gear' ),
    wbb_inner_field( 'grp_inclusion_4', 'Safety briefing before departure' ),
    wbb_inner_field( 'grp_inclusion_5', 'Life jackets for all passengers' ),
    wbb_inner_field( 'grp_inclusion_6', 'Cold drinks available on board' ),
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
  <img src="<?php echo esc_url( $icon_dir ); ?>boat-rudder.png" alt="" width="260" height="260" class="absolute -bottom-6 -right-6 w-56 h-56 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="relative z-10 py-24 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center">
      <p class="section-subheading text-brand-sky mb-3">Private Hire</p>
      <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
        <?php echo esc_html( $headline ); ?>
      </h1>
      <p class="font-body text-blue-100 text-lg sm:text-xl leading-relaxed">
        <?php echo esc_html( $subheading ); ?>
      </p>
    </div>
  </div>
</section>
<?php else : ?>
<section class="bg-brand-navy py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Page hero">
  <img src="<?php echo esc_url( $icon_dir ); ?>boat-rudder.png" alt="" width="260" height="260" class="absolute -bottom-6 -right-6 w-56 h-56 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <img src="<?php echo esc_url( $icon_dir ); ?>sailboat-anchor.png" alt="" width="160" height="160" class="absolute top-6 left-8 w-32 h-32 object-contain pointer-events-none select-none" style="opacity:0.05;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-brand-sky mb-3">Private Hire</p>
    <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="font-body text-blue-100 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- ── Occasion tiles ───────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="occasions-heading">
  <div class="max-w-7xl mx-auto">

    <div class="text-center mb-12">
      <p class="section-subheading mb-3">Who comes aboard</p>
      <h2 id="occasions-heading" class="section-heading text-3xl lg:text-4xl">Perfect For Every Occasion</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ( $occasions as $occasion ) : ?>
      <div class="bg-gray-50 rounded-3xl p-7 flex flex-col gap-4 hover:shadow-card-hover transition-shadow duration-200">
        <div class="w-12 h-12 bg-brand-navy rounded-2xl flex items-center justify-center flex-shrink-0">
          <img src="<?php echo esc_url( $occasion['icon'] ); ?>" alt="" width="28" height="28" class="w-7 h-7 object-contain brightness-0 invert" loading="lazy" aria-hidden="true">
        </div>
        <h3 class="font-heading text-brand-navy uppercase text-xl"><?php echo esc_html( $occasion['label'] ); ?></h3>
        <p class="font-body text-gray-600 text-sm leading-relaxed"><?php echo esc_html( $occasion['body'] ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- ── Private hire callout ─────────────────────────────────── -->
<section class="bg-brand-cream py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="hire-heading">
  <div class="max-w-7xl mx-auto">

    <?php if ( $hire_img_url ) : ?>
    <!-- With image: photo left, copy + inclusions stacked right -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-start">

      <!-- Image -->
      <div class="relative rounded-3xl overflow-hidden shadow-card-hover aspect-[4/3]">
        <img
          src="<?php echo esc_url( $hire_img_url ); ?>"
          alt="<?php echo esc_attr( $hire_img_alt ); ?>"
          class="absolute inset-0 w-full h-full object-cover"
          loading="lazy" decoding="async" width="800" height="600"
        >
      </div>

      <!-- Copy + inclusions stacked -->
      <div class="flex flex-col gap-8">
        <div>
          <h2 id="hire-heading" class="section-heading text-3xl lg:text-4xl mb-6">
            <?php echo esc_html( $hire_headline ); ?>
          </h2>
          <p class="font-body text-gray-700 text-base lg:text-lg leading-relaxed">
            <?php echo esc_html( $hire_body ); ?>
          </p>
        </div>
        <div class="bg-white rounded-3xl p-8 shadow-card">
          <h3 class="font-heading text-brand-navy uppercase text-lg mb-6">What's Included</h3>
          <ul class="flex flex-col gap-3 list-none m-0 p-0" role="list">
            <?php foreach ( $inclusions as $item ) : ?>
            <li class="flex items-center gap-3 font-body text-gray-700 text-sm">
              <svg class="w-5 h-5 text-brand-sky flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <?php echo esc_html( $item ); ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

    </div>
    <?php else : ?>
    <!-- Without image: copy left, inclusions right -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-start">

      <!-- Left: copy -->
      <div>
        <h2 id="hire-heading" class="section-heading text-3xl lg:text-4xl mb-6">
          <?php echo esc_html( $hire_headline ); ?>
        </h2>
        <p class="font-body text-gray-700 text-base lg:text-lg leading-relaxed">
          <?php echo esc_html( $hire_body ); ?>
        </p>
      </div>

      <!-- Right: inclusions -->
      <div class="bg-white rounded-3xl p-8 shadow-card">
        <h3 class="font-heading text-brand-navy uppercase text-lg mb-6">What's Included</h3>
        <ul class="flex flex-col gap-3 list-none m-0 p-0" role="list">
          <?php foreach ( $inclusions as $item ) : ?>
          <li class="flex items-center gap-3 font-body text-gray-700 text-sm">
            <svg class="w-5 h-5 text-brand-sky flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <?php echo esc_html( $item ); ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
    <?php endif; ?>

  </div>
</section>

<!-- ── Pricing placeholder ──────────────────────────────────── -->
<section class="bg-white py-16 px-4 sm:px-6 lg:px-8" aria-label="Pricing">
  <div class="max-w-2xl mx-auto text-center">
    <div class="bg-gray-50 rounded-3xl p-8 shadow-card">
      <h2 class="font-heading text-brand-navy uppercase text-2xl mb-3">Group Pricing</h2>
      <p class="font-body text-gray-600 text-base mb-6">Group pricing for multiple boats available on request. Call us on <?php echo esc_html( $phone ); ?> or send us a message.</p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>" class="btn-primary text-base px-8 py-4">
          Call <?php echo esc_html( $phone ); ?>
        </a>
        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="btn-outline-navy text-base px-8 py-4">Email Us</a>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA strip ─────────────────────────────────────────────── -->
<section class="bg-brand-navy py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Call to action">
  <img src="<?php echo esc_url( $icon_dir ); ?>cargo-ship-front-view.png" alt="" width="200" height="200" class="absolute -bottom-4 -right-4 w-48 h-48 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-heading text-white uppercase text-3xl lg:text-4xl mb-4">Lock In Your Date</h2>
    <p class="font-body text-blue-100 text-lg mb-8">Private hire — no strangers, no surprises.</p>
    <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-10 py-4">Book Now</a>
  </div>
</section>

<?php get_footer();
