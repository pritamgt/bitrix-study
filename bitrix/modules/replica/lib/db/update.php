<?php
namespace Bitrix\Replica\Db;

class Update extends Insert
{
	/**
	 * Writes UPDATE operation into log.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryField Primary key columns.
	 * @param array $primaryValue Primary key values.
	 *
	 * @return void
	 */
	public function writeToLog($tableName, $primaryField, $primaryValue)
	{
		$this->nodes = array();
		$this->tableRecord = new TableRecord($tableName, $primaryValue);

		//TODO what if primary updated ?!? make an insert ?
		if ($this->tableRecord->checkPrimary() || $this->tableRecord->checkPredicates())
		{
			if (!$this->tableRecord->beforeLogUpdate())
			{
				return;
			}

			$guid = $this->tableRecord->getTranslation();

			foreach ($this->tableRecord->getMissingFiles() as $missingFile)
			{
				$this->writeMissingFile($missingFile);
			}

			foreach ($this->tableRecord->getMissingTranslation() as $missing)
			{
				$this->writeMissing($missing);
			}

			$this->nodes = $this->tableRecord->getNodes();
			$event = array(
				"operation" => "update_op",
				"table" => $tableName,
				"primary" => array(
					"TABLE_NAME" => $this->tableRecord->getTableRelation()->getRelation(),
					"GUID" => $guid,
				),
				"nodes" => $this->nodes,
				"record" => $this->tableRecord->getLogRecord(),
				"translation" => $this->tableRecord->getLogTranslation(),
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$this->nodes,
				$event
			);
		}
	}

	/**
	 * Replay replication log.
	 *
	 * @param array $event Event description formed by writeToLog method.
	 * @param string $nodeFrom Source database identifier.
	 * @param string $nodeTo Target database identifier.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public function applyLog($event, $nodeFrom, $nodeTo)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$tableName = $event["table"];
		$tableHandler = \Bitrix\Replica\Client\HandlersManager::getTableHandler($tableName);

		$record = \Bitrix\Replica\Db\TableFields::convertLogToDb($event["table"], $event["record"]);

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$mapper->setNodeMap($event["nodes_map"]);
		foreach ($event["translation"] as $fieldName => $logGuidValue)
		{
			$relation = $tableHandler->getTranslationByField($fieldName);
			if (is_callable($relation))
			{
				$relation = call_user_func_array($relation, array($record));
			}

			if ($logGuidValue)
				$record[$fieldName] = $mapper->resolveLogGuid($nodeFrom, $relation, $logGuidValue);
			else
				$record[$fieldName] = false;
		}

		$recordAfter = $record;
		$where = array();
		foreach ($tableHandler->getPrimary() as $primaryField => $primaryType)
		{
			$where[] = $sqlHelper->quote($tableName.".".$primaryField)." = '".$sqlHelper->forSql($record[$primaryField])."'";
			unset($record[$primaryField]);
		}

		$sql = "SELECT * FROM ".$sqlHelper->quote($tableName)." WHERE ".implode(" AND ", $where);

		$sqlResult = $connection->query($sql);
		if ($recordBefore = $sqlResult->fetch())
		{
			$tableHandler->beforeUpdateTrigger($recordBefore, $record);

			$update = $sqlHelper->prepareUpdate($tableName, $record);
			if (strlen($update[0]) > 0)
			{
				$sql = "UPDATE ".$sqlHelper->quote($tableName)." SET ".$update[0]." WHERE ".implode(" AND ", $where);
				$connection->query($sql);
			}

			$tableHandler->afterUpdateTrigger($recordBefore, $recordAfter);
		}
		else
		{
			//TODO throw ?
		}
	}
}
