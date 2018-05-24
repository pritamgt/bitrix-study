<?
define("ADMIN_MODULE_NAME", "replicaserver");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
\Bitrix\Main\Loader::includeModule('replicaserver');

IncludeModuleLangFile(__FILE__);

$RIGHT = $APPLICATION->GetGroupRight("replicaserver");
if ($RIGHT < "W" || !$DB->TableExists('b_replica_node'))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$sTableID = "tbl_replica_admin_table";
$lAdmin = new CAdminList($sTableID);

if ($arID = $lAdmin->GroupAction())
{
	foreach ($arID as $ID)
	{
		if (strlen($ID) <= 0)
		{
			continue;
		}
		$ID = intval($ID);
		switch ($_REQUEST['action'])
		{
		case "skip":
			$rsData = $DB->Query("
				SELECT *
				FROM b_replica_node
				WHERE ID = ".intval($ID)."
			");
			$arData = $rsData->fetch();
			if ($arData && $arData["LOG_TO_ID"])
			{
				$DB->Query("DELETE FROM b_replica_log_to WHERE ID =".intval($arData["LOG_TO_ID"]));
				$DB->Query("DELETE FROM b_replica_node WHERE ID =".intval($arData["ID"]));
				sleep(1);
			}
			elseif ($arData && $arData["LOG_FROM_ID"])
			{
				$DB->Query("DELETE FROM b_replica_log_from WHERE ID =".intval($arData["LOG_FROM_ID"]));
				$DB->Query("DELETE FROM b_replica_node WHERE ID =".intval($arData["ID"]));
				sleep(1);
			}
			break;
		case "retry":
			$DB->Query("DELETE FROM b_replica_node WHERE ID =".intval($ID));
			sleep(1);
			break;
		}
	}
}

$arHeaders = array(
	array(
		"id" => "ID",
		"content" => "ID",
		"default" => true,
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => "TIMESTAMP_X",
		"default" => true,
	),
	array(
		"id" => "NODE_FROM",
		"content" => "NODE_FROM",
		"default" => true,
	),
	array(
		"id" => "LOG",
		"content" => "LOG",
		"default" => true,
	),
	array(
		"id" => "NODE_TO",
		"content" => "NODE_TO",
		"default" => true,
	),
	array(
		"id" => "RESPONSE",
		"content" => "RESPONSE",
		"default" => true,
	),
);
$lAdmin->AddHeaders($arHeaders);

$rsData = $DB->Query("
	SELECT *
	FROM b_replica_node
	ORDER BY ID
");

$rsData = new CAdminResult($rsData, $sTableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint("Records"));
$c = 0;
while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);

	if (
		\Bitrix\ReplicaServer\Log\Server::checkAutoRetry($arRes['HTTP_STATUS'], $arRes['HTTP_RESULT'])
		|| \Bitrix\ReplicaServer\Log\Server::checkAutoSkip($arRes['HTTP_STATUS'], $arRes['HTTP_RESULT'])
	)
	{
		$htmlFLAG = '<span class="adm-lamp adm-lamp-in-list adm-lamp-green"></span>';
	}
	else
	{
		$htmlFLAG = '<span class="adm-lamp adm-lamp-in-list adm-lamp-red"></span>';
	}

	$row->AddViewField("ID", $htmlFLAG.htmlspecialcharsEx($arRes['ID']));

	if ($arRes["NODE_TO"])
	{
		$domain = getDomainByName($arRes["NODE_TO"]);
		if ($domain)
		{
			$row->AddViewField("NODE_TO", htmlspecialcharsEx($arRes["NODE_TO"])."<br>".htmlspecialcharsEx($domain));
		}
	}

	if ($arRes["LOG_TO_ID"])
	{
		$rs = $DB->Query("select EVENT, SIGNATURE, NODE_FROM, NODE_TO from b_replica_log_to where id=".intval($arRes["LOG_TO_ID"]));
		$ar = $rs->Fetch();
		if ($ar)
		{
			if ($ar["NODE_FROM"])
			{
				$domain = getDomainByName($ar["NODE_FROM"]);
				if ($domain)
				{
					$row->AddViewField("NODE_FROM", htmlspecialcharsEx($ar["NODE_FROM"])."<br>".htmlspecialcharsEx($domain));
				}
			}

			$a = unserialize($ar["EVENT"]);
			if (is_array($a) && isset($a["event"]) && is_array($a["event"]))
			{
				$row->AddViewField("LOG", "<pre>".htmlspecialcharsEx(print_r($a["event"], 1))."</pre>");
			}
			elseif ($a)
			{
				$row->AddViewField("LOG", "<pre>".htmlspecialcharsEx(print_r($a, 1))."</pre>");
			}
			else
			{
				$row->AddViewField("LOG", htmlspecialcharsEx($ar["EVENT"]));
			}

			if ($arRes["HTTP_RESULT"] === '[]Wrong signature')
			{
				$fromSecret = getDbSecret($ar["NODE_TO"]);
				$signer = new \Bitrix\Main\Security\Sign\Signer();
				$signer->setKey($fromSecret);
				$signature = $signer->getSignature($ar["EVENT"]);

				$row->AddViewField("HTTP_RESULT", htmlspecialcharsEx($arRes["HTTP_RESULT"])."<br>"
					.'got:&nbsp;'.htmlspecialcharsEx($ar["SIGNATURE"])."<br>"
					.'expected:&nbsp;'.htmlspecialcharsEx($signature)
				);
			}
		}
	}
	elseif ($arRes["LOG_FROM_ID"])
	{
		$rs = $DB->Query("select EVENT, SIGNATURE, NODE_FROM from b_replica_log_from where id=".intval($arRes["LOG_FROM_ID"]));
		$ar = $rs->Fetch();
		if ($ar)
		{
			if ($ar["NODE_FROM"])
			{
				$domain = getDomainByName($ar["NODE_FROM"]);
				if ($domain)
				{
					$row->AddViewField("NODE_FROM", htmlspecialcharsEx($ar["NODE_FROM"])."<br>".htmlspecialcharsEx($domain));
				}
			}

			$a = unserialize($ar["EVENT"]);
			if (is_array($a) && isset($a["event"]) && is_array($a["event"]))
			{
				$row->AddViewField("LOG", "<pre>".htmlspecialcharsEx(print_r($a["event"], 1))."</pre>");
			}
			elseif ($a)
			{
				$row->AddViewField("LOG", "<pre>".htmlspecialcharsEx(print_r($a, 1))."</pre>");
			}
			else
			{
				$row->AddViewField("LOG", htmlspecialcharsEx($ar["EVENT"]));
			}
		}
	}

	$row->AddViewField("RESPONSE", htmlspecialcharsEx($arRes["HTTP_STATUS"])."<hr>".htmlspecialcharsEx($arRes["HTTP_RESULT"]));

	$arActions = array(
		array(
			"TEXT" => "Skip",
			"ACTION" => $lAdmin->ActionDoGroup($arRes["ID"], "skip"),
		),
		array(
			"TEXT" => "Retry",
			"ACTION" => $lAdmin->ActionDoGroup($arRes["ID"], "retry"),
		),
	);

	if ($arRes["LOG_TO_ID"] > 0 && IsModuleInstalled("perfmon"))
	{
		$arActions[] = array(
			"SEPARATOR" => "Y",
		);
		$arActions[] = array(
			"TEXT" => "Edit",
			"ACTION" => $lAdmin->ActionRedirect("perfmon_row_edit.php?lang=".LANGUAGE_ID."&table_name=b_replica_log_to&pk%5BID%5D=".intval($arRes["LOG_TO_ID"])),
		);
	}

	$row->AddActions($arActions);
	$c++;
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount(),
		),
	)
);

