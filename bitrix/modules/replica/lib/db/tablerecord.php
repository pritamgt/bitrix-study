<?php
namespace Bitrix\Replica\Db;

use \Bitrix\Replica\Client\HandlersManager;

class TableRecord
{
	public $addedNodes = array();
	/** @var string */
	protected $tableName = '';
	/** @var array */
	protected $primaryKeyValue = array();
	/** @var \Bitrix\Replica\Client\BaseHandler */
	protected $tableHandler = null;
	/** @var \Bitrix\Replica\Db\TableRelation */
	protected $tableRelation = null;
	/** @var \Bitrix\Replica\Mapper */
	protected $mapper = null;
	/** @var array */
	protected $record = null;

	/**
	 * TableRecord constructor.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryKeyValue Primary key value.
	 */
	public function __construct($tableName, array $primaryKeyValue)
	{
		$this->tableName = (string)$tableName;
		$this->primaryKeyValue = $primaryKeyValue;
		$this->mapper = \Bitrix\Replica\Mapper::getInstance();
		$this->tableHandler = HandlersManager::getTableHandler($this->tableName);
		$this->tableRelation = new \Bitrix\Replica\Db\TableRelation(
			$tableName,
			array_keys($this->tableHandler->getPrimary()),
			$primaryKeyValue
		);
	}

	/**
	 * Returns relation presentation of primary key.
	 *
	 * @return \Bitrix\Replica\Db\TableRelation
	 */
	public function getTableRelation()
	{
		return $this->tableRelation;
	}

	/**
	 * Returns metadata.
	 *
	 * @return \Bitrix\Replica\Client\BaseHandler|null
	 */
	public function getTableHandler()
	{
		return $this->tableHandler;
	}

	/**
	 * Checks if data record was not loaded from database and tries to fetch it by primary key.
	 * Returns true on success and false on failure.
	 *
	 * @return boolean
	 */
	public function loadRecord()
	{
		if ($this->record === null)
		{
			$records = $this->getRecordsList(
				$this->tableName,
				$this->tableHandler->getPrimary(),
				$this->primaryKeyValue
			);
			$this->record = $records->fetch();
			if ($this->record)
			{
				$this->tableRelation->setPrimaryKeyValue($this->record);
			}
		}
		return is_array($this->record) && $this->record;
	}

