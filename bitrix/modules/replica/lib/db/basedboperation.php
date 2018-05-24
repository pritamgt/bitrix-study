<?php
namespace Bitrix\Replica\Db;

class BaseDbOperation extends BaseOperation
{
	/** @var  \Bitrix\Replica\Db\TableRecord */
	protected $tableRecord;

	/**
	 * Returns table object involved in last operation.
	 *
	 * @return \Bitrix\Replica\Db\TableRecord
	 */
	public function getTableRecord()
	{
		return $this->tableRecord;
	}
}
