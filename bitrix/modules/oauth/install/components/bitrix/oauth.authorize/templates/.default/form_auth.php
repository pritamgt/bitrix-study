<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global array $arParams
 * @global array $arResult
 */
?>

<form action="<?=POST_FORM_ACTION_URI?>" method="POST">
	<div class="bx-oAuth-container">
		<div class="bx-oAuth-section">
			<div class="bx-oAuth-text"><?=GetMessage('OAUTH_REQUEST_TITLE', array('#APP_NAME#' => htmlspecialcharsbx($arResult["REQUEST"]["CLIENT"]["TITLE"])))?></div>
			<div class="bx-oAuth-profile">
				<div class="bx-oAuth-image" <?=isset($arResult['USER']['AVATAR']) ? ' style="background-image: url('.htmlspecialcharsbx($arResult['USER']['AVATAR']).')"' : ''?>></div>
				<?
				$name = CUser::FormatName("#NAME# #LAST_NAME#", $arResult['USER'], true, false);
				$bShowEmail = $name != $arResult['USER']['EMAIL'];
				?>
				<div class="bx-oAuth-name"><?=htmlspecialcharsbx($name)?></div>
				<?
				if($bShowEmail):
					?>
					<div class="bx-oAuth-mail"><?=$arResult['USER']['EMAIL']?></div>
					<?
				endif;
				?>
				<?/*<div class="bx-oAuth-logining">logon via <strong>Facebook</strong></div>*/?>
			</div>
		</div>
	</div>
	<div class="bx-oAuth-section-buttons">
		<div class="bx-oAuth-section-buttons-block">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="new_scope" value="auth">
			<input type="submit" name="accept" class="bx-btn big green" onclick="this.className+=' wait';" value="<?=GetMessage('OAUTH_REQUEST_SUBMIT')?>" />
			<a href="<?=\Bitrix\Main\Text\Converter::getHtmlConverter()->encode($arResult["REQUEST"]["CLIENT"]["REDIRECT_URI"])?>"<?=$arResult["MODE"] == "popup" ? ' onclick="window.close(); return false;"' : ''?> class="bx-btn big transparent"><?=GetMessage('OAUTH_REQUEST_CANCEL')?></a>
		</div>
	</div>
</form>
