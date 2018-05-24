<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
if(!CModule::IncludeModule("oauth"))
	return;

use Bitrix\Main\Web\Json;
use Bitrix\OAuth;

$APPLICATION->RestartBuffer();

$clientId = isset($_REQUEST["client_id"]) ? $_REQUEST["client_id"] : "";
$oauth = OAuth\Base::instance($clientId);

if($oauth)
{
	$oauth->grantAccessToken();
}
else
{
	Header("Content-Type: application/json");
	echo Json::encode(array(
		"error" => "wrong_client",
	));
}

