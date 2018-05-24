<?php
namespace Bitrix\Replica\Log;

class Client
{
	/** @var \Bitrix\Replica\Log\Client */
	protected static $instance = null;

	protected $error = '';
	protected $messageLimit = 5;

	/**
	 * Singleton method.
	 *
	 * @return \Bitrix\Replica\Log\Client
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Send the message to recipients.
	 *
	 * @param array $to Targets list.
	 * @param array $event Message.
	 *
	 * @return boolean
	 */
	public function write($to, $event)
	{
		$message = new Message(getNameByDomain(), $to, $event);
		$messageBody = $message->pack();

		static $flushRegistered = false;
		if (!$flushRegistered)
		{
			$flushRegistered = true;
			if (
				defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION")
				&& function_exists(BX_FORK_AGENTS_AND_EVENTS_FUNCTION)
				&& function_exists("getmypid")
				&& function_exists("posix_kill")
			)
			{
				\CMain::forkActions(array($this, "flush"), array());
			}
			else
			{
				addEventHandler("main", "OnAfterEpilog", array($this, "flush"));
				addEventHandler("main", "OnLocalRedirect", array($this, "flush"));
			}
		}

		//Put into local table for delayed delivery
		return $this->writeLocal($messageBody);
	}

	/**
	 * Sends all local queue to the remote server.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public function flush()
	{
		if ($this->lock())
		{
			while ($this->sendMessages())
			{
			}
			$this->unlock();
			return;
		}
	}

	/**
	 * @return boolean
	 */
	protected function sendMessages()
	{
		$limit = 5;
		$c = 0;
		$events = array();
		$from = '';
		$eventList = \Bitrix\Replica\LogTable::getList(array(
				"order" => array("ID" => "ASC"),
				"limit" => $limit,
		));
		while ($event = $eventList->fetch())
		{
			$c++;
			$message = Message::createFromEvent($event["EVENT"]);
			if ($message)
			{
				if ($from === '')
					$from = $message->getFrom();

				if ($from === $message->getFrom())
				{
					$events[$event["ID"]] = $message->getRawEvent();
				}
				else
				{
					$c = $limit;
					break;
				}
			}
			else
			{
				//If failed then leave message locally
				AddMessage2Log($event);
				return false;
			}
		}

		if ($events && $from !== null)
		{
			//Try to write the message to log server
			if ($this->writeRemoteBatch($events, $from))
			{
				foreach ($events as $eventId => $tmp)
				{
					//Consume
					\Bitrix\Replica\LogTable::delete($eventId);
				}
			}
			else
			{
				//If failed then leave message locally
				AddMessage2Log($this->error);
				return false;
			}
		}

		return ($limit <= $c);
	}

	/**
	 * Tries to put lock onto local queue.
	 *
	 * @return boolean
	 */
	protected function lock()
	{
		$uniq = \CMain::getServerUniqID();
		$connection = \Bitrix\Main\Application::getConnection();

		$result = $connection->query("SELECT GET_LOCK('".$uniq."_replica', 0) as L");
		$lock = $result->fetch();
		if ($lock && $lock["L"] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Unlocks local queue.
	 *
	 * @return void
	 */
	protected function unlock()
	{
		$uniq = \CMain::getServerUniqID();
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->query("SELECT RELEASE_LOCK('".$uniq."_replica')");
	}

	/**
	 * Writes encoded message to local queue.
	 *
	 * @param string $messageBody The message.
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	protected function writeLocal($messageBody)
	{
		$addResult = \Bitrix\Replica\LogTable::add(array(
			"EVENT" => $messageBody,
		));
		return $addResult->isSuccess();
	}

	/**
	 * Sends the message from local queue to remote.
	 *
	 * @param string $messageBody The message.
	 * @param string $from Source of message.
	 *
	 * @return boolean
	 */
	public function writeRemote($messageBody, $from)
	{
		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey(BX24_SECRET);
		$signature = $signer->getSignature($messageBody);

		$http = new \Bitrix\Main\Web\HttpClient(array(
			"redirect" => false,
			"socketTimeout" => 5,
			"streamTimeout" => 5,
		));
		$http->post(getReplicaServerUrl()."?action=queue", array(
			"NODE_FROM" => $from,
			"EVENT" => $messageBody,
			"SIGNATURE" => $signature,
		));

		if ($http->getStatus() == 200)
		{
			if ($http->getResult() === "true")
			{
				return true;
			}
			else
			{
				$this->error = $http->getResult();
				return false;
			}
		}
		else
		{
			$this->error = implode($http->getError());
			return false;
		}
	}

	/**
	 * Sends the multiple messages from local queue to remote.
	 *
	 * @param array $messageBodies The messages bodies.
	 * @param string $from Source of message.
	 *
	 * @return boolean
	 */
	public function writeRemoteBatch($messageBodies, $from)
	{
		$http = new \Bitrix\Main\Web\HttpClient(array(
				"redirect" => false,
				"socketTimeout" => 5,
				"streamTimeout" => 5,
		));

		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey(BX24_SECRET);
		$signature = $signer->getSignature(implode('', $messageBodies));

		$http->post(getReplicaServerUrl()."?action=queue",$p= array(
				"NODE_FROM" => $from,
				"EVENTS" => $messageBodies,
				"SIGNATURE" => $signature,
		));

		if ($http->getStatus() == 200)
		{
			if ($http->getResult() === "true")
			{
				return true;
			}
			else
			{
				$this->error = $http->getResult();
				return false;
			}
		}
		else
		{
			$this->error = implode($http->getError());
			return false;
		}
	}
}
