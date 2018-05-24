<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

$childrenCount = intval($argv[1]);
if ($childrenCount <= 0)
{
	die("Specify number of workers\n");
}

$children = array();
for ($i = 0; $i < $childrenCount; $i++)
{
	$children[$i] = pcntl_fork();
	if ($children[$i] === 0)
	{
		$mod = $i;
		break;
	}
	elseif ($children[$i] < 0)
	{
		//TODO finish all spawned children.
		die("Fork failed.\n");
	}
}

if (!isset($mod))
{
	while (pcntl_waitpid(0, $status) != -1)
	{
		$childStatus = pcntl_wexitstatus($status);
		echo "A Child completed with status $childStatus\n";
		/*foreach ($children as $childPid)
		{
			if ($childPid > 0)
			{
				echo "Killing $childPid\n";
				posix_kill($childPid, SIGINT);
			}
		}*/
		die("Dearest child has died.\n");
	}
}
else
{
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	define("BX_CRONTAB", true);
	define('BX_NO_ACCELERATOR_RESET', true);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	while (@ob_end_clean())
		;
	@set_time_limit(0);
	@ignore_user_abort(true);
	echo "\n";

	echo "Start@".date("Y-m-d H:i:s")
		." pid{".getmypid()."}"
		." mod{".$mod."}"
		."\n";

	if (!\Bitrix\Main\Loader::includeModule('replicaserver'))
	{
		die("Module replicaserver is not installed.\n");
	}

	$relay = new \Bitrix\ReplicaServer\Log\Relay($childrenCount, $mod);

	$startTime = date("Y-m-d");
	$statTime = "";

	while ($startTime === date("Y-m-d"))
	{
		$sleep = true;
		// Get the message
		if ($nextMessage = $relay->getNext())
		{
			if ($nextMessage["TIMESTAMP_X"] instanceof \Bitrix\Main\Type\Date)
			{
				$eventTime = $nextMessage["TIMESTAMP_X"]->format("Y-m-d H:i:s");
				$timeDiff = time() - $nextMessage["TIMESTAMP_X"]->getTimestamp();
			}
			else
			{
				$eventTime = '';
				$timeDiff = '';
			}

			$fromSecret = getDbSecret($nextMessage["NODE_FROM"]);
			if (!$fromSecret)
			{
				echo "Failed to find host@".date("Y-m-d H:i:s")
					." pid{".getmypid()."}"
					." mod{".$mod."}"
					." ".$nextMessage["NODE_FROM"]
					."\n";
				$relay->consume($nextMessage, \Bitrix\ReplicaServer\Log\Relay::FAIL, 'Failed to find host');
				continue;
			}

			$signer = new \Bitrix\Main\Security\Sign\Signer();
			$signer->setKey($fromSecret);
			$signature = $signer->getSignature($nextMessage["EVENT"]);

			if ($signature !== $nextMessage["SIGNATURE"])
			{
				echo "Failed to check signature@".date("Y-m-d H:i:s")
					." pid{".getmypid()."}"
					." mod{".$mod."}"
					." ".$nextMessage["NODE_FROM"]
					."\n";
				$relay->consume($nextMessage, \Bitrix\ReplicaServer\Log\Relay::FAIL, 'Failed to check signature');
				continue;
			}

			$message = \Bitrix\ReplicaServer\Log\Message::createFromEvent($nextMessage["EVENT"]);
			if (!$message)
			{
				echo "MessageFailed@".date("Y-m-d H:i:s")
					." pid{".getmypid()."}"
					." mod{".$mod."}"
					." ".$nextMessage["EVENT"]
					."\n";
				$relay->consume($nextMessage, \Bitrix\ReplicaServer\Log\Relay::FAIL, 'MessageFailed');
			}

			$event = $message->getEvent();
			if ($event && $event["event"])
			{
				$operation = $event["event"]["operation"];
				if ($event["event"]["nodes"])
				{
					$event["event"]["nodes_map"] = array();
					foreach ($event["event"]["nodes"] as $node)
					{
						$event["event"]["nodes_map"][$node] = getDomainByName($node);
					}
					$message->setEvent($event);
				}
			}
			else
			{
				$operation = '';
			}

			$relay->startRelay();
			$relay->consume($nextMessage);

			$nodeFrom = $message->getFrom();
			echo "Consumed@", date("Y-m-d H:i:s")
				." pid{".getmypid()."}"
				." mod{".$mod."}"
				." op{".$operation."}"
				." ".$nodeFrom
				."\n";

			if ($operation === "im_status_update" && $timeDiff > 600)
			{
				echo "Skip@", date("Y-m-d H:i:s")
					." pid{".getmypid()."}"
					." mod{".$mod."}"
					." op{".$operation."}"
					." ".$nodeFrom."\n";
				$relay->finishRelay();
				continue;
			}

			foreach ($message->getTo() as $nodeTo)
			{
				if ($nodeFrom !== $nodeTo)
				{
					$toSecret = getDbSecret($nodeTo);
					if (!$toSecret)
					{
						echo "Failed to sign@".date("Y-m-d H:i:s")
							." pid{".getmypid()."}"
							." mod{".$mod."}"
							." ".$nodeTo." - ".$message->getRawEvent()
							."\n";
					}
					else
					{
						$event = $message->getRawEvent();
						$signer = new \Bitrix\Main\Security\Sign\Signer();
						$signer->setKey($toSecret);
						$signature = $signer->getSignature($event);
						$relay->produce($message->getFrom(), $nodeTo, $event, $signature, $message->getCommandId());
						echo "Produced@".date("Y-m-d H:i:s")
							." pid{".getmypid()."}"
							." mod{".$mod."}"
							." op{".$operation."}"
							." ".$nodeFrom."->".$nodeTo
							."\n";
					}
				}
			}

			$relay->finishRelay();

			$sleep = false;
		}

		if ($sleep)
		{
			usleep(10000);
		}
	}
}
