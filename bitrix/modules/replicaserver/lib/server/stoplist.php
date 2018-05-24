<?php
namespace Bitrix\ReplicaServer\Server;

class StopList
{
	/**
	 * Returns true if node is blacklisted.
	 *
	 * @param string $node Database identifier.
	 *
	 * @return boolean
	 */
	public static function isExists($node)
	{
		$r = \Bitrix\ReplicaServer\StopTable::getList(array(
			"select" => array("NODE_TO"),
			"filter" => array(
				"=NODE_TO" => $node,
			),
			"limit" => 1,
		));
		return is_array($r->fetch());
	}
}