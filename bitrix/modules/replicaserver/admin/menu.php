<?
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
IncludeModuleLangFile(__FILE__);

$RIGHT = $APPLICATION->GetGroupRight("replica");
if ($RIGHT < "W" || !$DB->TableExists('b_replica_node'))
{
	return false;
}

$arMenu = array(
	"parent_menu" => "global_menu_settings",
	"section" => "replica",
	"sort" => 1500,
	"text" => "Replica",
	"title" => "Replica node list",
	"url" => "replica_admin.php?lang=".LANGUAGE_ID,
	"more_url" => array("replica_admin.php"),
	"icon" => "replica_menu_icon",
	"page_icon" => "replica_page_icon",
);

return $arMenu;

