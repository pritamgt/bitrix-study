<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\HttpApplication;

class ImOpenLinesComponentStatisticsDetail extends CBitrixComponent
{
	protected $gridId = "imopenlines_statistic_v3";
	protected $filterId = "imopenlines_statistic_detail_filter";

	/** @var  \Bitrix\Main\Grid\Options */
	protected $gridOptions;
	protected $excelMode = false;
	protected $enableExport = true;
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;
	protected $showHistory;
	protected $configId;

	private function init()
	{
		$this->enableExport = true; // TODO check perm
		$this->userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();

		$this->gridOptions = new CGridOptions($this->gridId);
		if(isset($_REQUEST['excel']) && $_REQUEST['excel'] === 'Y'  && $this->enableExport)
			$this->excelMode = 'Y';

		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$this->configId = 0;
		if ($request->get('CONFIG_ID'))
		{
			$this->configId = $request->get('CONFIG_ID');
			$config = \Bitrix\ImOpenLines\Config::getInstance()->get($request->get('CONFIG_ID'));
			$this->arResult['LINE_NAME'] = $config['LINE_NAME'];
		}

		$this->arResult['LINES'] = $this->getConfigList();
	}

	private function getConfigList()
	{
		$allowedUserIds = \Bitrix\ImOpenlines\Security\Helper::getAllowedUserIds(
			\Bitrix\ImOpenlines\Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_LINES, \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW)
		);

		$limit = null;
		if (is_array($allowedUserIds))
		{
			$limit = array();
			$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList(Array(
				'filter' => Array(
					'=USER_ID' => $allowedUserIds
				)
			));
			while ($row = $orm->fetch())
			{
				$limit[$row['CONFIG_ID']] = $row['CONFIG_ID'];
			}
		}

		$configManager = new \Bitrix\ImOpenLines\Config();
		$result = $configManager->getList(Array(
				'select' => Array(
					'ID', 'LINE_NAME', 'MODIFY_USER_ID'
				),
				'filter' => Array('=TEMPORARY' => 'N')
			),
			Array('QUEUE' => 'N')
		);

