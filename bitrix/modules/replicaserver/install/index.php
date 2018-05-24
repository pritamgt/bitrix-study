<?php
IncludeModuleLangFile(__FILE__);

if (class_exists("replicaserver"))
{
	return;
}

Class replicaserver extends CModule
{
	var $MODULE_ID = "replicaserver";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	var $errors = false;

	function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("REP_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("REP_MODULE_DESCRIPTION");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;

		$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/db/".strtolower($DB->type)."/install.sql");

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("replicaserver");
			CModule::IncludeModule("replicaserver");

			RegisterModuleDependences("perfmon", "OnGetTableSchema", "replicaserver", "replicaserver", "OnGetTableSchema");

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if (!array_key_exists("save_tables", $arParams) || $arParams["save_tables"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "replicaserver", "replicaserver", "OnGetTableSchema");

		UnRegisterModule("replicaserver");

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

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
		if ($_ENV["COMPUTERNAME"] != 'BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/public/bitrix/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools");
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/public/bitrix/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		}
		return true;
	}

	function UnInstallFiles()
	{
		if ($_ENV["COMPUTERNAME"] != 'BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/public/bitrix/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		}
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if ($USER->IsAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("REP_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/step1.php");
			}
			elseif ($step == 2)
			{
				if ($this->InstallDB())
				{
					$this->InstallFiles();
				}
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("REP_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/step2.php");
			}
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $step, $USER;
		if ($USER->IsAdmin())
		{
			$step = intval($step);
			if ($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("REP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/unstep1.php");
			}
			elseif ($step == 2)
			{
				$this->UnInstallDB(array(
					"save_tables" => $_REQUEST["save_tables"],
				));
				$this->UnInstallFiles();
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("REP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/replicaserver/install/unstep2.php");
			}
		}
	}

	function OnGetTableSchema()
	{
		return array(
			"replica" => array(
				"b_replica_node" => array(
					"NODE_TO" => array(
						"b_replica_log_from" => "NODE_FROM",
						"b_replica_host" => "NAME",
						"host" => "name",
					),
				),
				"b_replica_log_to" => array(
					"ID" => array(
						"b_replica_node" => "LOG_TO_ID",
					),
				),
				"b_replica_log_from" => array(
					"ID" => array(
						"b_replica_node" => "LOG_FROM_ID",
					),
				),
			),
		);
	}
}

