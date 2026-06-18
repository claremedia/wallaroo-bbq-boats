<?php
// Pull contact details from Site Settings (wallaroo_option returns fallback if empty)
$phone    = wallaroo_option( 'phone' );
$email    = wallaroo_option( 'email' );
$addr1    = wallaroo_option( 'address_line1' );
$addr2    = wallaroo_option( 'address_line2' );
$tagline  = wallaroo_option( 'footer_tagline' );
$facebook = wallaroo_option( 'facebook_url' );
$instagram = wallaroo_option( 'instagram_url' );

// tel: href — strip all spaces from display number
$tel_href = 'tel:' . preg_replace( '/\s+/', '', $phone );
?>
<!-- =====================================================
     SITE FOOTER — three columns, dark navy
     ===================================================== -->
<footer
  class="bg-brand-navy text-white"
  id="find-us"
  role="contentinfo"
>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-16">

      <!-- Column 1: Logo + tagline -->
      <div>
        <?php
        $logo = wallaroo_footer_logo_html( [
            'class' => 'h-24 w-auto mb-4',
            'alt'   => get_bloginfo( 'name' ),
        ] );
        if ( $logo ) {
            echo $logo;
        } else {
            ?>
            <p class="font-heading text-white text-2xl uppercase tracking-wide leading-tight mb-4">
              Wallaroo<br><span class="text-brand-red">BBQ Boats</span>
            </p>
            <?php
        }
        ?>
        <p class="font-body text-blue-200 text-sm leading-relaxed">
          <?php echo esc_html( $tagline ); ?>
        </p>

        <!-- Locally owned badge -->
        <p class="font-heading text-white text-xs uppercase tracking-widest mt-5">
          100% Locally Owned &amp; Operated
        </p>

        <?php if ( $facebook || $instagram ) : ?>
        <!-- Social links -->
        <div class="flex items-center gap-3 mt-5">
          <?php if ( $facebook ) : ?>
          <a href="<?php echo esc_url( $facebook ); ?>" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand-sky flex items-center justify-center text-white transition-colors" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Facebook">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
          </a>
          <?php endif; ?>
          <?php if ( $instagram ) : ?>
          <a href="<?php echo esc_url( $instagram ); ?>" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand-sky flex items-center justify-center text-white transition-colors" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Instagram">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
            </svg>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Decorative wave line -->
        <div class="mt-6">
          <svg viewBox="0 0 120 12" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-24 h-3 text-brand-sky opacity-50" aria-hidden="true">
            <path d="M0 6 Q15 0 30 6 Q45 12 60 6 Q75 0 90 6 Q105 12 120 6" stroke="currentColor" stroke-width="2" fill="none"/>
          </svg>
        </div>
      </div>

      <!-- Column 2: Nav links -->
      <div>
        <h2 class="font-heading text-white text-sm uppercase tracking-widest mb-5 opacity-60">Navigation</h2>
        <nav aria-label="Footer navigation">
          <?php wp_nav_menu( [
              'theme_location' => 'footer',
              'container'      => false,
              'items_wrap'     => '<ul class="flex flex-col gap-2 list-none m-0 p-0" role="list">%3$s</ul>',
              'walker'         => new Wallaroo_Nav_Walker(),
              'link_class'     => 'font-body text-sm text-blue-100 hover:text-white transition-colors',
              'depth'          => 1,
              'fallback_cb'    => 'wallaroo_footer_nav_fallback',
          ] ); ?>
        </nav>
      </div>

      <!-- Column 3: Contact -->
      <div>
        <h2 class="font-heading text-white text-sm uppercase tracking-widest mb-5 opacity-60">Get In Touch</h2>
        <ul class="flex flex-col gap-3 list-none m-0 p-0 font-body text-sm" role="list">

          <!-- Phone -->
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-brand-sky flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 010 1.22 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/>
            </svg>
            <div>
              <span class="text-blue-200 block text-xs uppercase tracking-wider mb-0.5">Phone</span>
              <a href="<?php echo esc_attr( $tel_href ); ?>" class="text-white hover:text-brand-sky transition-colors">
                <?php echo esc_html( $phone ); ?>
              </a>
            </div>
          </li>

          <!-- Email -->
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-brand-sky flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <div>
              <span class="text-blue-200 block text-xs uppercase tracking-wider mb-0.5">Email</span>
              <a href="mailto:<?php echo esc_attr( $email ); ?>" class="text-white hover:text-brand-sky transition-colors">
                <?php echo esc_html( $email ); ?>
              </a>
            </div>
          </li>

          <!-- Location -->
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-brand-sky flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <div>
              <span class="text-blue-200 block text-xs uppercase tracking-wider mb-0.5">Location</span>
              <span class="text-white">
                <?php echo esc_html( $addr1 ); ?><br>
                <?php echo esc_html( $addr2 ); ?>
              </span>
            </div>
          </li>

        </ul>
      </div>

    </div><!-- /.grid -->

    <!-- Bottom bar -->
    <div class="mt-12 pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
      <p class="font-body text-xs text-blue-300">
        &copy; <?php echo esc_html( date( 'Y' ) ); ?> Wallaroo BBQ Boats. All rights reserved.
      </p>
      <p class="font-body text-xs text-blue-300">
        <?php echo esc_html( $addr1 . ', ' . $addr2 ); ?>
      </p>
    </div>

  </div><!-- /.container -->
</footer>

<?php wp_footer(); ?>
</body>
</html>
