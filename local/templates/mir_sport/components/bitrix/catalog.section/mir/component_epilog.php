<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @var array $arResult
 * @global CMain $APPLICATION
 */
require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/format.php';

$total = (int)($arResult['MIR_TOTAL'] ?? 0);
$APPLICATION->AddViewContent('mir_catalog_count', $total . ' ' . \MirSport\plural($total, 'товар', 'товара', 'товаров'));