$aContext = array(
	array(
		"TEXT" => "Refresh",
		"LINK" => "replica_admin.php?lang=".LANGUAGE_ID,
	),
);

$lAdmin->AddAdminContextMenu($aContext, true);

$title = ($c? "($c) ": "")."Node list";

$lAdmin->BeginPrologContent();

?>
<script>
	document.title = '<?echo CUtil::JSEscape($title)?>';
	BX.adminPanel.setTitle('<?echo CUtil::JSEscape($title)?>');
</script>
<p>
	In queue size: <? echo \Bitrix\ReplicaServer\Log\Relay::getQueueStat() ?><br/>
	Out queue size: <? echo \Bitrix\ReplicaServer\Log\Server::getQueueStat() ?><br/>
	Delay: <? echo \Bitrix\ReplicaServer\Log\Server::getQueueStat(\Bitrix\ReplicaServer\Log\Server::STAT_TIME_DIFF) ?>
</p>
<?
$lAdmin->EndPrologContent();


$lAdmin->CheckListMode();

$APPLICATION->SetTitle($title);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayList();

?><p>
	<input type="checkbox" id="auto_refresh" onclick="toggleAutoRefresh();"><label for="auto_refresh">Auto
		refresh</label>
</p>
<script>
	var autoTimer = false;
	function toggleAutoRefresh()
	{
		var checkBox = BX('auto_refresh');
		if (checkBox.checked)
		{
			autoTimer = setInterval('autoRefresh();', 5000);
		}
		else if (autoTimer)
		{
			clearInterval(autoTimer);
			autoTimer = false;
		}
	}
	function autoRefresh()
	{
		<?=$sTableID?>.
		GetAdminList('<?echo $APPLICATION->GetCurPage();?>?lang=<?=LANGUAGE_ID?>');
	}
</script>
<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
