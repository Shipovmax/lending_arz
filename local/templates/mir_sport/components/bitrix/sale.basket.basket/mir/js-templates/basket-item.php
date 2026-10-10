<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * One cart line in the prototype's markup (.cart-item). Mustache template rendered by js/component.js;
 * the id and data-entity attributes are the component's hooks and must stay.
 *
 * @var array $arParams
 */
?>
<script id="basket-item-template" type="text/html">
  <div class="cart-item" id="basket-item-{{ID}}" data-entity="basket-item" data-id="{{ID}}">
    {{#SHOW_RESTORE}}
    <div class="cart-item__info" id="basket-item-height-aligner-{{ID}}">
      <span class="muted">Удалили «{{NAME}}»</span>
    </div>
    <button class="btn btn--sm" type="button" data-entity="basket-item-restore-button">Вернуть</button>
    <button class="icon-btn" type="button" aria-label="Скрыть" data-entity="basket-item-close-restore-button"><svg class="icon"><use href="#i-close"/></svg></button>
    {{/SHOW_RESTORE}}
    {{^SHOW_RESTORE}}
    <a class="cart-item__photo photo" href="{{DETAIL_PAGE_URL}}" tabindex="-1" aria-hidden="true">{{#IMAGE_URL}}<img src="{{{IMAGE_URL}}}" alt="">{{/IMAGE_URL}}{{^IMAGE_URL}}Фото{{/IMAGE_URL}}</a>
    <div class="cart-item__info" id="basket-item-height-aligner-{{ID}}">
      <a class="cart-item__name link-u" href="{{DETAIL_PAGE_URL}}"><span data-entity="basket-item-name">{{NAME}}</span></a>
      {{#PROPS}}
      <span class="muted small" data-entity="basket-item-property-value" data-property-code="{{CODE}}">{{{NAME}}}: {{{VALUE}}}</span>
      {{/PROPS}}
      <span class="muted small"><span id="basket-item-price-{{ID}}">{{{PRICE_FORMATED}}}</span> за шт.</span>
      {{#NOT_AVAILABLE}}<span class="err">Нет в наличии</span>{{/NOT_AVAILABLE}}
      {{#DELAYED}}<span class="muted small">Отложен. <a class="link-line" href="javascript:void(0)" data-entity="basket-item-remove-delayed">Вернуть в заказ</a></span>{{/DELAYED}}
      {{#WARNINGS.length}}
      <span class="err" data-entity="basket-item-warning-node">
        {{#WARNINGS}}<span data-entity="basket-item-warning-text">{{{.}}}</span> {{/WARNINGS}}
        <button class="link-line" type="button" data-entity="basket-item-warning-close">Скрыть</button>
      </span>
      {{/WARNINGS.length}}
    </div>
    <div class="qty" data-entity="basket-item-quantity-block">
      <button type="button" aria-label="Меньше" data-entity="basket-item-quantity-minus">−</button>
      <input type="text" inputmode="numeric" aria-label="Количество" value="{{QUANTITY}}"{{#NOT_AVAILABLE}} disabled{{/NOT_AVAILABLE}} data-value="{{QUANTITY}}" data-entity="basket-item-quantity-field" id="basket-item-quantity-{{ID}}">
      <button type="button" aria-label="Больше" data-entity="basket-item-quantity-plus">+</button>
    </div>
    <span class="cart-item__total" id="basket-item-sum-price-{{ID}}">{{{SUM_PRICE_FORMATED}}}</span>
    <button class="icon-btn" type="button" aria-label="Удалить товар" data-entity="basket-item-delete"><svg class="icon"><use href="#i-trash"/></svg></button>
    {{/SHOW_RESTORE}}
  </div>
</script>
