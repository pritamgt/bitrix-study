<?php
namespace Bitrix\Replica\Db;

class Delete extends BaseDbOperation
{
	/**
	 * Writes DELETE operation into log.
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
		$this->tableRecord->setRecord($primaryValue);

		if ($this->tableRecord->checkPrimary())
		{
			$guid = $this->tableRecord->getTranslation();

			$this->nodes = $this->tableRecord->getNodes();
			$event = array(
				"operation" => "delete_op",
				"table" => $tableName,
				"primary" => array(
					"TABLE_NAME" => $this->tableRecord->getTableRelation()->getRelation(),
					"GUID" => $guid,
				),
				"nodes" => $this->nodes,
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			\Bitrix\Replica\Log\Client::getInstance()->write($this->nodes, $event);

			$this->tableRecord->deleteTranslation();
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

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$idValue = $mapper->getByGuid($event["primary"]["TABLE_NAME"], $event["primary"]["GUID"]);
		if ($idValue === false)
		{
			throw new \Bitrix\Replica\ServerException("Delete failed. Map not found. [TABLE_NAME: ".$event["primary"]["TABLE_NAME"]."; GUID:".$event["primary"]["GUID"]."]");
		}

		$where = array();
		$relation = \Bitrix\Replica\Db\TableRelation::createFromRelation($event["primary"]["TABLE_NAME"], $idValue);
		$primaryValue = $relation->getPrimaryKeyValue();

		foreach ($tableHandler->getPrimary() as $primaryField => $primaryType)
		{
			$where[] = $sqlHelper->quote($tableName.".".$primaryField)." = '".$sqlHelper->forSql($primaryValue[$primaryField])."'";
		}

		$sql = "SELECT * FROM ".$sqlHelper->quote($tableName)." WHERE ".implode(" AND ", $where);
		$sqlResult = $connection->query($sql);
		$record = $sqlResult->fetch();
		if (!$record)
		{
			throw new \Bitrix\Replica\ServerException("Delete failed. Record not found. [SELECT * FROM ".$sqlHelper->quote($tableName)." WHERE ".implode(" AND ", $where)."]");
		}

		$sql = "DELETE FROM ".$sqlHelper->quote($tableName)." WHERE ".implode(" AND ", $where);
		$connection->query($sql);

		$mapper->deleteByGuid($event["primary"]["TABLE_NAME"], $event["primary"]["GUID"]);

		$tableHandler->afterDeleteTrigger($record);
	}
}