	/**
	 * Returns database record array or null if no record was found.
	 *
	 * @return array|null
	 */
	public function getRecord()
	{
		if ($this->loadRecord())
		{
			return $this->record;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Sets data record contents from external source.
	 *
	 * @param array $record Record array.
	 *
	 * @return void
	 */
	public function setRecord(array $record)
	{
		$this->record = $record;
	}

	/**
	 * Returns field value. Null if no record exists.
	 *
	 * @param string $fieldName Field.
	 *
	 * @return string|null
	 */
	public function getRecordField($fieldName)
	{
		if ($this->loadRecord())
		{
			return $this->record[$fieldName];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns true if record exists in database.
	 *
	 * @return boolean
	 */
	public function checkPrimary()
	{
		if ($this->mapper->getByPrimaryValue($this->tableName, $this->tableHandler->getPrimary(), $this->primaryKeyValue))
		{
			return true;
		}
		return false;
	}

	/**
	 * Asks table handler to check
	 *
	 * @return boolean
	 */
	public function beforeLogInsert()
	{
		if ($this->loadRecord())
		{
			return $this->tableHandler->beforeLogInsert($this->record);
		}
		return false;
	}

	/**
	 * Asks table handler to check
	 *
	 * @return boolean
	 */
	public function beforeLogUpdate()
	{
		if ($this->loadRecord())
		{
			return $this->tableHandler->beforeLogUpdate($this->record);
		}
		return false;
	}

	/**
	 * Method will be invoked after writing an missed record.
	 *
	 * @return void
	 */
	public function afterWriteMissing()
	{
		$this->tableHandler->afterWriteMissing($this->record);
	}

	/**
	 * Returns true if at least one field has reference in the map.
	 *
	 * @return boolean
	 */
	public function checkPredicates()
	{
		if ($this->loadRecord())
		{
			foreach ($this->tableHandler->getPredicates() as $fieldName => $relation)
			{
				if (is_callable($relation))
				{
					if (call_user_func_array($relation, array($fieldName, $this->record)))
					{
						return true;
					}
				}
				else
				{
					if ($this->mapper->getByPrimaryValue($relation, false, $this->record[$fieldName]))
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Returns array of all database nodes which may be interested in this record.
	 *
	 * @param boolean $primary To get primary key nodes or not.
	 * @param boolean $predicates To get all predicates nodes or not.
	 * 
	 * @return array
	 */
	public function getNodes($primary = true, $predicates = true)
	{
		$result = array();
		if ($this->loadRecord())
		{
			if ($primary)
			{
				$map = $this->mapper->getByPrimaryValue($this->tableName, $this->tableHandler->getPrimary(), $this->primaryKeyValue);
				foreach ($map as $guid => $nodes)
				{
					$result = array_merge($result, $nodes);
				}
			}

			if ($predicates)
			{
				foreach ($this->tableHandler->getPredicates() as $fieldName => $relation)
				{
					if (is_callable($relation))
					{
						$map = call_user_func_array($relation, array($fieldName, $this->record));
					}
					else
					{
						$map = $this->mapper->getByPrimaryValue($relation, false, $this->record[$fieldName]);
					}

					if ($map)
					{
						foreach ($map as $guid => $nodes)
						{
							$result = array_merge($result, $nodes);
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Returns global identifier of the record.
	 *
	 * @return mixed|false
	 */
	public function getTranslation()
	{
		if ($this->loadRecord())
		{
			$map = $this->mapper->getByPrimaryValue($this->tableName, $this->tableHandler->getPrimary(), $this->primaryKeyValue);
			if ($map)
			{
				return key($map);
			}
		}
		return false;
	}

	/**
	 * Registers the record in the map.
	 * Generates new GUID if none given.
	 * Returns generated GUID.
	 *
	 * @param string|null $guid Optional GUID.
	 * @param array|null $nodes Overwrite default nodes.
	 *
	 * @return false|string
	 * @see \Bitrix\Replica\Mapper::generateGuid
	 * @see \Bitrix\Replica\Db\TableRecord::getNodes
	 */
	public function addTranslation($guid = null, $nodes = null)
	{
		$this->addedNodes = array();
		if ($this->loadRecord())
		{
			$relation = $this->tableRelation->getRelation();
			$idValue = $this->tableRelation->getValueId();

			if ($guid === null || $guid === false)
			{
				$guid = $this->mapper->generateGuid();
			}

			if ($nodes === null || !is_array($nodes))
			{
				$nodes = $this->getNodes();
			}

			foreach ($nodes as $node)
			{
				if ($this->mapper->add($relation, $idValue, $node, $guid))
				{
					$this->addedNodes[$node] = $node;
				}
			}

			return $guid;
		}
		return false;
	}

	/**
	 * Deletes the record from the map.
	 *
	 * @return void
	 */
	public function deleteTranslation()
	{
		$guid = $this->getTranslation();
		if ($guid)
		{
			$relation = $this->tableRelation->getRelation();
			$this->mapper->deleteByGuid($relation, $guid);
		}
	}

	/**
	 * Finds out which files needs to be replicated.
	 *
	 * @return array
	 */
	public function getMissingFiles()
	{
		$allNodes = $this->getNodes();
		$translation = array();
		foreach ($this->tableHandler->getTranslation() as $fieldName => $relation)
		{
			if (
				!isset($this->record[$fieldName])
				|| strlen($this->record[$fieldName]) <= 0
				|| $this->record[$fieldName] === '0'
				|| $this->record[$fieldName] === 0
			)
			{
				continue;
			}

			if (is_callable($relation))
			{
				$relation = call_user_func_array($relation, array($this->record));
				if (!$relation)
				{
					continue;
				}
			}

			if ($relation !== 'b_file.ID')
			{
				continue;
			}

			$validGuid = false;
			$mappedNodes = array();
			foreach ($this->mapper->getByPrimaryValue($relation, false, $this->record[$fieldName]) as $guid => $nodes)
			{
				$validGuid = $guid;
				$mappedNodes = array_merge($mappedNodes, $nodes);
			}

			$missingNodes = array_diff($allNodes, $mappedNodes);
			if ($missingNodes)
			{
				$translation[] = array(
					"missing_nodes" => $missingNodes,
					"mapped_nodes" => $mappedNodes,
					"guid" => $validGuid,
					"fileId" => $this->record[$fieldName],
				);
			}
		}

		return $translation;
	}

	/**
	 * Finds out which parent tables records needs to be replicated.
	 *
	 * @return array
	 */
	public function getMissingTranslation()
	{
		$allNodes = $this->getNodes();
		$translation = array();
		foreach ($this->tableHandler->getTranslation() as $fieldName => $relation)
		{
			if (
				!isset($this->record[$fieldName])
				|| strlen($this->record[$fieldName]) <= 0
				|| $this->record[$fieldName] === '0'
				|| $this->record[$fieldName] === 0
			)
			{
				continue;
			}

			if (is_callable($relation))
			{
				$relation = call_user_func_array($relation, array($this->record));
				if (!$relation)
				{
					continue;
				}
			}

			if ($relation === 'b_file.ID')
			{
				continue;
			}

			$validGuid = false;
			$mappedNodes = array();
			foreach ($this->mapper->getByPrimaryValue($relation, false, $this->record[$fieldName]) as $guid => $nodes)
			{
				$validGuid = $guid;
				$mappedNodes = array_merge($mappedNodes, $nodes);
			}

			$missingNodes = array_diff($allNodes, $mappedNodes);
			if ($missingNodes)
			{
				$translation[] = array(
					"missing_nodes" => $missingNodes,
					"mapped_nodes" => $mappedNodes,
					"field" => $fieldName,
					"relation" => $relation,
					"guid" => $validGuid,
				);
			}
		}

		return $translation;
	}

	/**
	 * Finds out which children tables records needs to be replicated.
	 *
	 * @param array $allNodes Target databases.
	 * @return array
	 */
	public function getMissingChildren($allNodes)
	{
		$translation = array();
		foreach ($this->tableHandler->getChildren() as $fieldName => $relation)
		{
			if (is_array($relation))
			{
				$fieldName = key($relation);
				$relation = current($relation);
			}

			if (
				!isset($this->record[$fieldName])
				|| strlen($this->record[$fieldName]) <= 0
				|| $this->record[$fieldName] === '0'
				|| $this->record[$fieldName] === 0
			)
			{
				continue;
			}

			list ($childrenTable, $childrenField) = explode(".", $relation, 2);
			$childrenHandler = HandlersManager::getTableHandler($childrenTable);
			if ($childrenHandler)
			{
				$childrenRelation = new \Bitrix\Replica\Db\TableRelation($childrenTable, $childrenHandler->getPrimary());
				$children = $this->getRecordsList(
					$childrenTable,
					array($childrenField => 'string'/*TODO*/),
					array($childrenField => $this->record[$fieldName])
				);
				while ($child = $children->fetch())
				{
					$childrenRelation->setPrimaryKeyValue($child);

					$validGuid = false;
					$mappedNodes = array();
					foreach ($this->mapper->getByPrimaryValue($childrenRelation->getRelation(), false, $childrenRelation->getValueId()) as $guid => $nodes)
					{
						$validGuid = $guid;
						$mappedNodes = array_merge($mappedNodes, $nodes);
					}

					$missingNodes = array_diff($allNodes, $mappedNodes);
					if ($missingNodes)
					{
						$translation[] = array(
							"missing_nodes" => $missingNodes,
							"mapped_nodes" => $mappedNodes,
							"table" => $childrenTable,
							"relation" => $childrenRelation->getRelation(),
							"guid" => $validGuid,
							"record" => $child,
							"primary" => $childrenHandler->getPrimary(),
						);
					}
				}
			}
		}
		return $translation;
	}

	/**
	 * Formats record for the log.
	 *
	 * @return array
	 */
	public function getLogRecord()
	{
		$record = $this->getRecord();

		$this->tableHandler->beforeLogFormat($record);

		$record = \Bitrix\Replica\Db\TableFields::convertDbToLog($this->tableHandler->getTableName(), $record);

		return $record;
	}

	/**
	 * Returns translation table of the record for the log.
	 *
	 * @return array
	 */
	public function getLogTranslation()
	{
		$translation = array();
		foreach ($this->tableHandler->getTranslation() as $fieldName => $relation)
		{
			if (
				!isset($this->record[$fieldName])
				|| strlen($this->record[$fieldName]) <= 0
				|| $this->record[$fieldName] === '0'
				|| $this->record[$fieldName] === 0
			)
			{
				continue;
			}

			if (is_callable($relation))
			{
				$relation = call_user_func_array($relation, array($this->record));
			}

			if ($relation)
			{
				$translation[$fieldName] = $this->mapper->getLogGuid($relation, $this->record[$fieldName]);
			}
		}
		return $translation;
	}

	/**
	 * Helper for query database.
	 *
	 * @param string $tableName Table name.
	 * @param array $columns Where columns.
	 * @param array $columnValues Where column values.
	 *
	 * @return \Bitrix\Main\Db\Result
	 */
	protected static function getRecordsList($tableName, $columns, $columnValues)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql = "
			SELECT *
			FROM ".$sqlHelper->quote($tableName)."
			WHERE 1=1
		";
		foreach ($columns as $columnField => $columnType)
		{
			$columnValue = $columnValues[$columnField];
			if ($columnType == 'string')
			{
				$sql .= " AND ".$sqlHelper->quote($columnField)." = '".$sqlHelper->forSql($columnValue)."'";
			}
			elseif ($columnType == 'datetime')
			{
				$sql .= " AND ".$sqlHelper->quote($columnField)." = ".$sqlHelper->getCharToDateFunction($columnValue->format("Y-m-d H:i:s"))."";
			}
			else
			{
				$sql .= " AND ".$sqlHelper->quote($columnField)." = ".intval($columnValue)."";
			}
		}

		return $connection->query($sql);
	}
}
