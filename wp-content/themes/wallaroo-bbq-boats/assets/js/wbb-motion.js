/**
 * Wallaroo BBQ Boats — motion layer
 * ---------------------------------------------------------------
 * Scroll-DRIVEN motion. Progress is computed every frame from each
 * element's real position in the viewport (getBoundingClientRect),
 * so motion is genuinely tied to the scrollbar and reverses on the
 * way back up. No timeline guesswork.
 *
 *   • Hero: entrance fade on load (GSAP), then a cinematic scroll
 *     exit — background zooms while the content lifts + fades.
 *   • Sections / cards / testimonials: scrub up into place as they
 *     travel through the viewport, each by its own position → an
 *     organic cascade, not a fixed stagger.
 *   • Booking CTA: the boldest scrub (most travel + scale pop).
 *
 * Fail-safe: under reduced motion we do nothing and strip
 * .wbb-motion-ready (all content fully visible). Transform/opacity
 * only. Heavy hero parallax is desktop + fine-pointer.
 */
( function () {
  'use strict';

  var root = document.documentElement;

  // 1. Respect reduced motion — leave everything visible, do nothing.
  if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
    root.classList.remove( 'wbb-motion-ready' );
    return;
  }

  // GSAP powers the hero entrance fade; the scroll engine below is
  // self-contained vanilla JS and needs no library.
  var gsap = window.gsap;
  var hasGsap = gsap && typeof gsap.fromTo === 'function';

  window.__wbbMotionReady = true;
  root.classList.add( 'wbb-motion-ready' );

  var canParallax =
    ! window.matchMedia( '(pointer: coarse)' ).matches &&
    window.matchMedia( '(min-width: 768px)' ).matches;

  function clamp( v, lo, hi ) { return v < lo ? lo : ( v > hi ? hi : v ); }
  function easeOut( t ) { return 1 - Math.pow( 1 - t, 3 ); }
  function toArray( n ) { return Array.prototype.slice.call( n ); }

  // ── HERO entrance (timed, on load) ────────────────────────────
  var heroTitle = document.querySelector( '.wbb-hero__title' );
  var heroSub   = document.querySelector( '.wbb-hero__sub' );
  var heroCta   = document.querySelector( '.wbb-hero__cta' );
  if ( hasGsap ) {
    if ( heroTitle ) { gsap.fromTo( heroTitle, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' } ); }
    if ( heroSub )   { gsap.fromTo( heroSub,   { opacity: 0, y: 22 }, { opacity: 1, y: 0, duration: 0.9, delay: 0.12, ease: 'power3.out' } ); }
    if ( heroCta )   { gsap.fromTo( heroCta,   { opacity: 0, y: 18 }, { opacity: 1, y: 0, duration: 0.8, delay: 0.24, ease: 'power3.out' } ); }
  } else {
    [ heroTitle, heroSub, heroCta ].forEach( function ( el ) { if ( el ) { el.style.opacity = '1'; } } );
  }

  // ── Scroll engine ─────────────────────────────────────────────
  var hero        = document.querySelector( '.wbb-hero' );
  var heroBg      = document.querySelector( '.wbb-hero__bg' );
  var heroContent = document.querySelector( '.wbb-hero__content' );

  // Each scrubber maps the element's viewport position → 0..1 progress.
  // p = 0 when its top is `enter` down the viewport; p = 1 when its top
  // reaches `finish` (both as a fraction of viewport height).
  var scrubbers = [];
  function register( el, opts ) {
    opts = opts || {};
    scrubbers.push( {
      el:     el,
      y:      opts.y == null ? 44 : opts.y,
      scale:  opts.scale || 0,
      enter:  opts.enter == null ? 0.90 : opts.enter,
      finish: opts.finish == null ? 0.45 : opts.finish
    } );
  }

  toArray( document.querySelectorAll( '.wbb-section-title' ) ).forEach( function ( el ) { register( el, { y: 36 } ); } );
  toArray( document.querySelectorAll( '.wbb-card' ) ).forEach( function ( el ) { register( el, { y: 52, scale: 0.96 } ); } );
  toArray( document.querySelectorAll( '.wbb-testimonial' ) ).forEach( function ( el ) { register( el, { y: 52, scale: 0.96 } ); } );
  var cta = document.querySelector( '.wbb-booking-cta' );
  if ( cta ) { register( cta, { y: 64, scale: 0.90, finish: 0.55 } ); }

  var ticking = false;
  function onScroll() {
    if ( ! ticking ) { ticking = true; window.requestAnimationFrame( update ); }
  }

  function update() {
    ticking = false;
    var vh = window.innerHeight || document.documentElement.clientHeight;

    // Hero cinematic exit — desktop / fine-pointer only.
    if ( hero && canParallax ) {
      var r  = hero.getBoundingClientRect();
      var hp = clamp( -r.top / ( r.height || 1 ), 0, 1 );
      if ( heroBg ) {
        heroBg.style.transform = 'scale(' + ( 1 + 0.14 * hp ).toFixed( 4 ) + ')';
      }
      if ( heroContent ) {
        heroContent.style.transform = 'translate3d(0,' + ( -90 * hp ).toFixed( 2 ) + 'px,0)';
        heroContent.style.opacity   = ( 1 - hp ).toFixed( 3 );
      }
    }

    // Scrubbed reveals.
    for ( var i = 0; i < scrubbers.length; i++ ) {
      var s   = scrubbers[ i ];
      var top = s.el.getBoundingClientRect().top;
      var p   = clamp( ( s.enter * vh - top ) / ( ( s.enter - s.finish ) * vh ), 0, 1 );
      p = easeOut( p );
      s.el.style.opacity = p.toFixed( 3 );
      var tr = 'translate3d(0,' + ( s.y * ( 1 - p ) ).toFixed( 2 ) + 'px,0)';
      if ( s.scale ) { tr += ' scale(' + ( s.scale + ( 1 - s.scale ) * p ).toFixed( 4 ) + ')'; }
      s.el.style.transform = tr;
    }
  }

  window.addEventListener( 'scroll', onScroll, { passive: true } );
  window.addEventListener( 'resize', onScroll, { passive: true } );
  update(); // set initial positions before any scroll

} )();
