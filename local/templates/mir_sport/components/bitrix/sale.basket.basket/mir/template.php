<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Cart in the prototype's layout (cart.html): .checkout with the item list (.cart-list) and the total (.summary).
 * Based on sale.basket.basket / bootstrap_v4: js/, mutator.php and the BX.Sale.BasketComponent init are kept,
 * the markup of the page, the item and the total is the prototype's; the data-entity/id hooks stay intact.
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 * @var CBitrixComponentTemplate $this
 */
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$documentRoot = Main\Application::getDocumentRoot();

$arParams['DISPLAY_MODE'] = 'extended';
$arParams['USE_DYNAMIC_SCROLL'] = 'N';
$arParams['SHOW_FILTER'] = 'N';
$arParams['DATA_LAYER_NAME'] = isset($arParams['DATA_LAYER_NAME']) ? trim((string)$arParams['DATA_LAYER_NAME']) : 'dataLayer';
$arParams['BRAND_PROPERTY'] = isset($arParams['BRAND_PROPERTY']) ? trim((string)$arParams['BRAND_PROPERTY']) : '';
$arParams['USE_GIFTS'] = 'N';
// The total sits in the sidebar; the original sticky "top" total is not used.
$arParams['TOTAL_BLOCK_DISPLAY'] = ['bottom'];
$arParams['PRICE_DISPLAY_MODE'] = 'N';
$arParams['PRODUCT_BLOCKS_ORDER'] = ['props', 'sku', 'columns'];
$arParams['USE_PRICE_ANIMATION'] = 'N';
$arParams['EMPTY_BASKET_HINT_PATH'] = $arParams['EMPTY_BASKET_HINT_PATH'] ?? '/';
$arParams['USE_ENHANCED_ECOMMERCE'] = 'N';

\CJSCore::Init(['fx', 'popup', 'ajax']);
Main\UI\Extension::load(['ui.mustache']);

$this->addExternalJs($templateFolder . '/js/action-pool.js');
$this->addExternalJs($templateFolder . '/js/filter.js');
$this->addExternalJs($templateFolder . '/js/component.js');

$mobileColumns = array_fill_keys($arParams['COLUMNS_LIST_MOBILE'] ?? $arParams['COLUMNS_LIST'], true);

$jsTemplates = new Main\IO\Directory($documentRoot . $templateFolder . '/js-templates');
/** @var Main\IO\File $jsTemplate */
foreach ($jsTemplates->getChildren() as $jsTemplate) {
    include $jsTemplate->getPath();
}

if (empty($arResult['ERROR_MESSAGE'])) {
    if ($arResult['BASKET_ITEM_MAX_COUNT_EXCEEDED']) {
        ?>
        <p class="err" id="basket-item-message"><?= Loc::getMessage('SBB_BASKET_ITEM_MAX_COUNT_EXCEEDED', ['#PATH#' => $arParams['PATH_TO_BASKET']]) ?></p>
        <?php
    }
    ?>
    <div class="checkout" id="basket-root" style="opacity: 0;">
      <section class="checkout__main" aria-label="Товары">
        <div class="notice" id="basket-warning" role="alert" style="display: none;">
          <div>
            <div data-entity="basket-general-warnings"></div>
            <div data-entity="basket-item-warnings"><?= Loc::getMessage('SBB_BASKET_ITEM_WARNING') ?></div>
          </div>
          <button class="icon-btn" type="button" aria-label="Скрыть" data-entity="basket-items-warning-notification-close"><svg class="icon"><use href="#i-close"/></svg></button>
        </div>

        <div id="basket-items-list-wrapper">
          <?php /* Filter and counters of the original template: kept for the component's JS, not shown. */ ?>
          <div data-entity="basket-items-list-header" hidden>
            <div data-entity="basket-filter">
              <input type="text" data-entity="basket-filter-input" aria-label="<?= Loc::getMessage('SBB_BASKET_FILTER') ?>">
              <button type="button" data-entity="basket-filter-clear-btn">&times;</button>
            </div>
            <a href="javascript:void(0)" data-entity="basket-items-count" data-filter="all" style="display: none;"></a>
            <a href="javascript:void(0)" data-entity="basket-items-count" data-filter="similar" style="display: none;"></a>
            <a href="javascript:void(0)" data-entity="basket-items-count" data-filter="warning" style="display: none;"></a>
            <a href="javascript:void(0)" data-entity="basket-items-count" data-filter="delayed" style="display: none;"></a>
            <a href="javascript:void(0)" data-entity="basket-items-count" data-filter="not-available" style="display: none;"></a>
          </div>
          <div id="basket-items-list-container">
            <div id="basket-items-list-overlay" style="display: none;"></div>
            <div id="basket-item-list">
              <div class="empty" id="basket-item-list-empty-result" style="display: none;">
                <span class="empty__title"><?= Loc::getMessage('SBB_FILTER_EMPTY_RESULT') ?></span>
              </div>
              <div class="cart-list" id="basket-item-table"></div>
            </div>
          </div>
        </div>
      </section>

      <aside class="summary" aria-label="Итог заказа" data-entity="basket-total-block"></aside>
    </div>
    <?php
    if (!empty($arResult['CURRENCIES']) && Main\Loader::includeModule('currency')) {
        CJSCore::Init('currency');
        ?>
        <script>
            BX.Currency.setCurrencies(<?= CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true) ?>);
        </script>
        <?php
    }

    $signer = new \Bitrix\Main\Security\Sign\Signer;
    $signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
    $signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
    $messages = Loc::loadLanguageFile(__FILE__);
    ?>
    <script>
        BX.message(<?= CUtil::PhpToJSObject($messages) ?>);
        BX.Sale.BasketComponent.init({
            result: <?= CUtil::PhpToJSObject($arResult, false, false, true) ?>,
            params: <?= CUtil::PhpToJSObject($arParams) ?>,
            template: '<?= CUtil::JSEscape($signedTemplate) ?>',
            signedParamsString: '<?= CUtil::JSEscape($signedParams) ?>',
            siteId: '<?= CUtil::JSEscape($component->getSiteId()) ?>',
            siteTemplateId: '<?= CUtil::JSEscape($component->getSiteTemplateId()) ?>',
            templateFolder: '<?= CUtil::JSEscape($templateFolder) ?>'
        });
    </script>
    <?php
} elseif ($arResult['EMPTY_BASKET']) {
    include $documentRoot . $templateFolder . '/empty.php';
} else {
    ?>
    <p class="err"><?= htmlspecialcharsbx($arResult['ERROR_MESSAGE']) ?></p>
    <?php
}
