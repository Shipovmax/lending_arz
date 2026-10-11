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

use function MirSport\{image, plural, rub, size_labels};

$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$sizeProps = $store['size_props'];
$colorProps = $store['color_props'];

foreach ($arResult['ITEMS'] as $key => $item) {
    $rows = [];
    $sizes = [];
    $colors = [];
    $offers = $item['OFFERS'] ?? [];

    if ($offers) {
        foreach ($offers as $offer) {
            $price = $offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED'] ?? 0] ?? null;
            if ($price && (float)$price['RATIO_PRICE'] > 0) {
                $rows[] = $price;
            }
            foreach ($sizeProps as $code) {
                $value = $offer['PROPERTIES'][$code]['VALUE'] ?? '';
                if ($value !== '' && $value !== false) {
                    $sizes[(string)$value] = (int)($offer['PROPERTIES'][$code]['VALUE_SORT'] ?? 0);
                }
            }
            foreach ($colorProps as $code) {
                $color = trim((string)($offer['PROPERTIES'][$code]['VALUE'] ?? ''));
                if ($color !== '') {
                    $colors[$color] = true;
                    break;
                }
            }
        }
    } elseif (!empty($item['ITEM_PRICES']) && (float)$item['ITEM_PRICES'][$item['ITEM_PRICE_SELECTED'] ?? 0]['RATIO_PRICE'] > 0) {
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
        $labels = \MirSport\size_labels(array_keys($sizes));
        $names = array_values($labels);
        $first = reset($names);
        $last = end($names);
        $meta[] = count($names) > 1 ? 'Размеры ' . $first . '–' . $last : 'Размер ' . $first;
    }
    if (count($colors) > 1) {
        $meta[] = count($colors) . ' ' . plural(count($colors), 'цвет', 'цвета', 'цветов');
    }

    // The detail picture first: the 1C import makes the preview a small thumbnail (about 100 px wide).
    $picture = $item['DETAIL_PICTURE'] ?: $item['PREVIEW_PICTURE'] ?: null;
    if (!$picture && $offers) {
        foreach ($offers as $offer) {
            $picture = $offer['DETAIL_PICTURE'] ?: $offer['PREVIEW_PICTURE'] ?: null;
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
