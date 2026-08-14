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
   * The sequence is: glide down to the confirmation beside the form, hold long
   * enough to read it, raise the loader curtain, jump home behind it, then
   * lower the curtain onto the hero. The curtain is what makes the return trip
   * feel deliberate rather than like a second page load.
   *
   * Failures stop at the form — that is where the address gets corrected.
   */
  var STEP = {
    toForm:  900,   // let the scene settle before moving
    read:    2600,  // time on the confirmation
    curtain: 700,   // curtain fade-in before jumping
    home:    500    // beat at the top before revealing
  };

  function handleSignupReturn() {
    var search = window.location.search;

    if (search.indexOf('joined=') === -1) {
      return;
    }

    var failed  = search.indexOf('joined=0') !== -1;
    var section = document.getElementById('waitlist');
    var loader  = document.querySelector('[data-loader]');
    var root    = document.documentElement;
    var soft    = !reduced.matches;

    clearFlags();

    if (!section) {
      return;
    }

    // Land at the top without animating the way up there.
    scrollTop('auto');

    window.setTimeout(function () {
      section.scrollIntoView({ behavior: soft ? 'smooth' : 'auto', block: 'start' });
    }, soft ? STEP.toForm : 0);

    if (failed || !loader) {
      return;
    }

    window.setTimeout(function () {
      loader.classList.add('is-transition');

      window.setTimeout(function () {
        scrollTop('auto');

        window.setTimeout(function () {
          loader.classList.remove('is-transition');
        }, STEP.home);
      }, soft ? STEP.curtain : 0);
    }, (soft ? STEP.toForm : 0) + STEP.read);

    function scrollTop(behavior) {
      var previous = root.style.scrollBehavior;
      root.style.scrollBehavior = behavior;
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
