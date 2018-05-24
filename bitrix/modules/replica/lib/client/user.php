<?php
namespace Bitrix\Replica\Client;

class User
{
	/**
	 * Returns true if user is marked as "remote".
	 *
	 * @param integer $userId User identifier.
	 *
	 * @return boolean
	 */
	public static function isMapped($userId)
	{
		if ($userId <= 0)
		{
			return false;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue("b_user.ID", false, $userId);
		if (!$map)
		{
			return false;
		}

		return true;
	}

	/**
	 * Marks user as "remote".
	 *
	 * @param integer $userId User identifier.
	 * @param string $guid Global user identifier (network b_user.ID).
	 * @param string $domain Domain name where replication will go.
	 *
	 * @return boolean
	 */
	public static function addMap($userId, $guid, $domain)
	{
		$relation = "b_user.ID";
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$domainId = getNameByDomain($domain);
		if (!$domainId)
		{
			return false;
		}

		$mapper->add($relation, $userId, $domainId, $guid);
		return true;
	}

	/**
	 * Returns global user identifier.
	 *
	 * @param integer $userId User identifier.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getGuid($userId)
	{
		static $cache = array();

		if (!isset($cache[$userId]))
		{
			$cache[$userId] = self::getRemoteUserGuid($userId);
			if (!$cache[$userId])
			{
				$cache[$userId] = self::getLocalUserGuid($userId);
			}
			if (!$cache[$userId])
			{
				$cache[$userId] = self::getEmailUserGuid($userId);
			}
		}

		if (!$cache[$userId])
		{
			return false;
		}

		return $cache[$userId];
	}

	/**
	 * Returns global user identifier of user being replicated.
	 *
	 * @param integer $userId User identifier.
	 *
	 * @return string|false
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getRemoteUserGuid($userId)
	{
		$userList = \Bitrix\Main\UserTable::getList(array(
			"select" => array("XML_ID"),
			"filter" => array(
				'=ID' => $userId,
				'=EXTERNAL_AUTH_ID' => 'replica',
			),
		));
		$userInfo = $userList->fetch();
		if ($userInfo)
		{
			list(, $guid) = explode("|", $userInfo["XML_ID"], 2);
			return $guid;
		}
		return false;
	}

	/**
	 * Returns global user identifier of local user.
	 *
	 * @param integer $userId User identifier.
	 *
	 * @return string|false
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getLocalUserGuid($userId)
	{
		if (\Bitrix\Main\Loader::includeModule('socialservices'))
		{
			$socUser = \CSocServAuthDB::getList(
				array("ID" => "desc"),
				array(
					'USER_ID' => $userId,
					'EXTERNAL_AUTH_ID' => 'Bitrix24Net',
				), false, false, array(
					"XML_ID",
				)
			);
			$userInfo = $socUser->fetch();
			if ($userInfo)
			{
				return $userInfo["XML_ID"];
			}

			$mainUser = \Bitrix\Main\UserTable::getList(array(
				"select" => array("XML_ID"),
				"filter" => array(
					"=ID" => $userId,
					"!=XML_ID" => false,
					"=EXTERNAL_AUTH_ID" => "socservices",
				),
			));
			$userInfo = $mainUser->fetch();
			if ($userInfo)
			{
				return $userInfo["XML_ID"];
			}
		}
		return false;
	}

	/**
	 * Returns global user identifier of email user.
	 *
	 * @param integer $userId User identifier.
	 *
	 * @return string|false
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getEmailUserGuid($userId)
	{
		$mainUser = \Bitrix\Main\UserTable::getList(array(
			"select" => array("EMAIL"),
			"filter" => array(
				"=ID" => $userId,
				"=EXTERNAL_AUTH_ID" => "email",
			),
		));
		$userInfo = $mainUser->fetch();
		if ($userInfo)
		{
			return urlencode($userInfo["EMAIL"]."|".getNameByDomain());
		}
		return false;
	}

	/**
	 * Returns true if guid is an email.
	 *
	 * @param string $guid Global user identifier.
	 * @return boolean
	 */
	public static function isEmailGuid($guid)
	{
		return strpos($guid, "%") !== false;
	}

	/**
	 * Returns b_user.ID of user being replicated by its global identifier which is network.b_user.ID.
	 *
	 * @param string $userGuid Global user identifier.
	 *
	 * @return integer|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getRemoteUserId($userGuid)
	{
		$userList = \Bitrix\Main\UserTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				'=%XML_ID' => "%|".$userGuid,
				'=EXTERNAL_AUTH_ID' => 'replica',
			),
		));
		$userInfo = $userList->fetch();
		if ($userInfo)
		{
			return intval($userInfo["ID"]);
		}
		return false;
	}

	/**
	 * Returns b_user.ID of local user by user global identifier which is network.b_user.ID.
	 *
	 * @param string $userGuid Global user identifier.
	 * @param boolean $isEmail If guid is an email set this parameter to True.
	 *
	 * @return integer|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getLocalUserId($userGuid, $isEmail = false)
	{
		if ($isEmail)
		{
			list ($email, ) = explode("|", urldecode($userGuid));
			$mainUser = \Bitrix\Main\UserTable::getList(array(
				"select" => array("ID"),
				"filter" => array(
					"=EMAIL" => $email,
					"=EXTERNAL_AUTH_ID" => "email",
				),
			));
			$userInfo = $mainUser->fetch();
			if ($userInfo)
			{
				return intval($userInfo["ID"]);
			}
		}
		elseif (\Bitrix\Main\Loader::includeModule('socialservices'))
		{
			$socUser = \CSocServAuthDB::getList(
				array(),
				array(
					'XML_ID' => $userGuid,
					'EXTERNAL_AUTH_ID' => 'Bitrix24Net',
				), false, false, array(
					"USER_ID",
				)
			);
			$userInfo = $socUser->fetch();
			if ($userInfo)
			{
				return intval($userInfo["USER_ID"]);
			}

			$mainUser = \Bitrix\Main\UserTable::getList(array(
				"select" => array("ID"),
				"filter" => array(
					"=XML_ID" => $userGuid,
					"=EXTERNAL_AUTH_ID" => "socservices",
				),
			));
			$userInfo = $mainUser->fetch();
			if ($userInfo)
			{
				return intval($userInfo["ID"]);
			}
		}
		return false;
	}

	/**
	 * Returns b_user.ID by user global identifier which is network.b_user.ID.
	 *
	 * @param string $userGuid Global user identifier.
	 *
	 * @return integer|boolean
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getId($userGuid)
	{
		static $cache = array();

		if (!isset($cache[$userGuid]))
		{
			$cache[$userGuid] = self::getRemoteUserId($userGuid);

			if (!$cache[$userGuid])
			{
				$cache[$userGuid] = self::getLocalUserId($userGuid);
			}
		}

		if (!$cache[$userGuid])
		{
			return false;
		}

		return $cache[$userGuid];
	}

	/**
	 * Searches network for the user and if found adds him locally and marks as remote in the replication map.
	 *
	 * @param string $userGuid Global user identifier.
	 * @param string $userDomain Source domain.
	 *
	 * @return integer|boolean
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Replica\ClientException
	 */
	public static function registerNew($userGuid, $userDomain = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('socialservices'))
		{
			return false;
		}

		$query = \CBitrix24NetPortalTransport::init();
		if (!$query)
		{
			return false;
		}

		$queryResult = $query->call('profile.search', array(
			'USER_ID' => $userGuid,
			'skip_check' => 1,
		));

		if ($queryResult && $queryResult['result'])
		{
			if ($userDomain)
			{
				foreach ($queryResult['result'] as $item)
				{
					if ($item['CLIENT_DOMAIN'] === $userDomain)
					{
						return self::addUser($item);
					}
				}
			}

			foreach ($queryResult['result'] as $item)
			{
				if ($item["USER_ID"] == $userGuid)
				{
					return self::addUser($item);
				}
			}
		}
		else
		{
			throw new \Bitrix\Replica\ClientException('['.$queryResult['error'].']'.$queryResult['error_description']);
		}

		return false;
	}

