<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
require_once __DIR__ . '/lib/CheckoutConsent.php';
\Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible(
    'sale', 'OnSaleComponentOrderCreated', ['\MirSport\CheckoutConsent', 'validate']
);
