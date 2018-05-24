<?php
use \Bitrix\Main\Loader,
	\Bitrix\Main\Page\Asset,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\CustomConnectors;

class ImConnectorSettingsStatus extends CBitrixComponent
{
	private $error = array();
	private $messages = array();

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_STATUS_CONFIG_MODULE_NOT_INSTALLED'));
			return false;
		}
	}

	public function constructionForm()
	{
		$listActiveConnector = Connector::getListActiveConnectorReal();

		$customStyleCss = CustomConnectors::getStyleCss();
		$customStyleCssDisabled = CustomConnectors::getStyleCssDisabled();
		if(!empty($customStyleCss))
		{
			Asset::getInstance()->addString('<style>' . $customStyleCss . '</style>', true);
			Asset::getInstance()->addString('<style>' . $customStyleCssDisabled . '</style>', true);
		}

		foreach ($listActiveConnector as $id => $value)
		{
			$this->arResult[$id] = array(
				'ID' => $id,
				'NAME' => $value,
				'STATUS' => Status::getInstance($id, $this->arParams['LINE'])->isStatus()
			);
		}
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			$this->constructionForm();

			$this->includeComponentTemplate();
		}
	}
};