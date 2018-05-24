<?php
namespace Bitrix\Replica\Client;

class RatingVoteHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $entityTypeId = "";
	protected $entityIdTranslation = "";

	/**
	 * Registers event handlers for voting operations.
	 *
	 * @return void
	 */
	public function initDataManagerEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"main",
			"OnAddRatingVote",
			array($this, "onAddRatingVote")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecuteAddRatingVote",
			array($this, "onExecuteAddRatingVote")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"main",
			"OnCancelRatingVote",
			array($this, "onCancelRatingVote")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecuteCancelRatingVote",
			array($this, "onExecuteCancelRatingVote")
		);
	}

	/**
	 * OnAddRatingVote event handler. Writes AddRatingVote operation.
	 *
	 * @param integer $id Vote identifier.
	 * @param array $parameters Vote parameters.
	 *
	 * @return void
	 */
	function onAddRatingVote($id, $parameters)
	{
		$this->writeOperation("AddRatingVote", $parameters);
	}

	/**
	 * OnCancelRatingVote event handler. Writes AddRatingVote operation.
	 *
	 * @param integer $id Vote identifier.
	 * @param array $parameters Vote parameters.
	 *
	 * @return void
	 */
	function onCancelRatingVote($id, $parameters)
	{
		$this->writeOperation("CancelRatingVote", $parameters);
	}

	/**
	 * Writes operation to the replication log.
	 *
	 * @param string $operationName Operation name.
	 * @param array $parameters Vote parameters.
	 *
	 * @return void
	 */
	function writeOperation($operationName, $parameters)
	{
		if (
			!isset($parameters["FROM_REPLICA"])
			&& $this->entityTypeId !== ""
			&& $this->entityTypeId === $parameters["ENTITY_TYPE_ID"]
		)
		{
			$operation = new \Bitrix\Replica\Db\Execute();
			$operation->writeToLog(
				$operationName,
				array(
					array(
						"value" => $parameters,
					),
					array(
						"relation" => "b_user.ID",
						"value" => $parameters["USER_ID"],
					),
					array(
						"relation" => "b_user.ID",
						"value" => $parameters["OWNER_ID"],
					),
					array(
						"relation" => $this->entityIdTranslation,
						"value" => $parameters["ENTITY_ID"],
					),
				)
			);
		}
	}

	/**
	 * AddRatingVote replica event handler.
	 *
	 * @param \Bitrix\Main\Event $event Replica log event.
	 *
	 * @return void
	 */
	function onExecuteAddRatingVote(\Bitrix\Main\Event $event)
	{
		$eventParameters = $event->getParameters();
		$parameters = $eventParameters[0];
		$parameters["USER_ID"] = (int)$eventParameters[1];
		$parameters["OWNER_ID"] = (int)$eventParameters[2];
		$parameters["ENTITY_ID"] = (int)$eventParameters[3];
		$parameters["FROM_REPLICA"] = true;

		if ($parameters["ENTITY_ID"] > 0)
		{
			\CRatings::addRatingVote($parameters);
		}
	}

	/**
	 * CancelRatingVote replica event handler.
	 *
	 * @param \Bitrix\Main\Event $event Replica log event.
	 *
	 * @return void
	 */
	function onExecuteCancelRatingVote(\Bitrix\Main\Event $event)
	{
		$eventParameters = $event->getParameters();
		$parameters = $eventParameters[0];
		$parameters["USER_ID"] = (int)$eventParameters[1];
		$parameters["OWNER_ID"] = (int)$eventParameters[2];
		$parameters["ENTITY_ID"] = (int)$eventParameters[3];
		$parameters["FROM_REPLICA"] = true;

		if ($parameters["ENTITY_ID"] > 0)
		{
			\CRatings::cancelRatingVote($parameters);
		}
	}
}
