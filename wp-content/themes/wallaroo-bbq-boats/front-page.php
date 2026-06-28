<?php
/**
 * Homepage template — Wallaroo BBQ Boats
 * Sections: Hero · Trust Strip · How It Works · Who It's For ·
 *           What's On Board · Testimonials · CTA Banner
 */
get_header();

// ACF field helper with fallback
function wbb_field( $name, $fallback = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( $name );
        return ( $val !== false && $val !== '' && $val !== null ) ? $val : $fallback;
    }
    return $fallback;
}

// --- Site-wide settings ---
$phone       = wallaroo_option( 'phone' );
$tel_href    = 'tel:' . preg_replace( '/\s+/', '', $phone );
$booking_url = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );

// --- Hero ---
$hero_headline   = wbb_field( 'hero_headline',   'SELF-DRIVE BBQ BOATS. WALLAROO MARINA.' );
$hero_subheading = wbb_field( 'hero_subheading', 'Hire a boat, fire up the BBQ, and spend the day on the water.' );
$hero_image      = wbb_field( 'hero_image',      [] );
$hero_image_url  = ! empty( $hero_image['url'] ) ? $hero_image['url'] : 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1600&q=80';
$hero_image_alt  = ! empty( $hero_image['alt'] ) ? $hero_image['alt'] : 'Two friends relaxing on a boat on calm blue water at Wallaroo Marina';

// --- Section images ---
$who_image     = wbb_field( 'who_image', [] );
$who_image_url = ! empty( $who_image['url'] ) ? $who_image['url'] : 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80';
$who_image_alt = ! empty( $who_image['alt'] ) ? $who_image['alt'] : 'Calm coastal water at sunset';

// --- Icon directory ---
$icon_dir = get_template_directory_uri() . '/assets/icons/';

