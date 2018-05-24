<?IncludeModuleLangFile(__FILE__);?>
<p><?echo GetMessage("REP_INSTALL")?></p>
<?/** @var CMain $APPLICATION */?>
<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>" />
	<input type="hidden" name="id" value="replica" />
	<input type="hidden" name="install" value="Y" />
	<input type="hidden" name="step" value="2" />
	<input type="submit" name="inst" value="<?echo GetMessage("MOD_INSTALL")?>" />
</form>
