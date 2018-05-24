<?php
namespace Bitrix\Replica;

class Mapper
{
	protected static $instance = null;
	protected $pkMap = array();
	protected $nodeMap = array();

	/**
	 * Singleton method.
	 *
	 * @return \Bitrix\Replica\Mapper
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Registers new record in the map.
	 *
	 * @param string $relation Table name and column.
	 * @param string $idValue Primary key value.
	 * @param string $node Database.
	 * @param string $guid GUID.
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function add($relation, $idValue, $node, $guid)
	{
		$result = true;
		try
		{
			MapTable::add(array(
				"TABLE_NAME" => $relation,
				"ID_VALUE" => $idValue,
				"NODE_TO" => $node,
				"GUID" => $guid,
			));
		}
		catch (\Bitrix\Main\DB\SqlQueryException $e)
		{
			//Ignore the duplicates
			$result = false;
		}

		$this->pkMap[$relation][$idValue][$guid][$node] = $node;

		return $result;
	}

	/**
	 * Deletes record from the map.
	 *
	 * @param string $relation Table name and column.
	 * @param string $guid GUID.
	 * @param string|false $node Database.
	 *
	 * @return void
	 */
	public function deleteByGuid($relation, $guid, $node = false)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql = "
			DELETE
			FROM
				b_replica_map
			WHERE
				b_replica_map.TABLE_NAME = '".$sqlHelper->forSql($relation)."'
				AND b_replica_map.GUID = '".$sqlHelper->forSql($guid)."'
		";
		if ($node !== false)
		{
			$sql .= "AND b_replica_map.NODE_TO = '".$sqlHelper->forSql($node)."'";
		}

		$connection->query($sql);

