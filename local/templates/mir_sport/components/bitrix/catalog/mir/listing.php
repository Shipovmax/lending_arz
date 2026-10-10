<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Catalog listing in the prototype's layout (catalog.html): breadcrumbs, title with the count,
 * toolbar with sorting, filters on the left (sections + smart filter), product grid, pagination.
 * Used by sections.php (the whole catalog) and section.php (one section).
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var CBitrixComponent $component
 * @global CMain $APPLICATION
 */
use Bitrix\Main\Application;

$this->setFrameMode(true);
$APPLICATION->SetPageProperty('hide_h1', 'Y');

$request = Application::getInstance()->getContext()->getRequest();
$sectionCode = (string)($arResult['VARIABLES']['SECTION_CODE'] ?? '');
$sectionUrl = $arResult['FOLDER'] . ($sectionCode !== '' ? $sectionCode . '/' : '');
$filterName = 'mirFilter';

$priceId = 0;
if (\Bitrix\Main\Loader::includeModule('catalog')) {
    $priceCodes = $arParams['~PRICE_CODE'] ?? $arParams['PRICE_CODE'] ?? [];
    $priceGroup = \Bitrix\Catalog\GroupTable::getRow([
        'select' => ['ID'], 'filter' => ['=NAME' => (string)($priceCodes[0] ?? '')],
        'cache' => ['ttl' => 3600],
    ]);
    $priceId = (int)($priceGroup['ID'] ?? 0);
}
$priceSortField = $priceId > 0 ? 'catalog_PRICE_' . $priceId : 'name';
$sorts = [
    'popular' => ['Популярные', 'shows', 'desc'],
    'cheap' => ['Сначала дешевле', $priceSortField, 'asc'],
    'expensive' => ['Сначала дороже', $priceSortField, 'desc'],
    'new' => ['Новинки', 'created', 'desc'],
];
$sort = (string)$request->get('sort');
if (!isset($sorts[$sort])) {
    $sort = 'popular';
}
$inStock = $request->get('stock') === 'Y';

// Current section id for the smart filter (by code).
$sectionId = 0;
if ($sectionCode !== '' && \Bitrix\Main\Loader::includeModule('iblock')) {
    $row = \Bitrix\Iblock\SectionTable::getRow([
        'select' => ['ID'],
        'filter' => ['=IBLOCK_ID' => (int)$arParams['IBLOCK_ID'], '=CODE' => $sectionCode, '=ACTIVE' => 'Y'],
        'cache' => ['ttl' => 3600],
    ]);
    $sectionId = (int)($row['ID'] ?? 0);
}

