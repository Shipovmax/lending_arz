<?php
/**
 * Prepares the catalog that 1C created for the storefront. Run after an import that brought new properties:
 *   docker compose exec -T php php /var/www/html/local/tools/setup-1c-catalog.php
 * - product properties (except CML2_* service ones): shown in the product's characteristics;
 * - offer properties listed in local/config/store.php (sizes, colors): shown, used to pick an offer, sent to the basket;
 *   sizes and list-type product properties also go to the smart filter;
 * - rebuilds the facet index and clears the cache.
 * Changes only property settings, not products, prices or stock.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
while (ob_get_level()) {
    ob_end_flush();
}

use Bitrix\Catalog\Product\PropertyCatalogFeature;
use Bitrix\Iblock\Model\PropertyFeature;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$productsId = (int)$store['catalog_iblock_id'];
$sku = \CCatalogSku::GetInfoByProductIBlock($productsId);
$offersId = $sku ? (int)$sku['IBLOCK_ID'] : 0;
echo "products iblock $productsId, offers iblock $offersId\n";

// Offers whose size is only in the name get a real "Размер" property first (see the script for the rules).
if (is_file(__DIR__ . '/fill-sizes-from-names.php')) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/fill-sizes-from-names.php'));
    $store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
}

// Readable color values and name everywhere (product page, filter, basket, order, e-mails):
// "Цвет поставщика: цв.синий" -> "Цвет: синий". 1C writes them like that on every import, so this runs each time.
if ($offersId > 0) {
    global $DB;
    foreach ($store['color_props'] as $code) {
        $row = $DB->Query("select ID, NAME, PROPERTY_TYPE from b_iblock_property where IBLOCK_ID=" . (int)$offersId . " and CODE='" . $DB->ForSql($code) . "'")->Fetch();
        if (!$row) {
            continue;
        }
        if (preg_match('/^цвет\s+\S+/iu', (string)$row['NAME'])) {
            (new \CIBlockProperty())->Update((int)$row['ID'], ['NAME' => 'Цвет']);
        }
        if ($row['PROPERTY_TYPE'] === 'L') {
            $fixed = 0;
            $enums = $DB->Query("select ID, VALUE from b_iblock_property_enum where PROPERTY_ID=" . (int)$row['ID']);
            while ($e = $enums->Fetch()) {
                $clean = trim((string)preg_replace('/^цв\.?\s*/iu', '', (string)$e['VALUE']));
                if ($clean !== '' && $clean !== $e['VALUE']) {
                    \CIBlockPropertyEnum::Update((int)$e['ID'], ['VALUE' => $clean]);
                    $fixed++;
                }
            }
            echo "  color $code: name 'Цвет', cleaned values: $fixed\n";
        }
    }
}

$feature = static fn(string $module, string $id): array => ['MODULE_ID' => $module, 'FEATURE_ID' => $id, 'IS_ENABLED' => 'Y'];
$detail = $feature('iblock', PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW);
$list = $feature('iblock', PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW);
$tree = $feature('catalog', PropertyCatalogFeature::FEATURE_ID_OFFER_TREE_PROPERTY);
$basket = $feature('catalog', PropertyCatalogFeature::FEATURE_ID_BASKET_PROPERTY);
$smart = static function (int $propertyId, bool $on): void {
    \CIBlockSectionPropertyLink::Set(0, $propertyId, ['SMART_FILTER' => $on ? 'Y' : 'N', 'DISPLAY_TYPE' => 'F', 'DISPLAY_EXPANDED' => 'N', 'FILTER_HINT' => '']);
};
$ib = new \CIBlock();

