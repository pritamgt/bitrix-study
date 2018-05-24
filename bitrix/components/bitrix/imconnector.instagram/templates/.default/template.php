<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
/** $arResult["CONNECTION_STATUS"]; */
/** $arResult["REGISTER_STATUS"]; */
/** $arResult["ERROR_STATUS"]; */
/** $arResult["SAVE_STATUS"]; */

Loc::loadMessages(__FILE__);
?>
<?if(empty($arParams['INDIVIDUAL_USE'])):?>
<script>
	BX.ready(function(){
		<?if(empty($arResult["STATUS"])):?>
		BX.addClass(BX('status-<?=$arResult["CONNECTOR"]?>'), 'connector-icon-disabled');
		<?else:?>
		BX.removeClass(BX('status-<?=$arResult["CONNECTOR"]?>'), 'connector-icon-disabled');
		<?endif;?>
	});
</script>
<?endif;?>

<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>
<a name="configure<?=$arResult["CONNECTOR"]?>"></a>
<?if(!empty($arResult["ACTIVE_STATUS"]) && empty($arResult["PAGE"])):?>
	<?if(empty($arResult["STATUS"])):?>
		<div class="imconnector-block">
			<div class="imconnector-instagram-block-title imconnector-instagram-block-title-no-connect">
				<?=$arResult["NAME"]?>
			</div>
			<div class="imconnector-connect">
				<?if($arResult["ERROR_STATUS"]):?>
					<table class="imconnector-connect-table">
						<tr>
							<td><?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_CONNECTOR_ERROR_STATUS')?></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="2">
								<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
								   class="imconnector-connect-link-gray"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_ERROR_LINK_FIX')?></a>
							<span class="imconnector-connect-link-gray"
								  onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)"
								  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE_TITLE')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?></span>
							</td>
						</tr>
					</table>
				<?else:?>
					<table class="imconnector-connect-table">
						<tr>
							<td><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_SETTING_IS_NOT_COMPLETED')?></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="2">
								<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
								   class="imconnector-connect-link-gray"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CONTINUE_WITH_THE_SETUP')?></a>
							<span class="imconnector-connect-link-gray"
								  onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)"
								  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE_TITLE')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?></span>
							</td>
						</tr>
					</table>
				<?endif;?>
			</div>
		</div>
	<?else:?>
		<div class="imconnector-block">
			<div class="imconnector-instagram-block-title">
				<?=$arResult["NAME"]?>
			</div>
			<div class="imconnector-connect">
				<table class="imconnector-connect-table">
					<?if(!empty($arResult["FORM"]["USER"]["INFO"])):?>
							<tr>
								<td><?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_USER')?></td>
								<td>
									<a href="<?=$arResult["FORM"]["USER"]["INFO"]["URL"]?>"
									   class="imconnector-connect-link"
									   target="_blank">
										<?=$arResult["FORM"]["USER"]["INFO"]["NAME"]?>
									</a>
								</td>
							</tr>
					<?endif;?>

					<tr>
						<td colspan="2">
							<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
							   class="imconnector-connect-link-gray"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?></a>
							<span class="imconnector-connect-link-gray"
								  onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)"
								  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE_TITLE')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?></span>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?endif;?>
