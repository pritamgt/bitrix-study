<?
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\OAuth\ClientFeatureTable;
use Bitrix\OAuth\ClientTable;
use Bitrix\OAuth\License;

$module_id = "oauth";

/** @global CMain $APPLICATION */
$RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($RIGHT >= "R" && Loader::includeModule('oauth'))
{
	Loc::loadMessages(__FILE__);

	$aTabs = array(
		array(
			"DIV" => "edit1", "TAB" => Loc::getMessage("MAIN_TAB_SET"), "ICON" => "oauth_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET")
		),
		array(
			"DIV" => "edit2", "TAB" => Loc::getMessage("OAUTH_OPTION_REGISTER"), "ICON" => "oauth_settings", "TITLE" => Loc::getMessage("OAUTH_OPTION_REGISTER_TITLE")
		),
	);
	$tabControl = new CAdminTabControl("tabControl", $aTabs);

	$featuresList = array(
		ClientFeatureTable::REPLICA => array(),
	);

	$clientTypeList = array(
		ClientTable::TYPE_PORTAL => Loc::getMessage("OAUTH_CLIENT_TYPE_".ClientTable::TYPE_PORTAL),
		ClientTable::TYPE_EXTERNAL => Loc::getMessage("OAUTH_CLIENT_TYPE_".ClientTable::TYPE_EXTERNAL),
		ClientTable::TYPE_APPLICATION => Loc::getMessage("OAUTH_CLIENT_TYPE_".ClientTable::TYPE_APPLICATION),
		ClientTable::TYPE_SITE => Loc::getMessage("OAUTH_CLIENT_TYPE_".ClientTable::TYPE_SITE),
		ClientTable::TYPE_BITRIX => Loc::getMessage("OAUTH_CLIENT_TYPE_".ClientTable::TYPE_BITRIX),
	);

	$request = Context::getCurrent()->getRequest();

	if($request->isPost() && strlen($request["save"].$request["apply"]) > 0 && $RIGHT >= "W" && check_bitrix_sessid())
	{
		foreach($featuresList as $feature => $value)
		{
			foreach($clientTypeList as $clientType => $null)
			{
				Option::set(
					$module_id,
					"oauth_feature_".$feature."_".$clientType,
					$request["feature_".$feature."_".$clientType] === ClientFeatureTable::ENABLED
						? ClientFeatureTable::ENABLED
						: ClientFeatureTable::DISABLED
				);
			}
		}

		Option::set($module_id, "authorize_store", $request["authorize_store"]);
		Option::set($module_id, "check_client_license", $request["check_client_license"]);

		foreach($clientTypeList as $clientType => $null)
		{
			Option::set($module_id, "client_allow_".$clientType, $request["allowed_types"][$clientType]);
			Option::set($module_id, "client_allow_scope_".$clientType, $request["allowed_scopes"][$clientType]);
		}

		if(strlen($request["back_url_settings"]) > 0)
		{
			if(strlen($request["apply"]) > 0)
			{
				LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($request["back_url_settings"])."&".$tabControl->ActiveTabParam());
			}
			else
			{
				LocalRedirect($request["back_url_settings"]);
			}
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
		}
	}

	foreach($featuresList as $feature => $value)
	{
		foreach($clientTypeList as $clientType => $null)
		{
			$featuresList[$feature][$clientType] = Option::get($module_id, "oauth_feature_".$feature."_".$clientType, ClientFeatureTable::DISABLED);
		}
	}

	$allowedClients = array();
	$allowedClientsScope = array();
	foreach($clientTypeList as $clientType => $null)
	{
		$allowedClients[$clientType] = Option::get($module_id, "client_allow_".$clientType, "N");
		$allowedClientsScope[$clientType] = Option::get($module_id, "client_allow_scope_".$clientType, "");
	}

	$licenseCheck = Option::get($module_id, "check_client_license", License::LICENSE_CHECK);
	$authorizeStore = Option::get($module_id, "authorize_store", "N");

?>
<form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
<?=bitrix_sessid_post();?>
<?
	$tabControl->Begin();
	$tabControl->BeginNextTab();
?>
	<tr>
		<td width="30%" valign="top"><?=Loc::getMessage("OAUTH_OPTION_FEATURES")?>:</td>
		<td width="70%" valign="top">
			<table class="internal">
				<tr class="heading">
					<td></td>
<?
	foreach($clientTypeList as $clientType => $clientTypeTitle)
	{
?>
					<td><?=$clientTypeTitle?></td>
<?
	}
?>
				</tr>
<?
	foreach($featuresList as $feature => $values)
	{
?>
				<tr>
					<td><?=Loc::getMessage("OAUTH_OPTION_FEATURE_".$feature)?></td>
<?
		foreach($clientTypeList as $clientType => $clientTypeTitle)
		{
?>
					<td><input type="hidden" value="N" name="feature_<?=$feature?>_<?=$clientType?>" /><input type="checkbox" value="Y" id="feature_<?=$feature?>_<?=$clientType?>" name="feature_<?=$feature?>_<?=$clientType?>"<?=$values[$clientType] == ClientFeatureTable::ENABLED ? ' checked="checked"' : ''?> /><label for="feature_<?=$feature?>_<?=$clientType?>"> <?=Loc::getMessage('MAIN_YES')?></td>
<?
		}
?>
				</tr>
<?
	}
?>
			</table>
		</td>
	</tr>
<?
	$tabControl->BeginNextTab();
?>
	<tr>
		<td width="50%"><?=Loc::getMessage('OAUTH_OPTION_REGISTER_CHECK_LICENSE')?>: </td>
		<td width="50%"><input type="hidden" name="check_client_license" value="<?=License::LICENSE_SKIP?>" /><input type="checkbox" id="check_client_license" name="check_client_license" value="<?=License::LICENSE_CHECK?>" <?=$licenseCheck === License::LICENSE_CHECK ? ' checked="checked"' : ''?> /> <label for="check_client_license"><?=Loc::getMessage('MAIN_YES')?></label></td>
	</tr>
	<tr>
		<td width="50%"><?=Loc::getMessage('OAUTH_OPTION_AUTHORIZE_STORE')?>: </td>
		<td width="50%"><input type="hidden" name="authorize_store" value="N" /><input type="checkbox" id="authorize_store" name="authorize_store" value="Y" <?=$authorizeStore === "Y" ? ' checked="checked"' : ''?> /> <label for="authorize_store"><?=Loc::getMessage('MAIN_YES')?></label></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?=Loc::getMessage("OAUTH_OPTION_REGISTER_TYPE")?></td>
	</tr>
	<tr>
		<td align="center" colspan="2"><table class="internal">
<?
	foreach($clientTypeList as $type => $title):
?>
			<tr>
				<td><input type="hidden" name="allowed_types[<?=$type?>]" value="N" /><input type="checkbox" name="allowed_types[<?=$type?>]" id="type_<?=$type?>" value="Y" <?=$allowedClients[$type] === "Y" ? ' checked="checked"' : ''?> /> <label for="type_<?=$type?>"><?=$title?></label></td><td><input type="text" name="allowed_scopes[<?=$type?>]" value="<?=\Bitrix\Main\Text\Converter::getHtmlConverter()->encode($allowedClientsScope[$type])?>"/></td>
			</tr>
<?
	endforeach;
?>

		</table></td>
	</tr>
<?
	$tabControl->Buttons(array("save", "apply"));
	$tabControl->End();
?>
</form>
<?
}
?>