	/**
	 * Adds remote email user as local replica not active user.
	 *
	 * @param string $userGuid Global user identifier.
	 * @param string $userDomain Source domain.
	 *
	 * @return bool|int|string
	 * @throws \Bitrix\Replica\ClientException
	 */
	public static function registerNewEmail($userGuid, $userDomain = '')
	{
		$user = new \CUser;

		list ($email, $node) = explode("|", urldecode($userGuid), 2);
		$userFields = array(
			'LOGIN' => $email."|".$userDomain,
			'XML_ID' => "|".$userGuid,
			'NAME' => $email,
			'WORK_POSITION' => $userDomain,
			'EMAIL' => $email,
			'ACTIVE' => "N",
			'EXTERNAL_AUTH_ID' => 'replica',
		);

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
		{
			$userFields['UF_DEPARTMENT'] = array();
		}

		$result = $user->add($userFields);
		if ($result)
		{
			return $result;
		}
		throw new \Bitrix\Replica\ClientException('Email user add has failed ['.$user->LAST_ERROR.'].');
	}

	/**
	 * Returns new user identifier.
	 *
	 * @param array $userNetworkInfo User information from the network24.
	 *
	 * @return false|integer
	 * @throws \Bitrix\Replica\ClientException
	 */
	protected static function addUser($userNetworkInfo)
	{
		$user = \Bitrix\Socialservices\Network::formatUserParam($userNetworkInfo);
		if ($user)
		{
			$network = new \Bitrix\Socialservices\Network();
			$network->errorCollection->clear();
			$result = $network->addUser($user);
			if ($result)
			{
				return $result;
			}
			else
			{
				throw new \Bitrix\Replica\ClientException('User add has failed ['.implode($network->errorCollection->toArray()).'].');
			}
		}
		else
		{
			throw new \Bitrix\Replica\ClientException('User format has failed.');
		}
	}

