<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Избранное');
$APPLICATION->SetPageProperty('hide_h1', 'Y');

// Favorites live in the visitor's localStorage (no account); shell.js passes them here as ?ids=1,2,3.
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('ids'))))));
$ids = array_slice($ids, 0, 60);
?>
<div class="container page page--tight" data-fav-page>
  <nav aria-label="Хлебные крошки">
    <ol class="breadcrumbs"><li><a class="link-u" href="<?= SITE_DIR ?>">Главная</a></li><li aria-current="page">Избранное</li></ol>
  </nav>
  <div class="catalog-head">
    <h1 class="page-title">Избранное</h1>
    <span class="muted small" data-fav-page-count></span>
  </div>
  <?php if ($ids): ?>
  <div class="product-grid" data-fav-grid>
    <?php
    $GLOBALS['mirFavoritesFilter'] = ['ID' => $ids];
    $APPLICATION->IncludeComponent('bitrix:catalog.section', 'mir', [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => $store['catalog_iblock_id'],
        'SHOW_ALL_WO_SECTION' => 'Y',
        'INCLUDE_SUBSECTIONS' => 'Y',
        'FILTER_NAME' => 'mirFavoritesFilter',
        'PAGE_ELEMENT_COUNT' => '60',
        'ELEMENT_SORT_FIELD' => 'name',
        'ELEMENT_SORT_ORDER' => 'asc',
        'PRICE_CODE' => [$store['price_code']],
        'PRICE_VAT_INCLUDE' => 'Y',
        'OFFERS_FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
        'OFFERS_PROPERTY_CODE' => ['SIZES_SHOES', 'SIZES_CLOTHES', 'COLOR_REF'],
        'OFFERS_LIMIT' => '0',
        'DETAIL_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
        'SECTION_URL' => '/catalog/#SECTION_CODE#/',
        'BASKET_URL' => '/personal/cart/',
        'SET_TITLE' => 'N',
        'SET_BROWSER_TITLE' => 'N',
        'ADD_SECTIONS_CHAIN' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'N',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '3600',
        'CACHE_FILTER' => 'Y',
        'COMPATIBLE_MODE' => 'N',
        'MIR_WRAP_GRID' => 'N',
        'MIR_HIDE_EMPTY' => 'Y',
    ], false);
    ?>
  </div>
  <?php endif ?>
  <section class="empty"<?= $ids ? ' hidden' : '' ?> data-fav-empty>
    <svg class="icon" aria-hidden="true"><use href="#i-heart"/></svg>
    <h2 class="empty__title">В избранном пока пусто</h2>
    <p class="muted">Нажмите на сердечко у товара, чтобы сохранить его и вернуться к нему позже.</p>
    <a class="btn btn--primary" href="<?= SITE_DIR ?>catalog/">Перейти в каталог</a>
  </section>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
