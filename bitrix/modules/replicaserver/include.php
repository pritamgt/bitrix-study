<?php

CModule::AddAutoloadClasses(
	"replicaserver",
	array(
		"replicaserver" => "install/index.php",
	)
);

function getReplicaClientUrl($address)
{
	$conf = \Bitrix\Main\Config\Configuration::getValue("replicaserver");
	if ($conf && $conf["client"])
	{
		if (preg_match("/office.bitrix.ru\$/", $address)) //TODO: remove
		{
			return "http://".$address."/bitrix/tools/replica.php";
		}  //TODO: remove
		else //TODO: remove
		{
			return $conf["client"]["proto"]."://".$address."/bitrix/tools/replica.php";
		}
	}
	return "";
}

function getDomainByName($node)
{
	return queryHostTable("domain", "name", $node);
}

function getDbSecret($node)
{
	return queryHostTable("secret", "name", $node);
}

function queryHostTable($selectField, $whereField, $whereValue)
{
	if ($selectField !== "name" && $selectField !== "domain" && $selectField !== "secret")
	{
		return false;
	}

	if ($whereField !== "name" && $whereField !== "domain")
	{
		return false;
	}

	$connection = \Bitrix\Main\Application::getConnection();
	$sqlHelper = $connection->getSqlHelper();

	$select = "
		select $selectField
		from host
		where $whereField = '".$sqlHelper->forSql($whereValue)."'
	";
	$list = $connection->query($select);
	$info = $list->fetch();
	if ($info)
	{
		return $info[$selectField];
	}
	else
	{
		$select = "
			select $selectField
			from b_replica_host
			where $whereField = '".$sqlHelper->forSql($whereValue)."'
		";
		$list = $connection->query($select);
		$info = $list->fetch();
		if ($info)
		{
			return $info[$selectField];
		}
	}
	return false;
}
