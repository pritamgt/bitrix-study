<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global array $arParams
 * @global array $arResult
 */


if(strlen($arResult["ERROR_MESSAGE"])>0)
{
	ShowError($arResult["ERROR_MESSAGE"]);
}

if(is_array($arResult) && isset($arResult["OAUTH_PARAMS"])):
?>
	<div id="bx_auth_float" class="bx-auth-float">
		<div style="width:180px; text-align: center;" class="oauth-code-shower">
			<h3><?=GetMessage('OAUTH_CODE').":";?></h3>
			<h1 style="position: static; border: 1px solid; zoom:1.2; width:150px; text-align: center;"><?=$arResult["OAUTH_PARAMS"]["code"]?></h1>
		</div>
	</div>
<?
elseif(isset($arResult['REQUEST']) || isset($arResult["ERROR"])):
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
	if(isset($arResult["REQUEST"]))
	{
		if(in_array("auth", $arResult["REQUEST"]["NEW_SCOPE"]))
		{
			require_once("form_auth.php");
		}
		elseif(in_array("admin", $arResult["REQUEST"]["NEW_SCOPE"]))
		{
			require_once("form_admin.php");
		}
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
endif;
