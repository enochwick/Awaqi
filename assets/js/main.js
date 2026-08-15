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
   * Nothing moves on arrival: the URL fragment has already painted the page at
   * the form, so the visitor never appears to leave the spot they submitted
   * from and the confirmation is simply there. After a beat the page scrolls
   * back up to the hero, leaving the form below the fold.
   *
   * No curtain is used. The loading spinner belongs to a genuine first visit,
   * and is not even rendered on this request.
   *
   * Failures stay at the form — that is where the address gets corrected.
   */
  var READ_MS = 2200;

  function handleSignupReturn() {
    var search = window.location.search;

    if (search.indexOf('joined=') === -1) {
      return;
    }

    var failed = search.indexOf('joined=0') !== -1;

    clearFlags();

    // The confirmation is already on screen, so it must not start hidden and
    // fade in. Dropping the hook here means the reveal pass below skips it.
    Array.prototype.forEach.call(document.querySelectorAll('[data-reveal]'), function (el) {
      el.removeAttribute('data-reveal');
    });

    if (failed) {
      return;
    }

    window.setTimeout(function () {
      window.scrollTo({ top: 0, behavior: reduced.matches ? 'auto' : 'smooth' });
    }, READ_MS);
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
