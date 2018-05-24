<?php
namespace Bitrix\ReplicaServer\Server;

class NodeRelation
{
	const LIMIT_COUNT = 25;
	const LIMIT_TIME = 86400;

	protected static $instance = null;
	protected $relationMap = array();

	/**
	 * Singleton method.
	 *
	 * @return \Bitrix\ReplicaServer\Server\NodeRelation
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
	 * Checks if events are allowed based on relations.
	 *
	 * @param string $nodeFrom Left side of relation.
	 * @param array $events Events array to be analyzed.
	 *
	 * @return boolean
	 */
	public function checkRelations($nodeFrom, $events)
	{
		$nodesTo = array();
		foreach ($events as $event)
		{
			$event = unserialize($event);
			if (is_array($event) && is_array($event["to"]))
			{
				foreach ($event["to"] as $node)
				{
					$nodesTo[$node]++;
				}
			}
		}
		foreach ($nodesTo as $node => $cnt)
		{
			if (!$this->check($nodeFrom, $node))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if relation is allowed and adds new one.
	 *
	 * @param string $nodeFrom Left side of relation.
	 * @param string $nodeTo Right side of relation.
	 *
	 * @return boolean
	 */
	public function check($nodeFrom, $nodeTo)
	{
		if ($this->isExists($nodeFrom, $nodeTo))
		{
			return true;
		}
		elseif ($this->checkLimit($nodeFrom))
		{
			return $this->add($nodeFrom, $nodeTo);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Checks if relation already exists.
	 *
	 * @param string $nodeFrom Left side of relation.
	 * @param string $nodeTo Right side of relation.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function isExists($nodeFrom, $nodeTo)
	{
		if (isset($this->relationMap[$nodeFrom][$nodeTo]))
		{
			return true;
		}

		$relationList = \Bitrix\ReplicaServer\RelationTable::getList(array(
			"select" => array("ID", "START_DATE"),
			"filter" => array(
				"=NODE_FROM" => $nodeFrom,
				"=NODE_TO" => $nodeTo,
			),
		));

		$relation = $relationList->fetch();
		if ($relation)
		{
			$this->relationMap[$nodeFrom][$nodeTo] = $relation;
			return true;
		}

		return false;
	}

	/**
	 * Checks relation limit.
	 *
	 * @param string $nodeFrom Left side of relation.
	 * @param integer $limitCount Count relations.
	 * @param integer $limitTime Number of seconds to check.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function checkLimit($nodeFrom, $limitCount = NodeRelation::LIMIT_COUNT, $limitTime = NodeRelation::LIMIT_TIME)
	{
		$date = new \Bitrix\Main\Type\DateTime();
		$date->add("-PT".$limitTime."S");

		$relationList = \Bitrix\ReplicaServer\RelationTable::getList(array(
			"filter" => array(
				"=NODE_FROM" => $nodeFrom,
				">=START_DATE" => $date,
			),
		));
		$count = 0;
		while ($relation = $relationList->fetch())
		{
			$count++;
			$this->relationMap[$relation["NODE_FROM"]][$relation["NODE_TO"]] = $relation;
		}

		return ($count <= $limitCount);
	}

	/**
	 * Adds new relation.
	 *
	 * @param string $nodeFrom Left side of relation.
	 * @param string $nodeTo Right side of relation.
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function add($nodeFrom, $nodeTo)
	{
		$addResult = \Bitrix\ReplicaServer\RelationTable::add(array(
			"NODE_FROM" => $nodeFrom,
			"NODE_TO" => $nodeTo,
			"START_DATE" => new \Bitrix\Main\Type\DateTime(),
		));
		return $addResult->isSuccess();
	}
}
