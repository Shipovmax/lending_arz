<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 */

if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(Loc::getMessage("SOA_ORDER_COMPLETE"));
}
?>

<? if (!empty($arResult["ORDER"])): ?>

	<section class="success">
		<span class="ok caps">Заказ оформлен</span>
		<h1 class="page-title">Спасибо за заказ</h1>
		<p class="muted">Номер заказа: <?= htmlspecialcharsbx($arResult["ORDER"]["ACCOUNT_NUMBER"]) ?> от <?= $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format('d.m.Y H:i') ?>. Мы свяжемся с вами, чтобы подтвердить заказ и доставку.</p>
		<a class="btn btn--primary" href="<?= SITE_DIR ?>catalog/">Продолжить покупки</a>
	</section>

	<?
	if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y')
	{
		if (!empty($arResult["PAYMENT"]))
		{
			foreach ($arResult["PAYMENT"] as $payment)
			{
				if ($payment["PAID"] != 'Y')
				{
					if (!empty($arResult['PAY_SYSTEM_LIST'])
						&& array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
					)
					{
						$arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment["ID"]];

						if (empty($arPaySystem["ERROR"]))
						{
							?>

							<div class="row mb-2">
								<div class="col">
									<h2 class="step__title"><?=Loc::getMessage("SOA_PAY") ?></h2>
								</div>
							</div>
							<div class="row mb-2 align-items-center">
								<div class="col-auto"><strong><?=$arPaySystem["NAME"] ?></strong></div>
								<div class="col"><?=CFile::ShowImage($arPaySystem["LOGOTIP"], 100, 100, "border=0\" style=\"width:100px\"", "", false) ?></div>
							</div>
							<div class="row mb-2">
								<div class="col">
									<? if ($arPaySystem["ACTION_FILE"] <> '' && $arPaySystem["NEW_WINDOW"] == "Y" && $arPaySystem["IS_CASH"] != "Y"): ?>
									<?
										$orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
										$paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
									?>
									<script>
										window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=$orderAccountNumber?>&PAYMENT_ID=<?=$paymentAccountNumber?>');
									</script>
									<?=Loc::getMessage("SOA_PAY_LINK", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&PAYMENT_ID=".$paymentAccountNumber))?>
									<? if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
									<br/>
										<?=Loc::getMessage("SOA_PAY_PDF", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&pdf=1&DOWNLOAD=Y"))?>
									<? endif ?>
									<? else: ?>
										<?=$arPaySystem["BUFFERED_OUTPUT"]?>
									<? endif ?>
								</div>
							</div>



							<?
						}
						else
						{
							?>
							<div class="alert alert-danger" role="alert"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></div>
							<?
						}
					}
					else
					{
						?>
						<div class="alert alert-danger" role="alert"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></div>
						<?
					}
				}
			}
		}
	}
	else
	{
		?>
		<div class="alert alert-danger" role="alert"><?=$arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR']?></div>
		<?
	}
	?>

<? else: ?>


	<div class="row mb-2">
		<div class="col">
			<div class="alert alert-danger" role="alert"><strong><?=Loc::getMessage("SOA_ERROR_ORDER")?></strong><br />
				<?=Loc::getMessage("SOA_ERROR_ORDER_LOST", ["#ORDER_ID#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])])?><br />
				<?=Loc::getMessage("SOA_ERROR_ORDER_LOST1")?></div>
		</div>
	</div>

<? endif ?>