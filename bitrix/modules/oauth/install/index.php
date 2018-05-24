<?
IncludeModuleLangFile(__FILE__);

class oauth extends CModule
{
	var $MODULE_ID = "oauth";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";

	function oauth()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("OAUTH_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("OAUTH_MODULE_DESCRIPTION");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$errors = false;
		if(!$DB->Query("SELECT 'x' FROM b_oauth_client", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/install/db/".$DBType."/install.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("oauth");

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		if(\Bitrix\Main\ModuleManager::isModuleInstalled('b24network'))
		{
			$eventManager->registerEventHandler("main", "OnAfterUserRegister", "oauth", "\\Bitrix\\OAuth\\UserSecretTable", "onAfterUserRegister");
		}


		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $APPLICATION, $DB, $DOCUMENT_ROOT;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$errors = $DB->RunSQLBatch($DOCUMENT_ROOT."/bitrix/modules/oauth/install/db/".strtolower($DB->type)."/uninstall.sql");
			if(!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("main", "OnAfterUserRegister", "oauth", "\\Bitrix\\OAuth\\UserSecretTable", "onAfterUserRegister");

		UnRegisterModule("oauth");

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		if($_ENV["COMPUTERNAME"] != 'BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
			CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/oauth/install/components", $_SERVER['DOCUMENT_ROOT']."/bitrix/components", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"] != 'BX')
		{
			DeleteDirFilesEx("/oauth/");
		}
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $USER, $errors;

		if(!$USER->IsAdmin())
			return;

		$this->InstallDB(array());
		$this->InstallFiles(array());

		$errors = $this->errors;
		$APPLICATION->IncludeAdminFile(GetMessage("OAUTH_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/install/step2.php");
	}

	function DoUninstall()
	{
		global $APPLICATION, $USER, $errors;
		if($USER->IsAdmin())
		{
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();

			$errors = $this->errors;

			$APPLICATION->IncludeAdminFile(GetMessage("OAUTH_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/install/unstep2.php");
		}
	}
}
?>