<?php
namespace MirSport;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once __DIR__ . '/format.php';

/**
 * Category tiles for the home page, built from the real catalog so a 1C import changes them without code edits.
 * Sections: option main/mir_sport_home_sections (comma-separated XML_ID or CODE, in display order); when empty,
 * the first top-level sections by SORT. Only active sections that exist are returned.
 * Photo: the newest active product with a picture inside the section subtree, else the section picture.
 * Returns a list of ['name' => string, 'url' => string, 'photo' => string]. Cleared by the iblock tagged cache.
 */
function section_tiles(int $iblockId, int $limit = 5, int $size = 480): array
{
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }
    $wanted = array_values(array_filter(array_map('trim', explode(',', Option::get('main', 'mir_sport_home_sections', '')))));
    $cache = Cache::createInstance();
    $dir = '/mir_sport/section_tiles';
    if ($cache->initCache(3600, implode('|', [$iblockId, $limit, $size, SITE_ID, implode(',', $wanted)]), $dir)) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $tagged = Application::getInstance()->getTaggedCache();
    $tagged->startTagCache($dir);
    $tagged->registerTag('iblock_id_' . $iblockId);

    $filter = ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y', '=GLOBAL_ACTIVE' => 'Y'];
    if ($wanted) {
        $filter[] = ['LOGIC' => 'OR', '@XML_ID' => $wanted, '@CODE' => $wanted];
    } else {
        $filter['=DEPTH_LEVEL'] = 1;
    }
    $sections = SectionTable::getList([
        'select' => ['ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'PICTURE', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_SECTION_ID'],
        'filter' => $filter,
        'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
    ])->fetchAll();
    if ($wanted) {
        $rank = array_flip($wanted);
        usort($sections, static fn($a, $b) => ($rank[$a['XML_ID']] ?? $rank[$a['CODE']] ?? PHP_INT_MAX)
            <=> ($rank[$b['XML_ID']] ?? $rank[$b['CODE']] ?? PHP_INT_MAX));
    }
    $sections = array_slice($sections, 0, $limit);

    $urlTemplate = (string)(IblockTable::getList([
        'select' => ['SECTION_PAGE_URL'],
        'filter' => ['=ID' => $iblockId],
    ])->fetch()['SECTION_PAGE_URL'] ?? '');

    $result = [];
    foreach ($sections as $section) {
        $element = ElementTable::getList([
            'select' => ['DETAIL_PICTURE', 'PREVIEW_PICTURE'],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=ACTIVE' => 'Y',
                '=IBLOCK_SECTION.GLOBAL_ACTIVE' => 'Y',
                '>=IBLOCK_SECTION.LEFT_MARGIN' => $section['LEFT_MARGIN'],
                '<=IBLOCK_SECTION.RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
                ['LOGIC' => 'OR', '!=DETAIL_PICTURE' => null, '!=PREVIEW_PICTURE' => null],
            ],
            'order' => ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'],
            'limit' => 1,
        ])->fetch();
        if (!$element && !(int)\CIBlockSection::GetSectionElementsCount((int)$section['ID'], ['CNT_ACTIVE' => 'Y'])) {
            continue; // an empty 1C group is not a category to show
        }
        $photo = $element ? image($element['DETAIL_PICTURE'] ?: $element['PREVIEW_PICTURE'], $size, $size) : '';
        if ($photo === '') {
            $photo = image($section['PICTURE'], $size, $size);
        }
        $url = $urlTemplate !== ''
            ? \CIBlock::ReplaceSectionUrl($urlTemplate, $section, true, 'S')
            : '';
        $result[] = [
            'name' => (string)$section['NAME'],
            'url' => $url !== '' ? $url : SITE_DIR . 'catalog/',
            'photo' => $photo,
        ];
    }

    $tagged->endTagCache();
    $cache->endDataCache($result);
    return $result;
}

/**
 * Codes of the catalog's own properties for the product page specs: everything except the service properties
 * that 1C and the wizard create (CML2_*, photos, files). Cached with the iblock's tagged cache.
 */
function catalog_property_codes(int $iblockId): array
{
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }
    $cache = Cache::createInstance();
    $dir = '/mir_sport/property_codes';
    if ($cache->initCache(3600, 'props_' . $iblockId, $dir)) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $tagged = Application::getInstance()->getTaggedCache();
    $tagged->startTagCache($dir);
    $tagged->registerTag('iblock_id_' . $iblockId);

    $codes = [];
    $rows = \Bitrix\Iblock\PropertyTable::getList([
        'select' => ['CODE'],
        'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y', '!=CODE' => false],
        'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
    ]);
    while ($row = $rows->fetch()) {
        $code = (string)$row['CODE'];
        if ($code === '' || str_starts_with($code, 'CML2_') || in_array($code, ['MORE_PHOTO', 'FILES'], true)) {
            continue;
        }
        $codes[] = $code;
    }

    $tagged->endTagCache();
    $cache->endDataCache($codes);
    return $codes;
}

