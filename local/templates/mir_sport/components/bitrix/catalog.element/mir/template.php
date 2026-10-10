<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Product page in the prototype's markup (product.html): gallery, color and size buttons, delivery, tabs.
 * Behaviour (offer choice, add to cart, gallery, tabs) lives in script.js.
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
use Bitrix\Main\Web\Json;

$this->setFrameMode(true);

$mir = $arResult['MIR'];
$name = htmlspecialcharsbx($arResult['NAME']);
$gallery = $mir['GALLERY'];
$count = count($gallery);
$colors = $mir['COLORS'];
$defaultColor = $mir['DEFAULT_COLOR'];
$tree = [];
foreach ($mir['OFFERS'] as $offer) {
    $tree[$offer['color']][$offer['size']] = $offer['canBuy'];
}
$jsData = [
    'productId' => (int)$arResult['ID'],
    'offers' => $mir['OFFERS'],
    'gallery' => $gallery,
    'colors' => array_map(static fn($c) => ['name' => $c['name'], 'photos' => $c['photos']], $colors),
    'color' => $defaultColor,
    'actionUrl' => $arResult['DETAIL_PAGE_URL'],
    'basketUrl' => $arParams['BASKET_URL'],
];
?>
<section class="pdp" data-product data-id="<?= (int)$arResult['ID'] ?>" data-product-json="<?= htmlspecialcharsbx(Json::encode($jsData)) ?>">
  <div class="gallery">
    <div class="gallery__thumbs" aria-label="Фотографии товара" data-thumbs>
      <?php foreach ($gallery as $i => $photo): ?>
      <button class="gallery__thumb photo" type="button"<?= $i === 0 ? ' aria-current="true"' : '' ?> aria-label="Фото <?= $i + 1 ?>" data-thumb="<?= $i ?>"><img src="<?= htmlspecialcharsbx($photo['thumb']) ?>" alt=""></button>
      <?php endforeach ?>
    </div>
    <div class="gallery__main photo">
      <?php if ($count > 0): ?>
      <img src="<?= htmlspecialcharsbx($gallery[0]['main']) ?>" alt="<?= $name ?>" data-photo>
      <?php else: ?>
      <span>Фото товара</span>
      <?php endif ?>
      <div class="gallery__nav"<?= $count > 1 ? '' : ' hidden' ?> data-photo-nav>
        <button type="button" aria-label="Предыдущее фото" data-photo-prev>←</button>
        <button type="button" aria-label="Следующее фото" data-photo-next>→</button>
      </div>
    </div>
  </div>

  <div class="buybox">
    <div>
      <div class="buybox__top">
        <?php if ($mir['BRAND'] !== ''): ?><span class="buybox__brand"><?= htmlspecialcharsbx($mir['BRAND']) ?></span><?php else: ?><span></span><?php endif ?>
        <?php if ($mir['ARTICLE'] !== ''): ?><span class="muted small">Артикул: <?= htmlspecialcharsbx($mir['ARTICLE']) ?></span><?php endif ?>
      </div>
      <h1 class="buybox__title"><?= $name ?></h1>
    </div>

    <div class="buybox__price">
      <span class="price" data-price><?= $mir['PRICE_FROM'] ? 'от ' : '' ?><?= $mir['PRICE'] ?></span>
      <span class="price-old"<?= $mir['OLD_PRICE'] === '' ? ' hidden' : '' ?> data-price-old><span class="visually-hidden">Старая цена </span><span data-price-old-value><?= $mir['OLD_PRICE'] ?></span></span>
    </div>

    <?php if (count($colors) > 0): ?>
    <fieldset class="option">
      <legend>Цвет: <strong data-color-name><?= htmlspecialcharsbx($colors[$defaultColor]['name'] ?? '') ?></strong></legend>
      <div class="swatches">
        <?php foreach ($colors as $xml => $color): ?>
        <button class="swatch" type="button" aria-pressed="<?= $xml === $defaultColor ? 'true' : 'false' ?>" aria-label="<?= htmlspecialcharsbx($color['name']) ?>" data-color="<?= htmlspecialcharsbx($xml) ?>"><span<?= $color['pict'] !== '' ? ' style="background-image: url(\'' . htmlspecialcharsbx($color['pict']) . '\')"' : '' ?>></span></button>
        <?php endforeach ?>
      </div>
    </fieldset>
    <?php endif ?>

    <?php if ($mir['SIZES']): ?>
    <fieldset class="option">
      <legend><?= $mir['SIZE_LABEL'] ?></legend>
      <div class="sizes">
        <?php foreach ($mir['SIZES'] as $size):
            $available = !empty($tree[$defaultColor][$size]); ?>
        <button class="size" type="button" aria-pressed="false" data-size="<?= htmlspecialcharsbx($size) ?>"<?= $available ? '' : ' disabled aria-label="' . htmlspecialcharsbx($size) . ', нет в наличии"' ?>><?= htmlspecialcharsbx($size) ?></button>
        <?php endforeach ?>
      </div>
      <div class="option__foot">
        <span class="muted">Зачёркнутых размеров нет в наличии</span>
        <a class="link-line" href="<?= SITE_DIR ?>info/#sizes">Таблица размеров</a>
      </div>
    </fieldset>
    <span class="err" role="alert" hidden data-size-error>Выберите размер</span>
    <?php endif ?>

    <?php if ($mir['CAN_BUY']): ?>
    <div class="buy-row">
      <button class="btn btn--primary" type="button" data-add-to-cart>Добавить в корзину</button>
      <button class="fav-btn" type="button" aria-pressed="false" aria-label="Добавить в избранное" data-fav><svg class="icon"><use href="#i-heart"/></svg></button>
    </div>
    <?php else: ?>
    <div class="buy-row">
      <button class="btn btn--primary" type="button" disabled>Нет в наличии</button>
      <button class="fav-btn" type="button" aria-pressed="false" aria-label="Добавить в избранное" data-fav><svg class="icon"><use href="#i-heart"/></svg></button>
    </div>
    <?php endif ?>
    <span class="err" role="alert" hidden data-add-error>Не получилось добавить товар. Попробуйте ещё раз.</span>
    <div class="notice" role="status" hidden data-added>
      <span>Добавили в корзину<span data-added-size></span></span>
      <a class="link-line" href="<?= htmlspecialcharsbx($arParams['BASKET_URL']) ?>"><strong>Оформить заказ</strong></a>
    </div>

    <div class="delivery">
      <div class="delivery__row"><svg class="icon"><use href="#i-store"/></svg><span class="delivery__text"><span class="delivery__title">Самовывоз из магазина в Арзамасе</span><span class="muted">Сегодня, если товар в наличии</span></span><span class="delivery__price">Бесплатно</span></div>
      <div class="delivery__row"><svg class="icon"><use href="#i-box"/></svg><span class="delivery__text"><span class="delivery__title">Пункт выдачи СДЭК</span><span class="muted">Срок и стоимость — при оформлении</span></span><span class="delivery__price">от [цена] ₽</span></div>
      <div class="delivery__row"><svg class="icon"><use href="#i-truck"/></svg><span class="delivery__text"><span class="delivery__title">Курьер СДЭК</span><span class="muted">До двери, срок — при оформлении</span></span><span class="delivery__price">от [цена] ₽</span></div>
    </div>
  </div>
