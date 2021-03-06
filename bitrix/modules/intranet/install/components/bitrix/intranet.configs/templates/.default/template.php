<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?CJSCore::Init(array("access"));?>

<?if(isset($_GET['success'])): ?>
	<div class="content-edit-form-notice-successfully">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('CONFIG_SAVE_SUCCESSFULLY')?></span>
	</div>
<?endif;?>
<div class="content-edit-form-notice-error" <?if (!$arResult["ERROR"]):?>style="display: none;"<?endif?> id="config_error_block">
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=$arResult["ERROR"]?></span>
</div>

<form name="configPostForm" id="configPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<input type="hidden" name="save_settings" value="true" >
	<?=bitrix_sessid_post();?>

	<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
		<tr>
			<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_HEADER_SETTINGS')?></div>
			</td>
		</tr>

		<?if ($arResult["IS_BITRIX24"]):?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_COMPANY_NAME')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="logo_name" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "site_name", ""));?>"  class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_COMPANY_TITLE_NAME')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="site_title" value="<?=htmlspecialcharsbx(COption::GetOptionString("bitrix24", "site_title", ""));?>"  class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('config_rating_label_likeY')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="rating_text_like_y" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_y", ""));?>"  class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<!--
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('config_rating_label_likeN')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="rating_text_like_n" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_n", ""));?>"  class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		-->

		<?if ($arResult["IS_BITRIX24"]):?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_EMAIL_FROM')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="email_from" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "email_from", ""));?>"  class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

		<?if (
			$arResult["IS_BITRIX24"] && in_array($arResult["LICENSE_TYPE"], array("team", "company", "edu"))
			|| !$arResult["IS_BITRIX24"]
		):
			$logo24show = COption::GetOptionString("bitrix24", "logo24show", "Y");
		?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="logo24"><?=GetMessage('CONFIG_LOGO_24')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" id="logo24" name="logo24" <?if ($logo24show == "" || $logo24show == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>


		<tr data-field-id="congig_date">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_DATE_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<select name="date_format">
					<?foreach($arResult["DATE_FORMATS"] as $format):?>
					<option value="<?=$format?>" <?if ($format == $arResult["CUR_DATE_FORMAT"]) echo "selected"?>><?=$format?></option>
					<?endforeach?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_TIME_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<input type="radio" id="12_format" name="time_format" value="H:MI:SS T" <?if ($arResult["CUR_TIME_FORMAT"] == "H:MI:SS TT" || $arResult["CUR_TIME_FORMAT"] == "H:MI TT" || $arResult["CUR_TIME_FORMAT"] == "H:MI:SS T" || $arResult["CUR_TIME_FORMAT"] == "H:MI T") echo "checked"?>>
				<label for="12_format"><?=GetMessage("CONFIG_TIME_FORMAT_12")?></label>
				<br/>
				<input type="radio" id="24_format" name="time_format" value="HH:MI:SS" <?if ($arResult["CUR_TIME_FORMAT"] == "HH:MI:SS") echo "checked"?>>
				<label for="24_format"><?=GetMessage("CONFIG_TIME_FORMAT_24")?></label>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_NAME_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<select name="" onchange="if(this.value != 'other'){this.form.FORMAT_NAME.value = this.value;this.form.FORMAT_NAME.style.display='none';} else {this.form.FORMAT_NAME.style.display='block';}">
					<?
					$formatExists = false;
					foreach ($arResult["NAME_FORMATS"] as $template => $value)
					{
						if ($template == $arResult["CUR_NAME_FORMAT"])
							$formatExists = true;

						echo '<option value="'.$template.'"'.($template == $arResult["CUR_NAME_FORMAT"] ? ' selected' : '').'>'.htmlspecialcharsex($value).'</option>'."\n";
					}
					?>
					<option value="other" <?=($formatExists ? '' : "selected")?>><?echo GetMessage("CONFIG_CULTURE_OTHER")?></option>
				</select>

				<input type="text" style="margin-top: 10px;<?=($formatExists ? 'display:none' : '')?>" name="FORMAT_NAME"  value="<?=htmlspecialcharsbx($arResult["CUR_NAME_FORMAT"])?>" class="content-edit-form-field-input-text" />
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_week_start">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WEEK_START')?></td>
			<td class="content-edit-form-field-input">
				<select name="WEEK_START">
					<?
					for ($i = 0; $i < 7; $i++)
					{
						echo '<option value="'.$i.'"'.($i == $arResult["WEEK_START"] ? ' selected="selected"' : '').'>'.GetMessage('DAY_OF_WEEK_' .$i).'</option>';
					}
					?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WORK_TIME')?></td>
			<td class="content-edit-form-field-input">
				<select name="work_time_start">
					<?foreach($arResult["WORKTIME_LIST"] as $key => $val):?>
						<option value="<?= $key?>" <? if ($arResult["CALENDAT_SET"]['work_time_start'] == $key) echo ' selected="selected" ';?>><?= $val?></option>
					<?endforeach;?>
				</select>
				-
				<select name="work_time_end">
					<?foreach($arResult["WORKTIME_LIST"] as $key => $val):?>
						<option value="<?= $key?>" <? if ($arResult["CALENDAT_SET"]['work_time_end'] == $key) echo ' selected="selected" ';?>><?= $val?></option>
					<?endforeach;?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WEEK_HOLIDAYS')?></td>
			<td class="content-edit-form-field-input">
				<select size="7" multiple=true id="cal_week_holidays" name="week_holidays[]">
					<?foreach($arResult["WEEK_DAYS"] as $day):?>
						<option value="<?= $day?>" <?if (in_array($day, $arResult["CALENDAT_SET"]['week_holidays']))echo ' selected="selected"';?>><?= GetMessage('CAL_OPTION_FIRSTDAY_'.$day)?></option>
					<?endforeach;?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_YEAR_HOLIDAYS')?></td>
			<td class="content-edit-form-field-input">
				<input name="year_holidays" type="text" value="<?= htmlspecialcharsbx($arResult["CALENDAT_SET"]['year_holidays'])?>" id="cal_year_holidays" size="60" class="content-edit-form-field-input-text"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_PHONE_NUMBER_DEFAULT_COUNTRY')?></td>
			<td class="content-edit-form-field-input">
				<select name="phone_number_default_country">
					<?foreach($arResult["COUNTRIES"] as $key => $val):?>
						<option value="<?= $key?>" <? if ($arResult["PHONE_NUMBER_DEFAULT_COUNTRY"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
					<?endforeach;?>
				</select>
			</td>
		</tr>

		<?if ($arResult["IS_BITRIX24"]):?>
	<!-- Organization type-->
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_ORGANIZATION')?></td>
			<td class="content-edit-form-field-input">
				<input type="radio" id="organisation" name="organization" value="" <?if ($arResult["ORGANIZATION_TYPE"] == "") echo "checked"?>>
				<label for="organization"><?=GetMessage("CONFIG_ORGANIZATION_DEF")?></label>
				<br/>
				<input type="radio" id="organization_public" name="organization" value="public_organization" <?if ($arResult["ORGANIZATION_TYPE"] == "public_organization") echo "checked"?>>
				<label for="organization_public"><?=GetMessage("CONFIG_ORGANIZATION_PUBLIC")?></label>
				<?if (in_array(LANGUAGE_ID, array("ru", "ua"))):?>
					<br/>
					<input type="radio" id="organization_gov" name="organization" value="gov_organization" <?if ($arResult["ORGANIZATION_TYPE"] == "gov_organization") echo "checked"?>>
					<label for="organization_gov"><?=GetMessage("CONFIG_ORGANIZATION_GOV")?></label>
				<?endif?>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

	<!-- show fired employees -->
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="show_fired_employees"><?=GetMessage('CONFIG_SHOW_FIRED_EMPLOYEES')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="show_fired_employees" id="show_fired_employees" <?if (COption::GetOptionString("bitrix24", "show_fired_employees", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- webdav/disk-->
		<tr data-field-id="congig_date">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_DISK_VIEWER_SERVICE')?></td>
			<td class="content-edit-form-field-input">
				<select name="default_viewer_service">
					<?foreach($arResult["DISK_VIEWER_SERVICE"] as $code => $name):?>
						<option value="<?=$code?>" <?if ($code == $arResult["DISK_VIEWER_SERVICE_DEFAULT"]) echo "selected"?>><?=$name?></option>
					<?endforeach?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<?if ($arResult["IS_DISK_CONVERTED"]):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_edit_object_in_uf"><?=GetMessage('CONFIG_DISK_ALLOW_EDIT_OBJECT_IN_UF')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_edit_object_in_uf" id="disk_allow_edit_object_in_uf" <?if (COption::GetOptionString("disk", "disk_allow_edit_object_in_uf", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_autoconnect_shared_objects"><?=GetMessage('CONFIG_WEBDAV_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_autoconnect_shared_objects" id="disk_allow_autoconnect_shared_objects" <?if (COption::GetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<?if ($arResult["IS_TRANSFORMER_INSTALLED"]):?>
				<tr>
					<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_document_transformation"><?=GetMessage('CONFIG_DISK_ALLOW_DOCUMENT_TRANSFORMATION')?></label></td>
					<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_document_transformation" id="disk_allow_document_transformation" <?if (COption::GetOptionString("disk", "disk_allow_document_transformation", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
					<td class="content-edit-form-field-error"></td>
				</tr>
				<tr>
					<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_video_transformation"><?=GetMessage('CONFIG_DISK_ALLOW_VIDEO_TRANSFORMATION')?></label></td>
					<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_video_transformation" id="disk_allow_video_transformation" <?if (COption::GetOptionString("disk", "disk_allow_video_transformation", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
					<td class="content-edit-form-field-error"></td>
				</tr>
				<tr>
					<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_transform_files_on_open"><?=GetMessage('CONFIG_DISK_TRANSFORM_FILES_ON_OPEN')?></label></td>
					<td class="content-edit-form-field-input"><input type="checkbox" name="disk_transform_files_on_open" id="disk_transform_files_on_open" <?if (COption::GetOptionString("disk", "disk_transform_files_on_open", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
					<td class="content-edit-form-field-error"></td>
				</tr>
			<?endif;?>
		<?else:?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_global"><?=GetMessage('CONFIG_WEBDAV_SERVICES_GLOBAL')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_global" id="webdav_global" <?if (COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_local"><?=GetMessage('CONFIG_WEBDAV_SERVICES_LOCAL')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_local" id="webdav_local" <?if (COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_autoconnect_share_group_folder"><?=GetMessage('CONFIG_WEBDAV_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_autoconnect_share_group_folder" id="webdav_autoconnect_share_group_folder" <?if (COption::GetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_version_limit_per_file"><?=GetMessage('CONFIG_DISK_VERSION_LIMIT_PER_FILE')?></label>
				<?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))):?>
					<?
					CBitrix24::initLicenseInfoPopupJS("disk_version_limit_per_file");
					?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" data-role="config-disk-version-limit-per-file" style="position: relative;bottom: -1px; margin-left: 5px;"/>
					<script>
						BX.ready(function(){
							var lock4 = document.querySelector("[data-role='config-disk-version-limit-per-file']");
							if (lock4)
							{
								BX.bind(lock4, "click", function(){
									B24.licenseInfoPopup.show('disk_version_limit_per_file', '<?=GetMessageJS("CONFIG_DISK_LIMIT_LOCK_POPUP_TITLE")?>',
										'<?=GetMessageJS("CONFIG_DISK_LIMIT_LOCK_POPUP_TEXT")?>');
								});
							}
						});
					</script>
				<?endif?>

			</td>
			<td class="content-edit-form-field-input">
				<select name="disk_version_limit_per_file" <?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))) echo "disabled";?>>
					<?foreach($arResult["DISK_LIMIT_PER_FILE"] as $code => $name):?>
						<option value="<?=$code?>" <?if ($code == $arResult["DISK_LIMIT_PER_FILE_SELECTED"]) echo "selected"?>><?=$name?></option>
					<?endforeach?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_allow_use_external_link"><?=GetMessage('CONFIG_DISK_ALLOW_USE_EXTERNAL_LINK')?></label>
				<?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))):?>
					<?
					CBitrix24::initLicenseInfoPopupJS("disk_allow_use_external_link");
					?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" data-role="config-lock-disk-external-link" style="position: relative;bottom: -1px; margin-left: 5px;"/>
					<script>
						BX.ready(function(){
							var lock1 = document.querySelector("[data-role='config-lock-disk-external-link']");
							if (lock1)
							{
								BX.bind(lock1, "click", function(){
									B24.licenseInfoPopup.show('disk_allow_use_external_link', '<?=GetMessageJS("CONFIG_DISK_LOCK_POPUP_TITLE")?>',
										'<?=GetMessageJS("CONFIG_DISK_LOCK_POPUP_TEXT")?>');
								});
							}
						});
					</script>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" <?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))) echo "disabled";?> id="disk_allow_use_external_link" name="disk_allow_use_external_link" <?if (COption::GetOptionString("disk", "disk_allow_use_external_link", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_object_lock_enabled"><?=GetMessage('CONFIG_DISK_OBJECT_LOCK_ENABLED')?></label>
				<?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))):?>
					<?
					CBitrix24::initLicenseInfoPopupJS("disk_object_lock_enabled");
					?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" data-role="config-lock-disk-object-lock" style="position: relative;bottom: -1px; margin-left: 5px;"/>
					<script>
						BX.ready(function(){
							var lock2 = document.querySelector("[data-role='config-lock-disk-object-lock']");
							if (lock2)
							{
								BX.bind(lock2, "click", function(){
									B24.licenseInfoPopup.show('disk_object_lock_enabled', '<?=GetMessageJS("CONFIG_DISK_LOCK_POPUP_TITLE")?>',
										'<?=GetMessageJS("CONFIG_DISK_LOCK_POPUP_TEXT")?>');
								});
							}
						});
					</script>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" <?if ($arResult["IS_BITRIX24"] && !in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "demo", "edu"))) echo "disabled";?> id="disk_object_lock_enabled" name="disk_object_lock_enabled" <?if (COption::GetOptionString("disk", "disk_object_lock_enabled", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_livefeed_toall"><?=GetMessage('CONFIG_ALLOW_TOALL')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" id="allow_livefeed_toall" name="allow_livefeed_toall" <?if (COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- live feed right-->
		<tr id="RIGHTS_all" style="display: <?=(COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;">
			<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
			<td class="content-edit-form-field-input">
				<?
				$val = COption::GetOptionString("socialnetwork", "livefeed_toall_rights", 'a:1:{i:0;s:2:"AU";}');

				$arToAllRights = unserialize($val);
				if (!$arToAllRights)
					$arToAllRights = unserialize('a:1:{i:0;s:2:"AU";}');

				$access = new CAccess();
				$arNames = $access->GetNames($arToAllRights);
				?>
				<div id="RIGHTS_div">
					<?
					foreach($arToAllRights as $right)
					{
						?><input type="hidden" name="livefeed_toall_rights[]" id="livefeed_toall_rights_<?=htmlspecialcharsbx($right)?>" value="<?=htmlspecialcharsbx($right)?>"><?
						?><div data-bx-right="<?=htmlspecialcharsbx($right) ?>" class="toall-right"><span><?=(!empty($arNames[$right]["provider"]) ? $arNames[$right]["provider"].": " : "").$arNames[$right]["name"]?>&nbsp;</span><a href="javascript:void(0);" onclick="B24ConfigsLiveFeedObj.DeleteToAllAccessRow(this);" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a></div><?
					}
					?>
				</div>
				<div style="padding-top: 5px;"><a href="javascript:void(0)" class="bx-action-href" onclick="B24ConfigsLiveFeedObj.ShowToAllAccessPopup(B24ConfigsLiveFeedObj.arToAllRights);"><?=GetMessage("CONFIG_TOALL_RIGHTS_ADD")?></a></div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr id="DEFAULT_all" style="display: <?=(COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;">
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="default_livefeed_toall"><?=GetMessage('CONFIG_DEFAULT_TOALL')?></label>
			</td>
			<td class="content-edit-form-field-input"><input type="checkbox" id="default_livefeed_toall" name="default_livefeed_toall" <?if (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- im general chat right-->
		<?
			$imAllow = COption::GetOptionString("im", "allow_send_to_general_chat_all");
			$imAllowRights = COption::GetOptionString("im", "allow_send_to_general_chat_rights");
		?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_general_chat_toall"><?=GetMessage('CONFIG_IM_CHAT_RIGHTS')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" id="allow_general_chat_toall" name="allow_general_chat_toall" <?if ($imAllow == "Y" || $imAllow == "N" && !empty($imAllowRights)):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr id="chat_rights_all" style="display: <?=($imAllow == "Y" || $imAllow == "N" && !empty($imAllowRights) ? "table-row" : "none")?>;">
			<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
			<td class="content-edit-form-field-input">
				<?
				$arChatToAllRights = Array();
				if (!empty($imAllowRights))
					$arChatToAllRights = explode(",", $imAllowRights);

				$access = new CAccess();
				$arNames = $access->GetNames($arChatToAllRights);
				?>
				<div id="chat_RIGHTS_div">
					<?
					foreach($arChatToAllRights as $right)
					{
						?><input type="hidden" name="imchat_toall_rights[]" id="imchat_toall_rights_<?=htmlspecialcharsbx($right)?>" value="<?=htmlspecialcharsbx($right)?>"><?
						?><div data-bx-right="<?=htmlspecialcharsbx($right) ?>" class="toall-right"><span><?=(!empty($arNames[$right]["provider"]) ? $arNames[$right]["provider"].": " : "").$arNames[$right]["name"]?>&nbsp;</span><a href="javascript:void(0);" onclick="B24ImChatObj.DeleteToAllAccessRow(this);" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a></div><?
					}
					?>
				</div>
				<div style="padding-top: 5px;"><a href="javascript:void(0)" class="bx-action-href" onclick="B24ImChatObj.ShowToAllAccessPopup(B24ImChatObj.arToAllRights);"><?=GetMessage("CONFIG_TOALL_RIGHTS_ADD")?></a></div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="general_chat_message_join"><?=GetMessage('CONFIG_IM_GENERSL_CHAT_MESSAGE_JOIN')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="general_chat_message_join" id="general_chat_message_join" <?if (COption::GetOptionString("im", "general_chat_message_join")):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="general_chat_message_leave"><?=GetMessage('CONFIG_IM_GENERSL_CHAT_MESSAGE_LEAVE')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="general_chat_message_leave" id="general_chat_message_leave" <?if (COption::GetOptionString("im", "general_chat_message_leave")):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="url_preview_enable"><?=GetMessage('CONFIG_URL_PREVIEW_ENABLE')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="url_preview_enable" id="url_preview_enable" <?if (COption::GetOptionString("main", "url_preview_enable", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<?
		if ($arResult["IS_BITRIX24"])
		{
		?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_register"><?=GetMessage('CONFIG_ALLOW_SELF_REGISTER')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="allow_register" id="configs_allow_register" <?if ($arResult["ALLOW_SELF_REGISTER"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_invite_users"><?=GetMessage('CONFIG_ALLOW_INVITE_USERS')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="allow_invite_users" value="Y" id="configs_allow_invite_users" <?if ($arResult["ALLOW_INVITE_USERS"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_new_user_lf"><?=GetMessage('CONFIG_ALLOW_NEW_USER_LF')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="allow_new_user_lf" value="Y" id="configs_allow_new_user_lf" <?if ($arResult["ALLOW_NEW_USER_LF"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="network_avaiable"><?=GetMessage('CONFIG_NETWORK_AVAILABLE')?></label>
				<?
				$disabled = $arResult['ALLOW_NETWORK_CHANGE'] !== 'Y';
				if($arResult["IS_BITRIX24"] && !$arResult['CREATOR_CONFIRMED']):
				?>
					<img src="<?=$this->GetFolder();?>/images/lock.png"  style="position: relative;bottom: -1px; margin-left: 5px;" onmouseover="BX.hint(this, '<?=htmlspecialcharsbx(CUtil::JSEscape(GetMessage('CONFIG_NETWORK_AVAILABLE_NOT_CONFIRMED')))?>')" />
				<?
				elseif ($arResult['ALLOW_NETWORK_CHANGE'] === 'N'):

					CBitrix24::initLicenseInfoPopupJS("network_available");
				?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" data-role="config-lock-network-available" style="position: relative;bottom: -1px; margin-left: 5px;"/>
					<script>
						BX.ready(function(){
							var lock3 = document.querySelector("[data-role='config-lock-network-available']");
							if (lock3)
							{
								BX.bind(lock3, "click", function(){
									B24.licenseInfoPopup.show('network-available', '<?=GetMessageJS("CONFIG_NETWORK_AVAILABLE_TITLE")?>',
										'<?=GetMessageJS("CONFIG_NETWORK_AVAILABLE_TEXT_NEW", array("#PRICE#" => $arResult["PROJECT_PRICE"]))?>', false);
								});
							}
						});
					</script>
				<?
				endif;
				?>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox"  <?if($disabled) echo "disabled";?>  name="network_avaiable" value="Y" id="network_avaiable" <?if ($arResult["NETWORK_AVAILABLE"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?
		}
		?>

	<!-- secutity -->
		<tr>
			<td class="content-edit-form-header" colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_HEADER_SECUTIRY')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_OTP_SECURITY2')?></td>
			<td class="content-edit-form-field-input"><input type="checkbox" <?if (!$arResult["SECURITY_IS_USER_OTP_ACTIVE"] && !$arResult["SECURITY_OTP"]):?> onclick="BX.Bitrix24.Configs.Functions.adminOtpIsRequiredInfo(this);return false;"<?endif?> onchange="BX.Bitrix24.Configs.Functions.otpSwitchOffInfo(this);" name="security_otp"  class="content-edit-form-field-input-selector" <?if ($arResult["SECURITY_OTP"]):?>checked<?endif?>/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_OTP_SECURITY_DAYS')?></td>
			<td class="content-edit-form-field-input">
				<select id="security_otp_days" name="security_otp_days">
					<?for($i=5; $i<=10; $i++):?>
						<option value="<?=$i?>" <?if ($arResult["SECURITY_OTP_DAYS"] == $i) echo 'selected="selected"';?>><?=FormatDate("ddiff", time()-60*60*24*$i)?></option>
					<?endfor;?>
				</select>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td colspan="3">
				<div class="config_notify_message" style="margin: 10px 20px 10px 20px">
					<?=GetMessage("CONFIG_OTP_SECURITY_INFO")?>
					<a href="javascript:void(0)" onclick="BX.nextSibling(this).style.display='block'; BX.remove(this)"><?=GetMessage("CONFIG_MORE")?></a>
					<span style="display: none"><?=GetMessage("CONFIG_OTP_SECURITY_INFO2")?></span>
				</div>
			</td>
		</tr>

	<?
	if ($arResult["IS_BITRIX24"])
	{
	?>
	<!-- features -->
		<tr>
			<td class="content-edit-form-header " colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_FEATURES_TITLE')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input" colspan="2">

				<input type="checkbox" name="feature_crm" id="feature_crm" <?if (IsModuleInstalled("crm")) echo "checked"?>/>
				<label for="feature_crm"><?=GetMessage("CONFIG_FEATURES_CRM")?></label><br/>

				<?if (in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "edu"))):?>
					<input type="checkbox" name="feature_extranet" id="feature_extranet" <?if (IsModuleInstalled("extranet")) echo "checked"?>/>
					<label for="feature_extranet"><?=GetMessage("CONFIG_FEATURES_EXTRANET")?></label><br/>
				<?endif?>

				<?if ($arResult["LICENSE_TYPE"] == "company" || $arResult["LICENSE_TYPE"] == "nfr" || $arResult["LICENSE_TYPE"] == "edu"):?>
					<input type="checkbox" name="feature_timeman" id="feature_timeman" <?if (IsModuleInstalled("timeman")) echo "checked"?>/>
					<label for="feature_timeman"><?=GetMessage("CONFIG_FEATURES_TIMEMAN")?></label><br/>

					<input type="checkbox" name="feature_meeting" id="feature_meeting" <?if (IsModuleInstalled("meeting")) echo "checked"?>/>
					<label for="feature_meeting"><?=GetMessage("CONFIG_FEATURES_MEETING")?></label><br/>

					<input type="checkbox" name="feature_lists" id="feature_lists" <?if (IsModuleInstalled("lists")) echo "checked"?>/>
					<label for="feature_lists"><?=GetMessage("CONFIG_FEATURES_LISTS")?></label><br/>
				<?endif?>
			</td>
		</tr>
	<!--ip -->
		<?
		if (in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "edu")))
		{
			$arCurIpRights = $arResult["IP_RIGHTS"];
			if (!is_array($arCurIpRights))
				$arCurIpRights = array();
			$access = new CAccess();
			$arNames = $access->GetNames(array_keys($arCurIpRights));
			?>
			<tr>
				<td class="content-edit-form-header " colspan="3" >
					<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_IP_TITLE')?></div>
				</td>
			</tr>
			<?
			foreach($arCurIpRights as $right => $arIps)
			{
			?>
				<tr data-bx-right="<?=$right?>">
					<td class="content-edit-form-field-name">
						<?=(!empty($arNames[$right]["provider"]) ? $arNames[$right]["provider"].": " : "").$arNames[$right]["name"]?>
					</td>
					<td class="content-edit-form-field-input" colspan="2">
						<?foreach($arIps as $ip):?>
							<div>
								<input name="ip_access_rights_<?=$right?>[]" value="<?=$ip?>" size="30"/>
								<a href="javascript:void(0);" onclick="B24ConfigsIpObj.DeleteIpAccessRow(this);" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a>
							</div>
						<?endforeach?>
						<div>
							<input name="ip_access_rights_<?=$right?>[]" size="30" onclick="B24ConfigsIpObj.addInputForIp(this)"/>
							<a href="javascript:void(0);" onclick="B24ConfigsIpObj.DeleteIpAccessRow(this);" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a>
						</div>
					</td>
				</tr>
			<?
			}
			?>
			<tr id="ip_add_right_button">
				<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
				<td class="content-edit-form-field-input" colspan="2">
					<a href="javascript:void(0)" class="bx-action-href" onclick="B24ConfigsIpObj.ShowIpAccessPopup(B24ConfigsIpObj.arCurIpRights);"><?=GetMessage("CONFIG_TOALL_RIGHTS_ADD")?></a>
				</td>
			</tr>
		<?
		}
		?>
		<?if (LANGUAGE_ID == "ru"):?>
		<!-- marketplace -->
		<tr>
			<td class="content-edit-form-header " colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_MARKETPLACE_TITLE')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a href="/marketplace/category/migration/"><?=GetMessage("CONFIG_MARKETPLACE_MORE")?></a>
			</td>
		</tr>
		<?endif?>
	<?
	}


	if($arResult['ALLOW_DOMAIN_CHANGE'])
	{
	?>
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_NAME_CHANGE_SECTION')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_NAME_CHANGE_FIELD')?></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a href="javascript:void(0)" onclick="BX.Bitrix24.renamePortal()"><?=GetMessage('CONFIG_NAME_CHANGE_ACTION')?></a>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<div class="config_notify_message" style="margin: 10px 20px 10px 20px">
					<?=GetMessage("CONFIG_NAME_CHANGE_INFO")?>
				</div>
			</td>
		</tr>
	<?
	}

	if($arResult['SHOW_GOOGLE_API_KEY_FIELD'])
	{
	?>
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_NAME_GOOGLE_API_KEY')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_NAME_GOOGLE_API_KEY_FIELD')?></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a name="google_api_key"></a>
				<input class="content-edit-form-field-input-text" name="google_api_key" value="<?=\Bitrix\Main\Text\HtmlFilter::encode($arResult['GOOGLE_API_KEY'])?>">
			</td>
		</tr>
