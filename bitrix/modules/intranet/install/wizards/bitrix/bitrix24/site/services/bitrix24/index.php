<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!Bitrix\Main\Loader::includeModule("bitrix24") || !Bitrix\Main\Loader::includeModule("rest"))
{
	return;
}

// APPLICATIONS

$appArray = \CBitrix24::getAppsForWizard();
if (!is_array($appArray) || empty($appArray))
{
	return;
}

if(!\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
{
	try
	{
		\Bitrix\Rest\OAuthService::register();
	}
	catch(\Bitrix\Main\SystemException $e)
	{
	}
}

// INSTALL

if(\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
{
	foreach($appArray as $app)
	{
		if(isset($app["MODULE_DEPENDENCY"]) && !empty($app["MODULE_DEPENDENCY"]))
		{
			foreach($app["MODULE_DEPENDENCY"] as $moduleId)
			{
				if(!IsModuleInstalled($moduleId))
				{
					continue 2;
				}
			}
		}

		if(isset($app["LANGUAGE_DEPENDENCY"]) && !empty($app["LANGUAGE_DEPENDENCY"]))
		{
			if(!in_array(CBitrix24::getLicensePrefix(), $app["LANGUAGE_DEPENDENCY"]))
			{
				continue;
			}
		}

		$result = \Bitrix\Rest\AppTable::add($app["INSTALL"]);
		if($result->isSuccess())
		{
			$ID = $result->getId();

			if(is_array($app['MENU_NAME']))
			{
				foreach($app['MENU_NAME'] as $lang => $menuName)
				{
					\Bitrix\Rest\AppLangTable::add(array(
						'APP_ID' => $ID,
						'LANGUAGE_ID' => $lang,
						'MENU_NAME' => trim($menuName)
					));
				}
			}

			if(is_array($app['OPTIONS']['CLEAR_CACHE']) && !empty($app['OPTIONS']['CLEAR_CACHE']) && defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				foreach($app['OPTIONS']['CLEAR_CACHE'] as $cacheTag)
				{
					$CACHE_MANAGER->ClearByTag($cacheTag);
				}
			}

			if(is_array($app['EXECUTE']) && !empty($app['EXECUTE']))
			{
				foreach($app['EXECUTE'] as $func)
				{
					call_user_func($func, Array("APP_ID" => $ID, "APP" => $app["INSTALL"]));
				}
			}
		}
	}
}

function wizardInstallBotGiphy($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\Giphy::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}
function wizardInstallBotProperties($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\Properties::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}
function wizardInstallBotPropertiesUa($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\PropertiesUa::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}

CAgent::AddAgent('\\Bitrix\\ImOpenLines\\Security\\Helper::installRolesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));

CUserOptions::SetOption("bitrix24", "show_userinfo_spotlight", array("needShow" => "Y"), false, 1);
?>