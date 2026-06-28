<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="profile" href="https://gmpg.org/xfn/11">
  <?php // Enable motion start-states before first paint, but ONLY when JS runs and the
        // visitor hasn't asked to reduce motion. If this fails, motion is off, OR the
        // deferred init never runs, the class is removed and every wbb- element stays
        // fully visible (no FOUC, no stuck opacity:0). ?>
  <script>try{if(!matchMedia('(prefers-reduced-motion: reduce)').matches){var d=document.documentElement;d.classList.add('wbb-motion-ready');setTimeout(function(){if(!window.__wbbMotionReady){d.classList.remove('wbb-motion-ready');}},3000);}}catch(e){}</script>
  <?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-white text-gray-800 font-body' ); ?>>
<?php wp_body_open(); ?>

<?php
// Resolve booking URL — use settings value or fall back to on-page anchor
$booking_url = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
?>

<!-- =====================================================
     SITE HEADER — sticky, pill nav, red CTA
     ===================================================== -->
<header
  id="site-header"
  class="wbb-header fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-sm"
  role="banner"
>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20 lg:h-24">

      <!-- Logo -->
      <a
        href="<?php echo esc_url( home_url( '/' ) ); ?>"
        class="flex-shrink-0 flex items-center no-underline"
        aria-label="<?php bloginfo( 'name' ); ?> — Home"
      >
        <?php
        $logo = wallaroo_logo_html( [
            'class' => 'h-20 lg:h-24 w-auto',
            'alt'   => get_bloginfo( 'name' ),
        ] );
        if ( $logo ) {
            echo $logo;
        } else {
            ?>
            <span class="font-heading text-brand-navy text-lg lg:text-xl uppercase tracking-wide leading-none">
              Wallaroo<br class="hidden sm:block">
              <span class="text-brand-red">BBQ Boats</span>
            </span>
            <?php
        }
        ?>
      </a>

      <!-- Primary Nav — pill shape, desktop -->
      <nav
        class="hidden lg:flex items-center"
        aria-label="Primary navigation"
      >
        <?php wp_nav_menu( [
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '<ul class="list-none m-0 flex items-center gap-0.5 px-3 py-2 rounded-2xl border border-gray-200 bg-white" role="list">%3$s</ul>',
            'walker'         => new Wallaroo_Nav_Walker(),
            'link_class'     => 'nav-link',
            'show_icons'     => true,
            'depth'          => 1,
            'fallback_cb'    => 'wallaroo_nav_fallback',
        ] ); ?>
      </nav>

      <!-- Right-side CTA + mobile hamburger -->
      <div class="flex items-center gap-3">
        <a
          href="<?php echo esc_url( $booking_url ); ?>"
          class="btn-primary hidden sm:inline-flex text-sm"
          aria-label="Book a BBQ boat now"
        >
          Book Now
        </a>

        <!-- Mobile menu button -->
        <button
          id="mobile-menu-toggle"
          class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl text-brand-navy hover:bg-gray-100 transition-colors"
          aria-expanded="false"
          aria-controls="mobile-menu"
          aria-label="Open navigation menu"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
      </div>

    </div><!-- /.flex header row -->
  </div><!-- /.max-w container -->

  <!-- Mobile menu -->
  <div
    id="mobile-menu"
    class="lg:hidden hidden bg-white border-t border-gray-100 shadow-card"
    aria-label="Mobile navigation"
  >
    <nav class="max-w-7xl mx-auto px-4 py-4">
      <?php wp_nav_menu( [
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '<ul class="flex flex-col gap-1 list-none m-0 p-0" role="list">%3$s</ul>',
          'walker'         => new Wallaroo_Nav_Walker(),
          'link_class'     => 'flex items-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-brand-cream hover:text-brand-navy transition-colors',
          'show_icons'     => true,
          'depth'          => 1,
          'fallback_cb'    => 'wallaroo_nav_fallback',
      ] ); ?>
      <div class="mt-2 pt-2 border-t border-gray-100">
        <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-primary w-full justify-center">Book Now</a>
      </div>
    </nav>
  </div>

</header>

<!-- Spacer to prevent content sliding under fixed header -->
<div class="h-20 lg:h-24" aria-hidden="true"></div>
