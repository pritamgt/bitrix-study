<?php
namespace Bitrix\Replica\Db;

class TableRelation
{
	/** @var string */
	protected $tableName = '';
	/** @var array */
	protected $primaryKeyFields = array();
	/** @var array */
	protected $primaryKeyValue = array();

	/**
	 * TableRelation constructor.
	 *
	 * @param string $tableName Table name.
	 * @param string|array $primaryKeyFields Array of primary key columns names.
	 * @param array|false $primaryKeyValue Primary key value.
	 */
	public function __construct($tableName, $primaryKeyFields, $primaryKeyValue = false)
	{
		$this->tableName = (string)$tableName;

		if (!is_array($primaryKeyFields))
		{
			$this->primaryKeyFields = array($primaryKeyFields);
		}
		elseif (\Bitrix\Main\Type\Collection::isAssociative($primaryKeyFields))
		{
			$this->primaryKeyFields = array_keys($primaryKeyFields);
		}
		else
		{
			$this->primaryKeyFields = $primaryKeyFields;
		}

		if ($primaryKeyValue)
		{
			if (!is_array($primaryKeyValue))
			{
				$this->primaryKeyValue = array($primaryKeyValue);
			}
			elseif (\Bitrix\Main\Type\Collection::isAssociative($primaryKeyValue))
			{
				foreach ($this->primaryKeyFields as $fieldName)
				{
					$this->primaryKeyValue[] = $primaryKeyValue[$fieldName];
				}
			}
			else
			{
				$this->primaryKeyValue = $primaryKeyValue;
			}
		}
	}

	/**
	 * Creates an object instance from information retrieved from database.
	 *
	 * @param string $relation Relation.
	 * @param string|false $idValue Primary key value.
	 *
	 * @return \Bitrix\Replica\Db\TableRelation
	 */
	public static function createFromRelation($relation, $idValue = false)
	{
		list ($tableName, $primaryFields) = explode(".", $relation, 2);
		$primaryFields = explode(".", $primaryFields);

		if ($idValue !== false)
		{
			$primaryValue = explode(".", $idValue);
		}
		else
		{
			$primaryValue = false;
		}

		return new self($tableName, $primaryFields, $primaryValue);
	}

	/**
	 * Returns relation presentation of primary key.
	 *
	 * @return string
	 */
	public function getRelation()
	{
		return $this->tableName.".".implode(".", $this->primaryKeyFields);
	}

	/**
	 * Returns table name of the relation.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * Returns database presentation of the primary key value.
	 *
	 * @return string
	 */
	public function getValueId()
	{
		return implode(".", $this->primaryKeyValue);
	}

	/**
	 * Returns associative array of the primary key value.
	 *
	 * @return array
	 */
	public function getPrimaryKeyValue()
	{
		$pkValue = array();
		foreach ($this->primaryKeyFields as $i => $fieldName)
		{
			$pkValue[$fieldName] = $this->primaryKeyValue[$i];
		}
		return $pkValue;
	}

	/**
	 * Sets primary key value from an associative array.
	 *
	 * @param array $record Database record.
	 *
	 * @return void
	 */
	public function setPrimaryKeyValue($record)
	{
		$this->primaryKeyValue = array();
		foreach ($this->primaryKeyFields as $fieldName)
		{
			$this->primaryKeyValue[] = $record[$fieldName];
		}
	}
}
