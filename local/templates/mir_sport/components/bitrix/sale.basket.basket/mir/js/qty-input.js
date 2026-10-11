/*
 * Cart quantity field: the standard component recalculates only when the field loses focus.
 * Here the value is selected on focus (typing replaces it), and the cart is recalculated half a second after typing
 * stops or on Enter. Delegated, because the component redraws the rows after every change.
 */
(function () {
  'use strict';
  var FIELD = '[data-entity="basket-item-quantity-field"]';
  var timer = null;

  function apply(field) {
    clearTimeout(timer);
    if (!/^\d+$/.test(field.value) || Number(field.value) < 1) return;
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  document.addEventListener('focusin', function (e) {
    if (e.target.matches && e.target.matches(FIELD)) e.target.select();
  });
  document.addEventListener('input', function (e) {
    var field = e.target;
    if (!field.matches || !field.matches(FIELD)) return;
    field.value = field.value.replace(/\D+/g, '').slice(0, 3);
    clearTimeout(timer);
    timer = setTimeout(function () { apply(field); }, 500);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches && e.target.matches(FIELD)) {
      e.preventDefault();
      apply(e.target);
    }
  });
})();
