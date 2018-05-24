<?php
namespace Bitrix\Replica\Db;

class Insert extends BaseDbOperation
{
	/**
	 * Writes INSERT operation into log.
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

		if ($this->tableRecord->checkPredicates())
		{
			if (!$this->tableRecord->beforeLogInsert())
			{
				return;
			}

			$guid = $this->tableRecord->getTranslation();
			if ($guid === false)
			{
				$guid = $this->tableRecord->addTranslation();
			}
			else
			{
				$this->tableRecord->addTranslation($guid);
			}

			$targetNodes = $this->tableRecord->addedNodes;

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
				"operation" => "insert_op",
				"table" => $tableName,
				"primary" => array(
					"TABLE_NAME" => $this->tableRecord->getTableRelation()->getRelation(),
					"GUID" => $guid,
				),
				"nodes" => $this->nodes,
				"record" => $this->tableRecord->getLogRecord(),
				"translation" => $this->tableRecord->getLogTranslation(),
				"origin" => "Insert::writeToLog",
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			if ($this->tableRecord->getTableHandler()->insertIgnore)
			{
				$event["ignore"] = true;
			}

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$targetNodes,
				$event
			);

			foreach ($this->tableRecord->getMissingChildren($this->nodes) as $missingChild)
			{
				if ($missingChild["missing_nodes"])
				{
					$this->writeMissingChild(
						$missingChild["table"],
						$missingChild["record"],
						$missingChild["guid"],
						$missingChild["missing_nodes"],
						$this->nodes
					);
				}

				if ($missingChild["guid"] && $missingChild["missing_nodes"] && $missingChild["mapped_nodes"])
				{
					$this->writeAddMap(
						$missingChild["relation"],
						$missingChild["guid"],
						$missingChild["missing_nodes"],
						$missingChild["mapped_nodes"]
					);
				}
			}
		}
	}

	/**
	 * Writes missing file to log.
	 *
	 * @param array $missingFile Information about file.
	 *
	 * @return void
	 */
	protected function writeMissingFile($missingFile)
	{
		$fileOperation = new FileOperation();
		$fileOperation->writeAddToLog(
			$missingFile["fileId"],
			$missingFile["guid"],
			$this->tableRecord->getNodes(),
			$missingFile["missing_nodes"]
		);
		if ($missingFile["guid"] && $missingFile["missing_nodes"] && $missingFile["mapped_nodes"])
		{
			$this->writeAddMap(
				$missingFile["relation"],
				$missingFile["guid"],
				$missingFile["missing_nodes"],
				$missingFile["mapped_nodes"]
			);
		}
	}

	/**
	 * Writes missing parent of the record.
	 *
	 * @param array $missing Information about not yet replicated records.
	 *
	 * @return void
	 */
	protected function writeMissing($missing)
	{
		if ($missing["relation"] === "b_user.ID")
		{
			//No missing users allowed
		}
		elseif ($missing["relation"] === "b_file.ID")
		{
			//Missing files were already processed
		}
		else
		{
			$missingValue = $this->tableRecord->getRecordField($missing["field"]);
			$parentRelation = \Bitrix\Replica\Db\TableRelation::createFromRelation($missing["relation"], $missingValue);

			$parentTable = $parentRelation->getTableName();
			$parentPrimary = $parentRelation->getPrimaryKeyValue();

			$this->writeMissingInsert(
				$parentTable,
				$parentPrimary,
				$missing["guid"],
				$this->tableRecord->getNodes(),
				$missing["missing_nodes"]
			);
			if ($missing["guid"] && $missing["missing_nodes"] && $missing["mapped_nodes"])
			{
				$this->writeAddMap(
					$missing["relation"],
					$missing["guid"],
					$missing["missing_nodes"],
					$missing["mapped_nodes"]
				);
			}
		}
	}

	/**
	 * Writes missing parent of the record.
	 *
	 * @param string $parentTable Table name.
	 * @param array $primaryValue Primary key value.
	 * @param string $missingGuid Guid to be associated.
	 * @param array $allNodes All databases of the replicated record.
	 * @param array $targetNodes Target databases.
	 *
	 * @return void
	 */
	protected function writeMissingInsert($parentTable, $primaryValue, $missingGuid, $allNodes, $targetNodes)
	{
		$parentRecord = new TableRecord($parentTable, $primaryValue);

		if (!$parentRecord->beforeLogInsert())
		{
			return;
		}

		if ($parentRecord->loadRecord())
		{
			$missingGuid = $parentRecord->addTranslation($missingGuid, $targetNodes);

			foreach ($parentRecord->getMissingFiles() as $missingFile)
			{
				$this->writeMissingFile($missingFile);
			}

			$event = array(
				"operation" => "insert_op",
				"table" => $parentTable,
				"primary" => array(
					"TABLE_NAME" => $parentRecord->getTableRelation()->getRelation(),
					"GUID" => $missingGuid,
				),
				"nodes" => $allNodes,
				"record" => $parentRecord->getLogRecord(),
				"translation" => $parentRecord->getLogTranslation(),
				"origin" => "Insert::writeMissingInsert::$parentTable",
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$targetNodes,
				$event
			);

			$parentRecord->afterWriteMissing();

			foreach ($parentRecord->getMissingChildren($targetNodes) as $missingChild)
			{
				if ($missingChild["missing_nodes"])
				{
					$this->writeMissingChild(
						$missingChild["table"],
						$missingChild["record"],
						$missingChild["guid"],
						$missingChild["missing_nodes"],
						$targetNodes
					);
				}

				if ($missingChild["guid"] && $missingChild["missing_nodes"] && $missingChild["mapped_nodes"])
				{
					$this->writeAddMap(
						$missingChild["relation"],
						$missingChild["guid"],
						$missingChild["missing_nodes"],
						$missingChild["mapped_nodes"]
					);
				}
			}
		}
	}