<?else:?>
	<div class="imconnector-item<?
	if(!empty($arResult["PAGE"]) || !empty($arParams['INDIVIDUAL_USE'])):?> imconnector-item-show<?endif;
	?>">
		<?if(!empty($arResult["ACTIVE_STATUS"])):?>
			<span class="imconnector-back" onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)"
				  title="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE_TITLE')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?></span>
		<?endif;?>
	<label class="imconnector-instagram-button" for="imconnector-instagram" onclick="showHideImconnectors(this)">
		<?=$arResult["NAME"]?>
		<span class="imconnector-button-show"
			  id="imconnector-instagram-button-show"><?
			if(!empty($arResult["PAGE"])):?><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_COLLAPSE')?><?
			else:?><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DEPLOY')?><?
			endif;?></span>
	</label>
	<input type="checkbox"
		   id="imconnector-instagram"
		   class="imconnector-checkbox" hidden>
	<div class="imconnector-wrapper">
			<?if(!empty($arResult["ACTIVE_STATUS"]) && !empty($arResult["FORM"]["STEP"])):?>
				<div class="imconnector-wrapper-title-in">
					<?
					switch ($arResult["FORM"]["STEP"])
					{
						case 1:
							echo Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_STEP_1_OF_2_TITLE');
							break;
						case 2:
							echo Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_STEP_2_OF_2_TITLE');
							break;
					}
					?>
					<span class="imconnector-wrapper-title-nav"><?=str_replace('#STEP#', $arResult["FORM"]["STEP"], Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_STEP_N_OF_2'))?></span>
				</div>
			<?endif;?>
			<?
			if($arResult['messages'])
			{
				echo '<div class="imconnector-settings-message imconnector-settings-message-success">';
				foreach ($arResult['messages'] as $value)
				{
					echo '<div>' . $value . '</div>';
				}
				echo '</div>';
			}
			if($arResult['error'])
			{
				echo '<div class="imconnector-settings-message imconnector-settings-message-error">';
				foreach ($arResult['error'] as $value)
				{
					echo '<div>' . $value . '</div>';
				}
				echo '</div>';
			}
			?>
		<div class="imconnector-intro">
			<?if(empty($arResult["ACTIVE_STATUS"])):?>
			<div class="imconnector-intro-text">
				<?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_INDEX_DESCRIPTION_NEW')?>
			</div>
				<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post">
					<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
					<input type="submit" name="<?=$arResult["CONNECTOR"]?>_active" class="webform-small-button webform-small-button-accept webform-small-button-accept-nomargin" value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_CONNECT')?>">
					<?=bitrix_sessid_post();?>
				</form>
			<?else:?>
				<?if(!empty($arResult["PAGE"])):?>
					<?if(empty($arResult["FORM"]["USER"]["INFO"])):?>
						<table class="imconnector-instagram-table">
							<tr>
								<td><?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_ENTITY')?></td>
								<td><?if(!empty($arResult["FORM"]["USER"]["URI"])):?>
									<a href="javascript:void(0)" onclick="BX.util.popup('<?=$arResult["FORM"]["USER"]["URI"]?>', 700, 525)" class="imconnector-instagram-button-autorize"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_LOG_IN')?></a>
									<?endif;?></td>
							</tr>
						</table>
					<?else:?>
						<table class="imconnector-instagram-table">
							<tr>
								<td>
									<div class="imconnector-instagram-autorizate"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_YOU_ARE_LOGGED_AS')?>:</div>
									<img class="imconnector-instagram-avatar" src="<?=$templateFolder?>/images/imconnector-instagram-icon.svg" alt="">
									<div class="imconnector-instagram-name"><a href="<?=$arResult["FORM"]["USER"]["INFO"]["URL"]?>" target="_blank" ><?=$arResult["FORM"]["USER"]["INFO"]["NAME"]?></a></div>
								</td>
								<td>
									<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post">
										<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
										<input type="hidden" name="user_id" value="<?=$arResult["FORM"]["USER"]["INFO"]["ID"]?>">
										<input type="submit" name="<?=$arResult["CONNECTOR"]?>_del_user" class="imconnector-instagram-link-a" value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_DEL_REFERENCE')?>">
										<?=bitrix_sessid_post();?>
									</form>
							</tr>
						</table>
						<div class="imconnector-settings-message imconnector-settings-message-success imconnector-settings-message-align-left">
							<?=Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_FINAL_DESCRIPTION_1', array('#URL#' => Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_FINAL_DESCRIPTION_1_URL')))?>
						</div>
					<?endif;?>
				<?endif;?>
			<?endif;?>
			</div>
		</div>
	</div>
<?endif;?>