// New offer properties from 1C named "Размер..." or "Цвет..." (new product groups: clothes, shoes, ...) join the
// size/color lists automatically, so their products get offer selection and the filter without code changes.
if ($offersId > 0) {
    $sizes = $store['size_props'];
    $colors = $store['color_props'];
    $rows = PropertyTable::getList([
        'select' => ['ID', 'CODE', 'NAME'],
        'filter' => ['=IBLOCK_ID' => $offersId, '=ACTIVE' => 'Y', '!=CODE' => false],
    ]);
    while ($p = $rows->fetch()) {
        $code = (string)$p['CODE'];
        if ($code === '' || str_starts_with($code, 'CML2_') || in_array($code, $sizes, true) || in_array($code, $colors, true)) {
            continue;
        }
        if (preg_match('/^размер/iu', (string)$p['NAME'])) {
            $sizes[] = $code;
            echo "  new size property: $code ({$p['NAME']})\n";
        } elseif (preg_match('/^цвет/iu', (string)$p['NAME'])) {
            $colors[] = $code;
            echo "  new color property: $code ({$p['NAME']})\n";
        }
    }
    if ($sizes !== $store['size_props']) {
        \Bitrix\Main\Config\Option::set('main', 'mir_sport_size_props', implode(',', $sizes));
    }
    if ($colors !== $store['color_props']) {
        \Bitrix\Main\Config\Option::set('main', 'mir_sport_color_props', implode(',', $colors));
    }
    $store['size_props'] = $sizes;
    $store['color_props'] = $colors;
}

// URL templates: a 1C import can leave them empty, and then menus and tiles get empty links.
$ib->Update($productsId, [
    'LIST_PAGE_URL' => '#SITE_DIR#/catalog/',
    'SECTION_PAGE_URL' => '#SITE_DIR#/catalog/#SECTION_CODE#/',
    'DETAIL_PAGE_URL' => '#SITE_DIR#/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
]);
echo "  url templates set for iblock $productsId\n";

foreach (array_filter([$productsId, $offersId]) as $iblockId) {
    $ib->Update($iblockId, ['SECTION_PROPERTY' => 'Y', 'PROPERTY_INDEX' => 'Y']);
    $rows = PropertyTable::getList([
        'select' => ['ID', 'CODE', 'PROPERTY_TYPE'],
        'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
    ]);
    while ($p = $rows->fetch()) {
        $code = (string)$p['CODE'];
        if ($code === '' || $code === 'FILES' || (str_starts_with($code, 'CML2_') && $code !== 'CML2_ARTICLE')) {
            continue;
        }
        $isList = $p['PROPERTY_TYPE'] === PropertyTable::TYPE_LIST;
        $features = [$detail];
        $inFilter = false;
        if ($iblockId === $offersId) {
            $isSize = in_array($code, $store['size_props'], true);
            $isColor = in_array($code, $store['color_props'], true);
            if (!$isSize && !$isColor && $code !== 'MORE_PHOTO') {
                continue;
            }
            if ($isSize || $isColor) {
                $features = [$detail, $list, $basket];
                if ($isList) {
                    $features[] = $tree; // offer selection works with list properties only
                }
                $inFilter = $isSize;
            }
        } else {
            $inFilter = $isList && !in_array($code, ['STRANA_PROIZVODSTVA', 'MATERIAL'], true);
        }
        PropertyFeature::updateFeatures((int)$p['ID'], $features);
        if ($code !== 'MORE_PHOTO') {
            $smart((int)$p['ID'], $inFilter);
        }
        echo "  $iblockId $code" . ($inFilter ? ' [filter]' : '') . "\n";
    }
}

$indexer = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($productsId);
$indexer->startIndex();
$total = 0;
for ($i = 0; $i < 1000; $i++) {
    $n = (int)$indexer->continueIndex(0);
    $total += $n;
    if ($n === 0) {
        break;
    }
}
$indexer->endIndex();
echo "facet index: $total products\n";
foreach (array_filter([$productsId, $offersId]) as $iblockId) {
    \CIBlock::clearIblockTagCache($iblockId);
}
BXClearCache(true);
\Bitrix\Main\Application::getInstance()->getManagedCache()->cleanAll();
echo "cache cleared\n";
