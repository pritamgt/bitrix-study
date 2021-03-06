<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CUtil::InitJSCore();
?>
<form action="<?=POST_FORM_ACTION_URI?>" name="crmPermForm" method="POST">
	<input type="hidden" id="ROLE_ACTION" name="save" value=""/>
	<input type="hidden" name="ROLE_ID" value="<?=$arResult['ROLE']['ID']?>"/>
	<?=bitrix_sessid_post()?>
	<?=GetMessage('CRM_PERMS_FILED_NAME')?>: <input name="NAME" value="<?=htmlspecialcharsbx($arResult['ROLE']['NAME'])?>" class="crmPermRoleName"/>
	<br/>
	<br/>
	<table width="100%" cellpadding="0" cellspacing="0" class="crmPermRoleTable" id="crmPermRoleTable" >
		<tr>
			<th><?=GetMessage('CRM_PERMS_HEAD_ENTITY')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_READ')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_ADD')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_WRITE')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_DELETE')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_EXPORT')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_IMPORT')?></th>
		</tr>
		<? foreach ($arResult['ENTITY'] as $entityType => $entityName): ?>
		<tr>
			<td><? if (isset($arResult['ENTITY_FIELDS'][$entityType])): ?><a href="javascript:void(0)" class="crmPermRoleTreePlus" onclick="CrmPermRoleShowRow(this)"></a><?endif;?><?=$entityName?></td>
			<td>
				<? if (in_array('READ', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Read" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['READ']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Read_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Read_SelectBox" name="ROLE_PERMS[<?=$entityType?>][READ][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['READ']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('ADD', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Add" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['ADD']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Add_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Add_SelectBox" name="ROLE_PERMS[<?=$entityType?>][ADD][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['ADD']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('WRITE', $arResult['ENTITY_PERMS'][$entityType])):
				//TODO: remove this crutch
				if ($entityType === 'SALETARGET')
				{
					$arResult['ROLE_PERM'][$entityType] = array(
						BX_CRM_PERM_NONE => $arResult['ROLE_PERM'][$entityType][BX_CRM_PERM_NONE],
						BX_CRM_PERM_ALL => $arResult['ROLE_PERM'][$entityType][BX_CRM_PERM_ALL]
					);
				}
				?>
				<span id="divPermsBox<?=$entityType?>Write" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['WRITE']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Write_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Write_SelectBox" name="ROLE_PERMS[<?=$entityType?>][WRITE][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['WRITE']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('DELETE', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Delete" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['DELETE']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Delete_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Delete_SelectBox" name="ROLE_PERMS[<?=$entityType?>][DELETE][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['DELETE']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('EXPORT', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Export" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['EXPORT']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Export_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Export_SelectBox" name="ROLE_PERMS[<?=$entityType?>][EXPORT][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['EXPORT']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('IMPORT', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Import" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['IMPORT']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Import_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Import_SelectBox" name="ROLE_PERMS[<?=$entityType?>][IMPORT][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['IMPORT']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
		</tr>
		<?	if (isset($arResult['ENTITY_FIELDS'][$entityType])):
				foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue):
					foreach ($arFieldValue as $fieldValueID => $fieldValue):
		?>
		<tr class="crmPermRoleFields" style="display:none">
			<td><?=$fieldValue?></td>
			<td>
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID]))
							$sOrigPermAttr = $arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID];
					?>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Read')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read_Select" style="display:none">

					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read_SelectBox" name="ROLE_PERMS[<?=$entityType?>][READ][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName):?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Add')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add_SelectBox" name="ROLE_PERMS[<?=$entityType?>][ADD][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Write')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write_SelectBox" name="ROLE_PERMS[<?=$entityType?>][WRITE][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Delete')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete_SelectBox" name="ROLE_PERMS[<?=$entityType?>][DELETE][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Export')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export_SelectBox" name="ROLE_PERMS[<?=$entityType?>][EXPORT][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Import')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import_SelectBox" name="ROLE_PERMS[<?=$entityType?>][IMPORT][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
		</tr>
		<?
					endforeach;
				endforeach;
			endif;
		endforeach;
		?>
		<tr  class="ConfigEdit">
			<td colspan="7"><input name="ROLE_PERMS[CONFIG][WRITE][-]" <?=($arResult['ROLE_PERMS']['CONFIG']['WRITE']['-'] == 'X' ? 'checked="checked"' : '')?> value="X" id="crmConfigEdit" type="checkbox" /><label for="crmConfigEdit"><?=GetMessage("CRM_PERMS_PERM_ADD")?></label></td>
		</tr>
	</table>
	<br/>
	<div id="crmPermButtonBoxPlace">
		<? if ($arResult['ROLE']['ID'] > 0): ?>
		<div style="float:right; padding-right: 10px;"><a href="<?=$arResult['PATH_TO_ROLE_DELETE']?>" onclick="CrmRoleDelete('<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_TITLE'))?>', '<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_MESSAGE'))?>', '<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_BTN'))?>', '<?=CUtil::JSEscape($arResult['PATH_TO_ROLE_DELETE'])?>'); return false;" style="color:#E00000"><?=GetMessage('CRM_PERMS_ROLE_DELETE')?></a></div>
		<? endif;?>
		<div align="left">
			<input type="submit" name="save" value="<?=GetMessage('CRM_PERMS_BUTTONS_SAVE');?>"/>
			<input type="submit" naem="apply" value="<?=GetMessage('CRM_PERMS_BUTTONS_APPLY');?>" onclick="BX('ROLE_ACTION').name='apply'"/>
		</div>
	</div>
</form>
