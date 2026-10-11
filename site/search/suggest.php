<?php
/**
 * Live search suggestions for the header search: JSON with up to 6 products in stock, best matches first.
 * GET q=<text>. Used by js/shell.js.
 */
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('STOP_STATISTICS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/templates/mir_sport/include/format.php';

$query = trim((string)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('q'));
$out = ['query' => $query, 'items' => [], 'total' => 0];
if (mb_strlen($query) < 2 || !\Bitrix\Main\Loader::includeModule('search') || !\Bitrix\Main\Loader::includeModule('iblock')) {
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$iblockId = (int)$store['catalog_iblock_id'];
$search = new \CSearch();
$search->Search([
    'QUERY' => $query,
    'SITE_ID' => SITE_ID,
    'MODULE_ID' => 'iblock',
    'PARAM2' => (string)$iblockId,
    'CHECK_DATES' => 'Y',
], ['RANK' => 'DESC']);
$ids = [];
while ($row = $search->Fetch()) {
    if (ctype_digit((string)$row['ITEM_ID'])) {
        $ids[(int)$row['ITEM_ID']] = true;
    }
    if (count($ids) >= 60) {
        break;
    }
}
$ids = array_keys($ids);

// The site search matches whole words only; while typing, a prefix of a word has to work too ("клюш" -> "Клюшка").
// Product names that contain the typed text are added: those that start with it or with a word of it come first.
$byName = [];
$like = \CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', '%NAME' => $query, 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 80], ['ID', 'NAME']);
$needle = mb_strtolower($query);
while ($r = $like->Fetch()) {
    $n = mb_strtolower(html_entity_decode((string)$r['NAME']));
    $score = str_starts_with($n, $needle) ? 0 : (str_contains($n, ' ' . $needle) ? 1 : 2);
    $byName[(int)$r['ID']] = $score;
}
asort($byName);
$ids = array_values(array_unique(array_merge(array_keys(array_filter($byName, static fn($v) => $v < 2)), $ids, array_keys($byName))));
if (!$ids) {
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// Keep the search order, drop what cannot be bought.
$filter = ['IBLOCK_ID' => $iblockId, 'ID' => $ids, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'];
if ($store['hide_unavailable'] === 'Y') {
    $filter['AVAILABLE'] = 'Y';
}
$found = [];
$rows = \CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'IBLOCK_SECTION_ID']);
while ($r = $rows->GetNext()) {
    $found[(int)$r['ID']] = $r;
}
$ordered = [];
foreach ($ids as $id) {
    if (isset($found[$id])) {
        $ordered[] = $found[$id];
    }
}
$out['total'] = count($ordered);
$ordered = array_slice($ordered, 0, 6);

// Lowest price of a product: its own or its offers'.
$prices = [];
if ($ordered && \Bitrix\Main\Loader::includeModule('catalog')) {
    $productIds = array_map(static fn($r) => (int)$r['ID'], $ordered);
    $owner = [];
    foreach ($productIds as $id) {
        $owner[$id] = $id;
    }
    $offers = \CCatalogSku::getOffersList($productIds, $iblockId, [], ['ID']);
    foreach ($offers as $productId => $list) {
        foreach ($list as $offerId => $offer) {
            $owner[(int)$offerId] = (int)$productId;
        }
    }
    $rows = \Bitrix\Catalog\PriceTable::getList(['select' => ['PRODUCT_ID', 'PRICE'], 'filter' => ['@PRODUCT_ID' => array_keys($owner), '>PRICE' => 0]]);
    while ($p = $rows->fetch()) {
        $pid = $owner[(int)$p['PRODUCT_ID']];
        $prices[$pid] = isset($prices[$pid]) ? min($prices[$pid], (float)$p['PRICE']) : (float)$p['PRICE'];
    }
}

foreach ($ordered as $r) {
    $id = (int)$r['ID'];
    $out['items'][] = [
        'name' => html_entity_decode((string)$r['~NAME'] ?: (string)$r['NAME'], ENT_QUOTES),
        'url' => $r['DETAIL_PAGE_URL'],
        'photo' => \MirSport\image((int)($r['DETAIL_PICTURE'] ?: $r['PREVIEW_PICTURE']), 120, 160),
        'price' => isset($prices[$id]) ? \MirSport\rub($prices[$id]) : '',
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