$keepParams = static function (array $override) use ($request): string {
    $query = $request->getQueryList()->toArray();
    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($query[$k]);
        } else {
            $query[$k] = $v;
        }
    }
    unset($query['PAGEN_1']);
    return $query ? '?' . http_build_query($query) : '';
};
?>
<div class="container page page--tight">
  <nav aria-label="Хлебные крошки">
    <?php $APPLICATION->IncludeComponent('bitrix:breadcrumb', 'mir', ['START_FROM' => '0', 'PATH' => '', 'SITE_ID' => SITE_ID], false, ['HIDE_ICONS' => 'Y']); ?>
  </nav>

  <div class="catalog-head">
    <h1 class="page-title"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <span class="muted small"><?php $APPLICATION->ShowViewContent('mir_catalog_count') ?></span>
  </div>

  <div class="toolbar">
    <div class="toolbar__group toolbar__group--quick">
      <button class="pill pill--keep filters-toggle" type="button" aria-expanded="false" aria-controls="filters" data-filters-toggle><svg class="icon icon--sm"><use href="#i-filter"/></svg>Фильтры</button>
      <a class="pill pill--keep" role="button" href="<?= htmlspecialcharsbx($sectionUrl . $keepParams(['stock' => $inStock ? null : 'Y'])) ?>" aria-pressed="<?= $inStock ? 'true' : 'false' ?>">В наличии</a>
    </div>
    <form class="sort" method="get" action="<?= htmlspecialcharsbx($sectionUrl) ?>" data-sort-form>
      <?php foreach ($request->getQueryList()->toArray() as $k => $v):
          if ($k === 'sort' || $k === 'PAGEN_1' || is_array($v)) { continue; } ?>
      <input type="hidden" name="<?= htmlspecialcharsbx($k) ?>" value="<?= htmlspecialcharsbx($v) ?>">
      <?php endforeach ?>
      <label class="sort">Сортировка
        <select class="select" name="sort" data-sort-select>
          <?php foreach ($sorts as $code => [$label]): ?>
          <option value="<?= $code ?>"<?= $code === $sort ? ' selected' : '' ?>><?= $label ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <noscript><button class="btn btn--sm" type="submit">Показать</button></noscript>
    </form>
  </div>

  <div class="catalog-layout">
    <aside class="filters" id="filters" aria-label="Фильтры" data-filters>
      <?php
      $APPLICATION->IncludeComponent('bitrix:catalog.section.list', 'mir', [
          'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
          'IBLOCK_ID' => $arParams['IBLOCK_ID'],
          'SECTION_ID' => '',
          'SECTION_CODE' => '',
          'TOP_DEPTH' => '1',
          'COUNT_ELEMENTS' => 'Y',
          'COUNT_ELEMENTS_FILTER' => 'CNT_ACTIVE',
          'SECTION_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'],
          'ADD_SECTIONS_CHAIN' => 'N',
          'CACHE_TYPE' => $arParams['CACHE_TYPE'],
          'CACHE_TIME' => $arParams['CACHE_TIME'],
          'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
          'MIR_CURRENT_CODE' => $sectionCode,
          'MIR_ALL_URL' => $arResult['FOLDER'],
      ], $component, ['HIDE_ICONS' => 'Y']);

      $APPLICATION->IncludeComponent('bitrix:catalog.smart.filter', 'mir', [
          'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
          'IBLOCK_ID' => $arParams['IBLOCK_ID'],
          'SECTION_ID' => $sectionId,
          'FILTER_NAME' => $filterName,
          'PRICE_CODE' => $arParams['~PRICE_CODE'],
          'CACHE_TYPE' => $arParams['CACHE_TYPE'],
          'CACHE_TIME' => $arParams['CACHE_TIME'],
          'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
          'SAVE_IN_SESSION' => 'N',
          'XML_EXPORT' => 'N',
          'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
          'SEF_MODE' => 'N',
          'DISPLAY_ELEMENT_COUNT' => 'Y',
          'MIR_RESET_URL' => $sectionUrl,
          'MIR_KEEP' => ['sort' => $sort !== 'popular' ? $sort : null, 'stock' => $inStock ? 'Y' : null],
      ], $component, ['HIDE_ICONS' => 'Y']);
      ?>
    </aside>

    <div class="catalog-main">
      <?php
      if ($inStock) {
          $GLOBALS[$filterName]['=AVAILABLE'] = 'Y';
      }
      [, $sortField, $sortOrder] = $sorts[$sort];

      $APPLICATION->IncludeComponent('bitrix:catalog.section', 'mir', [
          'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
          'IBLOCK_ID' => $arParams['IBLOCK_ID'],
          'SECTION_ID' => $arResult['VARIABLES']['SECTION_ID'] ?? '',
          'SECTION_CODE' => $sectionCode,
          'SHOW_ALL_WO_SECTION' => $sectionCode === '' ? 'Y' : 'N',
          'INCLUDE_SUBSECTIONS' => 'Y',
          'ELEMENT_SORT_FIELD' => $sortField,
          'ELEMENT_SORT_ORDER' => $sortOrder,
          'ELEMENT_SORT_FIELD2' => 'id',
          'ELEMENT_SORT_ORDER2' => 'desc',
          'FILTER_NAME' => $filterName,
          'PAGE_ELEMENT_COUNT' => '24',
          'PRICE_CODE' => $arParams['~PRICE_CODE'],
          'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
          'OFFERS_FIELD_CODE' => $arParams['LIST_OFFERS_FIELD_CODE'],
          'OFFERS_PROPERTY_CODE' => $arParams['LIST_OFFERS_PROPERTY_CODE'] ?? [],
          'OFFERS_LIMIT' => '0',
          'OFFERS_SORT_FIELD' => $arParams['OFFERS_SORT_FIELD'],
          'OFFERS_SORT_ORDER' => $arParams['OFFERS_SORT_ORDER'],
          'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
          'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'] ?? 'N',
          'SECTION_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'],
          'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['element'],
          'BASKET_URL' => $arParams['BASKET_URL'],
          'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
          'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
          'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
          'CACHE_TYPE' => $arParams['CACHE_TYPE'],
          'CACHE_TIME' => $arParams['CACHE_TIME'],
          'CACHE_FILTER' => $arParams['CACHE_FILTER'],
          'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
          'SET_TITLE' => $sectionCode !== '' ? $arParams['SET_TITLE'] : 'N',
          // The demo's SEO templates name another shop; the browser title is the section name.
          'SET_BROWSER_TITLE' => 'N',
          'SET_META_KEYWORDS' => 'N',
          'SET_META_DESCRIPTION' => 'N',
          'SET_STATUS_404' => $arParams['SET_STATUS_404'],
          'SHOW_404' => $arParams['SHOW_404'] ?? 'N',
          'FILE_404' => $arParams['FILE_404'] ?? '',
          'MESSAGE_404' => $arParams['~MESSAGE_404'] ?? '',
          'ADD_SECTIONS_CHAIN' => 'Y',
          'DISPLAY_TOP_PAGER' => 'N',
          'DISPLAY_BOTTOM_PAGER' => 'Y',
          'PAGER_TEMPLATE' => 'mir',
          'PAGER_SHOW_ALWAYS' => 'N',
          'COMPATIBLE_MODE' => 'N',
          'MIR_RESET_URL' => $sectionUrl,
      ], $component);
      ?>
    </div>
  </div>
</div>
