<?
/* @var CMain $APPLICATION */

if(!check_bitrix_sessid())
	return;

if ($ex = $APPLICATION->GetException())
{
	$m = new CAdminMessage(array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_UNINST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
}
else
{
	$m = new CAdminMessage(array(
		"TYPE" => "OK",
		"MESSAGE" => GetMessage("MOD_UNINST_OK"),
	));
}
echo $m->Show();
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
<form>
