<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Text\Converter;

if(strlen($arResult["ERROR_MESSAGE"]) > 0)
{
	ShowError($arResult["ERROR_MESSAGE"]);
}
else
{

?>
<!DOCTYPE html>
<html style="position: relative;height: 100%;min-height: 420px;">
<head>
	<meta charset="<?=LANG_CHARSET;?>">
	<link rel="stylesheet" href="<?=$this->getFolder()?>/network/style.css">
	<link rel="stylesheet" href="<?=$this->getFolder()?>/network/buttons.css">
</head>
<body>
<div class="bx-oAuth-header">
	<a href="/" target="_blank" class="bx-network-logo <?=LANGUAGE_ID?> "></a>
</div>
<?
if(isset($arResult["APP"]))
{
	AddMessage2Log($arResult);
?>
	<div class="bx-oAuth-content">
		<div>
			<span class="bx-oAuth-title"><?=Converter::getHtmlConverter()->encode($arResult["APP"]["TITLE"])?></span>
		</div>
		<div>
			<span class="bx-oAuth-description">
				<?=\Bitrix\Main\Localization\Loc::getMessage('OAUTH_OC_CHOOSE_PORTAL')?>
			</span>
		</div>
		<div class="bx-oAuth-list-wrap">
			<div class="bx-oAuth-list-second-level">
				<ul class="bx-oAuth-list list-portal">
<?
	foreach($arResult['DATA']['portal'] as $portal)
	{
		$url = $portal['INSTALLED'] ? $arResult['QUERY_AUTHORIZE'] : $arResult['QUERY_INSTALL'];
?>
					<li class="bx-oAuth-list-item list-item-portal">
<?
		if($portal['INSTALLED'])
		{
?>
						<a href="<?=Converter::getHtmlConverter()->encode($portal["REDIRECT_URI"].$arResult['QUERY_AUTHORIZE'])?>" target="_blank"><?=Converter::getHtmlConverter()->encode($portal["TITLE"])?></a>
<?
		}
		else
		{
?>
						<span><?=Converter::getHtmlConverter()->encode($portal["TITLE"])?></span> <a href="<?=Converter::getHtmlConverter()->encode($portal["REDIRECT_URI"].$arResult['QUERY_INSTALL'])?>" target="_blank"><?=\Bitrix\Main\Localization\Loc::getMessage('OAUTH_OC_INSTALL')?></a>
<?
		}
?>
					</li>
<?
	}
?>
				</ul>
			</div>
		</div>
	</div>
<?
}
else
{
	?>
	<div class="bx-oAuth-container">
		<div class="bx-oAuth-section">
			<div class="bx-oAuth-error"><?=$arResult["ERROR"]["MESSAGE"];?></div>
		</div>
	</div>
	<?
}
?>
</body>
</html>

<?
}

