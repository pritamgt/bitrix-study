<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\OAuth;

if(array_key_exists("USER", $arResult) && $arResult["USER"]["PERSONAL_PHOTO"] > 0)
{
	$imageFile = CFile::GetFileArray($arResult["USER"]["PERSONAL_PHOTO"]);
	if ($imageFile !== false)
	{
		$resizedFile = CFile::ResizeImageGet(
			$imageFile,
			array("width" => 70, "height" => 70),
			BX_RESIZE_IMAGE_PROPORTIONAL,
			false
		);

		$arResult['USER']['AVATAR'] = $resizedFile['src'];
	}
}

if(isset($arResult['REQUEST']) && in_array("admin", $arResult["REQUEST"]["NEW_SCOPE"]))
{
	$arResult["LIST"] = array("portal" => array(), "admin" => array());

	$dbRes = OAuth\ClientProfileTable::getList(array(
		'filter' => array(
			'=USER_ID' => $USER->GetID(),
			'=CLIENT_PROFILE_ACTIVE' => OAuth\ClientProfileTable::ACTIVE,
			'=CLIENT.CLIENT_TYPE' => OAuth\ClientTable::TYPE_PORTAL,
			'=ACCEPTED' => OAuth\ClientProfileTable::ACCEPTED,
		),
		'select' => array(
			'CLIENT_TITLE' => 'CLIENT.TITLE', 'CLIENT_URL' => 'CLIENT.REDIRECT_URI'
		),
		'order' => array('LAST_AUTHORIZE' => 'DESC'),
	));

	while($res = $dbRes->fetch())
	{
		$urlInfo = parse_url($res['CLIENT_URL']);
		$arResult["LIST"]["portal"][] = array(
			'TITLE' => $res['CLIENT_TITLE'],
			'URL' => $urlInfo['scheme'].'://'.$urlInfo['host'].($urlInfo['port'] ? ':'.$urlInfo['port'] : '').'/',
		);
	}

	$dbRes = OAuth\ClientScopeTable::getList(array(
		'filter' => array(
			'=USER_ID' => $USER->GetID(),
			'=CLIENT.CLIENT_TYPE' => OAuth\ClientTable::TYPE_EXTERNAL,
		),
		'select' => array(
			'CLIENT_SCOPE',
			'CLIENT_TITLE' => 'CLIENT.TITLE', 'CLIENT_URL' => 'CLIENT.REDIRECT_URI'
		),
		'order' => array('LAST_AUTHORIZE' => 'DESC'),
	));
	while($res = $dbRes->fetch())
	{
		if(strpos($res["CLIENT_SCOPE"], "admin") !== false)
		{
			$urlInfo = parse_url($res['CLIENT_URL']);
			$arResult["LIST"]["admin"][] = array(
				'TITLE' => $res['CLIENT_TITLE'],
				'URL' => $urlInfo['scheme'].'://'.$urlInfo['host'].($urlInfo['port'] ? ':'.$urlInfo['port'] : '').'/',
			);
		}
	}
}