// --- Trust strip (Lucide inline SVG icons, labels editable via ACF) ---
$trust_items = [
    [ 'svg' => '<circle cx="12" cy="12" r="8"/><path d="M12 2v7.5"/><path d="m19 5-5.23 5.23"/><path d="M22 12h-7.5"/><path d="m19 19-5.23-5.23"/><path d="M12 14.5V22"/><path d="M10.23 13.77 5 19"/><path d="M9.5 12H2"/><path d="M10.23 10.23 5 5"/><circle cx="12" cy="12" r="2.5"/>', 'label' => wbb_field( 'trust_label_1', 'No licence needed' ) ],
    [ 'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>', 'label' => wbb_field( 'trust_label_2', '2 to 6 people per boat' ) ],
    [ 'svg' => '<path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>', 'label' => wbb_field( 'trust_label_3', 'BYO food welcome' ) ],
    [ 'svg' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>', 'label' => wbb_field( 'trust_label_4', 'Copper Cove Marina, Wallaroo' ) ],
    [ 'svg' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>', 'label' => wbb_field( 'trust_label_5', '100% locally owned & operated' ) ],
];

// --- How It Works ---
$how_steps = [
    [
        'step_number' => wbb_field( 'step_1_number',  '01' ),
        'heading'     => wbb_field( 'step_1_heading', 'BOOK ONLINE' ),
        'body'        => wbb_field( 'step_1_body',    'Pick your date and group size. Confirm your booking in a couple of minutes.' ),
    ],
    [
        'step_number' => wbb_field( 'step_2_number',  '02' ),
        'heading'     => wbb_field( 'step_2_heading', 'TURN UP AT THE MARINA' ),
        'body'        => wbb_field( 'step_2_body',    'Head down to Copper Cove Marina 15 minutes before your session. We will run you through the boat and fire up the BBQ.' ),
    ],
    [
        'step_number' => wbb_field( 'step_3_number',  '03' ),
        'heading'     => wbb_field( 'step_3_heading', 'SORT YOURSELVES OUT' ),
        'body'        => wbb_field( 'step_3_body',    'Cruise around, find a spot, cook up. The next few hours are yours.' ),
    ],
];

// --- Testimonials — sourced solely from the Reviews CPT ---
$review_query = new WP_Query( [
    'post_type'      => 'wbb_review',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
] );

$testimonials = [];
if ( $review_query->have_posts() ) {
    while ( $review_query->have_posts() ) {
        $review_query->the_post();
        $testimonials[] = [
            'quote'  => get_the_content(),
            'name'   => get_the_title(),
            'rating' => (int) ( function_exists( 'get_field' ) ? get_field( 'review_rating' ) : 5 ) ?: 5,
        ];
    }
    wp_reset_postdata();
}
?>

<!-- =====================================================
     SECTION 2: HERO
     Rounded container, not full-bleed. Floating booking card.
     ===================================================== -->
<section
  class="wbb-hero relative bg-gradient-to-b from-gray-50 to-white py-6 px-4 sm:px-6 lg:px-8 bg-wave-sky"
  aria-label="Hero"
>
  <!-- Decorative blob -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="wbb-hero__accent absolute -top-20 -right-20 w-96 h-96 rounded-full" style="background:radial-gradient(circle,rgba(63,169,220,0.07) 0%,transparent 70%)"></div>
    <div class="absolute top-1/2 -left-32 w-80 h-80 rounded-full" style="background:radial-gradient(circle,rgba(10,42,94,0.05) 0%,transparent 70%)"></div>
  </div>

  <div class="max-w-7xl mx-auto relative">

    <!-- Hero image container — rounded corners, contains everything -->
    <div class="relative rounded-3xl overflow-hidden bg-brand-navy" style="height:calc(100vh - 180px);min-height:520px;margin-bottom:20px;">

      <!-- Hero image (LCP — eager + high priority) -->
      <picture>
        <img
          src="<?php echo esc_url( $hero_image_url ); ?>"
          alt="<?php echo esc_attr( $hero_image_alt ); ?>"
          width="1600"
          height="900"
          loading="eager"
          fetchpriority="high"
          decoding="async"
          class="wbb-hero__bg absolute inset-0 w-full h-full object-cover"
        >
      </picture>

      <!-- Dark overlay gradient -->
      <div class="absolute inset-0 bg-gradient-to-r from-brand-navy/80 via-brand-navy/50 to-transparent" aria-hidden="true"></div>

      <!-- Hero text content — left side -->
      <div class="wbb-hero__content relative z-10 flex flex-col justify-end h-full p-8 lg:p-12 pb-14 lg:pb-16 max-w-xl">

        <h1 class="wbb-hero__title font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight text-shadow-hero mb-4">
          <?php echo esc_html( $hero_headline ); ?>
        </h1>

        <p class="wbb-hero__sub font-body text-white/90 text-lg sm:text-xl mb-8 leading-relaxed">
          <?php echo esc_html( $hero_subheading ); ?>
        </p>

        <div class="wbb-hero__cta flex flex-wrap gap-3">
          <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-8 py-4">Book Now</a>
          <a href="#whats-on-board" class="btn-outline text-base px-8 py-4">See What's On Board</a>
        </div>

        <!-- Scroll indicator -->
        <div class="flex items-center gap-2 mt-8 text-white/60 text-xs font-body uppercase tracking-widest" aria-hidden="true">
          <div class="w-8 h-px bg-white/40"></div>
          Copper Cove Marina · Wallaroo SA
        </div>

      </div><!-- /.hero text -->


    </div><!-- /.hero rounded container -->

    <!-- Mobile booking CTA (shows on small screens) -->
    <div class="md:hidden mt-4">
      <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary w-full justify-center text-base py-4">
        Book Now
      </a>
    </div>

  </div><!-- /.max-w container -->
</section>

<!-- =====================================================
     SECTION 3: TRUST STRIP
     Cream background, four inline icons + labels
     ===================================================== -->
<section
  class="bg-brand-cream py-8 px-4 sm:px-6 lg:px-8"
  aria-label="Key features"
>
  <div class="max-w-7xl mx-auto">
    <ul
      class="flex flex-wrap items-center justify-center gap-6 lg:gap-12 list-none m-0 p-0"
      role="list"
    >
      <?php foreach ( $trust_items as $item ) : ?>
      <li class="flex items-center gap-3">
        <span class="w-8 h-8 flex-shrink-0 text-brand-navy" aria-hidden="true">
          <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $item['svg']; ?></svg>
        </span>
        <span class="font-body font-semibold text-brand-navy text-sm lg:text-base">
          <?php echo esc_html( $item['label'] ); ?>
        </span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- =====================================================
     SECTION 4: HOW IT WORKS
     Three numbered step cards on white
     ===================================================== -->
<section
  class="bg-white py-20 px-4 sm:px-6 lg:px-8 bg-wave-navy relative overflow-hidden"
  aria-labelledby="how-it-works-heading"
>
  <!-- Decorative anchor (Lucide) -->
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="absolute -top-4 -right-8 w-64 h-64 text-brand-navy pointer-events-none select-none" style="opacity:0.08;" aria-hidden="true">
    <path d="M12 22V8"/>
    <path d="M5 12H2a10 10 0 0 0 20 0h-3"/>
    <circle cx="12" cy="5" r="3"/>
  </svg>
  <div class="max-w-7xl mx-auto">

    <div class="text-center mb-14">
      <p class="section-subheading mb-3">Simple as that</p>
      <h2 id="how-it-works-heading" class="wbb-section-title section-heading text-4xl lg:text-5xl">How It Works</h2>
    </div>

    <ol
      class="wbb-services grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 list-none m-0 p-0"
      role="list"
    >
      <?php foreach ( $how_steps as $step ) : ?>
      <li class="wbb-card card group">
        <span class="font-heading text-brand-sky text-6xl lg:text-7xl leading-none block mb-4" aria-hidden="true">
          <?php echo esc_html( $step['step_number'] ); ?>
        </span>
        <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-3">
          <?php echo esc_html( $step['heading'] ); ?>
        </h3>
        <p class="font-body text-gray-600 text-sm leading-relaxed">
          <?php echo esc_html( $step['body'] ); ?>
        </p>
      </li>
      <?php endforeach; ?>
    </ol>

  </div>
</section>

<!-- =====================================================
     SECTION 5: WHO IT'S FOR
     Four tiles in a grid, hover lift
     ===================================================== -->
<section
  class="bg-brand-cream py-20 px-4 sm:px-6 lg:px-8"
  aria-labelledby="who-heading"
  id="groups"
>
  <div class="max-w-7xl mx-auto">

    <div class="text-center mb-14">
      <p class="section-subheading mb-3">Anyone, really</p>
      <h2 id="who-heading" class="wbb-section-title section-heading text-4xl lg:text-5xl">Who It's For</h2>
    </div>

    <ul class="wbb-services grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 list-none m-0 p-0" role="list">

      <!-- Workmates -->
      <li class="wbb-card bg-white rounded-3xl shadow-card p-8 flex flex-col items-start">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Workmates</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">The team day that actually gets people off their phones. Book one boat or several. Get out on the water and actually relax together.</p>
        </div>
      </li>

      <!-- Mates -->
      <li class="wbb-card bg-white rounded-3xl shadow-card p-8 flex flex-col items-start">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Mates</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">Birthdays, bucks, hens, or just a Saturday that is not the pub. Bring the crew and book as many boats as you need.</p>
        </div>
      </li>

      <!-- Family -->
      <li class="wbb-card bg-white rounded-3xl shadow-card p-8 flex flex-col items-start">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Family</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">Kids love it. So does everyone else. Easy to drive and genuinely good fun for a mixed group.</p>
        </div>
      </li>

      <!-- Visitors -->
      <li class="wbb-card bg-white rounded-3xl shadow-card p-8 flex flex-col items-start">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Visitors</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">Coming through the Copper Coast? This is the thing to do. You will go home talking about it.</p>
        </div>
      </li>

    </ul>
  </div>
</section>

<!-- =====================================================
     SECTION 6: WHAT'S ON BOARD
     Two-column icon + text list, cream background
     ===================================================== -->
<section
  class="bg-white py-20 px-4 sm:px-6 lg:px-8 bg-wave-sky"
  aria-labelledby="aboard-heading"
  id="whats-on-board"
>
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

      <!-- Text side -->
      <div>
        <p class="section-subheading mb-3">Everything you need</p>
        <h2 id="aboard-heading" class="wbb-section-title section-heading text-4xl lg:text-5xl mb-10">What's On Board</h2>

        <ul class="flex flex-col gap-5 list-none m-0 p-0" role="list">

          <?php
          // What's On Board items are managed per-row via ACF on the homepage.
          // (ACF Free has no Repeater, so rows are fixed fields; blank rows skip.)
          $board_items = [];
          for ( $bi = 1; $bi <= 8; $bi++ ) {
            $text = wbb_field( 'board_item_' . $bi . '_text', '' );
            if ( $text === '' ) {
              continue;
            }
            $board_items[] = [
              'icon' => wbb_field( 'board_item_' . $bi . '_icon', [] ),
              'text' => $text,
            ];
          }
          foreach ( $board_items as $item ) :
            $icon_url = ! empty( $item['icon']['url'] ) ? $item['icon']['url'] : '';
            $icon_alt = ! empty( $item['icon']['alt'] ) ? $item['icon']['alt'] : '';
          ?>
          <li class="flex items-start gap-4">
            <span class="flex-shrink-0 w-10 h-10 rounded-xl bg-brand-sky/10 flex items-center justify-center text-brand-sky overflow-hidden" aria-hidden="true">
              <?php if ( $icon_url ) : ?>
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_alt ); ?>" class="w-6 h-6 object-contain" loading="lazy" decoding="async">
              <?php else : ?>
                <span class="w-2.5 h-2.5 rounded-full bg-brand-sky"></span>
              <?php endif; ?>
            </span>
            <span class="font-body text-gray-700 text-base leading-relaxed pt-2">
              <?php echo esc_html( $item['text'] ); ?>
            </span>
          </li>
          <?php endforeach; ?>

        </ul>

        <div class="mt-10">
          <a href="<?php echo esc_url( home_url( '/whats-on-board/' ) ); ?>" class="btn-outline-navy text-base px-8 py-4">
            See What's On Board
          </a>
        </div>
      </div>

      <!-- Visual side — decorative card stack -->
      <div class="relative hidden lg:block" aria-hidden="true">
        <div class="absolute inset-0 rounded-3xl bg-brand-cream -rotate-2 scale-95"></div>
        <div class="relative bg-brand-navy rounded-3xl overflow-hidden aspect-square shadow-card-hover">
          <picture>
            <img
              src="<?php echo esc_url( $who_image_url ); ?>"
              alt="<?php echo esc_attr( $who_image_alt ); ?>"
              width="800"
              height="800"
              loading="lazy"
              decoding="async"
              class="w-full h-full object-cover opacity-80"
            >
          </picture>
          <!-- Overlay badge -->
          <div class="absolute bottom-6 left-6 right-6 bg-white/95 rounded-2xl p-5 shadow-card">
            <p class="font-heading text-brand-navy uppercase text-lg leading-tight mb-1">No Licence.<br>No Experience.</p>
            <p class="font-body text-gray-600 text-sm">Just turn up and enjoy it.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php if ( ! empty( $testimonials ) ) : ?>
<!-- =====================================================
     SECTION 7: SOCIAL PROOF / TESTIMONIALS
     Three cards, white, cream background.
     Only renders when Reviews exist in the backend.
     ===================================================== -->
<section
  class="bg-brand-cream py-20 px-4 sm:px-6 lg:px-8"
  aria-labelledby="testimonials-heading"
>
  <div class="max-w-7xl mx-auto">

    <div class="text-center mb-14">
      <p class="section-subheading mb-3">From the people</p>
      <h2 id="testimonials-heading" class="wbb-section-title section-heading text-4xl lg:text-5xl">What People Are Saying</h2>
    </div>

    <ul class="wbb-testimonials grid grid-cols-1 md:grid-cols-3 gap-6 list-none m-0 p-0" role="list">
      <?php foreach ( $testimonials as $t ) :
        $rating = isset( $t['rating'] ) ? intval( $t['rating'] ) : 5;
        $rating = max( 1, min( 5, $rating ) );
      ?>
      <li class="wbb-testimonial card flex flex-col gap-5">

        <!-- Stars -->
        <div class="star-rating" aria-label="<?php echo esc_attr( $rating ); ?> out of 5 stars" role="img">
          <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
          <svg
            class="w-5 h-5 <?php echo $i <= $rating ? 'text-brand-red' : 'text-gray-200'; ?>"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <?php endfor; ?>
        </div>

        <blockquote class="font-body text-gray-700 text-sm leading-relaxed italic flex-grow">
          "<?php echo esc_html( $t['quote'] ); ?>"
        </blockquote>

        <footer class="flex items-center gap-3">
          <!-- Initials avatar -->
          <div class="w-9 h-9 rounded-full bg-brand-sky/20 text-brand-navy flex items-center justify-center font-heading text-sm flex-shrink-0" aria-hidden="true">
            <?php echo esc_html( mb_substr( $t['name'], 0, 1 ) ); ?>
          </div>
          <cite class="font-body text-xs font-semibold text-gray-700 not-italic">
            <?php echo esc_html( $t['name'] ); ?>
          </cite>
        </footer>

      </li>
      <?php endforeach; ?>
    </ul>

  </div>
</section>
<?php endif; ?>

<!-- =====================================================
     SECTION 8: CTA BANNER
     Full-width navy, white Anton headline, red button
     ===================================================== -->
<section
  class="bg-brand-navy py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden"
  aria-labelledby="cta-heading"
  id="gift-vouchers"
>
  <!-- Decorative blobs -->
  <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full" style="background:radial-gradient(circle,rgba(63,169,220,0.12) 0%,transparent 70%)"></div>
    <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full" style="background:radial-gradient(circle,rgba(63,169,220,0.08) 0%,transparent 70%)"></div>
    <!-- Wave SVG decoration -->
    <svg class="absolute bottom-0 left-0 right-0 w-full text-white opacity-5" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true">
      <path d="M0,40 C360,80 720,0 1080,40 C1260,60 1380,20 1440,40 L1440,80 L0,80 Z" fill="currentColor"/>
    </svg>
    <!-- Life buoy (inline SVG, faint white) -->
    <svg class="absolute -bottom-8 -right-8 w-72 h-72 text-white pointer-events-none select-none" style="opacity:0.07;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="10"/>
      <path d="m4.93 4.93 4.24 4.24"/>
      <path d="m14.83 9.17 4.24-4.24"/>
      <path d="m14.83 14.83 4.24 4.24"/>
      <path d="m9.17 14.83-4.24 4.24"/>
      <circle cx="12" cy="12" r="4"/>
    </svg>
    <img src="<?php echo esc_url( $icon_dir ); ?>old-lighthouse.png" alt="" width="180" height="180" class="absolute top-6 left-10 w-36 h-36 object-contain pointer-events-none select-none" style="opacity:0.05;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  </div>

  <div class="max-w-4xl mx-auto text-center relative z-10">
    <h2 id="cta-heading" class="wbb-section-title font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-6">
      READY TO GET ON THE WATER?
    </h2>
    <p class="font-body text-blue-200 text-lg lg:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
      Book your BBQ boat session at Wallaroo Marina. One boat or several. Locally owned and operated, right here on the Copper Coast.
    </p>
    <div class="flex flex-wrap items-center justify-center gap-4">
      <a href="<?php echo esc_url( $booking_url ); ?>" class="wbb-booking-cta btn-primary text-base px-10 py-4">
        Book Now
      </a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
