<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Оформление заказа');
$APPLICATION->SetPageProperty('hide_h1', 'Y');
?>
<div class="container page page--tight">
  <?php if ((string)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('ORDER_ID') === ''): ?>
  <h1 class="page-title">Оформление заказа</h1>
  <?php endif ?>
  <?php
  // Parameters are the wizard's (order logic is unchanged); only the template is ours.
  $APPLICATION->IncludeComponent('bitrix:sale.order.ajax', 'mir', [
      'PAY_FROM_ACCOUNT' => 'Y',
      'COUNT_DELIVERY_TAX' => 'N',
      'COUNT_DISCOUNT_4_ALL_QUANTITY' => 'N',
      'ONLY_FULL_PAY_FROM_ACCOUNT' => 'N',
      'ALLOW_AUTO_REGISTER' => 'Y',
      'SEND_NEW_USER_NOTIFY' => 'N',
      'DELIVERY_NO_AJAX' => 'N',
      'TEMPLATE_LOCATION' => 'popup',
      'PATH_TO_BASKET' => '/personal/cart/',
      'PATH_TO_PERSONAL' => '/personal/order/',
      'PATH_TO_PAYMENT' => '/personal/order/payment/',
      'PATH_TO_ORDER' => '/personal/order/make/',
      'SET_TITLE' => 'N',
      'SHOW_ACCOUNT_NUMBER' => 'Y',
      'DELIVERY_NO_SESSION' => 'Y',
      'COMPATIBLE_MODE' => 'N',
      'BASKET_POSITION' => 'before',
      'BASKET_IMAGES_SCALING' => 'adaptive',
      'SERVICES_IMAGES_SCALING' => 'adaptive',
      'USER_CONSENT' => 'Y',
      'USER_CONSENT_ID' => $store['consent_id'],
      'USER_CONSENT_IS_CHECKED' => 'N',
      'USER_CONSENT_IS_LOADED' => 'Y',
  ], false);
  ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