/**
 * Catalog sections for the menus: active sections up to $depth levels that contain active products,
 * as a tree [['name', 'url', 'count', 'children' => [...]]]. Built from the 1C groups, so the menu follows the catalog.
 */
function catalog_sections(int $iblockId, int $depth = 2): array
{
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }
    $cache = Cache::createInstance();
    $dir = '/mir_sport/catalog_sections';
    if ($cache->initCache(3600, 'sections_' . $iblockId . '_' . $depth . '_' . SITE_ID, $dir)) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $tagged = Application::getInstance()->getTaggedCache();
    $tagged->startTagCache($dir);
    $tagged->registerTag('iblock_id_' . $iblockId);

    $tree = [];
    $byId = [];
    $rows = \CIBlockSection::GetList(
        ['LEFT_MARGIN' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', '<=DEPTH_LEVEL' => $depth, 'CNT_ACTIVE' => 'Y'],
        true,
        ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'SECTION_PAGE_URL', 'CODE']
    );
    while ($row = $rows->GetNext()) {
        if ((int)$row['ELEMENT_CNT'] <= 0) {
            continue;
        }
        $node = ['name' => (string)$row['~NAME'], 'url' => (string)$row['SECTION_PAGE_URL'], 'count' => (int)$row['ELEMENT_CNT'], 'children' => []];
        $parent = (int)$row['IBLOCK_SECTION_ID'];
        if ((int)$row['DEPTH_LEVEL'] === 1) {
            $tree[(int)$row['ID']] = $node;
            $byId[(int)$row['ID']] = &$tree[(int)$row['ID']];
        } elseif (isset($byId[$parent])) {
            $byId[$parent]['children'][] = $node;
        }
    }
    unset($byId);
    $tree = array_values($tree);

    $tagged->endTagCache();
    $cache->endDataCache($tree);
    return $tree;
}

/**
 * Brands of the products on sale, with links to the catalog filtered by the brand:
 * [['name', 'url', 'count']], the most stocked brands first. The link uses the smart filter's GET names
 * (<filter name>_<property id>_<crc32 of the list value id>), the filter name is set in catalog/mir/listing.php.
 */
function brand_links(int $iblockId, array $codes, bool $onlyAvailable = true, int $limit = 0): array
{
    if ($iblockId <= 0 || !$codes || !Loader::includeModule('iblock')) {
        return [];
    }
    $cache = Cache::createInstance();
    $dir = '/mir_sport/brand_links';
    if ($cache->initCache(3600, implode('|', [$iblockId, implode(',', $codes), (int)$onlyAvailable, $limit, SITE_ID]), $dir)) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $tagged = Application::getInstance()->getTaggedCache();
    $tagged->startTagCache($dir);
    $tagged->registerTag('iblock_id_' . $iblockId);

    $result = [];
    $property = \Bitrix\Iblock\PropertyTable::getList([
        'select' => ['ID', 'CODE'],
        'filter' => ['=IBLOCK_ID' => $iblockId, '@CODE' => $codes, '=PROPERTY_TYPE' => 'L', '=ACTIVE' => 'Y'],
    ])->fetchAll();
    usort($property, static fn($a, $b) => array_search($a['CODE'], $codes, true) <=> array_search($b['CODE'], $codes, true));
    $property = $property[0] ?? null;
    if ($property) {
        $filter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'];
        if ($onlyAvailable) {
            $filter['AVAILABLE'] = 'Y';
        }
        $code = $property['CODE'];
        $rows = \CIBlockElement::GetList([], $filter, ['PROPERTY_' . $code]);
        while ($row = $rows->Fetch()) {
            $enumId = (int)($row['PROPERTY_' . $code . '_ENUM_ID'] ?? 0);
            $name = trim((string)($row['PROPERTY_' . $code . '_VALUE'] ?? ''));
            if ($enumId <= 0 || $name === '') {
                continue;
            }
            $result[] = [
                'name' => $name,
                'url' => SITE_DIR . 'catalog/?mirFilter_' . (int)$property['ID'] . '_' . abs(crc32((string)$enumId)) . '=Y&set_filter=Y',
                'count' => (int)$row['CNT'],
            ];
        }
        usort($result, static fn($a, $b) => $b['count'] <=> $a['count'] ?: strnatcasecmp($a['name'], $b['name']));
        if ($limit > 0) {
            $result = array_slice($result, 0, $limit);
        }
    }

    $tagged->endTagCache();
    $cache->endDataCache($result);
    return $result;
}
