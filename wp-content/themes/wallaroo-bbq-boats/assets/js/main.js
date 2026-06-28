/**
 * Wallaroo BBQ Boats — main.js
 * Vanilla JS only. Loaded with defer — DOM is ready when this runs.
 */

( function () {
  'use strict';

  // ──────────────────────────────────────────────
  // 1. Sticky header — add/remove 'scrolled' class
  // ──────────────────────────────────────────────
  const header = document.getElementById( 'site-header' );

  if ( header ) {
    const SCROLL_THRESHOLD = 20;

    function onScroll () {
      if ( window.scrollY > SCROLL_THRESHOLD ) {
        header.classList.add( 'scrolled' );
        header.style.boxShadow = '0 2px 24px rgba(0,0,0,0.10)';
        header.style.backgroundColor = 'rgba(255,255,255,0.98)';
      } else {
        header.classList.remove( 'scrolled' );
        header.style.boxShadow = '';
        header.style.backgroundColor = '';
      }
    }

    // Passive listener for scroll performance
    window.addEventListener( 'scroll', onScroll, { passive: true } );
    onScroll(); // run on load in case page is already scrolled
  }

  // ──────────────────────────────────────────────
  // 2. Mobile menu toggle
  // ──────────────────────────────────────────────
  const toggleBtn  = document.getElementById( 'mobile-menu-toggle' );
  const mobileMenu = document.getElementById( 'mobile-menu' );

  if ( toggleBtn && mobileMenu ) {
    toggleBtn.addEventListener( 'click', function () {
      const isOpen = ! mobileMenu.classList.contains( 'hidden' );

      mobileMenu.classList.toggle( 'hidden', isOpen );
      toggleBtn.setAttribute( 'aria-expanded', String( ! isOpen ) );

      // Swap hamburger ↔ close icon
      toggleBtn.innerHTML = isOpen
        ? '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>'
        : '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    } );

    // Close menu when a link inside it is clicked
    mobileMenu.querySelectorAll( 'a' ).forEach( function ( link ) {
      link.addEventListener( 'click', function () {
        mobileMenu.classList.add( 'hidden' );
        toggleBtn.setAttribute( 'aria-expanded', 'false' );
        toggleBtn.innerHTML = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
      } );
    } );
  }

  // ──────────────────────────────────────────────
  // 3. FAQ accordion
  // ──────────────────────────────────────────────
  document.querySelectorAll( '[data-faq-toggle]' ).forEach( function ( btn ) {
    btn.addEventListener( 'click', function () {
      const isExpanded = btn.getAttribute( 'aria-expanded' ) === 'true';
      const answerId   = btn.getAttribute( 'aria-controls' );
      const answer     = document.getElementById( answerId );
      const icon       = btn.querySelector( '[data-faq-icon]' );

      if ( ! answer ) return;

      btn.setAttribute( 'aria-expanded', String( ! isExpanded ) );
      answer.classList.toggle( 'hidden', isExpanded );

      if ( icon ) {
        icon.style.transform = isExpanded ? '' : 'rotate(180deg)';
      }
    } );
  } );

  // ──────────────────────────────────────────────
  // 4. Scroll reveal — fade/rise elements into view
  // ──────────────────────────────────────────────
  const reveals = document.querySelectorAll( '[data-reveal]' );

  if ( reveals.length ) {
    const prefersReduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

    // No motion or no observer support → just show everything immediately.
    if ( prefersReduced || ! ( 'IntersectionObserver' in window ) ) {
      reveals.forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
    } else {
      const io = new IntersectionObserver( function ( entries, obs ) {
        entries.forEach( function ( entry ) {
          if ( entry.isIntersecting ) {
            entry.target.classList.add( 'is-visible' );
            obs.unobserve( entry.target ); // reveal once, then stop watching
          }
        } );
      }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' } );

      reveals.forEach( function ( el ) { io.observe( el ); } );
    }
  }

  // ──────────────────────────────────────────────
  // 5. Smooth scroll for anchor links
  // ──────────────────────────────────────────────
  document.querySelectorAll( 'a[href^="#"]' ).forEach( function ( anchor ) {
    anchor.addEventListener( 'click', function ( e ) {
      const target = document.querySelector( this.getAttribute( 'href' ) );
      if ( target ) {
        e.preventDefault();
        const headerHeight = header ? header.offsetHeight : 0;
        const top = target.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
        window.scrollTo( { top: top, behavior: 'smooth' } );
      }
    } );
  } );

} )();
