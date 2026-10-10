<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Prepares the data of the prototype's product card (.card) for every item:
 * price and old price (minimum over offers), sizes/colors line, stock, photo.
 *
 * @var array $arResult
 * @var array $arParams
 */
require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/format.php';

use function MirSport\{image, plural, rub};

$sizeProps = ['SIZES_SHOES', 'SIZES_CLOTHES'];

foreach ($arResult['ITEMS'] as $key => $item) {
    $rows = [];
    $sizes = [];
    $colors = [];
    $offers = $item['OFFERS'] ?? [];

    if ($offers) {
        foreach ($offers as $offer) {
            $price = $offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED'] ?? 0] ?? null;
            if ($price) {
                $rows[] = $price;
            }
            foreach ($sizeProps as $code) {
                $value = $offer['PROPERTIES'][$code]['VALUE'] ?? '';
                if ($value !== '' && $value !== false) {
                    $sizes[(string)$value] = (int)($offer['PROPERTIES'][$code]['VALUE_SORT'] ?? 0);
                }
            }
            $color = $offer['PROPERTIES']['COLOR_REF']['VALUE'] ?? '';
            if ($color !== '') {
                $colors[$color] = true;
            }
        }
    } elseif (!empty($item['ITEM_PRICES'])) {
        $rows[] = $item['ITEM_PRICES'][$item['ITEM_PRICE_SELECTED'] ?? 0];
    }

    $min = null;
    foreach ($rows as $row) {
        if ($min === null || (float)$row['RATIO_PRICE'] < (float)$min['RATIO_PRICE']) {
            $min = $row;
        }
    }

    $meta = [];
    if ($sizes) {
        asort($sizes);
        $names = array_keys($sizes);
        $first = reset($names);
        $last = end($names);
        $meta[] = count($names) > 1 ? 'Размеры ' . $first . '–' . $last : 'Размер ' . $first;
    }
    if (count($colors) > 1) {
        $meta[] = count($colors) . ' ' . plural(count($colors), 'цвет', 'цвета', 'цветов');
    }

    $picture = $item['PREVIEW_PICTURE'] ?: $item['DETAIL_PICTURE'] ?: null;
    if (!$picture && $offers) {
        foreach ($offers as $offer) {
            $picture = $offer['PREVIEW_PICTURE'] ?: $offer['DETAIL_PICTURE'] ?: null;
            if ($picture) {
                break;
            }
        }
    }

    $hasOldPrice = $min && (float)$min['RATIO_BASE_PRICE'] > (float)$min['RATIO_PRICE'];

    $arResult['ITEMS'][$key]['CARD'] = [
        'PRICE' => $min ? rub((float)$min['RATIO_PRICE']) : '',
        'PRICE_FROM' => $min && count($rows) > 1 && count(array_unique(array_column($rows, 'RATIO_PRICE'))) > 1,
        'OLD_PRICE' => $hasOldPrice ? rub((float)$min['RATIO_BASE_PRICE']) : '',
        'META' => implode(' · ', $meta),
        'AVAILABLE' => !empty($item['CAN_BUY']) || ($item['PRODUCT']['AVAILABLE'] ?? 'N') === 'Y',
        'HAS_OFFERS' => (bool)$offers,
        'PHOTO' => image($picture, 460, 614),
    ];
}

$arResult['MIR_TOTAL'] = isset($arResult['NAV_RESULT']) && is_object($arResult['NAV_RESULT'])
    ? (int)$arResult['NAV_RESULT']->NavRecordCount
    : count($arResult['ITEMS']);
$this->__component->SetResultCacheKeys(['MIR_TOTAL']);
