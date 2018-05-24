<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_CRONTAB", true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
while(@ob_end_clean());
@set_time_limit(0);
@ignore_user_abort(true);

if (\Bitrix\Main\Loader::includeModule('replicaserver'))
{
	$servers = array(
		"alice.node01",
		"bob.node01",
		"charlie.node01",
	);
	foreach ($servers as $serverName)
	{
		$http = new \Bitrix\Main\Web\HttpClient();
		$response = $http->post(getReplicaClientUrl($serverName)."?action=tear_down");
		echo "server:", htmlspecialcharsEx($serverName), "\n";
		echo "tear down response:", htmlspecialcharsEx($response), "\n";
	}

	$connection = \Bitrix\Main\Application::getConnection();
	$connection->query("DELETE FROM b_replica_log_from");
	$connection->query("DELETE FROM b_replica_node");
	$connection->query("DELETE FROM b_replica_log_to");
}
