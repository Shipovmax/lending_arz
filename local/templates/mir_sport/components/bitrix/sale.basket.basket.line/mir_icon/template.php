<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Cart link with a counter for the site header. Based on bitrix:sale.basket.basket.line / bootstrap_v4:
 * script.js and the AJAX refresh are kept, the markup follows the prototype.
 *
 * @global string $componentPath
 * @global string $templateName
 * @var CBitrixComponentTemplate $this
 */
$cartId = 'bx_basket' . $this->randString();
$arParams['cartId'] = $cartId;
?>
<script>
var <?= $cartId ?> = new BitrixSmallCart;
</script>
<div id="<?= $cartId ?>" class="basket-line"><?php
    /** @var \Bitrix\Main\Page\FrameBuffered $frame */
    $frame = $this->createFrame($cartId, false)->begin();
        require __DIR__ . '/ajax_template.php';
    $frame->beginStub();
        $arResult['COMPOSITE_STUB'] = 'Y';
        require __DIR__ . '/top_template.php';
        unset($arResult['COMPOSITE_STUB']);
    $frame->end();
?></div>
<script>
    <?= $cartId ?>.siteId       = '<?= SITE_ID ?>';
    <?= $cartId ?>.cartId       = '<?= $cartId ?>';
    <?= $cartId ?>.ajaxPath     = '<?= CUtil::JSEscape($componentPath) ?>/ajax.php';
    <?= $cartId ?>.templateName = '<?= CUtil::JSEscape($templateName) ?>';
    <?= $cartId ?>.arParams     = <?= \Bitrix\Main\Web\Json::encode($arParams) ?>;
    <?= $cartId ?>.closeMessage = '';
    <?= $cartId ?>.openMessage  = '';
    <?= $cartId ?>.activate();
</script>
