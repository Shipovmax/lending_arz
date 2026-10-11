<?php
/** Read-only inventory of imported catalogs. Never exports customers or credentials. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\StoreTable;

try {
    foreach (['iblock', 'catalog'] as $module) {
        if (!Loader::includeModule($module)) {
            throw new RuntimeException('Required module is unavailable.');
        }
    }
    $report = [
        'mode' => 'read_only',
        'storefront_catalog_id' => (int)Option::get('main', 'mir_sport_catalog_iblock_id', '2'),
        'storefront_price_code' => Option::get('main', 'mir_sport_price_code', 'BASE'),
        'price_types' => GroupTable::getList(['select' => ['ID', 'NAME', 'BASE', 'XML_ID'], 'order' => ['ID' => 'ASC']])->fetchAll(),
        'warehouses' => StoreTable::getList(['select' => ['ID', 'TITLE', 'XML_ID', 'ACTIVE'], 'order' => ['ID' => 'ASC']])->fetchAll(),
        'catalogs' => [],
    ];
    $catalogs = CatalogIblockTable::getList([
        'select' => ['IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID'],
        'order' => ['IBLOCK_ID' => 'ASC'],
    ]);
    while ($catalog = $catalogs->fetch()) {
        $id = (int)$catalog['IBLOCK_ID'];
        $iblock = IblockTable::getRow(['filter' => ['=ID' => $id], 'select' => ['ID', 'NAME', 'XML_ID', 'ACTIVE']]);
        $summary = $iblock + [
            'parent_catalog_id' => (int)$catalog['PRODUCT_IBLOCK_ID'],
            'sku_link_property_id' => (int)$catalog['SKU_PROPERTY_ID'],
            'elements' => 0, 'active_elements' => 0, 'elements_with_images' => 0,
            'elements_with_prices' => 0, 'elements_with_positive_stock' => 0,
            'price_rows_by_type' => [], 'properties' => [],
        ];
        $elements = ElementTable::getList([
            'filter' => ['=IBLOCK_ID' => $id],
            'select' => ['ID', 'ACTIVE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
        ]);
        $ids = [];
        while ($element = $elements->fetch()) {
            $ids[] = (int)$element['ID'];
            $summary['elements']++;
            $summary['active_elements'] += $element['ACTIVE'] === 'Y' ? 1 : 0;
            $summary['elements_with_images'] += (!empty($element['PREVIEW_PICTURE']) || !empty($element['DETAIL_PICTURE'])) ? 1 : 0;
        }
        // Bounded SQL IN lists support larger 1C catalogs without per-product queries.
        foreach (array_chunk($ids, 500) as $chunk) {
            $priced = [];
            $prices = PriceTable::getList(['filter' => ['@PRODUCT_ID' => $chunk], 'select' => ['PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE']]);
            while ($price = $prices->fetch()) {
                $type = (int)$price['CATALOG_GROUP_ID'];
                $summary['price_rows_by_type'][$type] = ($summary['price_rows_by_type'][$type] ?? 0) + 1;
                if ((float)$price['PRICE'] > 0) {
                    $priced[(int)$price['PRODUCT_ID']] = true;
                }
            }
            $summary['elements_with_prices'] += count($priced);
            $summary['elements_with_positive_stock'] += ProductTable::getCount(['@ID' => $chunk, '>QUANTITY' => 0]);
        }
        $summary['properties'] = PropertyTable::getList([
            'filter' => ['=IBLOCK_ID' => $id],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'USER_TYPE', 'MULTIPLE', 'ACTIVE'],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();
        $report['catalogs'][] = $summary;
    }
    $report['limitations'] = [
        'Nonzero prices and stock do not establish completeness or agreement with 1C.',
        'Counts include inactive elements; active counts are shown separately.',
        'Product QUANTITY is aggregate stock, not a warehouse allocation.',
        'Image counts cover only preview/detail images, not additional image properties.',
    ];
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    // Do not print exception details: database errors may contain connection information.
    fwrite(STDERR, "Catalog inventory failed; review the private server log.\n");
    exit(1);
}
