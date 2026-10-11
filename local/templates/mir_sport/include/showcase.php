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
