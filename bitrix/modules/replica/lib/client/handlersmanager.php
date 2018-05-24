<?php
namespace Bitrix\Replica\Client;

class HandlersManager
{
	public static $tables = array();

	/**
	 * Binds replica to other modules events.
	 * And fires replica OnInit event to let other modules register their table handlers.
	 *
	 * @return void
	 */
	public static function init()
	{
		AddEventHandler("main", "OnFileDelete", array(__CLASS__, "OnFileDelete"));

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler("socialservices", "OnAfterRegisterUserByNetwork", array(__CLASS__, "OnStartUserReplication"), false, 100);

		$event = new \Bitrix\Main\Event("replica", "OnInit");
		$event->send();
	}

	/**
	 * Registers table handler with replication.
	 *
	 * @param \Bitrix\Replica\Client\BaseHandler $handler Table handler object instance.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\HandlersManager::getTableHandler
	 */
	public static function register(\Bitrix\Replica\Client\BaseHandler $handler)
	{
		$handler->initDataManagerEvents();
		self::$tables[$handler->getTableName()] = $handler;
	}

	/**
	 * Returns table handler by table name.
	 *
	 * @param string $tableName Table name.
	 *
	 * @return \Bitrix\Replica\Client\BaseHandler|null
	 * @see \Bitrix\Replica\Client\HandlersManager::register
	 */
	public static function getTableHandler($tableName)
	{
		return self::$tables[$tableName];
	}

	/**
	 * OnFileDelete event handler.
	 *
	 * @param array $fileInfo A record from b_file.
	 *
	 * @return void
	 * @see \CFile::Delete
	 */
	public static function onFileDelete($fileInfo)
	{
		$fileOperation = new \Bitrix\Replica\Db\FileOperation();
		if ($fileOperation->getTranslation($fileInfo["ID"]))
		{
			$fileOperation->writeDeleteToLog($fileInfo["ID"]);
		}
	}

	/**
	 * OnAfterRegisterUserByNetwork event handler.
	 *
	 * @param \Bitrix\Main\Event $event The event.
	 *
	 * @return void
	 */
	public static function onStartUserReplication(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		$userId = $parameters[0];
		$xmlId = $parameters[1];
		$domain = $parameters[2];

		if ($userId <= 0)
		{
			return;
		}

		if (\Bitrix\Replica\Client\User::isMapped($userId))
		{
			return;
		}

		if ($xmlId)
		{
			$guid = $xmlId;
		}
		else
		{
			$guid = \Bitrix\Replica\Client\User::getGuid($userId);
			if (!$guid)
			{
				return;
			}
		}

		\Bitrix\Replica\Client\User::addMap($userId, $guid, $domain);
	}
}
