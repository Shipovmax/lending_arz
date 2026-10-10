<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Order summary in the prototype's markup (.summary). Rendered by js/component.js into [data-entity=basket-total-block].
 *
 * @var array $arParams
 */
?>
<script id="basket-total-template" type="text/html">
  <h2>Ваш заказ</h2>
  <div class="summary__row"><span class="muted">Товары, {{MIR_TOTAL_QUANTITY}} шт.</span><span>{{{PRICE_WITHOUT_DISCOUNT_FORMATED}}}</span></div>
  {{#DISCOUNT_PRICE_FORMATED}}
  <div class="summary__row"><span class="muted">Скидка</span><span>−{{{DISCOUNT_PRICE_FORMATED}}}</span></div>
  {{/DISCOUNT_PRICE_FORMATED}}
  <div class="summary__row"><span class="muted">Доставка</span><span>При оформлении</span></div>
  <div class="summary__total"><span>Итого</span><strong data-entity="basket-total-price">{{{PRICE_FORMATED}}}</strong></div>
  <button class="btn btn--accent btn--block" type="button"{{#DISABLE_CHECKOUT}} disabled{{/DISABLE_CHECKOUT}} data-entity="basket-checkout-button">Оформить заказ</button>
</script>
