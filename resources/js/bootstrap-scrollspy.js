/*!
 * Bootstrap ScrollSpy - Lightweight standalone scrollspy
 * Compatible with Bootstrap 5
 */
(function () {
  'use strict';

  // Bootstrap 5 already includes ScrollSpy via bootstrap.bundle.min.js
  // This file ensures scrollspy is initialized on elements with data-bs-spy="scroll"
  document.addEventListener('DOMContentLoaded', function () {
    var scrollSpyElements = document.querySelectorAll('[data-bs-spy="scroll"]');
    scrollSpyElements.forEach(function (el) {
      if (typeof bootstrap !== 'undefined' && bootstrap.ScrollSpy) {
        new bootstrap.ScrollSpy(el, {
          target: el.getAttribute('data-bs-target') || null,
          offset: parseInt(el.getAttribute('data-bs-offset')) || 10
        });
      }
    });
  });
})();