	/**
	 * Writes missing child of the record.
	 *
	 * @param string $childTable Table name.
	 * @param array $primaryValue Primary key value.
	 * @param string $childGuid Guid to be associated.
	 * @param array $allNodes All databases of the replicated record.
	 * @param array $targetNodes Target databases.
	 *
	 * @return void
	 */
	protected function writeMissingChild($childTable, $primaryValue, $childGuid, $allNodes, $targetNodes)
	{
		$childRecord = new TableRecord($childTable, $primaryValue);
		if ($childRecord->loadRecord())
		{
			$childGuid = $childRecord->addTranslation($childGuid, $targetNodes);

			foreach ($childRecord->getMissingFiles() as $missingFile)
			{
				$this->writeMissingFile($missingFile);
			}

			$event = array(
				"operation" => "insert_op",
				"table" => $childTable,
				"primary" => array(
					"TABLE_NAME" => $childRecord->getTableRelation()->getRelation(),
					"GUID" => $childGuid,
				),
				"nodes" => $allNodes,
				"record" => $childRecord->getLogRecord(),
				"translation" => $childRecord->getLogTranslation(),
				"origin" => "Insert::writeMissingChild",
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$targetNodes,
				$event
			);

			$childRecord->afterWriteMissing();
		}
	}

	/**
	 * Writes ADD MAP operation into log.
	 *
	 * @param string $relation Table name and column.
	 * @param string $guid GUID.
	 * @param array $allNodes All databases of the replicated record.
	 * @param array $targetNodes Target databases.
	 *
	 * @return void
	 */
	protected function writeAddMap($relation, $guid, $allNodes, $targetNodes)
	{
		$event = array(
			"operation" => "map_op",
			"relation" => $relation,
			"guid" => $guid,
			"nodes" => $allNodes,
		);

		\Bitrix\Replica\Log\Client::getInstance()->write(
				$targetNodes,
				$event
		);
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
	 * @throws \Bitrix\Main\Db\SqlQueryException
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

		foreach ($tableHandler->getPrimary() as $primaryField => $primaryType)
		{
			if ($primaryType == 'auto_increment')
			{
				unset($record[$primaryField]);
			}
			elseif (!isset($record[$primaryField]))
			{
				\AddMessage2Log("PRIMARY FIELD $primaryField ON TABLE $tableName IS NOT SET. [".print_r(array(
					"event" => $event,
					"record" => $record,
				), true)."]");
				return;
			}
			elseif ($record[$primaryField] === false)
			{
				\AddMessage2Log("PRIMARY FIELD $primaryField ON TABLE $tableName TRANSLATION HAS BEEN FAILED. [".print_r(array(
					"event" => $event,
					"record" => $record,
				), true)."]");
				return;
			}
		}

		$oldRecord = $tableHandler->beforeInsertTrigger($record);

		if (is_array($oldRecord))
		{
			$record = $oldRecord;
		}
		else
		{
			$insert = $sqlHelper->prepareInsert($tableName, $record);
			if (strlen($insert[0]) > 0 && strlen($insert[1]) > 0)
			{
				$sql = "INSERT INTO ".$sqlHelper->quote($tableName)." (".$insert[0].") VALUES (".$insert[1].")";
				try
				{
					$connection->query($sql);
				}
				catch (\Bitrix\Main\Db\SqlQueryException $e)
				{
					if ($event["ignore"])
					{
						return;
					}
					else
					{
						throw $e;
					}
				}
			}

			foreach ($tableHandler->getPrimary() as $primaryField => $primaryType)
			{
				if ($primaryType == 'auto_increment')
				{
					$record[$primaryField] = $connection->getInsertedId();
				}
			}

			$tableHandler->afterInsertTrigger($record);
		}

		if ($event["primary"])
		{
			$relation = new \Bitrix\Replica\Db\TableRelation($tableHandler->getTableName(), array_keys($tableHandler->getPrimary()), $record);
			$idValue = $relation->getValueId();

			$guid = $event["primary"]["GUID"];
			$relation = $event["primary"]["TABLE_NAME"];
			$mapper->add($relation, $idValue, $nodeFrom, $guid);
			foreach ($event["nodes"] as $node)
			{
				if ($node != $nodeTo)
				{
					$mapper->add($relation, $idValue, $node, $guid);
				}
			}
		}
	}
}
