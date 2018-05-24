<?php
namespace Bitrix\ReplicaServer\Log;

class Server
{
	const READY = 'N';
	const SUCCESS = 'S';
	const FAIL = 'F';

	const STAT_COUNT = 0;
	const STAT_MIN_ID = 1;
	const STAT_MIN_TIME = 2;
	const STAT_TIME_DIFF = 3;

	protected $totalWorkers = 0;
	protected $workerNumber = 0;
	protected $messageLimit = 5;

	/**
	 * Server constructor.
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
	 * @param integer $lastId Optional. If provided all id's below given will be skipped.
	 *
	 * @return array|false
	 */
	public function getNext($lastId = 0)
	{
		$lastId = intval($lastId);
		$connection = \Bitrix\Main\Application::getConnection();
		$select = "
			SELECT q.*
			FROM b_replica_log_to q
			LEFT JOIN b_replica_node n1 on n1.NODE_TO = q.NODE_TO and n1.LOG_TO_ID is not null
			LEFT JOIN b_replica_node n2 on n2.NODE_TO = q.NODE_FROM and n2.LOG_TO_ID is not null
			WHERE mod(abs(crc32(q.NODE_FROM))+abs(crc32(q.NODE_TO)), ".$this->totalWorkers.") = ".$this->workerNumber."
			AND n1.ID is null
			AND n2.ID is null
			".($lastId > 0? "AND q.ID > ".$lastId: "")."
			ORDER BY q.ID
			LIMIT 1
		";
		$result = $connection->query($select);
		return $result->fetch();
	}

	/**
	 * Consumes queue item.
	 *
	 * @param array $record Result of getNext.
	 * @param string $status Process result.
	 * @param string $httpStatus Server status.
	 * @param string $httpResult Server response.
	 *
	 * @return boolean
	 */
	public function consume($record, $status = self::SUCCESS, $httpStatus = '', $httpResult = '')
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		if ($status === self::SUCCESS)
		{
			$delete = "
				DELETE FROM b_replica_log_to
				WHERE ID = ".intval($record["ID"])."
			";
			$connection->query($delete);
		}
		else
		{
			$update = "
				UPDATE b_replica_log_to SET
				CMD_STATUS = '".$sqlHelper->forSql($status)."'
				WHERE ID = ".intval($record["ID"])."
			";
			$connection->query($update);
			try
			{
				$insertQuery = "
					INSERT INTO b_replica_node
					(NODE_TO, LOG_TO_ID, HTTP_STATUS, HTTP_RESULT)
					VALUES
					(
						'".$sqlHelper->forSql($record["NODE_TO"])."'
						,'".$sqlHelper->forSql($record["ID"])."'
						,'".$sqlHelper->forSql($httpStatus)."'
						,'".$sqlHelper->forSql($httpResult)."'
					)
				";
				$connection->query($insertQuery);
			}
			catch (\Bitrix\Main\DB\SqlQueryException $e)
			{
				echo (string)$e, "\n";
				$insertQuery = "
					INSERT INTO b_replica_node
					(NODE_TO, LOG_TO_ID, HTTP_STATUS, HTTP_RESULT)
					VALUES
					(
						'".$sqlHelper->forSql($record["NODE_TO"])."'
						,'".$sqlHelper->forSql($record["ID"])."'
						,'".$sqlHelper->forSql($httpStatus)."'
						,'".$sqlHelper->forSql(base64_encode($httpResult))."'
					)
				";
				$connection->query($insertQuery);
			}
		}

		return true;
	}

	/**
	 * Produces input queue item.
	 *
	 * @param string $from Source database.
	 * @param string $event Encoded message.
	 * @param string $signature Signature.
	 *
	 * @return boolean
	 */
	public function produce($from, $event, $signature)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$insertQuery = "
			INSERT INTO b_replica_log_from
			(NODE_FROM, EVENT, SIGNATURE)
			VALUES
			(
				'".$sqlHelper->forSql($from)."'
				,'".$sqlHelper->forSql($event)."'
				,'".$sqlHelper->forSql($signature)."'
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
	 * Returns true if auto retry is possible.
	 *
	 * @param integer $httpStatus HTTP status.
	 * @param string $httpResult HTTP response body.
	 *
	 * @return boolean
	 */
	public static function checkAutoRetry($httpStatus, $httpResult)
	{
		return
			($httpStatus == 0 && $httpResult === '[[110] Connection timed out]')
			|| ($httpStatus == 0 && $httpResult === '[[111] Connection refused]')
			|| ($httpStatus == 0 && $httpResult === '[]')
			|| ($httpStatus == 0 && $httpResult === '[Stream reading error]')
			|| ($httpStatus == 0 && $httpResult === '[Socket connection error.]')
			|| ($httpStatus == 0 && $httpResult === '[Stream reading timeout of 60 second(s) has been reached]')
			|| ($httpStatus == 100 && $httpResult === '[]')
			|| ($httpStatus == 200 && strpos($httpResult, 'Failed to include replica.') !== false)
			|| ($httpStatus == 200 && strpos($httpResult, 'Web site is now updating.') !== false)
			|| ($httpStatus == 500)
			|| ($httpStatus == 502)
			|| ($httpStatus == 503 && $httpResult === '[]')
			|| ($httpStatus == 504 && $httpResult === '[]')
			|| ($httpStatus == 504 && strpos($httpResult, '/custom_error_pages/500') !== false)
			;
	}

	/**
	 * Returns true if auto skip is possible.
	 *
	 * @param integer $httpStatus HTTP status.
	 * @param string $httpResult HTTP response body.
	 *
	 * @return boolean
	 */
	public static function checkAutoSkip($httpStatus, $httpResult)
	{
		return
			($httpStatus == 200 && strpos($httpResult, "'Unknown operation.'") !== false)
			|| ($httpStatus == 403)
			|| ($httpStatus == -1 && $httpResult === "HostnameFailed")
			|| (strpos($httpResult, 'Mysql query error: Duplicate entry')!==false) //TODO remove this line !!!!!!!!!!!!!!!!
			|| (strpos($httpResult, 'Delete failed. Map not found.')!==false) //TODO remove this line !!!!!!!!!!!!!!!!
			|| (strpos($httpResult, 'Delete failed. Record not found.')!==false) //TODO remove this line !!!!!!!!!!!!!!!!
			;
	}

	/**
	 * Returns queue stats.
	 *
	 * @param integer $flag What statistic to return.
	 *
	 * @return integer|string
	 */
	public static function getQueueStat($flag = Server::STAT_COUNT)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		if ($flag === Server::STAT_MIN_ID)
		{
			$select = "SELECT min(ID) STAT FROM b_replica_log_to";
		}
		elseif ($flag === Server::STAT_MIN_TIME)
		{
			$select = "
				SELECT UNIX_TIMESTAMP(TIMESTAMP_X) STAT
				FROM b_replica_log_to
				INNER JOIN (SELECT min(ID) MIN_ID FROM b_replica_log_to) t on MIN_ID = ID
			";
		}
		elseif ($flag === Server::STAT_TIME_DIFF)
		{
			$select = "
				SELECT TIMEDIFF(now(), TIMESTAMP_X) STAT
				FROM b_replica_log_to
				INNER JOIN (SELECT min(ID) MIN_ID FROM b_replica_log_to) t on MIN_ID = ID
			";
		}
		else
		{
			$select = "SELECT count(1) STAT FROM b_replica_log_to";
		}

		return $connection->queryScalar($select);
	}
}
