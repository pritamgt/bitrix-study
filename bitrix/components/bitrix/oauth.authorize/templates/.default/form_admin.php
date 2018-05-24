<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Text\Converter;

/**
 * Bitrix vars
 * @global array $arParams
 * @global array $arResult
 */
?>

<form action="<?=POST_FORM_ACTION_URI?>" method="POST">
	<div class="bx-oAuth-content">
		<div><span class="bx-oAuth-title"><?=Converter::getHtmlConverter()->encode($arResult["REQUEST"]["CLIENT"]["TITLE"])?></span></div>
		<div>
			<span class="bx-oAuth-description">
				<?=GetMessage('OAUTH_REQUEST_TITLE_ADMIN')?>
			</span>
		</div>
		<div class="bx-oAuth-list-wrap">
			<ul class="bx-oAuth-list">
<?
foreach($arResult["LIST"] as $category => $list)
{
	if(count($list) > 0)
	{
?>
				<li class="bx-oAuth-list-item">
					<div class="bx-oAuth-list-item-inner" onclick="closeOpen(this);"><?=GetMessage('OAUTH_REQUEST_'.$category)?></div>
					<div class="bx-oAuth-list-second-level" data-block>
						<ul class="bx-oAuth-list list-<?=$category?>" data-block-inner>
<?
		foreach($list as $portalInfo)
		{
?>
							<li class="bx-oAuth-list-item list-item-<?=$category?>"><a href="<?= Converter::getHtmlConverter()->encode($portalInfo["URL"])?>" target="_blank"><?=Converter::getHtmlConverter()->encode($portalInfo["TITLE"])?></a></li>
<?
		}
?>
						</ul>
					</div>
				</li>
<?
	}
}
?>
			</ul>
		</div>
	</div>

	<div class="bx-oAuth-section-buttons">
		<div class="bx-oAuth-section-buttons-block">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="new_scope" value="admin">
			<input type="submit" name="accept" class="bx-btn big green" onclick="this.className+=' wait';" value="<?=GetMessage('OAUTH_REQUEST_SUBMIT')?>" />
			<input type="submit" name="reject" class="bx-btn big transparent" onclick="this.className+=' wait';" value="<?=GetMessage('OAUTH_REQUEST_REJECT')?>" />
		</div>
	</div>
</form>

<script type="text/javascript">

	function closeOpen(elem)
	{
		var parent = elem.parentNode;
		var block = parent.querySelector('[data-block]');
		var blockInner = parent.querySelector('[data-block-inner]');

		if(block.offsetHeight > 0)
		{
			block.style.height = 0;
			parent.className = parent.className.replace(' bx-oAuth-list-item-open', '');
		}
		else
		{
			parent.className += ' bx-oAuth-list-item-open';
			block.style.height = blockInner.offsetHeight + 'px';
		}

	}

</script>