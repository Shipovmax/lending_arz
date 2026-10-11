/* Smart filter: apply on change (no "Show" button, as in the prototype). */
(function () {
  'use strict';
  function init() {
    var form = document.querySelector('[data-smart-filter]');
    if (!form) return;
    // "Показать ещё" opens the folded values of a group.
    form.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-more-toggle]');
      if (!btn) return;
      Array.prototype.forEach.call(btn.parentNode.querySelectorAll('[data-more]'), function (n) { n.hidden = false; });
      btn.remove();
    });
    form.addEventListener('change', function () {
      // Drop empty price fields so the URL stays clean.
      Array.prototype.forEach.call(form.querySelectorAll('input[type=number]'), function (i) { i.disabled = i.value === ''; });
      form.submit();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
