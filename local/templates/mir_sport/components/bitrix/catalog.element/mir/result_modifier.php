<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Data for the prototype's product page: gallery, colors and sizes from the offers, prices, specs.
 * Offers keep their own stock (SKU level); the page only shows which color/size pairs can be bought.
 *
 * @var array $arResult
 * @var array $arParams
 */
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/format.php';

use function MirSport\{image, rub};

$sizeProps = ['SIZES_SHOES', 'SIZES_CLOTHES'];
$photoMain = [720, 960];
$photoThumb = [152, 203];

$photos = static function (array $element) use ($photoMain, $photoThumb): array {
    $ids = [];
    foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $field) {
        $id = is_array($element[$field] ?? null) ? (int)$element[$field]['ID'] : (int)($element[$field] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
            break;
        }
    }
    foreach ((array)($element['PROPERTIES']['MORE_PHOTO']['VALUE'] ?? []) as $id) {
        if ((int)$id > 0) {
            $ids[] = (int)$id;
        }
    }
    $result = [];
    foreach (array_unique($ids) as $id) {
        $result[] = ['main' => image($id, ...$photoMain), 'thumb' => image($id, ...$photoThumb)];
    }
    return $result;
};

$priceOf = static function (array $element): ?array {
    $row = $element['ITEM_PRICES'][$element['ITEM_PRICE_SELECTED'] ?? 0] ?? null;
    if (!$row) {
        return null;
    }
    $old = (float)$row['RATIO_BASE_PRICE'] > (float)$row['RATIO_PRICE'] ? rub((float)$row['RATIO_BASE_PRICE']) : '';
    return ['value' => (float)$row['RATIO_PRICE'], 'price' => rub((float)$row['RATIO_PRICE']), 'old' => $old];
};

$offers = [];
$colors = [];
$sizes = [];
$sizeCode = '';
foreach ($arResult['OFFERS'] ?? [] as $offer) {
    $color = (string)($offer['PROPERTIES']['COLOR_REF']['VALUE'] ?? '');
    $size = '';
    foreach ($sizeProps as $code) {
        $value = (string)($offer['PROPERTIES'][$code]['VALUE'] ?? '');
        if ($value !== '') {
            $size = $value;
            $sizeCode = $code;
            $sizes[$value] = (int)($offer['PROPERTIES'][$code]['VALUE_SORT'] ?? 0);
            break;
        }
    }
    if ($color !== '') {
        $colors[$color] = $colors[$color] ?? ['name' => $color, 'pict' => '', 'photos' => []];
        if (!$colors[$color]['photos']) {
            $colors[$color]['photos'] = $photos($offer);
        }
    }
    $price = $priceOf($offer);
    $offers[] = [
        'id' => (int)$offer['ID'],
        'color' => $color,
        'size' => $size,
        'canBuy' => !empty($offer['CAN_BUY']),
        'price' => $price['price'] ?? '',
        'old' => $price['old'] ?? '',
        'value' => $price['value'] ?? 0,
    ];
}
asort($sizes);

// Color names and swatch pictures come from the ColorReference highload block (the COLOR_REF directory).
if ($colors && Loader::includeModule('highloadblock')) {
    $block = HighloadBlockTable::getList(['filter' => ['=NAME' => 'ColorReference']])->fetch();
    if ($block) {
        $entity = HighloadBlockTable::compileEntity($block)->getDataClass();
        $rows = $entity::getList(['filter' => ['=UF_XML_ID' => array_keys($colors)], 'order' => ['UF_SORT' => 'ASC']]);
        $ordered = [];
        while ($row = $rows->fetch()) {
            $xml = $row['UF_XML_ID'];
            $ordered[$xml] = $colors[$xml];
            $ordered[$xml]['name'] = (string)$row['UF_NAME'];
            $ordered[$xml]['pict'] = !empty($row['UF_FILE']) ? image((int)$row['UF_FILE'], 112, 150) : '';
        }
        $colors = $ordered + $colors;
    }
}

// Default selection: the first color that has an offer in stock.
$defaultColor = '';
foreach ($offers as $offer) {
    if ($offer['canBuy'] && $offer['color'] !== '') {
        $defaultColor = $offer['color'];
        break;
    }
}
if ($defaultColor === '' && $colors) {
    $defaultColor = (string)array_key_first($colors);
}

// Price shown before a size is picked: the cheapest offer of the default color.
$start = null;
foreach ($offers as $offer) {
    if (($defaultColor === '' || $offer['color'] === $defaultColor) && $offer['price'] !== '' && ($start === null || $offer['value'] < $start['value'])) {
        $start = $offer;
    }
}
$own = $priceOf($arResult);

$gallery = $defaultColor !== '' && !empty($colors[$defaultColor]['photos']) ? $colors[$defaultColor]['photos'] : $photos($arResult);

$specs = [];
foreach ($arResult['DISPLAY_PROPERTIES'] ?? [] as $code => $prop) {
    if (in_array($code, ['ARTNUMBER', 'MORE_PHOTO', 'BRAND_REF'], true)) {
        continue;
    }
    $value = is_array($prop['DISPLAY_VALUE']) ? implode(', ', $prop['DISPLAY_VALUE']) : (string)$prop['DISPLAY_VALUE'];
    $value = trim(html_entity_decode(strip_tags($value)));
    if ($value !== '') {
        $specs[] = ['name' => (string)$prop['NAME'], 'value' => $value];
    }
}

$brand = trim(html_entity_decode(strip_tags((string)($arResult['DISPLAY_PROPERTIES']['BRAND_REF']['DISPLAY_VALUE'] ?? ''))));
if ($brand === '') {
    $brand = trim((string)($arResult['PROPERTIES']['MANUFACTURER']['~VALUE'] ?? ''));
}

$arResult['MIR'] = [
    'HAS_OFFERS' => (bool)$offers,
    'OFFERS' => $offers,
    'COLORS' => $colors,
    'SIZES' => array_keys($sizes),
    'SIZE_LABEL' => $sizeCode === 'SIZES_SHOES' ? 'Размер обуви (RU)' : 'Размер (RU)',
    'DEFAULT_COLOR' => $defaultColor,
    'PRICE' => $start['price'] ?? ($own['price'] ?? ''),
    'OLD_PRICE' => $start['old'] ?? ($own['old'] ?? ''),
    'PRICE_FROM' => $start && count(array_unique(array_column(array_filter($offers, static fn($o) => $o['color'] === $defaultColor), 'value'))) > 1,
    // A product with offers can be bought when at least one offer can.
    'CAN_BUY' => $offers ? in_array(true, array_column($offers, 'canBuy'), true) : !empty($arResult['CAN_BUY']),
    'GALLERY' => $gallery,
    'SPECS' => $specs,
    'BRAND' => $brand,
    'ARTICLE' => trim((string)($arResult['PROPERTIES']['ARTNUMBER']['~VALUE'] ?? '')),
    'DESCRIPTION' => $arResult['DETAIL_TEXT_TYPE'] === 'html' ? (string)$arResult['~DETAIL_TEXT'] : nl2br(htmlspecialcharsbx((string)$arResult['~DETAIL_TEXT'])),
];
