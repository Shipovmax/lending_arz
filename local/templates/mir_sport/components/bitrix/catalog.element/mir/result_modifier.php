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

use function MirSport\{image, rub, size_labels};

// Which offer properties carry the size and the color is configured in local/config/store.php.
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$sizeProps = $store['size_props'];
$colorProps = $store['color_props'];
// Twice the gallery's size, so photos stay sharp on high-density screens.
$photoMain = [1200, 1600];
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
    if (!$row || (float)$row['RATIO_PRICE'] <= 0) {
        return null;
    }
    $old = (float)$row['RATIO_BASE_PRICE'] > (float)$row['RATIO_PRICE'] ? rub((float)$row['RATIO_BASE_PRICE']) : '';
    return ['value' => (float)$row['RATIO_PRICE'], 'price' => rub((float)$row['RATIO_PRICE']), 'old' => $old];
};

$offers = [];
$colors = [];
$sizes = [];
$sizeCode = '';
$sizeName = '';
foreach ($arResult['OFFERS'] ?? [] as $offer) {
    $color = '';
    foreach ($colorProps as $code) {
        $color = trim((string)($offer['PROPERTIES'][$code]['VALUE'] ?? ''));
        if ($color !== '') {
            break;
        }
    }
    $size = '';
    foreach ($sizeProps as $code) {
        $value = (string)($offer['PROPERTIES'][$code]['VALUE'] ?? '');
        if ($value !== '') {
            $size = $value;
            $sizeCode = $code;
            $sizeName = trim((string)($offer['PROPERTIES'][$code]['NAME'] ?? ''));
            $sizes[$value] = (int)($offer['PROPERTIES'][$code]['VALUE_SORT'] ?? 0);
            break;
        }
    }
    if ($color !== '') {
        // 1C color names come as "цв.зеленый": show just "зеленый".
        $colorName = trim((string)preg_replace('/^цв\.?\s*/iu', '', $color));
        $colors[$color] = $colors[$color] ?? ['name' => $colorName !== '' ? $colorName : $color, 'pict' => '', 'photos' => []];
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
$sizeLabels = \MirSport\size_labels(array_keys($sizes));
// Sizes in natural order (10,5 < 14 < 14,5); the catalog's own sort order is kept when it is set.
$sizeKeys = array_keys($sizes);
if (count(array_unique($sizes)) <= 1) {
    $sizeKeys = array_keys($sizeLabels);
}

// Color names and swatch pictures come from the ColorReference highload block (the COLOR_REF directory).
// A plain list value (1C) has no directory row: the swatch then shows the first photo of that color.
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
foreach ($colors as $xml => $color) {
    if (($color['pict'] ?? '') === '' && !empty($color['photos'][0]['thumb'])) {
        $colors[$xml]['pict'] = $color['photos'][0]['thumb'];
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
    if (in_array($code, array_merge($store['article_props'], $store['brand_props'], ['MORE_PHOTO']), true)) {
        continue;
    }
    $value = is_array($prop['DISPLAY_VALUE']) ? implode(', ', $prop['DISPLAY_VALUE']) : (string)$prop['DISPLAY_VALUE'];
    $value = trim(html_entity_decode(strip_tags($value)));
    if ($value !== '') {
        $specs[] = ['name' => (string)$prop['NAME'], 'value' => $value];
    }
}

$brand = '';
foreach ($store['brand_props'] as $code) {
    $value = $arResult['DISPLAY_PROPERTIES'][$code]['DISPLAY_VALUE'] ?? '';
    $brand = trim(html_entity_decode(strip_tags(is_array($value) ? implode(', ', $value) : (string)$value)));
    if ($brand !== '') {
        break;
    }
}
if ($brand === '') {
    $brand = trim((string)($arResult['PROPERTIES']['MANUFACTURER']['~VALUE'] ?? ''));
}
$article = '';
foreach ($store['article_props'] as $code) {
    $article = trim((string)($arResult['PROPERTIES'][$code]['~VALUE'] ?? ''));
    if ($article !== '') {
        break;
    }
}

$arResult['MIR'] = [
    'HAS_OFFERS' => (bool)$offers,
    'OFFERS' => $offers,
    'COLORS' => $colors,
    'SIZES' => $sizeKeys,
    'SIZE_LABELS' => $sizeLabels,
    'SIZE_LABEL' => $sizeCode === 'SIZES_SHOES' ? 'Размер обуви (RU)' : ($sizeName !== '' && $sizeCode !== 'SIZES_CLOTHES' ? $sizeName : 'Размер (RU)'),
    'DEFAULT_COLOR' => $defaultColor,
    'PRICE' => $start['price'] ?? ($own['price'] ?? ''),
    'OLD_PRICE' => $start['old'] ?? ($own['old'] ?? ''),
    'PRICE_FROM' => $start && count(array_unique(array_column(array_filter($offers, static fn($o) => $o['color'] === $defaultColor), 'value'))) > 1,
    // A product with offers can be bought when at least one offer can.
    'CAN_BUY' => $offers ? in_array(true, array_column($offers, 'canBuy'), true) : !empty($arResult['CAN_BUY']),
    'GALLERY' => $gallery,
    'SPECS' => $specs,
    'BRAND' => $brand,
    'ARTICLE' => $article,
    'DESCRIPTION' => $arResult['DETAIL_TEXT_TYPE'] === 'html' ? (string)$arResult['~DETAIL_TEXT'] : nl2br(htmlspecialcharsbx((string)$arResult['~DETAIL_TEXT'])),
];
