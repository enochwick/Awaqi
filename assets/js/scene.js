/**
 * Holds the loader curtain until the Spline iframe reports back, then
 * cross-fades into the scene. A timeout fallback makes sure a slow or blocked
 * embed never leaves someone staring at a spinner.
 */
(function () {
  'use strict';

  var scene = document.querySelector('[data-scene]');
  if (!scene) {
    return;
  }

  var frame   = scene.querySelector('iframe');
  var loader  = document.querySelector('[data-loader]');
  var timeout = parseInt(scene.getAttribute('data-timeout'), 10) || 8000;
  var done    = false;

  function reveal() {
    if (done) {
      return;
    }
    done = true;

    scene.classList.add('is-ready');

    if (loader) {
      // A short beat so the scene's first frame paints before the curtain lifts.
      window.setTimeout(function () {
        loader.classList.add('is-hidden');
      }, 400);
    }
  }

  if (frame) {
    frame.addEventListener('load', reveal);
  }

  window.setTimeout(reveal, timeout);
})();
