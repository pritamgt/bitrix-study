<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global array $arResult
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\OAuth\Base;
use Bitrix\OAuth\Client\Bitrix;
use Bitrix\OAuth\ClientTable;
use Bitrix\OAuth\License;
use Bitrix\OAuth\LicenseVerify;

if(!Loader::includeModule("oauth"))
{
	return;
}

if(
	ModuleManager::isModuleInstalled('b24network')
	|| ModuleManager::isModuleInstalled('bitrix24')
)
{
	return;
}

$result = false;

$request = Context::getCurrent()->getRequest();

$type = trim(ToUpper($request['type']));
$redirect_uri = trim(ToLower($request['redirect_uri']));

if(empty($type))
{
	$type = ClientTable::TYPE_BITRIX;
}

if(
	!in_array($type, array(
		ClientTable::TYPE_BITRIX,
		ClientTable::TYPE_PORTAL,
		ClientTable::TYPE_EXTERNAL,
		ClientTable::TYPE_SITE,
		ClientTable::TYPE_APPLICATION
	))
	|| Option::get("client_allow_".$type, "N") !== "Y"
)
{
	$result = array("error" => "Wrong client type");
}

$checkResult = true;
/*
if($type === ClientTable::TYPE_BITRIX)
{
	$checkResult = false;
	if(isset($request[Bitrix::PARAM_KEY]))
	{
		if(License::checkHash($request[Bitrix::PARAM_KEY]))
		{
			$checkResult = true;
		}
	}
	elseif(isset($request[Bitrix::PARAM_TYPE]) && isset($request[Bitrix::PARAM_LICENSE]))
	{
		$verify = new LicenseVerify($request[Bitrix::PARAM_TYPE], $request[Bitrix::PARAM_LICENSE], $request->toArray());
		$verifyResult = $verify->getResult();

		if($verifyResult)
		{
			$checkResult = true;
		}
	}

	if(!$checkResult)
	{
		$result = array(
			"error" => "License check failed",
			"error_code" => Bitrix::ERROR_VERIFICATION
		);
	}
}
*/

if($checkResult)
{
	$arUrl = parse_url($redirect_uri);
	if(!$arUrl
		|| ($arUrl['scheme'] != 'http' && $arUrl['scheme'] != 'https')
		|| !$arUrl['host']
	)
	{
		$result = array("error" => "Wrong redirect_uri");
	}
	else
	{
		$dbRes = ClientTable::getList(array(
			'filter' => array(
				'=TITLE' => $arUrl['host'],
				'=CLIENT_TYPE' => $type,
			),
			'select' => array('ID', 'CLIENT_ID')
		));
		$existingClientInfo = $dbRes->fetch();

		$existingClientId = 0;
		if($existingClientInfo && preg_match('/^dummy_client/', $existingClientInfo['CLIENT_ID']))
		{
			$existingClientId = $existingClientInfo['ID'];
		}

		$clientId = uniqid(ClientTable::getClientSuffix($type), true);
		$clientSecret = RandString(50);
		$salt = RandString(8);
		$clientSecretSalted = $salt.md5($salt.$clientSecret);

		$clientFields = array(
			'CLIENT_ID' => $clientId,
			'CLIENT_SECRET' => $clientSecretSalted,
			'CLIENT_TYPE' => $type,
			'TITLE' => $arUrl['host'],
			'SCOPE' => explode(',', Option::get("oauth", "client_allow_scope_".$type, '')),
			'REDIRECT_URI' => $redirect_uri,
		);

		if($type == ClientTable::TYPE_BITRIX && !empty($request["member_id"]))
		{
			$clientFields['UF_MEMBER_ID'] = $request["member_id"];
		}

		if(!$existingClientId)
		{
			$res = ClientTable::add($clientFields);
		}
		else
		{
			$res = ClientTable::update($existingClientId, $clientFields);
		}

		if($res->isSuccess())
		{
			foreach(GetModuleEvents("oauth", "OnClientRegister", true) as $event)
			{
				ExecuteModuleEventEx($event, array($res->getId(), $clientFields));
			}

/*
			// needed to update client IDs
			if($type == ClientTable::TYPE_BITRIX && isset($request[Bitrix::PARAM_TYPE]))
			{
				$client = Base::instanceById($res->getId());
				$client->verifyClientCredentials($clientId, $clientSecret);
			}
*/

			\Bitrix\Oauth\LogTable::add(array(
				'INSTALL_CLIENT_ID' => $existingClientId,
				'MESSAGE' => 'CLIENT_REGISTER',
				'DETAIL' => $request->toArray(),
				'ERROR' => '',
				'RESULT' => $res->isSuccess()
			));

			$result = array(
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'host' => $arUrl['host'],
			);
		}
		else
		{
			\Bitrix\Oauth\LogTable::add(array(
				'INSTALL_CLIENT_ID' => $existingClientId,
				'MESSAGE' => 'CLIENT_REGISTER',
				'DETAIL' => $request->toArray(),
				'ERROR' => implode(";\n\n", $res->getErrorMessages()),
				'RESULT' => $res->isSuccess()
			));

			$result = array(
				"error" => "Error occured while processing your request. Try again later",
			);
		}
	}
}

if($result !== false)
{
	Header('Content-Type: application/json');
	echo \Bitrix\Main\Web\Json::encode($result);
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");