/**
 * Global theme JS.
 *
 * Page-specific behaviour lives in its own file (see scene.js) and is enqueued
 * only on the views that need it.
 */
(function () {
  'use strict';

  /**
   * Marks the document once the first pointer interaction happens, so styles
   * can distinguish touch from mouse without a media query guess.
   */
  function flagPointer() {
    document.documentElement.classList.add('has-pointer');
  }

  window.addEventListener('pointermove', flagPointer, { once: true });

  /**
   * Fades sections in as they scroll into view.
   *
   * The hidden state is added here rather than in the stylesheet, so content is
   * never invisible to someone without JavaScript. Anyone who has asked for
   * reduced motion is left alone entirely.
   */
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  /**
   * SVG SMIL animation ignores prefers-reduced-motion and cannot be stopped
   * from CSS, so the loader's morph is paused here instead.
   */
  if (reduced.matches) {
    Array.prototype.forEach.call(document.querySelectorAll('svg'), function (svg) {
      if (svg.querySelector('animate') && typeof svg.pauseAnimations === 'function') {
        svg.pauseAnimations();
      }
    });
  }

  /**
   * After a waitlist signup the page reloads with ?joined=.
   *
   * Nothing scrolls. The URL fragment has already painted the page at the form,
   * so the visitor never appears to leave the spot they submitted from. The
   * sequence is: read the confirmation, raise the loader curtain, jump home
   * behind it, lower the curtain onto the hero with the form below the fold.
   *
   * Failures stop at the form — that is where the address gets corrected.
   */
  var STEP = {
    read:    2200,  // time on the confirmation before the curtain
    curtain: 700,   // curtain fade-in before jumping home
    home:    500    // beat at the top before revealing the hero
  };

  function handleSignupReturn() {
    var search = window.location.search;

    if (search.indexOf('joined=') === -1) {
      return;
    }

    var failed = search.indexOf('joined=0') !== -1;
    var loader = document.querySelector('[data-loader]');
    var root   = document.documentElement;
    var soft   = !reduced.matches;

    clearFlags();

    // The confirmation is already on screen, so it must not start hidden and
    // fade in. Dropping the hook here means the reveal pass below skips it.
    Array.prototype.forEach.call(document.querySelectorAll('[data-reveal]'), function (el) {
      el.removeAttribute('data-reveal');
    });

    // A failed signup stays put: the address has to be corrected at the field.
    if (failed || !loader) {
      return;
    }

    window.setTimeout(function () {
      loader.classList.add('is-transition');

      window.setTimeout(function () {
        scrollTop();

        window.setTimeout(function () {
          loader.classList.remove('is-transition');
        }, STEP.home);
      }, soft ? STEP.curtain : 0);
    }, STEP.read);

    // Jumps home behind the raised curtain, never as a visible scroll.
    function scrollTop() {
      var previous = root.style.scrollBehavior;
      root.style.scrollBehavior = 'auto';
      window.scrollTo(0, 0);
      root.style.scrollBehavior = previous;
    }
  }

  /**
   * Drops the status flags so a refresh does not replay the sequence. Other
   * query parameters are preserved.
   */
  function clearFlags() {
    if (!window.history || !window.history.replaceState || !('URLSearchParams' in window)) {
      return;
    }

    var params = new URLSearchParams(window.location.search);
    params.delete('joined');
    params.delete('err');

    var query = params.toString();

    // Also drops the #waitlist fragment left by the redirect.
    window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
  }

  handleSignupReturn();

  var targets = document.querySelectorAll('[data-reveal]');

  if (!targets.length || reduced.matches || !('IntersectionObserver' in window)) {
    return;
  }

  Array.prototype.forEach.call(targets, function (el) {
    el.classList.add('js-reveal');
  });

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }
      entry.target.classList.add('is-visible');
      io.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -12% 0px', threshold: 0.12 });

  Array.prototype.forEach.call(targets, function (el) {
    io.observe(el);
  });
})();
