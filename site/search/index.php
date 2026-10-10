<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Поиск');
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$APPLICATION->IncludeComponent('bitrix:search.page', '', [
    'RESTART' => 'Y', 'CHECK_DATES' => 'Y', 'USE_TITLE_RANK' => 'Y',
    'arrFILTER' => ['iblock_catalog'],
    'arrFILTER_iblock_catalog' => [(string)$store['catalog_iblock_id']],
    'SHOW_WHERE' => 'N', 'SHOW_WHEN' => 'N', 'SHOW_ORDER_BY' => 'N',
    'PAGE_RESULT_COUNT' => '20', 'DISPLAY_BOTTOM_PAGER' => 'Y', 'PAGER_TEMPLATE' => 'mir',
    'CACHE_TYPE' => 'A', 'CACHE_TIME' => '3600',
], false);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
