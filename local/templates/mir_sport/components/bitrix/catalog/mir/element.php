<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Product page in the prototype's layout (product.html): breadcrumbs, catalog.element, similar products.
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var CBitrixComponent $component
 * @global CMain $APPLICATION
 */
$this->setFrameMode(true);
$APPLICATION->SetPageProperty('hide_h1', 'Y');
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
?>
<div class="container page page--tight">
  <nav aria-label="Хлебные крошки">
    <?php $APPLICATION->IncludeComponent('bitrix:breadcrumb', 'mir', ['START_FROM' => '0', 'PATH' => '', 'SITE_ID' => SITE_ID], false, ['HIDE_ICONS' => 'Y']); ?>
  </nav>
<?php
$elementId = $APPLICATION->IncludeComponent('bitrix:catalog.element', 'mir', [
    'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
    'IBLOCK_ID' => $arParams['IBLOCK_ID'],
    'ELEMENT_ID' => $arResult['VARIABLES']['ELEMENT_ID'] ?? '',
    'ELEMENT_CODE' => $arResult['VARIABLES']['ELEMENT_CODE'] ?? '',
    'SECTION_ID' => $arResult['VARIABLES']['SECTION_ID'] ?? '',
    'SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'] ?? '',
    'PROPERTY_CODE' => array_values(array_unique(array_merge($arParams['DETAIL_PROPERTY_CODE'] ?? [], $store['article_props'], $store['brand_props'], ['MORE_PHOTO']))),
    'META_KEYWORDS' => $arParams['DETAIL_META_KEYWORDS'],
    'META_DESCRIPTION' => $arParams['DETAIL_META_DESCRIPTION'],
    'BROWSER_TITLE' => $arParams['DETAIL_BROWSER_TITLE'],
    'BASKET_URL' => $arParams['BASKET_URL'],
    'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
    'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
    'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
    'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
    'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
    'CACHE_TYPE' => $arParams['CACHE_TYPE'],
    'CACHE_TIME' => $arParams['CACHE_TIME'],
    'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
    'SET_TITLE' => $arParams['SET_TITLE'],
    'SET_BROWSER_TITLE' => 'N',
    'SET_META_KEYWORDS' => 'N',
    'SET_META_DESCRIPTION' => 'N',
    'SET_STATUS_404' => $arParams['SET_STATUS_404'],
    'SHOW_404' => $arParams['SHOW_404'] ?? 'N',
    'FILE_404' => $arParams['FILE_404'] ?? '',
    'MESSAGE_404' => $arParams['~MESSAGE_404'] ?? '',
    'PRICE_CODE' => $arParams['~PRICE_CODE'],
    'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
    'USE_PRODUCT_QUANTITY' => 'N',
    'OFFERS_CART_PROPERTIES' => $arParams['OFFERS_CART_PROPERTIES'] ?? [],
    'OFFERS_FIELD_CODE' => array_merge($arParams['DETAIL_OFFERS_FIELD_CODE'] ?? [], ['PREVIEW_PICTURE', 'DETAIL_PICTURE']),
    'OFFERS_PROPERTY_CODE' => $arParams['DETAIL_OFFERS_PROPERTY_CODE'] ?? [],
    'OFFERS_SORT_FIELD' => $arParams['OFFERS_SORT_FIELD'],
    'OFFERS_SORT_ORDER' => $arParams['OFFERS_SORT_ORDER'],
    'OFFERS_SORT_FIELD2' => $arParams['OFFERS_SORT_FIELD2'],
    'OFFERS_SORT_ORDER2' => $arParams['OFFERS_SORT_ORDER2'],
    'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'] ?? [],
    'SECTION_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'],
    'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['element'],
    'ADD_SECTIONS_CHAIN' => $arParams['ADD_SECTIONS_CHAIN'] ?? 'Y',
    'ADD_ELEMENT_CHAIN' => $arParams['ADD_ELEMENT_CHAIN'] ?? 'N',
    'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
    'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'] ?? 'N',
    'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'] ?? '',
    'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'] ?? '',
    'PRODUCT_DISPLAY_MODE' => 'Y',
    'COMPATIBLE_MODE' => 'N',
    'USE_ELEMENT_COUNTER' => $arParams['USE_ELEMENT_COUNTER'],
], $component);

if ((int)$elementId > 0) {
    $GLOBALS['mirSimilarFilter'] = ['!ID' => (int)$elementId];
    ?>
  <section class="section" aria-labelledby="similar-title">
    <h2 class="section__title" id="similar-title">Похожие товары</h2>
    <?php
    $APPLICATION->IncludeComponent('bitrix:catalog.section', 'mir', [
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        'SECTION_ID' => $arResult['VARIABLES']['SECTION_ID'] ?? '',
        'SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'] ?? '',
        'INCLUDE_SUBSECTIONS' => 'Y',
        'FILTER_NAME' => 'mirSimilarFilter',
        'ELEMENT_SORT_FIELD' => 'shows',
        'ELEMENT_SORT_ORDER' => 'desc',
        'PAGE_ELEMENT_COUNT' => '4',
        'PRICE_CODE' => $arParams['~PRICE_CODE'],
        'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
        'OFFERS_FIELD_CODE' => $arParams['LIST_OFFERS_FIELD_CODE'],
        'OFFERS_PROPERTY_CODE' => $arParams['LIST_OFFERS_PROPERTY_CODE'] ?? [],
        'OFFERS_LIMIT' => '0',
        'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
        'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'] ?? 'N',
        'SECTION_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'],
        'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['element'],
        'BASKET_URL' => $arParams['BASKET_URL'],
        'CACHE_TYPE' => $arParams['CACHE_TYPE'],
        'CACHE_TIME' => $arParams['CACHE_TIME'],
        'CACHE_FILTER' => 'Y',
        'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
        'SET_TITLE' => 'N',
        'SET_BROWSER_TITLE' => 'N',
        'SET_META_KEYWORDS' => 'N',
        'SET_META_DESCRIPTION' => 'N',
        'ADD_SECTIONS_CHAIN' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'N',
        'COMPATIBLE_MODE' => 'N',
        'MIR_HIDE_EMPTY' => 'Y',
    ], $component, ['HIDE_ICONS' => 'Y']);
    ?>
  </section>
    <?php
}
?>
</div>
