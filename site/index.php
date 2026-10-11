<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Мир спорта');
$APPLICATION->SetPageProperty('title', 'Мир спорта — спорттовары и товары для рыбалки в Арзамасе');
$APPLICATION->SetPageProperty('description', 'Магазин спорттоваров и товаров для рыбалки в Арзамасе. Самовывоз из магазина и доставка СДЭК по всей России.');
// The hero prints the page's h1.
$APPLICATION->SetPageProperty('hide_h1', 'Y');

require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/contacts.php';
require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/showcase.php';
$contacts = \MirSport\contacts();

// Category tiles come from the catalog itself (see include/showcase.php), so a 1C import updates them.
$categoryTiles = \MirSport\section_tiles($store['catalog_iblock_id']);
$homeBrands = \MirSport\brand_links($store['catalog_iblock_id'], $store['brand_props'], $store['hide_unavailable'] === 'Y', 12);
?>
<div class="container page">

  <section class="hero">
    <div class="hero__content">
      <span class="hero__eyebrow">Магазин в Арзамасе&nbsp;· доставка по России</span>
      <h1 class="hero__title">Спорттовары <span class="hero__title-tail">и всё для рыбалки</span></h1>
      <p class="hero__lead">Одежда, обувь и инвентарь для спорта, снасти и экипировка для рыбалки. Самовывоз из магазина или доставка СДЭК.</p>
      <div class="hero__actions">
        <a class="btn btn--accent" href="<?= SITE_DIR ?>catalog/">Смотреть каталог</a>
        <a class="btn btn--light" href="<?= SITE_DIR ?>catalog/?sort=new">Новинки</a>
      </div>
    </div>
    <ul class="hero__facts" aria-label="Условия покупки">
      <li><a class="hero__fact" href="<?= SITE_DIR ?>info/#delivery"><svg class="icon" aria-hidden="true"><use href="#i-store"/></svg><span><span class="hero__fact-title">Самовывоз</span><span class="hero__fact-text">Из магазина в Арзамасе, бесплатно.</span></span></a></li>
      <li><a class="hero__fact" href="<?= SITE_DIR ?>info/#delivery"><svg class="icon" aria-hidden="true"><use href="#i-truck"/></svg><span><span class="hero__fact-title">Доставка СДЭК</span><span class="hero__fact-text">В пункт выдачи или курьером по России.</span></span></a></li>
      <li><a class="hero__fact" href="<?= SITE_DIR ?>info/#payment"><svg class="icon" aria-hidden="true"><use href="#i-card"/></svg><span><span class="hero__fact-title">Оплата</span><span class="hero__fact-text">Картой и СБП онлайн или при получении.</span></span></a></li>
      <li><a class="hero__fact" href="<?= SITE_DIR ?>info/#return"><svg class="icon" aria-hidden="true"><use href="#i-return"/></svg><span><span class="hero__fact-title">Возврат и обмен</span><span class="hero__fact-text">По закону о защите прав потребителей.</span></span></a></li>
    </ul>
  </section>

  <section class="section" aria-labelledby="cats-title">
    <div class="section__head">
      <h2 class="section__title" id="cats-title">Популярные категории</h2>
      <a class="caps link-u" href="<?= SITE_DIR ?>catalog/">Весь каталог</a>
    </div>
    <div class="tiles">
      <?php foreach ($categoryTiles as $tile): ?>
      <a class="tile" href="<?= htmlspecialcharsbx($tile['url']) ?>"><span class="tile__photo photo"><?php if ($tile['photo'] !== ''): ?><img src="<?= htmlspecialcharsbx($tile['photo']) ?>" alt="" loading="lazy"><?php endif ?></span><span class="tile__name"><?= htmlspecialcharsbx($tile['name']) ?></span></a>
      <?php endforeach ?>
      <a class="tile tile--all" href="<?= SITE_DIR ?>catalog/"><span class="tile__photo"><span class="tile__all-text">Весь каталог</span><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></span></a>
    </div>
  </section>

  <section class="section" aria-labelledby="new-title">
    <div class="section__head">
      <h2 class="section__title" id="new-title">Новые поступления</h2>
      <a class="caps link-u" href="<?= SITE_DIR ?>catalog/">Смотреть все</a>
    </div>
    <?php
    // The home page shows products with a photo only; the full catalog shows everything.
    $GLOBALS['mirHomeFilter'] = ['!DETAIL_PICTURE' => false];
    $APPLICATION->IncludeComponent(
        'bitrix:catalog.section',
        'mir',
        [
            'FILTER_NAME' => 'mirHomeFilter',
            'IBLOCK_TYPE' => 'catalog',
            'IBLOCK_ID' => $store['catalog_iblock_id'],
            'SECTION_ID' => '',
            'SECTION_CODE' => '',
            'SHOW_ALL_WO_SECTION' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y',
            'ELEMENT_SORT_FIELD' => 'created',
            'ELEMENT_SORT_ORDER' => 'desc',
            'ELEMENT_SORT_FIELD2' => 'id',
            'ELEMENT_SORT_ORDER2' => 'desc',
            'PAGE_ELEMENT_COUNT' => '4',
            'PRICE_CODE' => [$store['price_code']],
            'PRICE_VAT_INCLUDE' => 'Y',
            'OFFERS_FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
            'OFFERS_PROPERTY_CODE' => array_merge($store['size_props'], $store['color_props']),
            'OFFERS_LIMIT' => '0',
            'OFFERS_SORT_FIELD' => 'sort',
            'OFFERS_SORT_ORDER' => 'asc',
            'HIDE_NOT_AVAILABLE' => $store['hide_unavailable'],
            'HIDE_NOT_AVAILABLE_OFFERS' => $store['hide_unavailable'],
            'DETAIL_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
            'SECTION_URL' => '/catalog/#SECTION_CODE#/',
            'BASKET_URL' => '/personal/cart/',
            'ACTION_VARIABLE' => 'action',
            'PRODUCT_ID_VARIABLE' => 'id',
            'SET_TITLE' => 'N',
            'SET_BROWSER_TITLE' => 'N',
            'SET_META_KEYWORDS' => 'N',
            'SET_META_DESCRIPTION' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'DISPLAY_TOP_PAGER' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
            'CACHE_GROUPS' => 'Y',
            'COMPATIBLE_MODE' => 'N',
        ],
        false
    );
    ?>
  </section>

  <section class="store" aria-labelledby="store-title">
    <div class="store__head">
      <svg class="icon store__pin" aria-hidden="true"><use href="#i-pin"/></svg>
      <h2 class="store__title" id="store-title">Магазин в Арзамасе</h2>
      <p class="store__caption">Магазин и пункт самовывоза. Заказ можно забрать в день оформления, если товар в наличии.</p>
      <div class="store__actions">
        <a class="btn btn--primary" href="https://yandex.ru/maps/?text=<?= rawurlencode('Арзамас, ' . ($contacts['address'] !== '' ? $contacts['address'] : 'Мир спорта')) ?>" target="_blank" rel="noopener">Построить маршрут</a>
        <a class="btn" href="<?= SITE_DIR ?>info/#contacts">Все контакты</a>
      </div>
    </div>
    <?php $storeRows = array_filter(['Адрес' => htmlspecialcharsbx($contacts['address']), 'Часы' => htmlspecialcharsbx($contacts['hours']), 'Телефон' => $contacts['phone'] !== '' ? \MirSport\link_or_text($contacts['phone'], $contacts['phone_href'], '') : '']); ?>
    <?php if ($storeRows): ?>
    <dl class="def-list store__list">
      <?php foreach ($storeRows as $label => $value): ?>
      <div><dt><?= $label ?></dt><dd><?= $value ?></dd></div>
      <?php endforeach ?>
    </dl>
    <?php endif ?>
  </section>

  <?php if ($homeBrands): ?>
  <section class="section" aria-labelledby="brands-title">
    <div class="section__head">
      <h2 class="section__title" id="brands-title">Бренды</h2>
    </div>
    <div class="brands">
      <?php foreach ($homeBrands as $brand): ?>
      <a class="brand" href="<?= htmlspecialcharsbx($brand['url']) ?>"><?= htmlspecialcharsbx($brand['name']) ?></a>
      <?php endforeach ?>
    </div>
  </section>
  <?php endif ?>

</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
