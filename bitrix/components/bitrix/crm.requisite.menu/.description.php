<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_REQUISITE_MENU_NAME'),
	'DESCRIPTION' => GetMessage('CRM_REQUISITE_MENU_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 50,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'requisite',
			'NAME' => GetMessage('CRM_REQUISITE_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);
?>