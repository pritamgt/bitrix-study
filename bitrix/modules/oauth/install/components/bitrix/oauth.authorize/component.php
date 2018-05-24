<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global array $arResult
 */

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Converter;
use \Bitrix\Main\UserTable;
use \Bitrix\OAuth\Base;

if(!Loader::includeModule("oauth"))
	return;

$message = null;
$oauthClient = null;

$publicJoinToken = null;

if(isset($_GET["auth_service_id"]))
{
	if(isset($_GET["auth_service_error"]))
	{
		$message = array("TYPE" => "ERROR"); // message will be formed at auth component
	}
	elseif(CModule::IncludeModule("socialservices"))
	{
		$oAuthManager = new CSocServAuthManager();
		if(!$oAuthManager->Authorize($_GET["auth_service_id"]))
		{
			if($ex = $APPLICATION->GetException())
			{
				$message = array("TYPE" => "ERROR", "MESSAGE" => $ex->GetString(),);
			}
		}
	}
}

if($message === null)
{
	if(isset($_GET['oauth_proxy_params']) && Loader::includeModule('socialservices'))
	{
		CSocServUtil::checkOAuthProxyParams();
	}

	$clientId = $_GET["client_id"];
	$oauthClient = Base::instance($clientId);

	if(!$oauthClient)
	{
		$arResult["ERROR"] = array("TYPE" => "ERROR", "MESSAGE" => Loc::getMessage("OAUTH_ERROR_WRONG_CLIENT"));
		return $this->IncludeComponentTemplate();
	}
	else
	{
		$clientInfo = $oauthClient->getClient();

		if($oauthClient->getClientType() == \Bitrix\OAuth\ClientTable::TYPE_BITRIX)
		{
			LocalRedirect($clientInfo["REDIRECT_URI"]."?error=update_needed&error_description=".urlencode("SEO module update needed")."&state=".urlencode($_REQUEST["state"]));
		}

		if(isset($_GET["check_register"]) && $_GET["check_register"] !== "yes")
		{
			$publicJoinToken = $_GET["check_register"];
			//unset($_GET["check_register"]);

			$oauthClient->setPublicJoinToken($publicJoinToken);
			$APPLICATION->SetPageProperty("OAUTH_CLIENT_SECRET", $publicJoinToken);
			//	$APPLICATION->SetPageProperty("OAUTH_CLIENT_JOIN", $oauthClient->checkPublicJoin());
		}
		else
		{
			$oauthClient->setPublicJoinToken();
		}

		$APPLICATION->SetPageProperty("OAUTH_CLIENT_TITLE", $clientInfo["TITLE"]);
		$APPLICATION->SetPageProperty("OAUTH_CLIENT_DOMAIN", $oauthClient->getClientHost());
		$APPLICATION->SetPageProperty("OAUTH_CLIENT_TYPE", $clientInfo["CLIENT_TYPE"]);
		$APPLICATION->SetPageProperty("OAUTH_CLIENT_ID", $clientInfo["CLIENT_ID"]);

		$title = $clientInfo["TITLE"];

		if($oauthClient->needAuth() && !$USER->IsAuthorized())
		{
			$arAuthParams = array('forgot_password', 'change_password');

			if(isset($_REQUEST['forgot_password']) || isset($_REQUEST['change_password']))
			{
				$message = '';
			}
			else
			{
				$title = $clientInfo["TITLE"];
				$type = $clientInfo["CLIENT_TYPE"];

				$message = array(
					"TYPE" => "OK",
					"MESSAGE" => Loc::getMessage(
						"OAUTH_NEED_AUTHORIZE_" . $type,
						array(
							"#APP_ID#" => Converter::getHtmlConverter()->encode($title)
						)
					),
					"OAUTH_TITLE" => Loc::getMessage("OAUTH_TITLE"),
					"OAUTH_REGISTER_TEXT" => isset($clientInfo["REGISTER_TEXT"]) ? $clientInfo["REGISTER_TEXT"] : "",
				);

				if($oauthClient->checkPublicJoin())
				{
					define("OAUTH_OFFER_REGISTER", true);
				}
			}
		}
		else
		{
			$checkResult = $oauthClient->check();
			if($checkResult !== true)
			{
				if(!isset($_REQUEST["join"]) || !check_bitrix_sessid() || !$oauthClient->checkPublicJoin())
				{
					define("OAUTH_OFFER_LOGOUT", is_array($checkResult) && $checkResult["OAUTH_LOGOUT"] == 'Y');
					define("OAUTH_OFFER_REGISTER", is_array($checkResult) && $checkResult["OAUTH_REGISTER"] == 'Y');
					define("OAUTH_STOP", is_array($checkResult) && $checkResult["OAUTH_STOP"] == 'Y');
					$message = $checkResult;
				}
			}
		}
	}
}

if($message === null)
{
	$APPLICATION->RestartBuffer();

	$userId = intval($USER->GetID());
	$oauthParams = $oauthClient->getAuthorizeParams($userId);

	if(is_array($oauthParams) && !empty($oauthParams))
	{
		if(isset($oauthParams["ERROR_MESSAGE"]))
		{
			$arResult["ERROR_MESSAGE"] = $oauthParams["ERROR_MESSAGE"];
		}
		elseif(isset($oauthParams['REQUEST']))
		{
			$arResult["REQUEST"] = $oauthParams;

			$dbRes = UserTable::getByPrimary($USER->GetID());
			$arResult["USER"] = $dbRes->fetch();
		}
		else
		{
			// LocalRedirect inside in case of success
			$authResult = $oauthClient->sendAuthorizationParams(true, $oauthParams);

			if(is_array($authResult))
			{
				$arResult["OAUTH_PARAMS"] = $authResult;
			}
		}

		$arResult["MODE"] = $_REQUEST["mode"] == "page" ? "page" : "popup";

		$this->IncludeComponentTemplate();
	}
}
else
{
	if(isset($_REQUEST["register"]))
	{
		if($message["OAUTH_REGISTER_TEXT"])
		{
			$APPLICATION->SetPageProperty("OAUTH_REGISTER_TEXT", $message["OAUTH_REGISTER_TEXT"]);
		}
		else
		{
			$APPLICATION->SetPageProperty("OAUTH_REGISTER_TEXT", Loc::getMessage("OAUTH_REGISTER_TEXT_".$oauthClient->getClientType()));
		}

		$message = '';

		$APPLICATION->SetPageProperty("OAUTH_CLIENT_REGISTER", "Y");
	}
	elseif(!$USER->IsAuthorized()
		&& $oauthClient
		&& (
			$oauthClient->checkPublicJoin() && $publicJoinToken !== null
			|| isset($_GET["check_register"]) && $_GET["check_register"] == "yes"
		)
		&& !isset($_REQUEST["login"])
	)
	{
		if($_GET["check_register"] == "yes")
		{
			unset($_GET["check_register"]);
		}

		LocalRedirect($APPLICATION->GetCurPageParam("register=yes")/*, array("check_register")*/);
	}

	$_REQUEST["backurl"] = $APPLICATION->GetCurPageParam();
	$APPLICATION->AuthForm($message);
}