		$lines = Array();
		foreach ($result as $id => $config)
		{
			if (!is_null($limit))
			{
				if (!isset($limit[$config['ID']]) && !in_array($config['MODIFY_USER_ID'], $allowedUserIds))
				{
					unset($result[$id]);
					continue;
				}
			}
			$lines[$config['ID']] = $config['LINE_NAME'];
		}
		return $lines;
	}

	protected function checkModules()
	{
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}

		if (!Loader::includeModule('imconnector'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	protected function checkAccess()
	{
		if(!$this->userPermissions->canPerform(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_SESSION, \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	public static function getFormattedCrmColumn($row)
	{
		$crmData = Array();
		$crmLink = self::getCrmLink($row["data"]);
		if ($crmLink)
		{
			$crmData[] = '<a href="'.$crmLink.'" target="_blank">'.self::getCrmName($row["data"]['CRM_ENTITY_TYPE']).'</a>';
		}

		$crmActivityLink = self::getCrmActivityLink($row["data"]);
		if ($crmActivityLink)
		{
			$crmData[] = '<a href="'.$crmActivityLink.'" target="_blank">'.self::getCrmName('ACTIVITY').'</a>';
		}

		if (empty($crmData))
		{
			$result = Loc::getMessage('OL_COMPONENT_TABLE_NO');
		}
		else
		{
			$result = implode('<br>', $crmData);
		}

		return $result;
	}

	private static function getCrmName($type)
	{
		$name = '';

		if (\CModule::IncludeModule('crm'))
		{
			$name = CCrmOwnerType::GetDescription(CCrmOwnerType::ResolveID($type));
		}

		return $name;
	}
	private static function getCrmLink($row)
	{
		if ($row['CRM'] != 'Y')
		{
			return '';
		}

		return \Bitrix\ImOpenLines\Crm::getLink($row['CRM_ENTITY_TYPE'], $row['CRM_ENTITY_ID']);
	}

	private static function getCrmActivityLink($row)
	{
		if ($row['CRM'] != 'Y' || $row['CRM_ACTIVITY_ID'] <= 0)
		{
			return '';
		}

		return \Bitrix\ImOpenLines\Crm::getLink('ACTIVITY', $row['CRM_ACTIVITY_ID']);
	}

	private static function formatDate($date)
	{
		if (!$date)
		{
			return '-';
		}

		return formatDate('x', $date->toUserTime()->getTimestamp(), (time() + \CTimeZone::getOffset()));
	}

	private static function formatDuration($duration)
	{
		$duration = intval($duration);
		if ($duration <= 0)
			return "-";

		$currentTime = new \Bitrix\Main\Type\DateTime();
		$formatTime = $currentTime->getTimestamp()-$duration;
		if ($duration < 3600)
		{
			$result = \FormatDate(Array(
				"s" => "sdiff",
				"i" => "idiff",
			), $formatTime);
		}
		elseif ($duration >= 3600 && $duration < 86400)
		{

			$formatTime = $currentTime->getTimestamp()-$duration;
			$result = \FormatDate('Hdiff', $formatTime);

			if ($duration % 3600 != 0)
			{
				$formatTime = $currentTime->getTimestamp()-($duration % 3600);
				$result = $result .' '. \FormatDate(Array(
				"s" => "sdiff",
				"i" => "idiff",
				), $formatTime);
			}
		}
		elseif ($duration >= 86400)
		{

			$formatTime = $currentTime->getTimestamp()-$duration;
			$result = \FormatDate('ddiff', $formatTime);

			if ($duration % 86400 != 0 && ceil($duration % 86400) > 3600)
			{
				$formatTime = $currentTime->getTimestamp()-ceil($duration % 86400);
				$result = $result .' '. \FormatDate(Array(
					"i" => "idiff",
					"H" => "Hdiff",
				), $formatTime);
			}
		}
		else
		{
			$result = '';
		}

		return $result;
	}

	private static function formatVote($sessionId, $rating, $field = 'VOTE')
	{
		$rating = intval($rating);

		$result = '-';
		if ($field == 'VOTE' && in_array($rating, Array(5,1)))
		{
			$result = '<span class="ol-stat-rating ol-stat-rating-'.$rating.'"></span>';
		}
		else if ($field == 'VOTE_HEAD' && $rating >= 1 && $rating <= 5)
		{
			$result = '<span class="ol-stat-rating-head" title="'.$rating.'/5"><span class="ol-stat-rating-head-wrap ol-stat-rating-head-'.$rating.'"></span></span>';
		}
		else if ($field == 'VOTE_HEAD_PERM')
		{
			$result = '<div id="ol-vote-head-placeholder-'.$sessionId.'"></div><script>BX.ready(function(){
				var voteChild = BX.MessengerCommon.linesVoteHeadNodes('.$sessionId.', '.$rating.', true);
				BX("ol-vote-head-placeholder-'.$sessionId.'").appendChild(voteChild);
			})</script>';
		}

		return $result;
	}

	private function getFilterDefinition()
	{
		$result = array(
			"CONFIG_ID" => array(
				"id" => "CONFIG_ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_CONFIG_NAME"),
				"type" => "list",
				"items" => $this->arResult['LINES'],
				"default" => !$this->configId,
				"default_value" => $this->configId? $this->configId: '',
				"params" => array(
					"multiple" => "Y"
				)
			),
		);

		$result = array_merge($result, array(
			"TYPE" => array(
				"id" => "TYPE",
				"name" => Loc::getMessage("OL_STATS_HEADER_MODE_NAME"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"input" => Loc::getMessage("OL_COMPONENT_TABLE_INPUT"),
					"output" => Loc::getMessage("OL_COMPONENT_TABLE_OUTPUT"),
				),
				"default" => false,
			),
			"DATE_CREATE" => array(
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage("OL_STATS_HEADER_DATE_CREATE"),
				"type" => "date",
				"default" => true
			),
			"DATE_CLOSE" => array(
				"id" => "DATE_CLOSE",
				"name" => Loc::getMessage("OL_STATS_HEADER_DATE_CLOSE"),
				"type" => "date",
				"default" => false
			),
			"OPERATOR_ID" => array(
				"id" => "OPERATOR_ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_OPERATOR_NAME"),
				"type" => "custom_entity",
				"selector" => array(
					"TYPE" => "user",
					"DATA" => array("ID" => "user_id", "FIELD_ID" => "OPERATOR_ID")
				),
				"default" => true,
			),
			"CLIENT_NAME" => array(
				"id" => "CLIENT_NAME",
				"name" => Loc::getMessage("OL_STATS_HEADER_USER_NAME"),
				"type" => "string",
				"default" => false,
			),
			'SOURCE' => array(
				"id" => "SOURCE",
				"name" => Loc::getMessage("OL_STATS_HEADER_SOURCE_TEXT_2"),
				"type" => "list",
				"items" => \Bitrix\ImConnector\Connector::getListConnector(),
				"default" => true,
				"params" => array(
					"multiple" => "Y"
				)
			),
			"ID" => array(
				"id" => "ID",
				"name" => Loc::getMessage("OL_STATS_HEADER_SESSION_ID"),
				"type" => "string",
				"default" => true
			),
			'EXTRA_URL' => array(
				"id" => "EXTRA_URL",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_URL"),
				"type" => "string",
				"default" => false
			),
			"STATUS" => array(
				"id" => "STATUS",
				"name" => Loc::getMessage("OL_STATS_HEADER_STATUS"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"client" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT"),
					"operator" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR"),
					"closed" => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED"),
				),
				"default" => true,
			),
			"STATUS_DETAIL" => array(
				"id" => "STATUS_DETAIL",
				"name" => Loc::getMessage("OL_STATS_HEADER_STATUS_DETAIL"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					0 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_NEW"),
					5 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP"),
					10 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER"),
					20 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT"),
					25 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR"),
					40 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR"),
					50 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2"),
					60 => Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED"),
					65 => Loc::getMessage("OL_STATS_HEADER_SPAM_2"),
				),
				"params" => array(
					"multiple" => "Y"
				),
				"default" => false
			),
		));
		if (defined('IMOL_FDC'))
		{
			$result["EXTRA_TARIFF"] = array(
				"id" => "EXTRA_TARIFF",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_TARIFF"),
				"type" => "string",
				"default" => false
			);
			$result["EXTRA_REGISTER"] = array(
				"id" => "EXTRA_REGISTER",
				"name" => Loc::getMessage("OL_STATS_HEADER_EXTRA_REGISTER"),
				"default" => false,
				"type" => "number"
			);
		}
		if(Loader::includeModule('crm'))
		{
			$result["CRM"] = array(
				"id" => "CRM",
				"name" => Loc::getMessage("OL_STATS_HEADER_CRM"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			);
			$result["CRM_ENTITY"] = array(
				"id" => "CRM_ENTITY",
				"name" => Loc::getMessage("OL_STATS_HEADER_CRM_TEXT"),
				"default" => false,
				"type" => "custom_entity",
				"selector" => array(
					"TYPE" => "crm_entity",
					"DATA" => array(
						"ID" => "CRM_ENTITY",
						"FIELD_ID" => "CRM_ENTITY",
						'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName),
						'IS_MULTIPLE' => false
					)
				)
			);
		}

		$result = array_merge($result, Array(
			"SEND_FORM" => array(
				"id" => "SEND_FORM",
				"name" => Loc::getMessage("OL_STATS_HEADER_SEND_FORM"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"SEND_HISTORY" => array(
				"id" => "SEND_HISTORY",
				"name" => Loc::getMessage("OL_STATS_HEADER_SEND_HISTORY"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"WORKTIME" => array(
				"id" => "WORKTIME",
				"name" => Loc::getMessage("OL_STATS_HEADER_WORKTIME_TEXT"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"SPAM" => array(
				"id" => "SPAM",
				"name" => Loc::getMessage("OL_STATS_HEADER_SPAM_2"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"Y" => Loc::getMessage("OL_STATS_FILTER_Y"),
					"N" => Loc::getMessage("OL_STATS_FILTER_N"),
				)
			),
			"MESSAGE_COUNT" => array(
				"id" => "MESSAGE_COUNT",
				"name" => Loc::getMessage("OL_STATS_FILTER_MESSAGE_COUNT"),
				"default" => false,
				"type" => "number"
			),
			"VOTE" => array(
				"id" => "VOTE",
				"name" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("OL_STATS_FILTER_UNSET"),
					"5" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT_LIKE"),
					"1" => Loc::getMessage("OL_STATS_HEADER_VOTE_CLIENT_DISLIKE"),
				)
			),
			"VOTE_HEAD" => array(
				"id" => "VOTE_HEAD",
				"name" => Loc::getMessage("OL_STATS_HEADER_VOTE_HEAD"),
				"default" => false,
				"type" => "list",
				"items" => array(
					"wo" => Loc::getMessage("OL_STATS_HEADER_VOTE_HEAD_WO"),
					"5" => 5,
					"4" => 4,
					"3" => 3,
					"2" => 2,
					"1" => 1,
				),
				"params" => array(
					"multiple" => "Y"
				)
			),
		));

		return $result;
	}

	private function getFilter(array $filterDefinition)
	{
		$request = HttpApplication::getInstance()->getContext()->getRequest();

		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$result = array();

		$allowedUserIds = \Bitrix\ImOpenlines\Security\Helper::getAllowedUserIds(
			\Bitrix\ImOpenlines\Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_SESSION, \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW)
		);

		if ($request->get('GUEST_USER_ID'))
		{
			$result['=USER_ID'] = intval($request->get('GUEST_USER_ID'));
		}

		if (!isset($filter["OPERATOR_ID"]) && $request->get('OPERATOR_ID') !== null)
		{
			$filter['OPERATOR_ID'] = intval($request->get('OPERATOR_ID'));
		}

		if(isset($filter["CLIENT_NAME"]))
		{
			$filterUserClient = \Bitrix\Main\UserUtils::getUserSearchFilter(Array(
				'FIND' => $filter["CLIENT_NAME"]
			));

			$filterUserClient['EXTERNAL_AUTH_ID'] = array('imconnector');

			$userClientRaw = \Bitrix\Main\UserTable::getList(Array(
				'select' => Array('ID'),
				'filter' => $filterUserClient
			));

			while ($userClientRow = $userClientRaw->fetch())
			{
				$result["=USER_ID"][] = $userClientRow['ID'];
			}

			if(empty($result["=USER_ID"]))
				$result["=USER_ID"] = -1;
		}

		if(isset($filter["OPERATOR_ID"]))
		{
			$filter["OPERATOR_ID"] = (int)$filter["OPERATOR_ID"];
			if(is_array($allowedUserIds))
			{
				$result["=OPERATOR_ID"] = array_intersect($allowedUserIds, array($filter["OPERATOR_ID"]));
			}
			else
			{
				$result["=OPERATOR_ID"] = $filter["OPERATOR_ID"];
			}
		}
		else
		{
			if(is_array($allowedUserIds))
			{
				$result["=OPERATOR_ID"] = $allowedUserIds;
			}
		}

		if (strlen($filter["DATE_CREATE_from"]) > 0)
		{
			try
			{
				$result[">=DATE_CREATE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CREATE_from"]);
			}
			catch (Exception $e)
			{
			}
		}
		if (strlen($filter["DATE_CREATE_to"]) > 0)
		{
			try
			{
				$result["<=DATE_CREATE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CREATE_to"]);
			}
			catch (Exception $e)
			{
			}
		}

		if (strlen($filter["DATE_CLOSE_from"]) > 0)
		{
			try
			{
				$result[">=DATE_CLOSE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CLOSE_from"]);
			} catch (Exception $e){}
		}
		if (strlen($filter["DATE_CLOSE_to"]) > 0)
		{
			try
			{
				$result["<=DATE_CLOSE"] = new \Bitrix\Main\Type\DateTime($filter["DATE_CLOSE_to"]);
			} catch (Exception $e){}
		}

		if(is_array($filter["SOURCE"]))
			$result["=SOURCE"] = $filter["SOURCE"];

		if(is_array($filter["CONFIG_ID"]))
		{
			$result["=CONFIG_ID"] = $filter["CONFIG_ID"];
		}
		else if ($this->configId)
		{
			$result['=CONFIG_ID'] = $this->configId;
		}

		if(!empty($filter["EXTRA_URL"]))
			$result["%EXTRA_URL"] = $filter["EXTRA_URL"];

		if(!empty($filter["EXTRA_TARIFF"]))
			$result["=EXTRA_TARIFF"] = $filter["EXTRA_TARIFF"];

		if(isset($filter["STATUS"]))
		{
			switch ($filter["STATUS"])
			{
				case "client":
					$result["<STATUS"] = 40;
				break;

				case "operator":
					$result[">=STATUS"] = 40;
					$result["<STATUS"] = 60;
				break;

				case "closed":
					$result[">=STATUS"] = 60;
				break;
			}
		}

		if(is_array($filter["STATUS_DETAIL"]))
			$result["=STATUS"] = $filter["STATUS_DETAIL"];

		if(isset($filter["CRM"]))
			$result["=CRM"] = $filter["CRM"];

		if(isset($filter['CRM_ENTITY']) && $filter['CRM_ENTITY'] != '')
		{
			$crmFilter = array();
			try
			{
				$crmFilter = \Bitrix\Main\Web\Json::decode($filter['CRM_ENTITY']);
			} catch (\Bitrix\Main\ArgumentException $e) {};

			if(count($crmFilter) == 1)
			{
				$entityTypes = array_keys($crmFilter);
				$entityType = $entityTypes[0];
				$entityId = $crmFilter[$entityType][0];
				$result['=CRM_ENTITY_TYPE'] = $entityType;
				$result['=CRM_ENTITY_ID'] = $entityId;
			}
		}

		if(isset($filter["SEND_FORM"]))
		{
			if ($filter["SEND_FORM"] == 'Y')
			{
				$result["!=SEND_FORM"] = 'none';
			}
			else
			{
				$result["=SEND_FORM"] = 'none';
			}
		}

		if(isset($filter["SEND_HISTORY"]))
		{
			if ($filter["SEND_HISTORY"] == 'Y')
			{
				$result["=SEND_HISTORY"] = 'Y';
			}
			else if ($filter["SEND_HISTORY"] == 'N')
			{
				$result["!=SEND_HISTORY"] = 'Y';
			}
		}

		if(isset($filter["SPAM"]))
		{
			if ($filter["SPAM"] == 'Y')
			{
				$result["=SPAM"] = 'Y';
			}
			else if ($filter["SPAM"] == 'N')
			{
				$result["!=SPAM"] = 'Y';
			}
		}

		if (isset($filter["MESSAGE_COUNT_numsel"]))
		{
			if ($filter["MESSAGE_COUNT_numsel"] == 'range')
			{
				if (intval($filter["MESSAGE_COUNT_from"]) > 0 && intval($filter["MESSAGE_COUNT_to"]) == 0)
				{
					$filter["MESSAGE_COUNT_numsel"] = 'more';
					$filter["MESSAGE_COUNT_from"] = $filter["MESSAGE_COUNT_from"]-1;
				}
				else if (intval($filter["MESSAGE_COUNT_from"]) == 0 && intval($filter["MESSAGE_COUNT_to"]) > 0)
				{
					$filter["MESSAGE_COUNT_numsel"] = 'less';
					$filter["MESSAGE_COUNT_to"] = $filter["MESSAGE_COUNT_to"]+1;
				}
				else
				{
					$result[">=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
					$result["<=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_to"]);
				}
			}
			if ($filter["MESSAGE_COUNT_numsel"] == 'more')
			{
				$result[">MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
			}
			else if ($filter["MESSAGE_COUNT_numsel"] == 'less')
			{
				$result["<MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_to"]);
			}
			else if ($filter["MESSAGE_COUNT_numsel"] != 'range')
			{
				$result["=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT_from"]);
			}
		}
		else if (isset($filter["MESSAGE_COUNT"]))
		{
			$result["=MESSAGE_COUNT"] = intval($filter["MESSAGE_COUNT"]);
		}

		if (isset($filter["EXTRA_REGISTER_numsel"]))
		{
			if ($filter["EXTRA_REGISTER_numsel"] == 'range')
			{
				if (intval($filter["EXTRA_REGISTER_from"]) > 0 && intval($filter["EXTRA_REGISTER_to"]) == 0)
				{
					$filter["EXTRA_REGISTER_numsel"] = 'more';
					$filter["EXTRA_REGISTER_from"] = $filter["EXTRA_REGISTER_from"]-1;
				}
				else if (intval($filter["EXTRA_REGISTER_from"]) == 0 && intval($filter["EXTRA_REGISTER_to"]) > 0)
				{
					$filter["EXTRA_REGISTER_numsel"] = 'less';
					$filter["EXTRA_REGISTER_to"] = $filter["EXTRA_REGISTER_to"]+1;
				}
				else
				{
					$result[">=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
					$result["<=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_to"]);
				}
			}
			if ($filter["EXTRA_REGISTER_numsel"] == 'more')
			{
				$result[">EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
			}
			else if ($filter["EXTRA_REGISTER_numsel"] == 'less')
			{
				$result["<EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_to"]);
			}
			else if ($filter["EXTRA_REGISTER_numsel"] != 'range')
			{
				$result["=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER_from"]);
			}
		}
		else if (isset($filter["EXTRA_REGISTER"]))
		{
			$result["=EXTRA_REGISTER"] = intval($filter["EXTRA_REGISTER"]);
		}

		if(isset($filter["TYPE"]))
			$result["=MODE"] = $filter["TYPE"];

		if(isset($filter["ID"]))
			$result["=ID"] = $filter["ID"];

		if(isset($filter["WORKTIME"]))
			$result["=WORKTIME"] = $filter["WORKTIME"];

		if(isset($filter["VOTE"]))
			$result["=VOTE"] = intval($filter["VOTE"]);

		if(is_array($filter["VOTE_HEAD"]))
		{
			foreach ($filter["VOTE_HEAD"] as $key => $value)
			{
				if ($value == 'wo')
				{
					$filter["VOTE_HEAD"][$key] = 0;
				}
			}
			$result["=VOTE_HEAD"] = $filter["VOTE_HEAD"];
		}

		if(isset($filter['FIND']) && \Bitrix\Main\Search\Content::canUseFulltextSearch($filter['FIND'], \Bitrix\Main\Search\Content::TYPE_MIXED))
		{
			global $DB;
			if (!\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT') && $DB->IndexExists("b_imopenlines_session_index", array("SEARCH_CONTENT"), true))
			{
				\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->enableFullTextIndex("SEARCH_CONTENT");
			}
			if (\Bitrix\Imopenlines\Model\SessionIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT'))
			{
				if (\Bitrix\Main\Search\Content::isIntegerToken($filter['FIND']))
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareIntegerToken($filter['FIND']);
				}
				else
				{
					$result['*INDEX.SEARCH_CONTENT'] = \Bitrix\Main\Search\Content::prepareStringToken($filter['FIND']);
				}
			}
		}

		return $result;
	}

	private function getUserHtml($userId, $userData)
	{
		if ($this->excelMode)
		{
			if ($userId > 0)
			{
				$result = $userData[$userId]["FULL_NAME"];
			}
			else
			{
				$result = '-';
			}
		}
		else
		{
			if ($userId > 0)
			{
				$photoStyle = '';
				if ($userData[$userId]["PHOTO"])
				{
					$photoStyle = "background: url('".$userData[$userId]["PHOTO"]."') no-repeat center;";
				}
				$userHtml = '<span class="ol-stat-user-img user-avatar" style="'.$photoStyle.'"></span>';
				$userHtml .= $userData[$userId]["FULL_NAME"];
			}
			else
			{
				$userHtml = '<span class="ol-stat-user-img user-avatar"></span> &mdash;';
			}
			$result = '<nobr>'.$userHtml.'</nobr>';
		}
		return $result;
	}

	private function getUserData($id = array())
	{
		$users = array();
		if (empty($id))
			return $users;

		$orm = \Bitrix\Main\UserTable::getList(Array(
			'filter' => Array('=ID' => $id)
		));
		while($user = $orm->fetch())
		{
			$users[$user["ID"]]["FULL_NAME"] =  CUser::FormatName("#NAME# #LAST_NAME#", array(
				"NAME" => $user["NAME"],
				"LAST_NAME" => $user["LAST_NAME"],
				"SECOND_NAME" => $user["SECOND_NAME"],
				"LOGIN" => $user["LOGIN"]
			));
			if (intval($user["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = \CFile::GetFileArray($user["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$file = CFile::ResizeImageGet(
						$imageFile,
						array("width" => "30", "height" => "30"),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$users[$user["ID"]]["PHOTO"] = $file["src"];
				}
			}
		}

		return $users;
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
			return false;

		$this->init();

		if (!$this->checkAccess())
			return false;

		$this->arResult["ENABLE_EXPORT"] = $this->enableExport;

		if(!$this->enableExport)
		{
			$this->arResult['TRIAL'] = \Bitrix\ImOpenlines\Security\Helper::getTrialText(); // TODO restrict
		}

		$this->arResult["EXPORT_HREF"] = ($this->enableExport ? $APPLICATION->GetCurPageParam('excel=Y') : 'javascript: viOpenTrialPopup(\'excel-export\');');

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER_ID"] = $this->filterId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();

		$sorting = $this->gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = \Bitrix\ImOpenLines\Model\SessionTable::getList(array(
			'order' => $sorting["sort"],
			'filter' => $this->getFilter($this->arResult["FILTER"]),
			'select' => \Bitrix\ImOpenLines\Model\SessionTable::getSelectFieldsPerformance(),
			"count_total" => true,
			'limit' => ($this->excelMode ? 0 : $nav->getLimit()),
			'offset' => ($this->excelMode ? 0 : $nav->getOffset())
		));

		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());

		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;

		$userId = array();
		$this->arResult["ELEMENTS_ROWS"] = array();
		while($data = $cursor->fetch())
		{
			if ($data["USER_ID"] > 0)
			{
				$userId[$data["USER_ID"]] = $data["USER_ID"];
			}
			if ($data["OPERATOR_ID"] > 0)
			{
				$userId[$data["OPERATOR_ID"]] = $data["OPERATOR_ID"];
			}
			$this->arResult["ELEMENTS_ROWS"][] = array("data" => $data, "columns" => array());
		}

		$this->showHistory = \Bitrix\ImOpenlines\Security\Helper::getAllowedUserIds(
			\Bitrix\ImOpenlines\Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_HISTORY, \Bitrix\ImOpenlines\Security\Permissions::ACTION_VIEW)
		);
		$configManager = new \Bitrix\ImOpenLines\Config();

		$arUsers = $this->getUserData($userId);
		$arSources = \Bitrix\ImConnector\Connector::getListConnector();
		foreach($this->arResult["ELEMENTS_ROWS"] as $key => $row)
		{
			$newRow = $this->arResult["ELEMENTS_ROWS"][$key]["columns"];

			$newRow["CONFIG_ID"] = $this->arResult['LINES'][$row["data"]["CONFIG_ID"]];

			$newRow["USER_NAME"] = $this->getUserHtml($row["data"]["USER_ID"], $arUsers);
			$newRow["OPERATOR_NAME"] = $this->getUserHtml($row["data"]["OPERATOR_ID"], $arUsers);
			$newRow["MODE_NAME"] = $row["data"]["MODE"] == 'input'? Loc::getMessage('OL_COMPONENT_TABLE_INPUT'): Loc::getMessage('OL_COMPONENT_TABLE_OUTPUT');

			$newRow["SOURCE_TEXT"] = $arSources[$row["data"]["SOURCE"]];

			if ($row["data"]["STATUS"] < 40)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT");
			}
			else if ($row["data"]["STATUS"] >= 40 && $row["data"]["STATUS"] < 60)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR");
			}
			else if ($row["data"]["STATUS"] >= 60)
			{
				$newRow["STATUS"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED");
			}

			switch ($row["data"]["STATUS"])
			{
				case 0:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_NEW");
				break;
				case 5:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_SKIP");
				break;
				case 10:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR_ANSWER");
				break;
				case 20:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT");
				break;
				case 25:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLIENT_AFTER_OPERATOR");
				break;
				case 40:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_OPERATOR");
				break;
				case 50:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_WAIT_ACTION_2");
				break;
				case 60:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_COMPONENT_TABLE_STATUS_CLOSED");
				break;
				case 65:
					$newRow["STATUS_DETAIL"] = Loc::getMessage("OL_STATS_HEADER_SPAM_2");
				break;
			}

			$newRow["PAUSE_TEXT"] = $row["data"]["PAUSE"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			$newRow["SEND_FORM"] = $row["data"]["SEND_FORM"] != 'none'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');
			$newRow["SEND_HISTORY"] = $row["data"]["SEND_HISTORY"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			$newRow["CRM_TEXT"] = self::getFormattedCrmColumn($row);

			if ($this->excelMode)
			{
				$newRow["CRM_TEXT"] = $row["data"]["CRM"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');
				$newRow["CRM_LINK"] = self::getCrmLink($row["data"]).' '.self::getCrmActivityLink($row["data"]);
			}

			$newRow["WORKTIME_TEXT"] = $row["data"]["WORKTIME"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			if (!$this->excelMode)
			{
				if (!is_array($this->showHistory) || in_array($row["data"]["OPERATOR_ID"], $this->showHistory))
				{
					$newRow["ACTION"] = '<nobr><a href="#history" onclick="BXIM.openHistory(\'imol|'.$row["data"]["ID"].'\'); return false;">'.Loc::getMessage('OL_COMPONENT_TABLE_ACTION_HISTORY').'</a></nobr> ';
				}
				if ($configManager->canJoin($row["data"]["CONFIG_ID"]))
				{
					$newRow["ACTION"] .= '<nobr><a href="#startSession" onclick="BXIM.openMessenger(\'imol|'.$row["data"]["USER_CODE"].'\'); return false;">'.Loc::getMessage('OL_COMPONENT_TABLE_ACTION_START').'</a></nobr>';
				}
			}

			$newRow["TIME_ANSWER_WO_BOT"] = $row["data"]["TIME_ANSWER"]? $row["data"]["TIME_ANSWER"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_CLOSE_WO_BOT"] = $row["data"]["TIME_CLOSE"]? $row["data"]["TIME_CLOSE"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_CLOSE"] = $row["data"]["TIME_CLOSE"] != $row["data"]["TIME_BOT"]? $row["data"]["TIME_CLOSE"]: 0;
			$newRow["TIME_DIALOG_WO_BOT"] = $row["data"]["TIME_DIALOG"]? $row["data"]["TIME_DIALOG"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["TIME_FIRST_ANSWER"] = $row["data"]["TIME_FIRST_ANSWER"]? $row["data"]["TIME_FIRST_ANSWER"]-$row["data"]["TIME_BOT"]: 0;
			$newRow["EXTRA_REGISTER"] = $row["data"]["EXTRA_REGISTER"]? $row["data"]["EXTRA_REGISTER"]: ($this->excelMode? '': '-');
			$newRow["EXTRA_TARIFF"] = $row["data"]["EXTRA_TARIFF"]? $row["data"]["EXTRA_TARIFF"]: ($this->excelMode? '': '-');

			if ($row["data"]["EXTRA_URL"])
			{
				$parsedUrl = parse_url($row["data"]["EXTRA_URL"]);
				if ($this->excelMode)
				{
					$newRow["EXTRA_DOMAIN"] = $parsedUrl['host'];
					$newRow["EXTRA_URL"] = $row["data"]["EXTRA_URL"];
				}
				else
				{
					$newRow["EXTRA_URL"] = '<a href="'.htmlspecialcharsbx($row["data"]["EXTRA_URL"]).'" target="_blank">'.htmlspecialcharsbx($parsedUrl['host']).'</a>';
				}
			}
			else
			{
				$newRow["EXTRA_URL"] = $this->excelMode? '': '-';
				if ($this->excelMode)
				{
					$newRow["EXTRA_DOMAIN"] = '';
				}
			}

			$newRow["SPAM"] = $row["data"]["SPAM"] == 'Y'? Loc::getMessage('OL_COMPONENT_TABLE_YES'): Loc::getMessage('OL_COMPONENT_TABLE_NO');

			if (!$this->excelMode)
			{
				$newRow["DATE_CREATE"] = self::formatDate($row["data"]["DATE_CREATE"]);
				$newRow["DATE_OPERATOR"] = self::formatDate($row["data"]["DATE_OPERATOR"]);
				$newRow["DATE_OPERATOR_ANSWER"] = self::formatDate($row["data"]["DATE_OPERATOR_ANSWER"]);
				$newRow["DATE_OPERATOR_CLOSE"] = self::formatDate($row["data"]["DATE_OPERATOR_CLOSE"]);
				$newRow["DATE_CLOSE"] = self::formatDate($row["data"]["DATE_CLOSE"]);
				$newRow["DATE_LAST_MESSAGE"] = self::formatDate($row["data"]["DATE_LAST_MESSAGE"]);
				$newRow["DATE_FIRST_ANSWER"] = self::formatDate($row["data"]["DATE_FIRST_ANSWER"]);
				$newRow["TIME_ANSWER_WO_BOT"] = self::formatDuration($newRow["TIME_ANSWER_WO_BOT"]);
				$newRow["TIME_CLOSE_WO_BOT"] = self::formatDuration($newRow["TIME_CLOSE_WO_BOT"]);
				$newRow["TIME_ANSWER"] = self::formatDuration($row["data"]["TIME_ANSWER"]);
				$newRow["TIME_CLOSE"] = self::formatDuration($newRow["TIME_CLOSE"]);
				$newRow["TIME_BOT"] = self::formatDuration($row["data"]["TIME_BOT"]);
				$newRow["TIME_DIALOG_WO_BOT"] = self::formatDuration($newRow["TIME_DIALOG_WO_BOT"]);
				$newRow["TIME_FIRST_ANSWER"] = self::formatDuration($newRow["TIME_FIRST_ANSWER"]);
				$newRow["TIME_DIALOG"] = self::formatDuration($row["data"]["TIME_DIALOG"]);
				$newRow["VOTE"] = self::formatVote($row["data"]["ID"], $row["data"]["VOTE"], 'VOTE');

				if ($configManager->canVoteAsHead($row["data"]["CONFIG_ID"]))
				{
					$newRow["VOTE_HEAD"] = self::formatVote($row["data"]["ID"], $row["data"]["VOTE_HEAD"], 'VOTE_HEAD_PERM');
				}
				else
				{
					$newRow["VOTE_HEAD"] = self::formatVote($row["data"]["ID"], $row["data"]["VOTE_HEAD"], 'VOTE_HEAD');
				}
			}

			$actions = Array();
			if (!$this->excelMode)
			{
				if (!is_array($this->showHistory) || in_array($row["data"]["OPERATOR_ID"], $this->showHistory))
				{
					$actions[] = $arActivityMenuItems[] = array(
						'TITLE' => GetMessage('OL_COMPONENT_TABLE_ACTION_HISTORY'),
						'TEXT' => GetMessage('OL_COMPONENT_TABLE_ACTION_HISTORY'),
						'ONCLICK' => "BXIM.openHistory('imol|{$row["data"]["ID"]}')",
						'DEFAULT' => true
					);
				}
				if ($configManager->canJoin($row["data"]["CONFIG_ID"]))
				{
					$actions[] = $arActivityMenuItems[] = array(
						'TITLE' => GetMessage('OL_COMPONENT_TABLE_ACTION_START'),
						'TEXT' => GetMessage('OL_COMPONENT_TABLE_ACTION_START'),
						'ONCLICK' => "BXIM.openMessenger('imol|{$row["data"]["USER_CODE"]}')"
					);
				}
			}

			if (!empty($actions))
			{
				$this->arResult["ELEMENTS_ROWS"][$key]["actions"] = $actions;
			}

			$this->arResult["ELEMENTS_ROWS"][$key]["columns"] = $newRow;
		}


		$this->arResult["HEADERS"] = array(
			array("id"=>"ID", "name"=> GetMessage("OL_STATS_HEADER_MODE_ID"), "default"=>true, "editable"=>false, "sort"=>"ID"),
			array("id"=>"CONFIG_ID", "name"=>GetMessage("OL_STATS_HEADER_CONFIG_NAME"), "default"=>false, "editable"=>false, "sort"=>"CONFIG_ID")
		);

		$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
			array("id"=>"MODE_NAME", "name"=>GetMessage("OL_STATS_HEADER_MODE_NAME"), "default"=>true, "editable"=>false, "sort"=>"MODE"),
			array("id"=>"STATUS", "name"=>GetMessage("OL_STATS_HEADER_STATUS"), "default"=>true, "editable"=>false),
			array("id"=>"STATUS_DETAIL", "name"=>GetMessage("OL_STATS_HEADER_STATUS_DETAIL"), "default"=>false, "editable"=>false),
			array("id"=>"SPAM", "name"=>GetMessage("OL_STATS_HEADER_SPAM"), "default"=>true, "editable"=>false, "sort"=>"SPAM"),
			array("id"=>"SOURCE_TEXT", "name"=>GetMessage("OL_STATS_HEADER_SOURCE_TEXT_2"), "default"=>true, "editable"=>false, "sort"=>"SOURCE"),
			array("id"=>"USER_NAME", "name"=>GetMessage("OL_STATS_HEADER_USER_NAME"), "default"=>true, "editable"=>false, "sort"=>"USER_ID"),
			array("id"=>"SEND_FORM", "name"=>GetMessage("OL_STATS_HEADER_SEND_FORM"), "default"=>false, "editable"=>false, "sort"=>"SEND_FORM"),
			array("id"=>"SEND_HISTORY", "name"=>GetMessage("OL_STATS_HEADER_SEND_HISTORY"), "default"=>false, "editable"=>false, "sort"=>"SEND_HISTORY"),
			array("id"=>"CRM_TEXT", "name"=>GetMessage("OL_STATS_HEADER_CRM_TEXT"), "default"=>true, "editable"=>false),
			array("id"=>"ACTION", "name"=>GetMessage("OL_STATS_HEADER_ACTION"), "default"=>true, "editable"=>false),
		));
		if ($this->excelMode)
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"CRM_LINK", "name"=>GetMessage("OL_STATS_HEADER_CRM_LINK"), "default"=>true, "editable"=>false),
				array("id"=>"EXTRA_DOMAIN", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_DOMAIN"), "default"=>true, "editable"=>false),
				array("id"=>"EXTRA_URL", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_URL"), "default"=>true, "editable"=>false),
			));
		}
		else
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"EXTRA_URL", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_URL"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_URL"),
			));
		}

		if (defined('IMOL_FDC'))
		{
			$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
				array("id"=>"EXTRA_REGISTER", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_REGISTER"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_REGISTER"),
				array("id"=>"EXTRA_TARIFF", "name"=>GetMessage("OL_STATS_HEADER_EXTRA_TARIFF"), "default"=>true, "editable"=>false, "sort"=>"EXTRA_TARIFF")
			));
		}

		$this->arResult["HEADERS"] = array_merge($this->arResult["HEADERS"], Array(
			array("id"=>"PAUSE_TEXT", "name"=>GetMessage("OL_STATS_HEADER_PAUSE_TEXT"), "default"=>false, "editable"=>false, "sort"=>"PAUSE"),
			array("id"=>"WORKTIME_TEXT", "name"=>GetMessage("OL_STATS_HEADER_WORKTIME_TEXT"), "default"=>false, "editable"=>false, "sort"=>"WORKTIME"),
			array("id"=>"MESSAGE_COUNT", "name"=>GetMessage("OL_STATS_HEADER_MESSAGE_COUNT"), "default"=>true, "editable"=>false, "sort"=>"MESSAGE_COUNT"),
			array("id"=>"OPERATOR_NAME", "name"=>GetMessage("OL_STATS_HEADER_OPERATOR_NAME"), "default"=>true, "editable"=>false, "sort"=>"OPERATOR_ID"),
			array("id"=>"DATE_CREATE", "name"=>GetMessage("OL_STATS_HEADER_DATE_CREATE"), "default"=>true, "editable"=>false, "sort"=>"DATE_CREATE"),
			array("id"=>"DATE_OPERATOR", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR"), "default"=>false, "editable"=>false),
			array("id"=>"DATE_FIRST_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_DATE_FIRST_ANSWER"), "default"=>true, "editable"=>false),
			array("id"=>"DATE_OPERATOR_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR_ANSWER"), "default"=>false, "editable"=>false),
			array("id"=>"DATE_LAST_MESSAGE", "name"=>GetMessage("OL_STATS_HEADER_DATE_LAST_MESSAGE"), "default"=>true, "editable"=>false),
			array("id"=>"DATE_OPERATOR_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_DATE_OPERATOR_CLOSE"), "default"=>true, "editable"=>false),
			array("id"=>"DATE_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_DATE_CLOSE"), "default"=>false, "editable"=>false, "sort"=>"DATE_CLOSE"),
			array("id"=>"DATE_MODIFY", "name"=>GetMessage("OL_STATS_HEADER_DATE_MODIFY"), "default"=>false, "editable"=>false, "sort"=>"DATE_MODIFY"),
			array("id"=>"TIME_FIRST_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_TIME_FIRST_ANSWER"), "default"=>true, "editable"=>false),
			array("id"=>"TIME_ANSWER_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_ANSWER_WO_BOT"), "default"=>false, "editable"=>false),
			array("id"=>"TIME_CLOSE_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_CLOSE_WO_BOT"), "default"=>false, "editable"=>false),
		//	array("id"=>"TIME_ANSWER", "name"=>GetMessage("OL_STATS_HEADER_TIME_ANSWER"), "default"=>false, "editable"=>false),
		//	array("id"=>"TIME_CLOSE", "name"=>GetMessage("OL_STATS_HEADER_TIME_CLOSE"), "default"=>false, "editable"=>false),
			array("id"=>"TIME_DIALOG_WO_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_DIALOG_WO_BOT"), "default"=>true, "editable"=>false),
		//	array("id"=>"TIME_DIALOG", "name"=>GetMessage("OL_STATS_HEADER_TIME_DIALOG"), "default"=>false, "editable"=>false),
			array("id"=>"TIME_BOT", "name"=>GetMessage("OL_STATS_HEADER_TIME_BOT"), "default"=>true, "editable"=>false),
			array("id"=>"VOTE", "name"=>GetMessage("OL_STATS_HEADER_VOTE_CLIENT"), "default"=>true, "editable"=>false, "sort"=>"VOTE"),
			array("id"=>"VOTE_HEAD", "name"=>GetMessage("OL_STATS_HEADER_VOTE_HEAD"), "default"=>true, "editable"=>false, "sort"=>"VOTE_HEAD"),
		));

		if($this->excelMode)
		{
			$now = new \Bitrix\Main\Type\Date();
			$filename = 'openlines_details_'.$now->format('Y_m_d').'.xls';
			$APPLICATION->RestartBuffer();
			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: filename=".$filename);
			$this->includeComponentTemplate('excel');
			CMain::FinalActions();
			die();
		}
		else
		{
			global $USER;
			\CPullWatch::Add($USER->GetId(), 'IMOL_STATISTICS');

			$this->includeComponentTemplate();
			return $this->arResult;
		}
	}
};