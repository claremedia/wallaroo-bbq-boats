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
        [ 'q' => 'Do I need a boat licence?',       'a' => 'No. Anyone 18 or over can take the wheel. We show you the basics before you head out and you will have it sorted in five minutes.' ],
        [ 'q' => 'How many people can come?',       'a' => 'Between 2 and 6 people per boat. Got a bigger group? Book multiple boats and run them side by side. Get in touch and we will sort it out.' ],
        [ 'q' => 'Can we book more than one boat?', 'a' => 'Yes. If your group is larger than 6 we can run multiple boats at the same time. Works brilliantly for workplace days, Christmas parties, and anything where you want to split into teams. Get in touch to check availability.' ],
        [ 'q' => 'Can I bring my own food?',        'a' => 'Yes. BYO food is encouraged. Pack a cooler, bring the snags, sort yourselves out. We also have platter options available if you want us to handle the food. Ask when you book.' ],
        [ 'q' => 'Can I bring my own drinks?',      'a' => 'No BYO alcohol. Cold drinks are available to purchase on board. Non-alcoholic drinks are fine to bring.' ],
        [ 'q' => 'How long is a session?',          'a' => 'Session lengths and pricing are on the Book Now page. We recommend at least two hours for groups who want to make a proper afternoon of it.' ],
        [ 'q' => 'Where exactly are you located?',  'a' => 'Copper Cove Marina, Wallaroo SA. If you are heading toward the Coopers Alehouse you will see us on the way down. Full directions and a map are on the Find Us page.' ],
        [ 'q' => 'What if the weather is bad?',     'a' => 'Safety comes first. If conditions are not suitable we will contact you directly to reschedule or refund. We keep an eye on the forecast and will not send anyone out in unsafe conditions.' ],
        [ 'q' => 'Is it suitable for kids?',        'a' => 'Yes. Life jackets are provided in all sizes. Kids need to be supervised by an adult on board at all times.' ],
        [ 'q' => 'What should we bring?',           'a' => 'Food, sunscreen, and your people. Everything else is on board.' ],
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
          class="hidden px-6 pb-6 font-body text-gray-600 text-sm leading-relaxed"
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
    <p class="font-body text-gray-600 text-base mb-6">We're pretty easy to reach. Give us a call or shoot us an email.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="<?php echo esc_url( $tel_href ); ?>" class="btn-primary text-base px-8 py-4">
        Call <?php echo esc_html( $phone ); ?>
      </a>
      <a href="mailto:<?php echo esc_attr( $email ); ?>" class="btn-outline-navy text-base px-8 py-4">
        <?php echo esc_html( $email ); ?>
      </a>
    </div>
  </div>
</section>

<?php get_footer();
