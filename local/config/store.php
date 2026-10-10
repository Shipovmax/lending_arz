<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }
// Defaults target the existing local wizard sandbox; production values must be configured.
return [
    'catalog_iblock_id' => (int)\Bitrix\Main\Config\Option::get('main', 'mir_sport_catalog_iblock_id', '2'),
    'consent_id' => (int)\Bitrix\Main\Config\Option::get('main', 'mir_sport_consent_id', '1'),
    'price_code' => \Bitrix\Main\Config\Option::get('main', 'mir_sport_price_code', 'BASE'),
];
