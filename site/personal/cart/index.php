<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Корзина');
$APPLICATION->SetPageProperty('hide_h1', 'Y');
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
?>
<div class="container page page--tight">
  <h1 class="page-title">Корзина</h1>
  <?php
  $APPLICATION->IncludeComponent('bitrix:sale.basket.basket', 'mir', [
      'COUNT_DISCOUNT_4_ALL_QUANTITY' => 'N',
      'COLUMNS_LIST' => ['NAME', 'PREVIEW_PICTURE', 'DISCOUNT', 'PRICE', 'QUANTITY', 'SUM', 'PROPS', 'DELETE'],
      'AJAX_MODE' => 'N',
      'PATH_TO_ORDER' => '/personal/order/make/',
      'HIDE_COUPON' => 'Y',
      'QUANTITY_FLOAT' => 'N',
      'PRICE_VAT_SHOW_VALUE' => 'N',
      'SET_TITLE' => 'N',
      'OFFERS_PROPS' => array_merge($store['size_props'], $store['color_props']),
  ], false);
  ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