<?
		if(strlen($arResult['GOOGLE_API_KEY_HOST']) > 0 && strlen($arResult['GOOGLE_API_KEY']) > 0):
?>
			<tr>
				<td colspan="3">
					<div class="config_notify_message" style="margin: 10px 20px 10px 20px">
						<?=GetMessage("CONFIG_NAME_GOOGLE_API_HOST_HINT", array(
							'#domain#' => \Bitrix\Main\Text\HtmlFilter::encode($arResult['GOOGLE_API_KEY_HOST'])
						))?>
					</div>
				</td>
			</tr>
<?
		else:
?>
			<tr>
				<td colspan="3">
					<div class="config_notify_message" style="margin: 10px 20px 10px 20px">
						<?=GetMessage("CONFIG_NAME_GOOGLE_API_KEY_HINT")?>
					</div>
				</td>
			</tr>
<?
		endif;
	}
	?>
	</table>
</form>

<br/><br/>
<table class="content-edit-form" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center">
			<span onclick="BX.Bitrix24.Configs.Functions.submitForm(this)" class="webform-button webform-button-create">
				<?=GetMessage("CONFIG_SAVE")?>
			</span>
		</td>
	</tr>
</table>
<br/><br/><br/><br/>
<!-- logo -->
<?
if ($arResult["IS_BITRIX24"] && in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "edu", "demo")) || !$arResult["IS_BITRIX24"])
{
	$clientLogoID = COption::GetOptionInt("bitrix24", "client_logo", "");
?>
<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content-edit-form-header" colspan="3" >
			<div class="content-edit-form-header-wrap content-edit-form-header-wrap"><?=GetMessage('CONFIG_CLIENT_LOGO')?></div>
		</td>
	</tr>
	<tr>
		<td colspan="3" >
			<div class="content-edit-form-notice-error" style="display: none;" id="config_logo_error_block">
				<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span></span>
			</div>
		</td>
	</tr>
	<tr>
		<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_CLIENT_LOGO')?></td>
		<td class="content-edit-form-field-input" colspan="2">
			<?=GetMessage('CONFIG_CLIENT_LOGO_DESCR')?>
			<form name="configLogoPostForm" id="configLogoPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
				<?=bitrix_sessid_post();?>

				<label for="client_logo" class="config-webform-field-upload" style="margin-top:10px;vertical-align: middle;" onmouseup="BX.removeClass(this,'content-edit-form-button-press')" onmousedown="BX.addClass(this, 'content-edit-form-button-press')">
					<span class=""><span class="content-edit-form-button-left"></span><span class="content-edit-form-button-text"><?=GetMessage('CONFIG_ADD_LOGO_BUTTON')?></span><span class="content-edit-form-button-right"></span></span>
					<input type="file" name="client_logo" id="client_logo" value=""/>
				</label>
				<br/><br/>
				<div id="config-wait" style="display:none;"><img src="<?=$this->GetFolder();?>/images/wait.gif"/></div>

				<div id="config_logo_img_div" class="config-webform-logo-img" <?if (!$clientLogoID):?>style="display:none"<?endif?>>
					<img id="config_logo_img" src="<?if ($clientLogoID) echo CFile::GetPath($clientLogoID)?>" />
				</div>

				<a href="javascript:void(0)" id="config_logo_delete_link" class="config_logo_delete_link"  <?if (!$clientLogoID):?>style="display:none"<?endif?>>
					<?=GetMessage("CONFIG_ADD_LOGO_DELETE")?>
				</a>
			</form>
		</td>
	</tr>
</table>
<?
}
?>

