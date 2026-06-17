<?php
/**
 * Template Name: Find Us
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
$addr1    = wallaroo_option( 'address_line1' );
$addr2    = wallaroo_option( 'address_line2' );
$tel_href = 'tel:' . preg_replace( '/\s+/', '', $phone );

$headline   = wbb_inner_field( 'fu_hero_headline',           'FIND US DOWN THE MARINA' );
$subheading = wbb_inner_field( 'fu_hero_subheading',         'Copper Cove Marina, Wallaroo SA.' );
$dir_kadina = wbb_inner_field( 'fu_directions_kadina',       'Head north on the Copper Coast Highway toward Wallaroo. Follow signs to the marina.' );
$dir_adel   = wbb_inner_field( 'fu_directions_adelaide',     'Take the Yorke Highway north. Turn off at Kadina and follow signs to Wallaroo marina.' );
$dir_cc     = wbb_inner_field( 'fu_directions_copper_coast', 'Head into Wallaroo town centre and follow the signs down to Copper Cove Marina.' );
$open_times = wbb_inner_field( 'fu_opening_times',           "Monday – Friday: by appointment\nSaturday – Sunday: 8am – 6pm\nPublic Holidays: 9am – 5pm" );
$parking    = wbb_inner_field( 'fu_parking_note',            'Free parking is available at the marina. Look for the Copper Cove Marina signs.' );

$directions = [
    [ 'from' => 'From Kadina',       'copy' => $dir_kadina ],
    [ 'from' => 'From Adelaide',     'copy' => $dir_adel   ],
    [ 'from' => 'From Copper Coast', 'copy' => $dir_cc     ],
];
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="bg-brand-navy py-20 px-4 sm:px-6 lg:px-8" aria-label="Page hero">
  <div class="max-w-3xl mx-auto text-center">
    <p class="section-subheading text-brand-sky mb-3">Wallaroo SA</p>
    <h1 class="font-heading text-white uppercase text-4xl sm:text-5xl lg:text-6xl leading-tight mb-5">
      <?php echo esc_html( $headline ); ?>
    </h1>
    <p class="font-body text-blue-100 text-lg sm:text-xl leading-relaxed">
      <?php echo esc_html( $subheading ); ?>
    </p>
  </div>
</section>

<!-- ── Address + Map ─────────────────────────────────────────── -->
<section class="bg-white py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="location-heading">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

      <!-- Left: contact details -->
      <div>
        <h2 id="location-heading" class="section-heading text-3xl lg:text-4xl mb-8">Where to Find Us</h2>

        <ul class="flex flex-col gap-6 list-none m-0 p-0 mb-8" role="list">

          <!-- Address -->
          <li class="flex items-start gap-4">
            <div class="w-10 h-10 bg-brand-cream rounded-xl flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
            </div>
            <div>
              <p class="font-body font-semibold text-gray-800 mb-0.5">Address</p>
              <p class="font-body text-gray-600 text-sm"><?php echo esc_html( $addr1 ); ?><br><?php echo esc_html( $addr2 ); ?></p>
            </div>
          </li>

          <!-- Phone -->
          <li class="flex items-start gap-4">
            <div class="w-10 h-10 bg-brand-cream rounded-xl flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 010 1.22 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/>
              </svg>
            </div>
            <div>
              <p class="font-body font-semibold text-gray-800 mb-0.5">Phone</p>
              <a href="<?php echo esc_url( $tel_href ); ?>" class="font-body text-brand-navy hover:text-brand-red transition-colors text-sm"><?php echo esc_html( $phone ); ?></a>
            </div>
          </li>

          <!-- Email -->
          <li class="flex items-start gap-4">
            <div class="w-10 h-10 bg-brand-cream rounded-xl flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
            </div>
            <div>
              <p class="font-body font-semibold text-gray-800 mb-0.5">Email</p>
              <a href="mailto:<?php echo esc_attr( $email ); ?>" class="font-body text-brand-navy hover:text-brand-red transition-colors text-sm"><?php echo esc_html( $email ); ?></a>
            </div>
          </li>

        </ul>

        <p class="font-body text-gray-600 text-sm leading-relaxed">
          We're at Copper Cove Marina, Wallaroo. If you're heading toward the Coopers Alehouse, you'll see us on the way down.
        </p>

      </div>

      <!-- Right: Google Map -->
      <div class="rounded-3xl overflow-hidden shadow-card-hover" style="height:420px;">
        <iframe
          src="https://maps.google.com/maps?q=Copper+Cove+Marina,+Wallaroo,+SA+5556,+Australia&output=embed&z=16"
          width="100%"
          height="100%"
          style="border:0;"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="Copper Cove Marina, Wallaroo SA"
        ></iframe>
      </div>

    </div>
  </div>
</section>

<!-- ── Directions ────────────────────────────────────────────── -->
<section class="bg-gray-50 py-16 px-4 sm:px-6 lg:px-8" aria-labelledby="directions-heading">
  <div class="max-w-7xl mx-auto">
    <h2 id="directions-heading" class="section-heading text-center text-2xl lg:text-3xl mb-10">Getting Here</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <?php foreach ( $directions as $dir ) : ?>
      <div class="bg-white rounded-3xl p-7 shadow-card">
        <div class="w-10 h-10 bg-brand-navy rounded-xl flex items-center justify-center mb-4">
          <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
          </svg>
        </div>
        <h3 class="font-heading text-brand-navy uppercase text-base mb-3"><?php echo esc_html( $dir['from'] ); ?></h3>
        <p class="font-body text-gray-600 text-sm leading-relaxed"><?php echo esc_html( $dir['copy'] ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Parking + Opening Times ──────────────────────────────── -->
<section class="bg-white py-16 px-4 sm:px-6 lg:px-8" aria-label="Parking and hours">
  <div class="max-w-4xl mx-auto grid grid-cols-1 sm:grid-cols-2 gap-6">

    <!-- Parking -->
    <div class="bg-brand-cream rounded-3xl p-7">
      <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center mb-4 shadow-card">
        <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="1" y="3" width="15" height="13" rx="1"/>
          <path d="M16 8h5l3 3v5h-8V8z"/>
          <circle cx="5.5" cy="18.5" r="2.5"/>
          <circle cx="18.5" cy="18.5" r="2.5"/>
        </svg>
      </div>
      <h3 class="font-heading text-brand-navy uppercase text-base mb-3">Parking</h3>
      <p class="font-body text-gray-700 text-sm leading-relaxed"><?php echo esc_html( $parking ); ?></p>
    </div>

    <!-- Opening times -->
    <div class="bg-brand-cream rounded-3xl p-7">
      <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center mb-4 shadow-card">
        <svg class="w-5 h-5 text-brand-navy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <h3 class="font-heading text-brand-navy uppercase text-base mb-3">Opening Times</h3>
      <div class="font-body text-gray-700 text-sm leading-relaxed">
        <?php
        $lines = explode( "\n", $open_times );
        foreach ( $lines as $line ) {
            echo '<p>' . esc_html( trim( $line ) ) . '</p>';
        }
        ?>
      </div>
    </div>

  </div>
</section>

<?php get_footer();
