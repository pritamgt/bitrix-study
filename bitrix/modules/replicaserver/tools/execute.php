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
	{
		;
	}
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

	$connection = \Bitrix\Main\Application::getConnection();
	$sqlHelper = $connection->getSqlHelper();
	$server = new \Bitrix\ReplicaServer\Log\Server($childrenCount, $mod);

	$myPid = getmypid();
	$startTime = date("Y-m-d");
	$cacheTime = 600;
	$domainToAddressCache = array();
	$addressToDomainCache = array();
	$sleepLessTimeStart = microtime(1);

	$ch = curl_init();
	$lastId = 0;

	while ($startTime === date("Y-m-d"))
	{
		$sleep = true;
		// Get the message
		if ($nextMessage = $server->getNext(/*$lastId*/))
		{
			$lastId = $nextMessage["ID"];
			$message = \Bitrix\ReplicaServer\Log\Message::createFromEvent($nextMessage["EVENT"]);
			if ($message)
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

				$event = $message->getEvent();
				if ($event && $event["event"])
				{
					$operation = $event["event"]["operation"];
					$opTimestamp = $event["event"]["ts"];
					$opAddress = $event["event"]["ip"];
				}
				else
				{
					$operation = '';
					$opTimestamp = '';
					$opAddress = '';
				}

				$isMessage = (
					$event
					&& $event["event"]
					&& $event["event"]["table"] === "b_im_message"
					&& $event["event"]["record"]["AUTHOR_ID"] > 0
					&& $event["event"]["record"]["NOTIFY_TYPE"] <= 0
				);

				if (\Bitrix\ReplicaServer\Server\StopList::isExists($nextMessage["NODE_TO"]))
				{
					echo "StopList@".date("Y-m-d H:i:s")." ".$eventTime
						." pid{".$myPid."}"
						." mod{".$mod."}"
						." op{".$operation."}"
						." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
						."\n";
					$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::SUCCESS);
					continue;
				}

				//Skip duplicates
				if ($operation === "im_status_update")
				{
					$connection = \Bitrix\Main\Application::getConnection();
					$sqlHelper = $connection->getSqlHelper();
					$statusList = $connection->query("
							select *
							from b_replica_log_to
							where id > ".intval($nextMessage["ID"])."
							and node_from = '".$sqlHelper->forSql($nextMessage["NODE_FROM"])."'
							and node_to = '".$sqlHelper->forSql($nextMessage["NODE_TO"])."'
							and cmd_id = '".$sqlHelper->forSql($nextMessage["CMD_ID"])."'
							limit 1
						");
					if ($statusList->fetch())
					{
						echo "Skip@".date("Y-m-d H:i:s")." ".$eventTime
							." dif{".$timeDiff."}"
							." pid{".$myPid."}"
							." mod{".$mod."}"
							." op{".$operation."}"
							." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
							."\n";
						$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::SUCCESS);
						continue;
					}
				}

				if ($host = getDomainByName($nextMessage["NODE_TO"]))
				{
					echo "Execute@".date("Y-m-d H:i:s")." ".$eventTime.""
						." dif{".$timeDiff."}"
						." pid{".$myPid."}"
						." mod{".$mod."}"
						." op{".$operation."}"
						." ts{".$opTimestamp."}"
						." ip{".$opAddress."}"
						." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
						."\n";
					$executeStart = microtime(true);

					if (!isset($domainToAddressCache[$host]) || $domainToAddressCache[$host]["expire"] < $executeStart)
					{
						$ip = gethostbyname($host);
						$domainToAddressCache[$host] = array(
							"expire" => $executeStart + $cacheTime,
							"ip" => $ip,
						);

						if (!isset($addressToDomainCache[$ip]) || $addressToDomainCache[$ip]["expire"] < $executeStart)
						{
							$addressToDomainCache[$ip] = array(
								"expire" => $executeStart + $cacheTime,
								"host" => $host,
							);
						}
					}
					//$http = new \Bitrix\Main\Web\HttpClient();
					//$response = $http->post($url = getReplicaClientUrl($host)."?action=execute&from=".urlencode($nextMessage["NODE_FROM"])."&op=".urlencode($operation), $message->getPost($nextMessage["NODE_TO"], $nextMessage["SIGNATURE"]));
					//$httpStatus = $http->getStatus();

					$ip = $domainToAddressCache[$host]["ip"];
					$hostToConnect = $addressToDomainCache[$ip]["host"];

					echo "Connect@".date("Y-m-d H:i:s")." ".$eventTime
						." pid{".$myPid."}"
						." mod{".$mod."}"
						." dasize{".count($domainToAddressCache)."}"
						." adsize{".count($addressToDomainCache)."}"
						." host{".$host."}"
						." ip{".$ip."}"
						." connect{".$hostToConnect."}"
						." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
						."\n";

					curl_setopt_array($ch, array(
						CURLOPT_URL => $url = getReplicaClientUrl($hostToConnect)."?action=execute&from=".urlencode($nextMessage["NODE_FROM"])."&op=".urlencode($operation),
						CURLOPT_HTTPHEADER => array('Host: '.$host),
						CURLOPT_POST => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POSTFIELDS => http_build_query($message->getPost($nextMessage["NODE_TO"], $nextMessage["SIGNATURE"])),
						CURLOPT_CONNECTTIMEOUT => 60,
						CURLOPT_TIMEOUT => 60,
						CURLOPT_MAXCONNECTS => max(5, count($addressToDomainCache) + 2),
					));

					$response = curl_exec($ch);
					$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

					$executeEnd = microtime(true);
					if ($response === 'true')
					{
						echo "Consume@".date("Y-m-d H:i:s")." ".$eventTime
							." dif{".$timeDiff."}"
							." pid{".$myPid."}"
							." mod{".$mod."}"
							." op{".$operation."}"
							." time{".(round($executeEnd - $executeStart, 3))."}"
							." is_msg{".$isMessage."}"
							." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
							."\n";
						$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::SUCCESS);
					}
					else
					{
						//$httpResult = "[".implode($http->getError())."]".$http->getResult();
						$httpResult = "[".implode(curl_error($ch))."]".$response;
						echo "Http@".date("Y-m-d H:i:s")." ".$eventTime
							." dif{".$timeDiff."}"
							." pid{".$myPid."}"
							." mod{".$mod."}"
							." op{".$operation."}"
							." time{".(round($executeEnd - $executeStart, 3))."}"
							." is_msg{".$isMessage."}"
							." host{".$host."}"
							." ip{".$ip."}"
							." connect{".$hostToConnect."}"
							." http_status{".$httpStatus."}"
							." ".$message->getFrom()."->".$nextMessage["NODE_TO"]
							." http_response(".strlen($httpResult)."): ".substr(str_replace(array("\n", "\r"), " ", $httpResult), 0, 100)
							."\n";
						if ($server->checkAutoSkip($httpStatus, $httpResult))
						{
							echo "AutoSkip@".date("Y-m-d H:i:s")
								." pid{".$myPid."}"
								." mod{".$mod."}"
								." log_id{".$nextMessage["ID"]."}"
								."\n";
							$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::SUCCESS);
						}
						else
						{
							$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::FAIL, $httpStatus, $httpResult);
						}
					}
					echo $response."\n";
				}
				else
				{
					echo "HostnameFailed@".date("Y-m-d H:i:s")." ".$eventTime
						." dif{".$timeDiff."}"
						." pid{".$myPid."}"
						." mod{".$mod."}"
						." ".$nextMessage["EVENT"]
						."\n";
					$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::FAIL, '-1', 'HostnameFailed');
				}
			}
			else
			{
				echo "UnpackFailed@".date("Y-m-d H:i:s")
					." pid{".$myPid."}"
					." mod{".$mod."}"
					." ".$nextMessage["EVENT"]
					."\n";
				$server->consume($nextMessage, \Bitrix\ReplicaServer\Log\Server::FAIL, '-1', 'UnpackFailed');
			}
			$sleep = false;
		}

		if ($sleep)
		{
			usleep(10000);
			$sleepLessTimeStart = microtime(1);
			$checkForFailed = true;
		}
		elseif ((microtime(1) - $sleepLessTimeStart) > 60)
		{
			echo "Throttle@".date("Y-m-d H:i:s")." pid{".$myPid."} mod{".$mod."}\n";
			$sleepLessTimeStart = microtime(1);
			$checkForFailed = true;
		}
		else
		{
			$checkForFailed = false;
		}

		if ($checkForFailed)
		{
			$select = "
					select *
					from b_replica_node
					where TIMESTAMP_X < date_sub(now(), interval 1 minute)
				";
			$list = $connection->query($select);
			while ($info = $list->fetch())
			{
				if ($server->checkAutoRetry($info["HTTP_STATUS"], $info["HTTP_RESULT"]))
				{
					$lastId = 0;
					$connection->query("
							DELETE FROM b_replica_node
							WHERE ID = ".$info["ID"]."
						");
				}
				elseif (
					$info["LOG_TO_ID"] > 0
					&& $server->checkAutoSkip($info["HTTP_STATUS"], $info["HTTP_RESULT"])
				)
				{
					$lastId = 0;
					echo "AutoSkip@".date("Y-m-d H:i:s")
						." pid{".$myPid."}"
						." mod{".$mod."}"
						." log_id{".$info["LOG_TO_ID"]."}"
						."\n";
					$connection->query("
							DELETE FROM b_replica_log_to
							WHERE ID = ".$info["LOG_TO_ID"]."
						");
					$connection->query("
							DELETE FROM b_replica_node
							WHERE ID = ".$info["ID"]."
						");
				}
			}
		}
	}
	curl_close($ch);
}
