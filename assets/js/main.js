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
   * After a waitlist signup the page reloads with ?joined=. The visitor starts
   * at the top so the scene is on screen, then the page glides down to the
   * confirmation — rather than the browser jumping straight to an opaque panel
   * and looking like it loaded black.
   */
  function handleSignupReturn() {
    if (window.location.search.indexOf('joined=') === -1) {
      return;
    }

    var section = document.getElementById('waitlist');
    if (!section) {
      return;
    }

    var root = document.documentElement;

    // Start at the top without animating the way up there.
    var previous = root.style.scrollBehavior;
    root.style.scrollBehavior = 'auto';
    window.scrollTo(0, 0);
    root.style.scrollBehavior = previous;

    function glide() {
      section.scrollIntoView({
        behavior: reduced.matches ? 'auto' : 'smooth',
        block: 'start'
      });
    }

    // A beat for the scene to fade in before moving.
    if (reduced.matches) {
      glide();
    } else {
      window.setTimeout(glide, 1100);
    }

    // Drop the flags so a refresh does not replay the message or the glide.
    // Any other query parameters on the URL are preserved.
    if (window.history && window.history.replaceState && 'URLSearchParams' in window) {
      var params = new URLSearchParams(window.location.search);
      params.delete('joined');
      params.delete('err');
      var query = params.toString();
      window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
    }
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