		unset($this->pkMap[$relation]);
	}

	/**
	 * Deletes record from the map.
	 *
	 * @param string $tableName Table name and column.
	 * @param array|false $primary Array with primary key columns names.
	 * @param array|string $primaryValue Record primary key value.
	 *
	 * @return void
	 */
	public function deleteByPrimaryValue($tableName, $primary, $primaryValue)
	{
		if ($primary === false)
		{
			$relation = \Bitrix\Replica\Db\TableRelation::createFromRelation($tableName, $primaryValue);
		}
		else
		{
			$relation = new \Bitrix\Replica\Db\TableRelation($tableName, $primary, $primaryValue);
		}

		$relationKey = $relation->getRelation();
		$idValue = $relation->getValueId();

		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql = "
			DELETE
			FROM
				b_replica_map
			WHERE
				b_replica_map.TABLE_NAME = '".$sqlHelper->forSql($relationKey)."'
				AND b_replica_map.ID_VALUE = '".$sqlHelper->forSql($idValue)."'
		";

		$query = $connection->query($sql);

		unset($this->pkMap[$relationKey][$idValue]);
	}

	/**
	 * Generates new GUID.
	 *
	 * @return string
	 */
	public function generateGuid()
	{
		return md5(uniqid());
	}

	/**
	 * Returns map data for the given value.
	 * The keys of returned array are guids and values are arrays of nodes.
	 *
	 * @param string $tableName Table name.
	 * @param array|false $primary Primary key description or false.
	 * @param array|string $primaryValue Primary key value. When $primary is false then string.
	 *
	 * @return array
	 */
	public function getByPrimaryValue($tableName, $primary, $primaryValue)
	{
		if ($primary === false)
		{
			$relation = \Bitrix\Replica\Db\TableRelation::createFromRelation($tableName, $primaryValue);
		}
		else
		{
			$relation = new \Bitrix\Replica\Db\TableRelation($tableName, $primary, $primaryValue);
		}

		$relationKey = $relation->getRelation();
		$idValue = $relation->getValueId();

		if (!isset($this->pkMap[$relationKey][$idValue]))
		{
			$this->pkMap[$relationKey][$idValue] = array();

			$connection = \Bitrix\Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$sql = "
				SELECT
					b_replica_map.GUID,
					b_replica_map.NODE_TO
				FROM
					b_replica_map
				WHERE
					b_replica_map.TABLE_NAME = '".$sqlHelper->forSql($relationKey)."'
					AND b_replica_map.ID_VALUE = '".$sqlHelper->forSql($idValue)."'
			";

			$query = $connection->query($sql);
			while ($row = $query->fetch())
			{
				$this->pkMap[$relationKey][$idValue][$row["GUID"]][$row["NODE_TO"]] = $row["NODE_TO"];
			}
		}

		return $this->pkMap[$relationKey][$idValue];
	}

	/**
	 * Returns primary key value for given guid.
	 *
	 * @param string $relation Table name and column.
	 * @param string $guid GUID.
	 * @param string|false $node Database.
	 *
	 * @return string|false
	 */
	public function getByGuid($relation, $guid, $node = false)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql = "
			SELECT
				b_replica_map.ID_VALUE,
				b_replica_map.NODE_TO
			FROM
				b_replica_map
			WHERE
				b_replica_map.TABLE_NAME = '".$sqlHelper->forSql($relation)."'
				AND b_replica_map.GUID = '".$sqlHelper->forSql($guid)."'
		";
		if ($node !== false)
		{
			$sql .= "AND b_replica_map.NODE_TO = '".$sqlHelper->forSql($node)."'";
		}

		$query = $connection->query($sql);
		if ($row = $query->fetch())
		{
			return $row["ID_VALUE"];
		}

		return false;
	}

	/**
	 * Returns log presentation of the guid.
	 *
	 * @param string $relation Table name and column.
	 * @param string $primaryValue Primary key value.
	 *
	 * @return string|false
	 */
	public function getLogGuid($relation, $primaryValue)
	{
		$guidMap = $this->getByPrimaryValue($relation, false, $primaryValue);
		if ($guidMap)
		{
			if ($relation === "b_user.ID")
			{
				foreach ($guidMap as $guid => $nodes)
				{
					foreach ($nodes as $node)
					{
						$type = \Bitrix\Replica\Client\User::isEmailGuid($guid)? "email": "user";
						return $type.":".$guid.'@'.$node;
					}
				}
			}
			else
			{
				return "guid:".key($guidMap);
			}
		}
		//Local user
		if ($relation === "b_user.ID")
		{
			$guid = \Bitrix\Replica\Client\User::getGuid($primaryValue);
			if ($guid)
			{
				$type = \Bitrix\Replica\Client\User::isEmailGuid($guid)? "email": "user";
				return $type.":".$guid.'@'.getNameByDomain().'@'.getCurrentDomain();
			}
		}
		return false;
	}

	/**
	 * Returns primary key value.
	 *
	 * @param string $nodeFrom Database.
	 * @param string $relation Table name and column.
	 * @param string $logGuid GUID from log.
	 *
	 * @return string|false
	 */
	public function resolveLogGuid($nodeFrom, $relation, $logGuid)
	{
		list($guidType, $guidValue) = explode(":", $logGuid, 2);

		if ($guidType === 'user')
		{
			list($userGuid, $userNode, $userDomain) = explode("@", $guidValue, 3);
			$idValue = $this->resolveUserGuid($userNode, $relation, $userGuid, $userDomain);
		}
		elseif ($guidType === 'email')
		{
			list($userGuid, $userNode, $userDomain) = explode("@", $guidValue, 3);
			$idValue = $this->resolveEmailUserGuid($userNode, $relation, $userGuid, $userDomain);
		}
		else //if ($translationType === "guid")
		{
			$idValue = $this->getByGuid($relation, $guidValue, $nodeFrom);
			if ($idValue === false)
			{
				$idValue = $this->getByGuid($relation, $guidValue);
				if ($idValue !== false)
				{
					$this->add($relation, $idValue, $nodeFrom, $guidValue);
				}
			}
		}

		return $idValue;
	}

	/**
	 * Returns user identifier by it's guid.
	 *
	 * @param string $userNode Database.
	 * @param string $relation Table name and column.
	 * @param string $userGuid GUID.
	 * @param string|null $userDomain Source domain.
	 *
	 * @return integer|false
	 * @throws \Bitrix\Replica\ClientException
	 */
	public function resolveUserGuid($userNode, $relation, $userGuid, $userDomain = null)
	{
		//Already mapped
		$idValue = $this->getByGuid($relation, $userGuid, $userNode);
		if ($idValue === false)
		{
			if ($userNode === getNameByDomain())
			{
				$idValue = \Bitrix\Replica\Client\User::getLocalUserId($userGuid);
				if ($idValue === false)
				{
					throw new \Bitrix\Replica\ClientException("Local user not found: user:$userGuid@$userNode@$userDomain");
				}
			}
			else
			{
				if ($userDomain === null)
				{
					$userDomain = $this->nodeMap[$userNode];
				}

				$idValue = \Bitrix\Replica\Client\User::registerNew($userGuid, $userDomain);
				if ($idValue === false)
				{
					throw new \Bitrix\Replica\ClientException("Register new has failed: user:$userGuid@$userNode@$userDomain");
				}
				$this->add($relation, $idValue, $userNode, $userGuid);
			}
		}

		return $idValue;
	}

	/**
	 * Returns user identifier by it's guid.
	 *
	 * @param string $userNode Database.
	 * @param string $relation Table name and column.
	 * @param string $userGuid GUID.
	 * @param string|null $userDomain Source domain.
	 *
	 * @return integer|false
	 * @throws \Bitrix\Replica\ClientException
	 */
	public function resolveEmailUserGuid($userNode, $relation, $userGuid, $userDomain)
	{
		//Already mapped
		$idValue = $this->getByGuid($relation, $userGuid, $userNode);
		if ($idValue === false)
		{
			if ($userNode === getNameByDomain())
			{
				$idValue = \Bitrix\Replica\Client\User::getLocalUserId($userGuid, true);
				if ($idValue === false)
				{
					throw new \Bitrix\Replica\ClientException("Local email user not found: user:$userGuid");
				}
			}
			else
			{
				if ($userDomain === null)
				{
					$userDomain = $this->nodeMap[$userNode];
				}

				$idValue = \Bitrix\Replica\Client\User::registerNewEmail($userGuid, $userDomain);
				if ($idValue === false)
				{
					throw new \Bitrix\Replica\ClientException("Register new email has failed: user:$userGuid");
				}
				$this->add($relation, $idValue, $userNode, $userGuid);
			}
		}

		return $idValue;
	}

	/**
	 * Sets database name to domain name mapping.
	 *
	 * @param array $nodeMap Array of node to domain.
	 *
	 * @return void
	 */
	public function setNodeMap($nodeMap)
	{
		if ($nodeMap && is_array($nodeMap))
		{
			$this->nodeMap = $nodeMap;
		}
		else
		{
			$this->nodeMap = array();
		}
	}
}
