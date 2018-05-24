<?php
namespace Bitrix\Replica\Server;

use \Bitrix\Replica\Db\Operation;

class Event
{
	protected static $userOperations = array();

	/**
	 * Executes appropriate operation depending on "operation" field.
	 *
	 * @param array $event Message.
	 * @param string $nodeFrom Source database.
	 * @param string $nodeTo Target database.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public static function execute($event, $nodeFrom, $nodeTo)
	{
		if (!is_array($event))
		{
			throw new \Bitrix\Replica\ServerException("Malformed event.");
		}

		if ($event["operation"] === "insert_op")
		{
			Operation::executeInsert($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "update_op")
		{
			Operation::executeUpdate($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "delete_op")
		{
			Operation::executeDelete($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "file_add")
		{
			Operation::executeFileAdd($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "file_delete")
		{
			Operation::executeFileDelete($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "execute_op")
		{
			Operation::executeCode($event, $nodeFrom, $nodeTo);
		}
		elseif ($event["operation"] === "map_op")
		{
			Operation::executeMapAdd($event, $nodeFrom);
		}
		elseif ($event["operation"] === "map_delete")
		{
			Operation::executeMapDelete($event);
		}
		elseif (isset(self::$userOperations[$event["operation"]]))
		{
			call_user_func_array(self::$userOperations[$event["operation"]], array(
				$event,
				$nodeFrom,
				$nodeTo,
			));
		}
		else
		{

			throw new \Bitrix\Replica\ServerException("Unknown operation.");
		}
	}

	/**
	 * Adds custom operation.
	 *
	 * @param string $operationName Custom unique name.
	 * @param callable $callback Callback.
	 *
	 * @return void
	 */
	public static function registerOperation($operationName, $callback)
	{
		if (is_callable($callback))
		{
			self::$userOperations[$operationName] = $callback;
		}
	}
}
