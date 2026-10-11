<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$APPLICATION->SetPageProperty('hide_h1', 'Y');

$query = trim((string)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('q'));
$APPLICATION->SetTitle($query !== '' ? 'Поиск: ' . $query : 'Поиск');

// Product ids found by the site search (module "search"), best matches first. Offers are not searched, only products.
$ids = [];
if ($query !== '' && \Bitrix\Main\Loader::includeModule('search')) {
    $search = new \CSearch();
    $search->Search([
        'QUERY' => $query,
        'SITE_ID' => SITE_ID,
        'MODULE_ID' => 'iblock',
        'PARAM2' => (string)$store['catalog_iblock_id'],
        'CHECK_DATES' => 'Y',
    ], ['RANK' => 'DESC']);
    while ($row = $search->Fetch()) {
        if (ctype_digit((string)$row['ITEM_ID'])) {
            $ids[(int)$row['ITEM_ID']] = true;
        }
        if (count($ids) >= 500) {
            break;
        }
    }
    $ids = array_keys($ids);
}
$GLOBALS['mirSearchFilter'] = ['ID' => $ids ?: [0]];
?>
<div class="container page page--tight">
  <nav aria-label="Хлебные крошки">
    <ol class="breadcrumbs"><li><a class="link-u" href="<?= SITE_DIR ?>">Главная</a></li><li aria-current="page">Поиск</li></ol>
  </nav>
  <div class="catalog-head">
    <h1 class="page-title"><?= $query !== '' ? 'Поиск: ' . htmlspecialcharsbx($query) : 'Поиск' ?></h1>
  </div>

  <?php if ($query === ''): ?>
  <div class="empty">
    <span class="empty__title">Введите, что ищете</span>
    <span class="muted">Название товара, бренд или артикул в строке поиска сверху.</span>
    <a class="btn btn--sm" href="<?= SITE_DIR ?>catalog/">Открыть каталог</a>
  </div>
  <?php else: ?>
  <?php
  $APPLICATION->IncludeComponent('bitrix:catalog.section', 'mir', [
      'IBLOCK_TYPE' => 'catalog',
      'IBLOCK_ID' => $store['catalog_iblock_id'],
      'SECTION_ID' => '',
      'SECTION_CODE' => '',
      'SHOW_ALL_WO_SECTION' => 'Y',
      'INCLUDE_SUBSECTIONS' => 'Y',
      'FILTER_NAME' => 'mirSearchFilter',
      'ELEMENT_SORT_FIELD' => 'shows',
      'ELEMENT_SORT_ORDER' => 'desc',
      'ELEMENT_SORT_FIELD2' => 'id',
      'ELEMENT_SORT_ORDER2' => 'desc',
      'PAGE_ELEMENT_COUNT' => '24',
      'PRICE_CODE' => [$store['price_code']],
      'PRICE_VAT_INCLUDE' => 'Y',
      'OFFERS_FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
      'OFFERS_PROPERTY_CODE' => array_merge($store['size_props'], $store['color_props']),
      'OFFERS_LIMIT' => '0',
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
      'DISPLAY_BOTTOM_PAGER' => 'Y',
      'PAGER_TEMPLATE' => 'mir',
      'CACHE_TYPE' => 'A',
      'CACHE_TIME' => '600',
      'CACHE_FILTER' => 'Y',
      'CACHE_GROUPS' => 'Y',
      'COMPATIBLE_MODE' => 'N',
      'MIR_RESET_URL' => SITE_DIR . 'catalog/',
      'MIR_EMPTY_TITLE' => 'Ничего не нашлось по запросу «' . $query . '»',
      'MIR_EMPTY_TEXT' => 'Проверьте написание или поищите по бренду. Товары, которых нет в наличии, не показываются.',
      'MIR_EMPTY_BUTTON' => 'Открыть каталог',
  ], false);
  ?>
  <?php endif ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
