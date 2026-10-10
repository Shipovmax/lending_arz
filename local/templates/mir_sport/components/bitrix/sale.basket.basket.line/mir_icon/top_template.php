<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @var array $arParams
 * @var array $arResult
 */
$isStub = isset($arResult['COMPOSITE_STUB']) && $arResult['COMPOSITE_STUB'] === 'Y';
$count = $isStub ? 0 : (int)($arResult['NUM_PRODUCTS'] ?? 0);
?>
<a class="header-icon" href="<?= htmlspecialcharsbx($arParams['PATH_TO_BASKET']) ?>" aria-label="Корзина<?= $count > 0 ? ', товаров: ' . $count : '' ?>">
  <svg class="icon"><use href="#i-bag"/></svg>
  <span class="header-icon__label">Корзина</span>
  <span class="badge badge--accent" data-cart-count<?= $count > 0 ? '' : ' hidden' ?>><?= $count ?></span>
</a>
