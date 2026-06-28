/**
 * Wallaroo BBQ Boats — cursor perspective tilt (GSAP)
 * ---------------------------------------------------------------
 * The home-page hero card and its title lean in 3D toward the
 * cursor. Cursor position is mapped to rotation with
 * gsap.utils.interpolate(); the values are pushed through
 * gsap.quickTo() setters so high-frequency pointer moves stay
 * smooth without spawning a tween per event.
 *
 * Pointer/fine-input + desktop only — disabled for touch, small
 * screens, reduced motion, or if GSAP is unavailable. Rotation is
 * transform-only and composes with the GSAP entrance + the scroll
 * engine (separate transform properties / separate elements).
 */
( function () {
  'use strict';

  if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { return; }
  if ( window.matchMedia( '(pointer: coarse)' ).matches ) { return; }
  if ( ! window.matchMedia( '(min-width: 768px)' ).matches ) { return; }

  var gsap = window.gsap;
  if ( ! gsap || typeof gsap.quickTo !== 'function' ) { return; }

  var hero  = document.querySelector( '.wbb-hero' );
  var card  = document.querySelector( '.wbb-hero__tilt' );
  var title = document.querySelector( '.wbb-hero__title' );
  if ( ! hero || ! card ) { return; }

  var interp = gsap.utils.interpolate;
  var clamp  = gsap.utils.clamp( 0, 1 );

  // How far things lean (degrees / px) at the edges of the hero.
  // Kept very small — a barely-there drift, not an obvious tilt.
  var CARD_TILT  = 2;   // background card
  var TITLE_TILT = 1.5; // title — a touch more lean than the card
  var TITLE_SHIFT = 6;  // title — horizontal parallax drift (px)

  // Give each element its own perspective so rotateX/Y render in 3D.
  gsap.set( card,  { transformPerspective: 1000, transformOrigin: 'center center' } );
  if ( title ) { gsap.set( title, { transformPerspective: 800, transformOrigin: 'center center' } ); }

  // quickTo setters — smoothed, reused across every pointer move.
  var EASE = 'power3.out', DUR = 0.9;
  var cardRotX = gsap.quickTo( card, 'rotationX', { duration: DUR, ease: EASE } );
  var cardRotY = gsap.quickTo( card, 'rotationY', { duration: DUR, ease: EASE } );

  var titleRotX, titleRotY, titleX;
  if ( title ) {
    titleRotX = gsap.quickTo( title, 'rotationX', { duration: DUR, ease: EASE } );
    titleRotY = gsap.quickTo( title, 'rotationY', { duration: DUR, ease: EASE } );
    titleX    = gsap.quickTo( title, 'x',         { duration: DUR, ease: EASE } );
  }

  function onMove( e ) {
    var r = card.getBoundingClientRect();
    var px = clamp( ( e.clientX - r.left ) / r.width );   // 0 (left)  → 1 (right)
    var py = clamp( ( e.clientY - r.top )  / r.height );  // 0 (top)   → 1 (bottom)

    // Map normalised position → rotation. rotationY follows the
    // horizontal axis; rotationX is inverted so the top edge tips away.
    cardRotY( interp( -CARD_TILT, CARD_TILT, px ) );
    cardRotX( interp( CARD_TILT, -CARD_TILT, py ) );

    if ( title ) {
      titleRotY( interp( -TITLE_TILT, TITLE_TILT, px ) );
      titleRotX( interp( TITLE_TILT, -TITLE_TILT, py ) );
      titleX( interp( -TITLE_SHIFT, TITLE_SHIFT, px ) );
    }
  }

  function onLeave() {
    cardRotX( 0 ); cardRotY( 0 );
    if ( title ) { titleRotX( 0 ); titleRotY( 0 ); titleX( 0 ); }
  }

  hero.addEventListener( 'mousemove', onMove );
  hero.addEventListener( 'mouseleave', onLeave );

} )();