	/**
	 * Returns array with replica users fltered by $searchParams["SEARCH"].
	 *
	 * @param array $searchParams Array with search parameters.<br>
	 *	possible keys:
	 *	<ul>
	 *	<li>SEARCH - search query.
	 *	<li>NAME_TEMPLATE - how user name will be formatted.
	 *	</ul>
	 *      Search query will be split by space and '%' will be added to each part.
	 *
	 * @return array
	 */
	public static function search($searchParams)
	{
		$search = trim($searchParams["SEARCH"]);
		$nameTemplate = (isset($searchParams["NAME_TEMPLATE"]) ? $searchParams["NAME_TEMPLATE"] : '');

		$users = array();

		$search = trim($search);
		if ($search == '')
		{
			return $users;
		}

		$searchValue = preg_split('/\s+/', trim(ToUpper($search)));
		array_walk($searchValue, array('CSocNetLogDestination', '__percent_walk'));

		$logicFilter = array(
			'LOGIC' => 'OR',
			'NAME' => $searchValue,
			'LAST_NAME' => $searchValue,
		);

		if (
			count($searchValue) == 1
			&& strlen($searchValue[0]) > 2
		)
		{
			$logicFilter['LOGIN'] = $searchValue[0];
		}

		$filter = array(
			$logicFilter,
			'=ACTIVE' => 'Y',
			'=EXTERNAL_AUTH_ID' => 'replica',
		);

		$select = array(
			"ID",
			"NAME",
			"LAST_NAME",
			"SECOND_NAME",
			"EMAIL",
			"LOGIN",
			"WORK_POSITION",
			"PERSONAL_PROFESSION",
			"PERSONAL_PHOTO",
			"PERSONAL_GENDER",
			"EXTERNAL_AUTH_ID",
			new \Bitrix\Main\Entity\ExpressionField('MAX_LAST_USE_DATE', 'MAX(%s)', array('\Bitrix\Main\FinderDest:CODE_USER_CURRENT.LAST_USE_DATE'))
		);

		$userList = \Bitrix\Main\UserTable::getList(array(
			'order' => array(
				"\\Bitrix\\Main\\FinderDest:CODE_USER_CURRENT.LAST_USE_DATE" => 'DESC',
				'LAST_NAME' => 'ASC'
			),
			'filter' => $filter,
			'select' => $select,
			'limit' => 50,
			'data_doubling' => false
		));

		while ($user = $userList->fetch())
		{
			$users['U'.$user["ID"]] = \CSocNetLogDestination::formatUser($user, array(
				"NAME_TEMPLATE" => $nameTemplate,
			));
		}

		return $users;
	}
}
