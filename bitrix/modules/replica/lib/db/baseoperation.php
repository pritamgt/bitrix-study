<?php
namespace Bitrix\Replica\Db;

class BaseOperation
{
	protected $nodes = array();

	/**
	 * Returns array of nodes involved in last operation.
	 * 
	 * @return array
	 */
	public function getLastNodes()
	{
		return $this->nodes;
	}

	/**
	 * Writes specific operation into log.
	 *
	 * @param string $tableName Table name.
	 * @param array $primaryField Primary key columns.
	 * @param array $primaryValue Primary key values.
	 *
	 * @return void
	 */
	public function writeToLog($tableName, $primaryField, $primaryValue)
	{
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
		throw new \Bitrix\Replica\ServerException("Method must be implemented.");
	}
}
