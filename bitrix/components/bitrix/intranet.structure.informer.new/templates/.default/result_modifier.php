<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);

foreach ($arResult['USERS'] as $key => $arUser)
{
	if ($arUser['PERSONAL_PHOTO'])
	{
		$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 50);
		$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
		//$arUser['PERSONAL_PHOTO'] = CFile::ShowImage($arUser['PERSONAL_PHOTO'], 50, 50);
	}

	//$arUser['DATE_REGISTER'] = $GLOBALS['DB']->FormatDate($arUser['DATE_REGISTER'], CSite::GetDateFormat("FULL") ,CSite::GetDateFormat("SHORT"));
	
	$arResult['USERS'][$key] = $arUser;
}
?>