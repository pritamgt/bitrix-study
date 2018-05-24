<?php
define("NOT_CHECK_PERMISSIONS", true);
define("NO_KEEP_STATISTIC", true);
define("BX_SECURITY_SESSION_VIRTUAL", true);
define("SKIP_DISK_QUOTA_CHECK", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
/** @var CDatabase $DB */
/** @var CUser $USER */
if (!$USER->IsAuthorized())
{
	@session_destroy();
}

$connection = \Bitrix\Main\Application::getConnection();
$sqlHelper = $connection->getSqlHelper();
$postList = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList();

if (\Bitrix\Main\Loader::includeModule('replicaserver'))
{
	switch ($_GET["action"])
	{
	case "queue":
		$EVENT = $postList->getRaw("EVENT");
		$EVENTS = $postList->getRaw("EVENTS");
		$SIGNATURE = $postList->getRaw("SIGNATURE");
		$NODE_FROM = $postList->getRaw("NODE_FROM");

		$toSecret = getDbSecret($NODE_FROM);
		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey($toSecret);
		if ($EVENT !== null)
		{
			$signature = $signer->getSignature($EVENT);
		}
		elseif ($EVENTS !== null && is_array($EVENTS))
		{
			$signature = $signer->getSignature(implode('', $EVENTS));
		}
		else
		{
			echo "No event(s)";
			break;
		}

		if ($signature !== $SIGNATURE)
		{
			echo "Wrong signature";
			break;
		}

		if (isset($EVENT) && is_array($event = unserialize($EVENT)))
		{
			$events = array($event);
		}
		elseif (isset($EVENTS) && is_array($EVENTS))
		{
			$events = array();
			foreach ($EVENTS as $k => $event)
			{
				if (is_array(unserialize($event)))
				{
					$events[] = $event;
				}
				else
				{
					echo "unpack error at event #".intval($k);
					$events = false;
					break;
				}
			}
		}
		else
		{
			$events = false;
		}

		if (!\Bitrix\ReplicaServer\Server\NodeRelation::getInstance()->checkRelations($NODE_FROM, $events))
		{
			echo "Wrong relations";
			break;
		}

		if ($events)
		{
			$connection->startTransaction();
			$server = new \Bitrix\ReplicaServer\Log\Server;
			foreach ($events as $event)
			{
				$server->produce($NODE_FROM, $event, $signer->getSignature($event));
			}
			$connection->commitTransaction();
			echo "true";
		}
		else
		{
			echo "No event(s)";
		}

		break;
	case "query":
		$DOMAIN = $postList->getRaw("DOMAIN");
		$SIGNATURE = $postList->getRaw("SIGNATURE");
		$NODE_FROM = $postList->getRaw("NODE_FROM");

		$toSecret = getDbSecret($NODE_FROM);
		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey($toSecret);
		$signature = $signer->getSignature($DOMAIN);

		if ($signature !== $SIGNATURE)
		{
			echo "Wrong signature";
		}
		elseif (isset($DOMAIN) && strlen($DOMAIN) > 0)
		{
			$name = queryHostTable("name", "domain", $DOMAIN);
			if ($name)
			{
				echo "name:{", $name."}";
			}
		}
		else
		{
			echo "No domain";
		}
		break;
	case "monitoring":
		if ($DB->TableExists("b_replica_node"))
		{
			echo \Bitrix\ReplicaServer\Log\Relay::getQueueStat() + \Bitrix\ReplicaServer\Log\Server::getQueueStat();
		}
		else
		{
			echo "No server size table(s)";
		}
		break;
	}
}
else
{
	echo "Failed to include replica.";
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
