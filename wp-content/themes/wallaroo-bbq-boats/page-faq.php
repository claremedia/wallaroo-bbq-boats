<?php
/**
 * Template Name: FAQ
 */
get_header();

function wbb_inner_field( $name, $fallback = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( $name );
        return ( $val !== false && $val !== '' && $val !== null ) ? $val : $fallback;
    }
    return $fallback;
}

$phone    = wallaroo_option( 'phone' );
$email    = wallaroo_option( 'email' );
$tel_href = 'tel:' . preg_replace( '/\s+/', '', $phone );

$headline   = wbb_inner_field( 'faq_hero_headline',   'QUESTIONS ANSWERED' );
$subheading = wbb_inner_field( 'faq_hero_subheading', 'Everything you need to know before you head down the marina.' );

// Query FAQ CPT — falls back to hardcoded if no items exist yet
$faq_query = new WP_Query( [
    'post_type'      => 'wbb_faq',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
] );

if ( $faq_query->have_posts() ) {
    $faqs = [];
    while ( $faq_query->have_posts() ) {
        $faq_query->the_post();
        $faqs[] = [
            'q' => get_the_title(),
            'a' => get_the_content(),
        ];
    }
    wp_reset_postdata();
} else {
    $faqs = [
        [ 'q' => 'Can we take a dip in the marina while we are hiring a BBQ boat?', 'a' => "No, that's not advisable. Many boats use the marina, so safety is paramount — and swimming will also bring water into the BBQ boat." ],
        [ 'q' => 'Can we anchor in the marina?', 'a' => "No. The Copper Cove Marina authority doesn't allow anchoring or fishing from a vessel in the marina. Of course, if there is an emergency or you're having issues with a BBQ boat, there is an anchor on each vessel to deploy for safety reasons." ],
        [ 'q' => 'Who can hire a BBQ boat?', 'a' => 'Anyone who holds a full car driver\'s licence and is at least 21 years of age. No boat licence is required.' ],
        [ 'q' => 'Can the nominated operator drink alcohol while operating the boat?', 'a' => 'Yes, but remember — as for every person operating a boat — the limit is under 0.05. Police or Marine Safety may conduct tests if they choose to.' ],
        [ 'q' => 'What about bad weather on the hiring day?', 'a' => 'If Wallaroo BBQ Boats management decides it is too windy, the temperature is over 40 degrees, or it may be unsafe to operate for any reason, the trip may be cancelled. Alternative hiring times or days can be rescheduled, or a refund of hiring costs made. We want you to have a great time, but your safety comes first.' ],
        [ 'q' => 'Can we take the BBQ boats outside the marina entrance?', 'a' => 'No. That may be dangerous, and the BBQ boats are licensed to operate within the safe marina waterways only. Numerous other boats are coming and going through the entrance. See the map on board each vessel for exclusion zones.' ],
        [ 'q' => 'Is there a toilet on board the BBQ boat?', 'a' => 'No. Please use the toilets adjacent to the commencement area before you begin your trip. Do not use the marina as a toilet.' ],
        [ 'q' => "What if we need more drinks or assistance when we're on the water?", 'a' => 'Contact the operator on 0416 106 041 and every effort will be made, where possible, to meet your needs.' ],
        [ 'q' => 'Do I need to wear a life jacket on board?', 'a' => 'No, unless you are under 12 years of age. A life jacket is stowed on board for every passenger if needed, and a life buoy is also fitted to each boat.' ],
    ];
}
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="bg-brand-cream py-20 px-4 sm:px-6 lg:px-8" aria-label="Page hero">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading mb-3">Got a question?</p>
    <h1 class="font-heading text-brand-navy uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="font-body text-gray-600 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>

<!-- ── FAQ Accordion ─────────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="faq-heading">
  <div class="max-w-3xl mx-auto">

    <h2 id="faq-heading" class="sr-only">Frequently Asked Questions</h2>

    <dl class="flex flex-col gap-3" role="list">
      <?php foreach ( $faqs as $i => $faq ) :
        $faq_id     = 'faq-answer-' . $i;
        $trigger_id = 'faq-trigger-' . $i;
      ?>
      <div
        class="border border-gray-200 rounded-2xl overflow-hidden"
        data-faq-item
      >
        <dt>
          <button
            id="<?php echo esc_attr( $trigger_id ); ?>"
            class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left font-body font-semibold text-gray-800 text-base hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-sky"
            aria-expanded="false"
            aria-controls="<?php echo esc_attr( $faq_id ); ?>"
            data-faq-toggle
          >
            <?php echo esc_html( $faq['q'] ); ?>
            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center text-brand-navy transition-transform duration-200" data-faq-icon aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </span>
          </button>
        </dt>
        <dd
          id="<?php echo esc_attr( $faq_id ); ?>"
          class="hidden px-6 pt-2 pb-6 font-body text-gray-600 text-sm leading-relaxed"
          role="region"
          aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"
        >
          <?php echo esc_html( $faq['a'] ); ?>
        </dd>
      </div>
      <?php endforeach; ?>
    </dl>

  </div>
</section>

<!-- ── Still got questions? ─────────────────────────────────── -->
<section class="bg-brand-cream py-16 px-4 sm:px-6 lg:px-8" aria-label="Contact">
  <div class="max-w-2xl mx-auto text-center">
    <h2 class="font-heading text-brand-navy uppercase text-2xl lg:text-3xl mb-4">Still Got Questions?</h2>
    <p class="font-body text-gray-600 text-base mb-6">We're pretty easy to reach. Shoot us an email and we'll get back to you.</p>
    <div class="flex justify-center">
      <a href="<?php echo esc_url( home_url( '/find-us/' ) ); ?>" class="btn-primary text-base px-8 py-4">
        Contact Us
      </a>
    </div>
  </div>
</section>

<?php get_footer();