<?
if (isset($_GET["otp"]))
{
?>
	<form id="bitrix24-otp-tell-about-form" style="display: none" action="/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=str_replace("%23", "#", urlencode($arParams["CONFIG_PATH_TO_POST"]))?>" method="POST">
		<div style="padding: 4px">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="POST_TITLE" value="<?=GetMessage("CONFIG_OTP_IMPORTANT_TITLE")?>">
			<textarea name="POST_MESSAGE" style="display: none"><?=GetMessage("CONFIG_OTP_IMPORTANT_TEXT")?></textarea>
			<input type="hidden" name="changePostFormTab" value="important">
			<div style="margin-bottom: 10px; font-size: 14px">
				<?=GetMessage("CONFIG_OTP_POPUP_TEXT")?>
			</div>
		</div>
	</form>

	<script>
		BX.ready(function(){
			var popup = BX.PopupWindowManager.create("bitrix24OtpImportant", null, {
				autoHide: true,
				offsetLeft: 0,
				offsetTop: 0,
				overlay : true,
				draggable: { restrict:true },
				closeByEsc: true,
				closeIcon: true,
				titleBar: "<?=GetMessageJS("CONFIG_OTP_POPUP_TITLE")?>",
				content: BX("bitrix24-otp-tell-about-form"),
				buttons: [
					new BX.PopupWindowButton({
						text: "<?=GetMessage("CONFIG_OTP_POPUP_SHARE")?>",
						className: "popup-window-button-accept",
						events: {
							click: function() {
								BX.submit(BX("bitrix24-otp-tell-about-form"), 'dummy')
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: "<?=GetMessage("CONFIG_OTP_POPUP_CLOSE")?>",
						events: {
							click: function() {
								this.popupWindow.close();
							}
						}
					})
				]
			});

			popup.show();
		});
	</script>
<?
}
?>

<script>
	BX.message({
		SLToAllDel: '<?=CUtil::JSEscape(GetMessage("CONFIG_TOALL_DEL"))?>',
		LogoDeleteConfirm: '<?=GetMessageJS("CONFIG_ADD_LOGO_DELETE_CONFIRM")?>',
		CONFIG_OTP_SECURITY_SWITCH_OFF_INFO: '<?=GetMessageJS("CONFIG_OTP_SECURITY_SWITCH_OFF_INFO")?>',
		CONFIG_OTP_ADMIN_IS_REQUIRED_INFO: '<?=GetMessageJS("CONFIG_OTP_ADMIN_IS_REQUIRED_INFO")?>'
	});

	var B24ConfigsLogo = new BX.Bitrix24.Configs.LogoClass("<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>");

	BX.ready(function(){
		BX.Bitrix24.Configs.Functions.init();
	});

	<?if (in_array($arResult["LICENSE_TYPE"], array("team", "company", "nfr", "edu"))):?>
	var B24ConfigsIpObj = new BX.Bitrix24.Configs.IpSettingsClass(<?=CUtil::PhpToJSObject(array_keys($arCurIpRights))?>);
	<?endif?>

	var B24ConfigsLiveFeedObj = new BX.Bitrix24.Configs.LiveFeedRightClass(<?=CUtil::PhpToJSObject($arToAllRights)?>);

	var B24ImChatObj = new BX.Bitrix24.Configs.ImGeneralChatClass(<?=CUtil::PhpToJSObject($arChatToAllRights)?>);
</script>