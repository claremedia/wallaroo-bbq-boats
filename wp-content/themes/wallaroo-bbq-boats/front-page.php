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
$hero_subheading = wbb_field( 'hero_subheading', 'Bring your people. We\'ll sort the rest.' );
$hero_image      = wbb_field( 'hero_image',      [] );
$hero_image_url  = ! empty( $hero_image['url'] ) ? $hero_image['url'] : 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1600&q=80';
$hero_image_alt  = ! empty( $hero_image['alt'] ) ? $hero_image['alt'] : 'Two friends relaxing on a boat on calm blue water at Wallaroo Marina';

// --- Section images ---
$who_image     = wbb_field( 'who_image', [] );
$who_image_url = ! empty( $who_image['url'] ) ? $who_image['url'] : 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80';
$who_image_alt = ! empty( $who_image['alt'] ) ? $who_image['alt'] : 'Calm coastal water at sunset';

// --- Icon directory ---
$icon_dir = get_template_directory_uri() . '/assets/icons/';

// --- Trust strip (nautical PNG icons, labels editable via ACF) ---
$trust_items = [
    [ 'icon' => $icon_dir . 'boat-rudder.png',        'label' => wbb_field( 'trust_label_1', 'No licence needed' ) ],
    [ 'icon' => $icon_dir . 'round-sailboat.png',    'label' => wbb_field( 'trust_label_2', '2 to 6 people per boat' ) ],
    [ 'icon' => $icon_dir . 'fish-facing-right.png', 'label' => wbb_field( 'trust_label_3', 'BYO food welcome' ) ],
    [ 'icon' => $icon_dir . 'sailboat-anchor.png',   'label' => wbb_field( 'trust_label_4', 'Copper Cove Marina, Wallaroo' ) ],
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
  class="relative bg-gradient-to-b from-gray-50 to-white py-6 px-4 sm:px-6 lg:px-8 bg-wave-sky"
  aria-label="Hero"
>
  <!-- Decorative blob -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full" style="background:radial-gradient(circle,rgba(63,169,220,0.07) 0%,transparent 70%)"></div>
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
          class="absolute inset-0 w-full h-full object-cover"
        >
      </picture>

      <!-- Dark overlay gradient -->
      <div class="absolute inset-0 bg-gradient-to-r from-brand-navy/80 via-brand-navy/50 to-transparent" aria-hidden="true"></div>

      <!-- Hero text content — left side -->
      <div class="relative z-10 flex flex-col justify-end h-full p-8 lg:p-12 pb-14 lg:pb-16 max-w-xl">

        <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight text-shadow-hero mb-4">
          <?php echo esc_html( $hero_headline ); ?>
        </h1>

        <p class="font-body text-white/90 text-lg sm:text-xl mb-8 leading-relaxed">
          <?php echo esc_html( $hero_subheading ); ?>
        </p>

        <div class="flex flex-wrap gap-3">
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
      <a href="<?php echo esc_attr( $tel_href ); ?>" class="btn-primary w-full justify-center text-base py-4">
        Call to Book — <?php echo esc_html( $phone ); ?>
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
        <span class="w-8 h-8 flex-shrink-0" aria-hidden="true">
          <img src="<?php echo esc_url( $item['icon'] ); ?>" alt="" width="32" height="32" class="w-8 h-8 object-contain" loading="lazy">
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
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="absolute -top-4 -right-8 w-64 h-64 text-brand-navy pointer-events-none select-none" style="opacity:0.08;" aria-hidden="true">
    <circle cx="12" cy="12" r="10"/>
    <circle cx="12" cy="12" r="4"/>
    <line x1="12" y1="2" x2="12" y2="8"/>
    <line x1="12" y1="16" x2="12" y2="22"/>
    <line x1="2" y1="12" x2="8" y2="12"/>
    <line x1="16" y1="12" x2="22" y2="12"/>
  </svg>
  <div class="max-w-7xl mx-auto">

    <div class="text-center mb-14">
      <p class="section-subheading mb-3">Simple as that</p>
      <h2 id="how-it-works-heading" class="section-heading text-4xl lg:text-5xl">How It Works</h2>
    </div>

    <ol
      class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 list-none m-0 p-0"
      role="list"
    >
      <?php foreach ( $how_steps as $step ) : ?>
      <li class="card group">
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
      <h2 id="who-heading" class="section-heading text-4xl lg:text-5xl">Who It's For</h2>
    </div>

    <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 list-none m-0 p-0" role="list">

      <!-- Workmates -->
      <li class="bg-white rounded-3xl shadow-card p-8 flex flex-col items-start transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Workmates</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">The team day that actually gets people off their phones. Book one boat or several. Get out on the water and actually relax together.</p>
        </div>
      </li>

      <!-- Mates -->
      <li class="bg-white rounded-3xl shadow-card p-8 flex flex-col items-start transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Mates</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">Birthdays, bucks, hens, or just a Saturday that is not the pub. Bring the crew and book as many boats as you need.</p>
        </div>
      </li>

      <!-- Family -->
      <li class="bg-white rounded-3xl shadow-card p-8 flex flex-col items-start transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
        <div>
          <h3 class="font-heading text-brand-navy uppercase text-xl tracking-wide mb-2">Family</h3>
          <p class="font-body text-gray-600 text-sm leading-relaxed">Kids love it. So does everyone else. Easy to drive and genuinely good fun for a mixed group.</p>
        </div>
      </li>

      <!-- Visitors -->
      <li class="bg-white rounded-3xl shadow-card p-8 flex flex-col items-start transition-all duration-200 hover:-translate-y-1 hover:shadow-card-hover">
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
        <h2 id="aboard-heading" class="section-heading text-4xl lg:text-5xl mb-10">What's On Board</h2>

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
      <h2 id="testimonials-heading" class="section-heading text-4xl lg:text-5xl">What People Are Saying</h2>
    </div>

    <ul class="grid grid-cols-1 md:grid-cols-3 gap-6 list-none m-0 p-0" role="list">
      <?php foreach ( $testimonials as $t ) :
        $rating = isset( $t['rating'] ) ? intval( $t['rating'] ) : 5;
        $rating = max( 1, min( 5, $rating ) );
      ?>
      <li class="card flex flex-col gap-5">

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
    <img src="<?php echo esc_url( $icon_dir ); ?>round-sailboat.png" alt="" width="320" height="320" class="absolute -bottom-8 -right-8 w-72 h-72 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
    <img src="<?php echo esc_url( $icon_dir ); ?>old-lighthouse.png" alt="" width="180" height="180" class="absolute top-6 left-10 w-36 h-36 object-contain pointer-events-none select-none" style="opacity:0.05;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  </div>

  <div class="max-w-4xl mx-auto text-center relative z-10">
    <h2 id="cta-heading" class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-6">
      READY TO BRING YOUR PEOPLE?
    </h2>
    <p class="font-body text-blue-200 text-lg lg:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
      Book your BBQ boat session at Wallaroo Marina. One boat or several. Just a good time on the water with the people you want to be with.
    </p>
    <div class="flex flex-wrap items-center justify-center gap-4">
      <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-10 py-4">
        Book Now
      </a>
      <a href="<?php echo esc_attr( $tel_href ); ?>" class="btn-outline text-base px-10 py-4">
        Call <?php echo esc_html( $phone ); ?>
      </a>
    </div>
  </div>
</section>

<!-- =====================================================
     SECTION: FAQ (anchor only, placeholder)
     ===================================================== -->
<section
  class="bg-white py-20 px-4 sm:px-6 lg:px-8"
  aria-labelledby="faq-heading"
  id="faq"
>
  <div class="max-w-3xl mx-auto">

    <div class="text-center mb-14">
      <p class="section-subheading mb-3">Got questions?</p>
      <h2 id="faq-heading" class="section-heading text-4xl lg:text-5xl">FAQ</h2>
    </div>

    <?php
    $faqs = [
      [ 'q' => 'Do I need a boat licence?',        'a' => 'No. Anyone 18 or over can take the wheel. We show you the basics before you head out and you will have it sorted in five minutes.' ],
      [ 'q' => 'How many people can come?',        'a' => 'Between 2 and 6 people per boat. Got a bigger group? Book multiple boats and run them side by side. Get in touch and we will sort it out.' ],
      [ 'q' => 'Can we book more than one boat?',  'a' => 'Yes. If your group is larger than 6 we can run multiple boats at the same time. Works brilliantly for workplace days, Christmas parties, and anything where you want to split into teams. Get in touch to check availability.' ],
      [ 'q' => 'Can I bring my own food?',         'a' => 'Yes. BYO food is encouraged. Pack a cooler, bring the snags, sort yourselves out. We also have platter options available if you want us to handle the food. Ask when you book.' ],
      [ 'q' => 'Can I bring my own drinks?',       'a' => 'No BYO alcohol. Cold drinks are available to purchase on board. Non-alcoholic drinks are fine to bring.' ],
      [ 'q' => 'How long is a session?',           'a' => 'Session lengths and pricing are on the Book Now page. We recommend at least two hours for groups who want to make a proper afternoon of it.' ],
      [ 'q' => 'Where exactly are you located?',   'a' => 'Copper Cove Marina, Wallaroo SA. If you are heading toward the Coopers Alehouse you will see us on the way down. Full directions and a map are on the Find Us page.' ],
      [ 'q' => 'What if the weather is bad?',      'a' => 'Safety comes first. If conditions are not suitable we will contact you directly to reschedule or refund. We keep an eye on the forecast and will not send anyone out in unsafe conditions.' ],
      [ 'q' => 'Is it suitable for kids?',         'a' => 'Yes. Life jackets are provided in all sizes. Kids need to be supervised by an adult on board at all times.' ],
      [ 'q' => 'What should we bring?',            'a' => 'Food, sunscreen, and your people. Everything else is on board.' ],
    ];
    ?>

    <dl class="flex flex-col gap-4">
      <?php foreach ( $faqs as $i => $faq ) : ?>
      <div class="card">
        <dt>
          <button
            class="w-full flex items-center justify-between text-left gap-4 font-heading text-brand-navy uppercase text-base tracking-wide"
            aria-expanded="false"
            aria-controls="faq-answer-<?php echo esc_attr( $i ); ?>"
            data-faq-toggle
          >
            <span><?php echo esc_html( $faq['q'] ); ?></span>
            <span class="flex-shrink-0 w-5 h-5 text-brand-sky transition-transform duration-200" data-faq-icon aria-hidden="true">
              <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </span>
          </button>
        </dt>
        <dd
          id="faq-answer-<?php echo esc_attr( $i ); ?>"
          class="hidden pt-4 font-body text-gray-600 text-sm leading-relaxed border-t border-gray-100 mt-4"
        >
          <?php echo esc_html( $faq['a'] ); ?>
        </dd>
      </div>
      <?php endforeach; ?>
    </dl>

  </div>
</section>

<?php get_footer(); ?>
