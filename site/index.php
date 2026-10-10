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
$contacts = \MirSport\contacts();
?>
<div class="container page">

  <section class="hero">
    <span class="hero__photo-label">Фото: витрина магазина или съёмка сезона</span>
    <div class="hero__content">
      <span class="hero__eyebrow">Магазин в Арзамасе · доставка по России</span>
      <h1 class="hero__title">Спорттовары и всё для рыбалки</h1>
      <div class="hero__actions">
        <a class="btn btn--primary" href="<?= SITE_DIR ?>catalog/">Спорттовары</a>
        <a class="btn btn--light" href="<?= SITE_DIR ?>catalog/">Рыбалка</a>
      </div>
    </div>
  </section>

  <section class="section" aria-labelledby="sections-title">
    <h2 class="section__title" id="sections-title">Разделы</h2>
    <div class="gateways">
      <a class="gateway" href="<?= SITE_DIR ?>catalog/"><span class="gateway__photo photo photo--alt">Фото</span><span class="gateway__name">Мужчинам <span aria-hidden="true">→</span></span></a>
      <a class="gateway" href="<?= SITE_DIR ?>catalog/"><span class="gateway__photo photo">Фото</span><span class="gateway__name">Женщинам <span aria-hidden="true">→</span></span></a>
      <a class="gateway" href="<?= SITE_DIR ?>catalog/"><span class="gateway__photo photo">Фото</span><span class="gateway__name">Подросткам <span aria-hidden="true">→</span></span></a>
      <a class="gateway" href="<?= SITE_DIR ?>catalog/"><span class="gateway__photo photo photo--alt">Фото</span><span class="gateway__name">Рыбалка <span aria-hidden="true">→</span></span></a>
    </div>
  </section>

  <section class="section" aria-labelledby="cats-title">
    <div class="section__head">
      <h2 class="section__title" id="cats-title">Популярные категории</h2>
      <a class="caps link-u" href="<?= SITE_DIR ?>catalog/">Весь каталог</a>
    </div>
    <div class="tiles">
      <a class="tile" href="<?= SITE_DIR ?>catalog/shoes/"><span class="tile__photo photo"></span><span class="tile__name">Обувь</span></a>
      <a class="tile" href="<?= SITE_DIR ?>catalog/sportswear/"><span class="tile__photo photo"></span><span class="tile__name">Спортивная одежда</span></a>
      <a class="tile" href="<?= SITE_DIR ?>catalog/t-shirts/"><span class="tile__photo photo"></span><span class="tile__name">Футболки</span></a>
      <a class="tile" href="<?= SITE_DIR ?>catalog/pants/"><span class="tile__photo photo"></span><span class="tile__name">Штаны</span></a>
      <a class="tile" href="<?= SITE_DIR ?>catalog/accessories/"><span class="tile__photo photo"></span><span class="tile__name">Аксессуары</span></a>
      <a class="tile" href="<?= SITE_DIR ?>catalog/"><span class="tile__photo photo"></span><span class="tile__name">Весь каталог</span></a>
    </div>
  </section>

  <section class="section" aria-labelledby="new-title">
    <div class="section__head">
      <h2 class="section__title" id="new-title">Новые поступления</h2>
      <a class="caps link-u" href="<?= SITE_DIR ?>catalog/">Смотреть все</a>
    </div>
    <?php
    $APPLICATION->IncludeComponent(
        'bitrix:catalog.section',
        'mir',
        [
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
            'OFFERS_PROPERTY_CODE' => ['SIZES_SHOES', 'SIZES_CLOTHES', 'COLOR_REF'],
            'OFFERS_LIMIT' => '0',
            'OFFERS_SORT_FIELD' => 'sort',
            'OFFERS_SORT_ORDER' => 'asc',
            'HIDE_NOT_AVAILABLE' => 'N',
            'HIDE_NOT_AVAILABLE_OFFERS' => 'N',
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
    <div class="store__photo photo photo--alt">Фото магазина</div>
    <div class="store__info">
      <h2 class="store__title" id="store-title">Магазин в Арзамасе</h2>
      <dl class="def-list">
        <div><dt>Адрес</dt><dd><?= htmlspecialcharsbx($contacts['address']) ?></dd></div>
        <div><dt>Часы</dt><dd><?= htmlspecialcharsbx($contacts['hours']) ?></dd></div>
        <div><dt>Телефон</dt><dd><?= \MirSport\link_or_text($contacts['phone'], $contacts['phone_href'], '') ?></dd></div>
      </dl>
      <div class="store__actions">
        <a class="btn btn--primary" href="https://yandex.ru/maps/?text=<?= rawurlencode('Арзамас, ' . $contacts['address']) ?>" target="_blank" rel="noopener">Построить маршрут</a>
        <a class="btn" href="<?= SITE_DIR ?>info/#contacts">Все контакты</a>
      </div>
    </div>
  </section>

  <section class="terms" aria-label="Условия покупки">
    <a class="term" href="<?= SITE_DIR ?>info/#delivery"><svg class="icon"><use href="#i-store"/></svg><span class="term__title">Самовывоз</span><span class="term__text">Из магазина в Арзамасе, бесплатно.</span></a>
    <a class="term" href="<?= SITE_DIR ?>info/#delivery"><svg class="icon"><use href="#i-truck"/></svg><span class="term__title">Доставка СДЭК</span><span class="term__text">В пункт выдачи или курьером по России.</span></a>
    <a class="term" href="<?= SITE_DIR ?>info/#payment"><svg class="icon"><use href="#i-card"/></svg><span class="term__title">Оплата</span><span class="term__text">Картой и СБП онлайн или при получении.</span></a>
    <a class="term" href="<?= SITE_DIR ?>info/#return"><svg class="icon"><use href="#i-return"/></svg><span class="term__title">Возврат и обмен</span><span class="term__text">По закону о защите прав потребителей.</span></a>
  </section>

  <section class="section" aria-labelledby="brands-title">
    <div class="section__head">
      <h2 class="section__title" id="brands-title">Бренды</h2>
      <a class="caps link-u" href="<?= SITE_DIR ?>catalog/">Все бренды</a>
    </div>
    <div class="brands">
      <a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 1]</a><a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 2]</a><a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 3]</a>
      <a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 4]</a><a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 5]</a><a class="brand" href="<?= SITE_DIR ?>catalog/">[Бренд 6]</a>
    </div>
  </section>

</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
