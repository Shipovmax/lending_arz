<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
require_once __DIR__ . '/lib/CheckoutConsent.php';
require_once __DIR__ . '/lib/BasketNames.php';
\Bitrix\Main\EventManager::getInstance()->addEventHandlerCompatible(
    'sale', 'OnSaleComponentOrderCreated', ['\MirSport\CheckoutConsent', 'validate']
);
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale', 'OnSaleBasketItemBeforeSaved', ['\MirSport\BasketNames', 'clean']
);
