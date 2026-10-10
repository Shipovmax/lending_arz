/* Smart filter: apply on change (no "Show" button, as in the prototype). */
(function () {
  'use strict';
  function init() {
    var form = document.querySelector('[data-smart-filter]');
    if (!form) return;
    form.addEventListener('change', function () {
      // Drop empty price fields so the URL stays clean.
      Array.prototype.forEach.call(form.querySelectorAll('input[type=number]'), function (i) { i.disabled = i.value === ''; });
      form.submit();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
