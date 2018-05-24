<?php
namespace Bitrix\ReplicaServer\Log;

class Relay
{
	const READY = 'N';
	const SUCCESS = 'S';
	const FAIL = 'F';

	const STAT_COUNT = 0;
	const STAT_MIN_ID = 1;

	protected $totalWorkers = 0;
	protected $workerNumber = 0;
	protected $messageLimit = 5;

	/**
	 * Relay constructor.
	 *
	 * @param integer $totalWorkers Number of queue workers.
	 * @param integer $workerNumber Worker instance number (0..totalWorkers-1).
	 */
	public function __construct($totalWorkers = 1, $workerNumber = 1)
	{
		$this->totalWorkers = intval($totalWorkers);
		$this->workerNumber = intval($workerNumber);
	}

	/**
	 * Returns next queue message or false if queue is empty.
	 *
	 * @return array|false
	 */
	public function getNext()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$select = "
			SELECT q.*
			FROM b_replica_log_from q
			WHERE mod(abs(crc32(q.NODE_FROM)), ".$this->totalWorkers.") = ".$this->workerNumber."
			AND NOT EXISTS (SELECT 1 FROM b_replica_node n WHERE n.NODE_TO = q.NODE_FROM AND n.LOG_FROM_ID is not null)
			ORDER BY q.ID
			LIMIT 1
		";
		$result = $connection->query($select);
		return $result->fetch();
	}

	/**
	 * Must be called before starting to produce target messages from the source.
	 *
	 * @return void
	 */
	public function startRelay()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->startTransaction();
	}

	/**
	 * Must be called after last target message produced.
	 *
	 * @return void
	 */
	public function finishRelay()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->commitTransaction();
	}

	/**
	 * Consumes queue item.
	 *
	 * @param array $record Result of getNext.
	 * @param string $status Process result.
	 * @param string $message Additional error message.
	 *
	 * @return boolean
	 */
	public function consume($record, $status = self::SUCCESS, $message = '')
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		if ($status === self::SUCCESS)
		{
			$delete = "
				DELETE FROM b_replica_log_from
				WHERE ID = ".intval($record["ID"])."
			";
			$connection->query($delete);
		}
		else
		{
			$update = "
				UPDATE b_replica_log_from SET
				CMD_STATUS = '".$sqlHelper->forSql($status)."'
				WHERE ID = ".intval($record["ID"])."
			";
			$connection->query($update);
			$insertQuery = "
				INSERT INTO b_replica_node
				(NODE_TO, LOG_FROM_ID, HTTP_RESULT)
				VALUES
				(
					'".$sqlHelper->forSql($record["NODE_FROM"])."'
					,'".$sqlHelper->forSql($record["ID"])."'
					,'".$sqlHelper->forSql($message)."'
				)
			";
			$connection->query($insertQuery);
		}

		return true;
	}

	/**
	 * Produces output queue item.
	 *
	 * @param string $from Source database.
	 * @param string $to Target database.
	 * @param string $event Encoded message.
	 * @param string $signature Signature.
	 * @param string $commandId Identifier of the command.
	 *
	 * @return boolean
	 */
	public function produce($from, $to, $event, $signature, $commandId='')
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insertQuery = "
			INSERT INTO b_replica_log_to
			(NODE_FROM, NODE_TO, EVENT, SIGNATURE, CMD_ID)
			VALUES
			(
				'".$sqlHelper->forSql($from)."'
				,'".$sqlHelper->forSql($to)."'
				,'".$sqlHelper->forSql($event)."'
				,'".$sqlHelper->forSql($signature)."'
				,".($commandId? "'".$sqlHelper->forSql($commandId)."'": "null")."
			)
		";
		$result = $connection->query($insertQuery);

		if ($result)
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns queue stats.
	 *
	 * @param integer $flag What statistic to return.
	 *
	 * @return integer
	 */
	public static function getQueueStat($flag = Relay::STAT_COUNT)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		if ($flag === Relay::STAT_MIN_ID)
			$select = "SELECT min(ID) STAT FROM b_replica_log_from";
		else
			$select = "SELECT count(1) STAT FROM b_replica_log_from";

		return intval($connection->queryScalar($select));
	}
}
