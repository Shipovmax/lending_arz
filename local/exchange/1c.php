<?php
/** Dedicated storefront exchange entry point; all CommerceML logic remains in Bitrix. */
if (($_REQUEST['type'] ?? '') !== 'catalog' || ($_GET['type'] ?? '') !== 'catalog') {
    http_response_code(403);
    exit('failure' . "\n" . 'Only catalog import is enabled.');
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/admin/1c_exchange.php';
