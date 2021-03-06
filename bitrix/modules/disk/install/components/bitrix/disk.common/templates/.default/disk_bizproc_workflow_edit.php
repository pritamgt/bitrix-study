<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

$arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT_TOOLBAR"] = CComponentEngine::MakePathFromTemplate($arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"], array("ID" => 0));
$arButtons = array();
$arButtons[] = array(
	"TEXT"  => Loc::getMessage("DISK_BIZPROC_STATUS_TITLE"),
	"TITLE" => Loc::getMessage("DISK_BIZPROC_STATUS_TEXT"),
	"LINK"  => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT_TOOLBAR"].(strpos($arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : "&").
		"init=statemachine",
	"ICON"  => "copy-link");
$arButtons[] = array(
	"TEXT"  => Loc::getMessage("DISK_BIZPROC_SERIAL_TITLE"),
	"TITLE" => Loc::getMessage("DISK_BIZPROC_SERIAL_TEXT"),
	"LINK"  => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT_TOOLBAR"].(strpos($arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : ""),
	"ICON"  => "copy-link");

$APPLICATION->includeComponent(
	'bitrix:disk.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => 'bp_toolbar',
		'CLASS_NAME' => 'bx-filepage',
		'BUTTONS'    => $arButtons,
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
<div class="bx-disk-bizproc-section">
<?
$APPLICATION->IncludeComponent(
	"bitrix:disk.bizproc.edit",
	"",
	Array(
		"MODULE_ID"          => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
		"STORAGE_ID"      => $arParams["STORAGE"]->getId(),
		"ID"                 => $arResult['VARIABLES']['ID'],
		"EDIT_PAGE_TEMPLATE" => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"],
		"LIST_PAGE_URL"      => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"],
		"SHOW_TOOLBAR"       => "Y",
		"SET_TITLE"          => "Y",
		"HIDE_TAB_PERMISSION" => "Y"
	)
);
?>
</div>