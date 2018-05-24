<?php
namespace Bitrix\Replica\Db;

class Operation
{
	/**
	 * Writes INSERT operation into replication log.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryField Primary key fields.
	 * @param array $primaryValue Primary key values.
	 *
	 * @return \Bitrix\Replica\Db\BaseDbOperation|null
	 */
	public static function writeInsert($tableName, $primaryField, $primaryValue)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($tableName))
		{
			return null;
		}

		$operation = new Insert();
		$operation->writeToLog($tableName, $primaryField, $primaryValue);
		return $operation;
	}

	/**
	 * Writes UPDATE operation into replication log.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryField Primary key fields.
	 * @param array $primaryValue Primary key values.
	 *
	 * @return \Bitrix\Replica\Db\BaseDbOperation|null
	 */
	public static function writeUpdate($tableName, $primaryField, $primaryValue)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($tableName))
		{
			return null;
		}

		$operation = new Update();
		$operation->writeToLog($tableName, $primaryField, $primaryValue);
		return $operation;
	}

	/**
	 * Writes DELETE operation into replication log.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryField Primary key fields.
	 * @param array $primaryValue Primary key values.
	 *
	 * @return \Bitrix\Replica\Db\BaseDbOperation|null
	 */
	public static function writeDelete($tableName, $primaryField, $primaryValue)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($tableName))
		{
			return null;
		}

		$operation = new Delete();
		$operation->writeToLog($tableName, $primaryField, $primaryValue);
		return $operation;
	}

	/**
	 * Apply INSERT operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeInsert($event, $nodeFrom, $nodeTo)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($event["table"]))
		{
			throw new \Bitrix\Replica\ServerException("Insert failed. Table handler not found [".$event["table"]."]");
		}

		$operation = new Insert();
		$operation->applyLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply UPDATE operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeUpdate($event, $nodeFrom, $nodeTo)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($event["table"]))
		{
			throw new \Bitrix\Replica\ServerException("Update failed. Table handler not found [".$event["table"]."]");
		}

		$operation = new Update();
		$operation->applyLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply DELETE operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeDelete($event, $nodeFrom, $nodeTo)
	{
		if (!\Bitrix\Replica\Client\HandlersManager::getTableHandler($event["table"]))
		{
			throw new \Bitrix\Replica\ServerException("Delete failed. Table handler not found [".$event["table"]."]");
		}

		$operation = new Delete();
		$operation->applyLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply NEW FILE operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeFileAdd($event, $nodeFrom, $nodeTo)
	{
		$operation = new FileOperation();
		$operation->applyAddLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply FILE DELETE operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeFileDelete($event, $nodeFrom, $nodeTo)
	{
		$operation = new FileOperation();
		$operation->applyDeleteLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply EXECUTE operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 * @param array $nodeTo Target databases.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeCode($event, $nodeFrom, $nodeTo)
	{
		$operation = new Execute();
		$operation->applyLog($event, $nodeFrom, $nodeTo);
	}

	/**
	 * Apply MAP ADD operation.
	 *
	 * @param array $event Operation description.
	 * @param string $nodeFrom Source database.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeMapAdd($event, $nodeFrom)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$idValue = $mapper->getByGuid($event["relation"], $event["guid"]);
		if ($idValue !== false)
		{
			foreach ($event["nodes"] as $node)
			{
				$mapper->add($event["relation"], $idValue, $node, $event["guid"]);
			}
		}
	}

	/**
	 * Apply MAP DELETE operation.
	 *
	 * @param array $event Operation description.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function executeMapDelete($event)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		foreach ($event["nodes"] as $node)
		{
			$mapper->deleteByGuid($event["relation"], $event["guid"], $node);
		}
	}
}