</section>

<section class="tabs" data-tabs>
  <div class="tabs__list" role="tablist" aria-label="Информация о товаре">
    <button class="tabs__tab" type="button" role="tab" id="tab-about" aria-selected="true" aria-controls="panel-about">Описание</button>
    <?php if ($mir['SPECS']): ?>
    <button class="tabs__tab" type="button" role="tab" id="tab-specs" aria-selected="false" aria-controls="panel-specs" tabindex="-1">Характеристики</button>
    <?php endif ?>
    <button class="tabs__tab" type="button" role="tab" id="tab-delivery" aria-selected="false" aria-controls="panel-delivery" tabindex="-1">Доставка и возврат</button>
  </div>
  <div class="tabs__panel" id="panel-about" role="tabpanel" aria-labelledby="tab-about">
    <?php if (trim(strip_tags($mir['DESCRIPTION'])) !== ''): ?>
    <div><?= $mir['DESCRIPTION'] ?></div>
    <?php else: ?>
    <p class="muted">Описание появится позже.</p>
    <?php endif ?>
  </div>
  <?php if ($mir['SPECS']): ?>
  <div class="tabs__panel" id="panel-specs" role="tabpanel" aria-labelledby="tab-specs" hidden>
    <dl class="def-list">
      <?php foreach ($mir['SPECS'] as $spec): ?>
      <div><dt><?= htmlspecialcharsbx($spec['name']) ?></dt><dd><?= htmlspecialcharsbx($spec['value']) ?></dd></div>
      <?php endforeach ?>
    </dl>
  </div>
  <?php endif ?>
  <div class="tabs__panel" id="panel-delivery" role="tabpanel" aria-labelledby="tab-delivery" hidden>
    <p>Самовывоз из магазина в Арзамасе — бесплатно, в день заказа, если товар в наличии. Доставка СДЭК — в пункт выдачи или курьером, стоимость и срок рассчитываются при оформлении.</p>
    <p>Товар надлежащего качества можно вернуть, если сохранены товарный вид и ярлыки. Условия и сроки — на странице <a class="link-line" href="<?= SITE_DIR ?>info/#return">«Возврат и обмен»</a>.</p>
  </div>
</section>
