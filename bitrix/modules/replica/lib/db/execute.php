<?php
namespace Bitrix\Replica\Db;

class Execute extends BaseOperation
{
	protected $name = '';
	protected $args = array();

	/**
	 * Execute constructor.
	 *
	 * @param string $name Event name.
	 * @param array|null $args Event handler arguments.
	 */
	public function __construct($name = '', $args = null)
	{
		if (is_array($args))
		{
			$this->args = $args;
		}
		if ($name)
		{
			$this->name = $name;
		}
	}

	/**
	 * Writes EXECUTE operation into log.
	 *
	 * @param string $name Event name.
	 * @param array|null $args Event handler arguments.
	 *
	 * @return void
	 */
	public function writeToLog($name = '', $args = null)
	{
		if (!is_array($args))
		{
			$args = $this->args;
		}
		else
		{
			$this->args = $args;
		}

		if (!$name)
		{
			$name = $this->name;
		}
		else
		{
			$this->name = $name;
		}

		$this->nodes = array();
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		foreach ($args as $i => $arg)
		{
			if (isset($arg["relation"]))
			{
				$guid = $mapper->getLogGuid($arg["relation"], $arg["value"]);
				if ($guid !== false)
				{
					$args[$i]["translation"] = $guid;

					foreach ($mapper->getByPrimaryValue($arg["relation"], false, $arg["value"]) as $nodes)
					{
						$this->nodes = array_merge($this->nodes, $nodes);
					}
				}
			}
		}

		if ($this->nodes)
		{
			$event = array(
				"operation" => "execute_op",
				"name" => $name,
				"args" => $args,
				"nodes" => $this->nodes,
				"ts" => time(),
				"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
			);

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$this->nodes,
				$event
			);
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
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$mapper->setNodeMap($event["nodes_map"]);
		$args = array();
		foreach ($event["args"] as $i => $arg)
		{
			if (isset($arg["translation"]) && isset($arg["relation"]))
			{
				$idValue = $mapper->resolveLogGuid($nodeFrom, $arg["relation"], $arg["translation"]);
				if ($idValue !== false)
				{
					$args[$i] = $idValue;
				}
			}
			else
			{
				$args[$i] = $arg["value"];
			}
		}

		$event = new \Bitrix\Main\Event("replica", "OnExecute".$event["name"], $args);
		$event->send();
	}
}
