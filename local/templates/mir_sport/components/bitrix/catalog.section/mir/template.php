<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Product cards in the prototype's markup (.product-grid > .card).
 * Products with offers link to the product page to choose a size; simple products are added to the cart here.
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

$isEmpty = empty($arResult['ITEMS']);
$wrapGrid = ($arParams['MIR_WRAP_GRID'] ?? 'Y') !== 'N';
?>
<?php if ($isEmpty && ($arParams['MIR_HIDE_EMPTY'] ?? 'N') === 'Y'): ?>
<p class="muted">Других товаров в этом разделе пока нет.</p>
<?php elseif ($isEmpty): ?>
<div class="empty">
  <span class="empty__title">Ничего не нашлось</span>
  <span class="muted">Уберите часть фильтров или выберите другой раздел.</span>
  <a class="btn btn--sm" href="<?= htmlspecialcharsbx($arParams['MIR_RESET_URL'] ?? SITE_DIR . 'catalog/') ?>">Сбросить фильтры</a>
</div>
<?php else: ?>
<?php if ($wrapGrid): ?><div class="product-grid"><?php endif ?>
<?php foreach ($arResult['ITEMS'] as $item):
    $card = $item['CARD'];
    $name = htmlspecialcharsbx($item['NAME']);
    $url = htmlspecialcharsbx($item['DETAIL_PAGE_URL']);
?>
  <article class="card" data-id="<?= (int)$item['ID'] ?>">
    <div class="card__media">
      <a class="card__photo photo" href="<?= $url ?>" tabindex="-1" aria-hidden="true"><?php if ($card['PHOTO'] !== ''): ?><img src="<?= htmlspecialcharsbx($card['PHOTO']) ?>" alt="" loading="lazy"><?php else: ?>Фото товара<?php endif ?></a>
      <button class="icon-btn card__fav" type="button" aria-pressed="false" aria-label="Добавить в избранное: <?= $name ?>" data-fav><svg class="icon"><use href="#i-heart"/></svg></button>
    </div>
    <?php if ($card['PRICE'] !== ''): ?>
    <div class="card__price"><span class="price"><?= $card['PRICE_FROM'] ? 'от ' : '' ?><?= $card['PRICE'] ?></span><?php if ($card['OLD_PRICE'] !== ''): ?><span class="price-old"><span class="visually-hidden">Старая цена </span><?= $card['OLD_PRICE'] ?></span><?php endif ?></div>
    <?php endif ?>
    <a class="card__name link-u" href="<?= $url ?>"><?= $name ?></a>
    <?php if ($card['META'] !== ''): ?><span class="card__meta"><?= htmlspecialcharsbx($card['META']) ?></span><?php endif ?>
    <?php if ($card['AVAILABLE']): ?>
    <span class="card__stock ok">В наличии</span>
    <?php else: ?>
    <span class="card__stock muted">Нет в наличии</span>
    <?php endif ?>
    <?php if ($card['HAS_OFFERS'] || !$card['AVAILABLE']): ?>
    <a class="btn btn--sm card__buy" href="<?= $url ?>"><?= $card['HAS_OFFERS'] && $card['AVAILABLE'] ? 'Выбрать размер' : 'Подробнее' ?></a>
    <?php else: ?>
    <button class="btn btn--sm card__buy" type="button" data-buy="<?= (int)$item['ID'] ?>">В корзину</button>
    <?php endif ?>
  </article>
<?php endforeach ?>
<?php if ($wrapGrid): ?></div><?php endif ?>
<?php // The component turns DISPLAY_BOTTOM_PAGER into a boolean.
if (in_array($arParams['DISPLAY_BOTTOM_PAGER'] ?? false, [true, 'Y'], true) && !empty($arResult['NAV_STRING'])): ?>
<div class="pager"><?= $arResult['NAV_STRING'] ?></div>
<?php endif ?>
<?php endif ?>
