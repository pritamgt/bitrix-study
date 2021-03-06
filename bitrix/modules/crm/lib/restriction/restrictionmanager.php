<?php
namespace Bitrix\Crm\Restriction;
use Bitrix\Main;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Category\DealCategory;

class RestrictionManager
{
	const SQL_ROW_COUNT_THRESHOLD = 5000;
	/** @var bool */
	private static $isInitialized = null;
	/** @var Bitrix24SqlRestriction|null */
	private static $sqlRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $conversionRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $dupControlRestriction = null;
	/** @var Bitrix24AccessRestriction|null  */
	private static $historyViewRestriction = null;
	/** @var Bitrix24QuantityRestriction|null  */
	private static $dealCategoryLimitRestriction = null;
	/**
	* @return SqlRestriction
	*/
	public static function getSqlRestriction()
	{
		self::initialize();
		return self::$sqlRestriction;
	}
	/**
	* @return AccessRestriction
	*/
	public static function getConversionRestriction()
	{
		self::initialize();
		return self::$conversionRestriction;
	}
	/**
	* @return AccessRestriction
	*/
	public static function getDuplicateControlRestriction()
	{
		self::initialize();
		return self::$dupControlRestriction;
	}
	/**
	* @return AccessRestriction
	*/
	public static function getHistoryViewRestriction()
	{
		self::initialize();
		return self::$historyViewRestriction;
	}
	/**
	 * @return QuantityRestriction
	 */
	public static function getDealCategoryLimitRestriction()
	{
		self::initialize();
		return self::$dealCategoryLimitRestriction;
	}
	/**
	* @return void
	*/
	public static function reset()
	{
		self::initialize();

		self::$sqlRestriction->reset();
		self::$conversionRestriction->reset();
		self::$dupControlRestriction->reset();
		self::$historyViewRestriction->reset();
		self::$dealCategoryLimitRestriction->reset();

		self::$sqlRestriction = null;
		self::$conversionRestriction = null;
		self::$dupControlRestriction = null;
		self::$historyViewRestriction = null;
		self::$dealCategoryLimitRestriction = null;

		self::$isInitialized = false;
	}
	/**
	* @return bool
	*/
	public static function isConversionPermitted()
	{
		return self::getConversionRestriction()->hasPermission();
	}
	/**
	* @return bool
	*/
	public static function isDuplicateControlPermitted()
	{
		return self::getDuplicateControlRestriction()->hasPermission();
	}
	/**
	* @return bool
	*/
	public static function isHistoryViewPermitted()
	{
		return self::getHistoryViewRestriction()->hasPermission();
	}
	/**
	 * @return int
	 */
	public static function getDealCategoryLimit()
	{
		return self::getDealCategoryLimitRestriction()->getQuantityLimit();
	}
	/**
	* @return void
	*/
	private static function initialize()
	{
		if(self::$isInitialized)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		
		$isFree = !Bitrix24Manager::isEnabled()
			|| Bitrix24Manager::hasPurchasedLicense()
			|| Bitrix24Manager::hasNfrLicense()
			|| Bitrix24Manager::hasDemoLicense();

		//region SQL
		self::$sqlRestriction = new Bitrix24SqlRestriction('crm_clr_cfg_sql');
		if(!self::$sqlRestriction->load())
		{
			self::$sqlRestriction->setRowCountThreshold($isFree ? 0 : self::SQL_ROW_COUNT_THRESHOLD);
		}
		//endregion
		//region Conversion
		self::$conversionRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_conv',
			false,
			null,
			array(
				'ID' => 'crm_entity_conversion',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);
		if(!self::$conversionRestriction->load())
		{
			self::$conversionRestriction->permit($isFree);
		}
		//endregion
		//region Duplicate Control
		self::$dupControlRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_dup_ctrl',
			false,
			array(
				'ID' => 'crm_duplicate_control',
				'CONTENT' => GetMessage('CRM_RESTR_MGR_DUP_CTRL_MSG_CONTENT_2')
			),
			array(
				'ID' => 'crm_duplicate_control',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);

		if(!self::$dupControlRestriction->load())
		{
			self::$dupControlRestriction->permit($isFree);
		}
		//endregion
		//region History View
		self::$historyViewRestriction = new Bitrix24AccessRestriction(
			'crm_clr_cfg_hx',
			false,
			array(
				'ID' => 'crm_history_view',
				'CONTENT' => GetMessage('CRM_RESTR_MGR_HX_VIEW_MSG_CONTENT_2')
			),
			array(
				'ID' => 'crm_history_view',
				'TITLE' => GetMessage('CRM_RESTR_MGR_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_POPUP_CONTENT_2')
			)
		);
		//endregion
		//region Deal Category Limit
		if(!self::$historyViewRestriction->load())
		{
			self::$historyViewRestriction->permit($isFree);
		}

		self::$dealCategoryLimitRestriction = new Bitrix24QuantityRestriction(
			'crm_clr_cfg_deal_category',
			false,
			null,
			array(
				'ID' => 'crm_deal_category',
				'TITLE' => GetMessage('CRM_RESTR_MGR_DEAL_CATEGORY_POPUP_TITLE'),
				'CONTENT' => GetMessage('CRM_RESTR_MGR_DEAL_CATEGORY_POPUP_CONTENT')
			)
		);

		if(!self::$dealCategoryLimitRestriction->load())
		{
			self::$dealCategoryLimitRestriction->setQuantityLimit(Bitrix24Manager::getDealCategoryCount());
		}
		//endregion

		self::$isInitialized = true;
	}

	public static function onDealCategoryLimitChange(Main\Event $event)
	{
		DealCategory::applyMaximumLimitRestrictions(Bitrix24Manager::getDealCategoryCount());
	}
}