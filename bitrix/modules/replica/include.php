<?php

if (!defined("CACHED_b_replica_file_dl"))
	define("CACHED_b_replica_file_dl", 3600);

function getReplicaServerUrl()
{
	$conf = \Bitrix\Main\Config\Configuration::getValue("replica");
	if ($conf && $conf["server"])
	{
		return $conf["server"]["proto"]."://".$conf["server"]["address"]."/bitrix/tools/replica.php";
	}
	return "";
}

if (!defined("BX24_SECRET"))
{
	define("BX24_SECRET", "replica");
}

function getCurrentDomain()
{
	if (defined("BX24_HOST_NAME"))
	{
		return BX24_HOST_NAME;
	}
	else
	{
		return BX24_REPLICA_DOMAIN;
	}
}

function getNameByDomain($domain = '')
{
	if ($domain === '' || $domain === null)
	{
		if (function_exists('bx_domain_to_name')) //current domain B24
		{
			return bx_domain_to_name(BX24_HOST_NAME);
		}
		elseif (defined("BX24_REPLICA_NAME")) //current domain BOX
		{
			return BX24_REPLICA_NAME;
		}
		else //current domain other cases
		{
			return false;
		}
	}
	else
	{
		//Check for B24 peer
		if (function_exists('bx_domain_to_name'))
		{
			$name = bx_domain_to_name($domain);
			if ($name)
			{
				return $name;
			}
		}

		//Query remote for BOX peer
		if (function_exists('bx_domain_to_name'))
		{
			$nodeFrom = bx_domain_to_name(BX24_HOST_NAME);
		}
		elseif (defined("BX24_REPLICA_NAME"))
		{
			$nodeFrom = BX24_REPLICA_NAME;
		}
		else
		{
			return false;
		}

		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey(BX24_SECRET);
		$signature = $signer->getSignature($domain);

		$http = new \Bitrix\Main\Web\HttpClient(array(
			"redirect" => false,
			"socketTimeout" => 5,
			"streamTimeout" => 5,
		));
		$http->post(getReplicaServerUrl()."?action=query", array(
			"NODE_FROM" => $nodeFrom,
			"DOMAIN" => $domain,
			"SIGNATURE" => $signature,
		));

		if ($http->getStatus() == 200)
		{
			if (preg_match("/name:{([^}]+)}/", $http->getResult(), $match))
			{
				return $match[1];
			}
		}

		return false;
	}
}

