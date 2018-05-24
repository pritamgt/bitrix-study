<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage oauth
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\OAuth;

use \Bitrix\Main\Entity;
use Bitrix\Main\Event;

class ClientScopeTable extends Entity\DataManager
{
	private static $entryCache = array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_oauth_client_scope';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CLIENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CLIENT_SCOPE' => array(
				'data_type' => 'string',
			),
			'LAST_AUTHORIZE' => array(
				'data_type' => 'datetime',
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
		);

		return $fieldsMap;
	}

	public static function onBeforeDelete(Event $event)
	{
		$primary = $event->getParameter("primary");
		$id = $primary["ID"];
		if($id)
		{
			$dbRes = static::getByPrimary($id);
			self::$entryCache[$id] = $dbRes->fetch();
		}
	}

	public static function onDelete(Event $event)
	{
		$primary = $event->getParameter("primary");
		$id = $primary["ID"];
		if($id)
		{
			if(array_key_exists($id, self::$entryCache))
			{
				TokenTable::clearByUser(self::$entryCache[$id]["USER_ID"], self::$entryCache[$id]["CLIENT_ID"]);
				RefreshTokenTable::clearByUser(self::$entryCache[$id]["USER_ID"], self::$entryCache[$id]["CLIENT_ID"]);
			}
		}
	}
}
