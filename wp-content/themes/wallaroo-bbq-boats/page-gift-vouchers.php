<?php
/**
 * Template Name: Gift Vouchers
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

$headline       = wbb_inner_field( 'gv_hero_headline',    'GIVE THEM A DAY ON THE WATER' );
$subheading     = wbb_inner_field( 'gv_hero_subheading',  'Gift vouchers for any occasion.' );
$voucher_tagline = wbb_inner_field( 'gv_voucher_tagline', 'Available in any amount. Redeemable online.' );

$logo = wallaroo_logo_html( [
    'class' => 'h-16 w-auto brightness-0 invert mx-auto mb-6',
    'alt'   => get_bloginfo( 'name' ),
] );

$how_steps = [
    [ 'number' => '01', 'heading' => wbb_inner_field( 'gv_step_1_heading', 'Choose your amount' ),      'body' => wbb_inner_field( 'gv_step_1_body', 'Any dollar value — you decide what works.' ) ],
    [ 'number' => '02', 'heading' => wbb_inner_field( 'gv_step_2_heading', 'We send you the voucher' ), 'body' => wbb_inner_field( 'gv_step_2_body', 'Digital voucher sent straight to your inbox.' ) ],
    [ 'number' => '03', 'heading' => wbb_inner_field( 'gv_step_3_heading', 'They book when ready' ),    'body' => wbb_inner_field( 'gv_step_3_body', 'Recipient books a session at a time that suits them.' ) ],
];
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="bg-brand-sky py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Page hero">
  <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/icons/message-in-a-bottle.png" alt="" width="220" height="220" class="absolute -bottom-4 -right-4 w-48 h-48 object-contain pointer-events-none select-none" style="opacity:0.07;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-white/80 mb-3">The Perfect Present</p>
    <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="font-body text-white/90 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>

<!-- ── Voucher card visual ──────────────────────────────────── -->
<section class="bg-gray-50 py-20 px-4 sm:px-6 lg:px-8" aria-label="Gift voucher">
  <div class="max-w-2xl mx-auto">

    <!-- Gift card -->
    <div class="relative bg-brand-navy rounded-3xl overflow-hidden shadow-card-hover p-10 text-center">

      <!-- Decorative wave top-right -->
      <div class="absolute -top-8 -right-8 w-48 h-48 opacity-10" aria-hidden="true">
        <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="100" cy="100" r="90" stroke="white" stroke-width="2"/>
          <circle cx="100" cy="100" r="65" stroke="white" stroke-width="2"/>
          <circle cx="100" cy="100" r="40" stroke="white" stroke-width="2"/>
        </svg>
      </div>

      <!-- Wave decoration bottom-left -->
      <div class="absolute bottom-4 left-4 opacity-10" aria-hidden="true">
        <svg viewBox="0 0 120 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-32">
          <path d="M0 10 Q15 2 30 10 Q45 18 60 10 Q75 2 90 10 Q105 18 120 10" stroke="white" stroke-width="2" fill="none"/>
          <path d="M0 16 Q15 8 30 16 Q45 24 60 16 Q75 8 90 16 Q105 24 120 16" stroke="white" stroke-width="1" fill="none"/>
        </svg>
      </div>

      <!-- Logo or text -->
      <?php if ( $logo ) : echo $logo; else : ?>
        <p class="font-heading text-white text-3xl uppercase tracking-wide mb-6">Wallaroo<br><span class="text-brand-sky">BBQ Boats</span></p>
      <?php endif; ?>

      <p class="font-heading text-brand-sky uppercase text-sm tracking-widest mb-4">Gift Voucher</p>
      <p class="font-heading text-white uppercase text-4xl lg:text-5xl mb-2">A Day on<br>the Water</p>
      <p class="font-body text-blue-200 text-sm mt-6"><?php echo esc_html( $voucher_tagline ); ?></p>

      <!-- Dashed divider -->
      <div class="my-6 border-t border-dashed border-white/20"></div>
      <p class="font-body text-blue-300 text-xs tracking-wider uppercase">Copper Cove Marina · Wallaroo SA</p>

    </div>

    <p class="font-body text-center text-gray-500 text-sm mt-6">
      Contact us to order — vouchers issued within one business day.
    </p>
  </div>
</section>

<!-- ── How to order ─────────────────────────────────────────── -->
<section class="bg-white py-16 px-4 sm:px-6 lg:px-8 border-t border-gray-100" aria-labelledby="how-order-heading">
  <div class="max-w-4xl mx-auto">
    <h2 id="how-order-heading" class="section-heading text-center text-2xl lg:text-3xl mb-10">How to Order</h2>
    <ol class="grid grid-cols-1 sm:grid-cols-3 gap-8 list-none m-0 p-0" role="list">
      <?php foreach ( $how_steps as $step ) : ?>
      <li class="flex flex-col items-center text-center gap-3">
        <span class="font-heading text-brand-red text-5xl leading-none"><?php echo esc_html( $step['number'] ); ?></span>
        <h3 class="font-heading text-brand-navy uppercase text-base"><?php echo esc_html( $step['heading'] ); ?></h3>
        <p class="font-body text-gray-600 text-sm leading-relaxed"><?php echo esc_html( $step['body'] ); ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ── Order contact card ───────────────────────────────────── -->
<section class="bg-gray-50 py-16 px-4 sm:px-6 lg:px-8" aria-label="Order a voucher">
  <div class="max-w-xl mx-auto text-center">
    <div class="bg-white rounded-3xl shadow-card p-8">
      <h2 class="font-heading text-brand-navy uppercase text-2xl mb-3">Order a Voucher</h2>
      <p class="font-body text-gray-600 text-sm mb-6 leading-relaxed">
        Call or email us to order. Tell us the amount and who it's for — we'll sort the rest.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?php echo esc_url( $tel_href ); ?>" class="btn-primary text-base px-8 py-4">
          Call <?php echo esc_html( $phone ); ?>
        </a>
        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="btn-outline-navy text-base px-8 py-4">Email Us</a>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA strip ─────────────────────────────────────────────── -->
<section class="bg-brand-navy py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-label="Call to action">
  <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/icons/treasure-map.png" alt="" width="200" height="200" class="absolute -bottom-4 -right-4 w-48 h-48 object-contain pointer-events-none select-none" style="opacity:0.06;filter:grayscale(1);" loading="lazy" aria-hidden="true">
  <div class="max-w-3xl mx-auto text-center">
    <h2 class="font-heading text-white uppercase text-3xl lg:text-4xl mb-4">The Gift That Gets Them Off the Couch</h2>
    <p class="font-body text-blue-100 text-lg mb-8">Give them something worth doing.</p>
    <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary text-base px-10 py-4">Book Now</a>
  </div>
</section>

<?php get_footer();
