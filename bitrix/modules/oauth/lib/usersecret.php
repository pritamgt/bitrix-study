<?php
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 */

namespace Bitrix\OAuth;

use Bitrix\Main;

/**
 * Class UserSecretTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> CLIENT_ID int optional
 * <li> SECRET string(20) mandatory
 * </ul>
 *
 * @package Bitrix\Oauth
 **/
class UserSecretTable extends Main\Entity\DataManager
{
	const PUBLIC_JOIN_SESSION_KEY = "OAUTH_PUBLIC_JOIN_TOKEN";

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_user_secret';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SECRET' => array(
				'data_type' => 'string',
				'required' => true,
			),
		);
	}

	public static function deleteBySecret($secret)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->query("DELETE FROM ".static::getTableName()." WHERE SECRET='".$helper->forSql($secret)."'");
	}

	public static function registerSecret($token = null, $userId = null)
	{
		if($token !== null)
		{
			if(!isset($_SESSION[static::PUBLIC_JOIN_SESSION_KEY]))
			{
				$_SESSION[static::PUBLIC_JOIN_SESSION_KEY] = array();
			}

			if(!array_key_exists($token, $_SESSION[static::PUBLIC_JOIN_SESSION_KEY]))
			{
				$_SESSION[static::PUBLIC_JOIN_SESSION_KEY][$token] = 1;
			}
		}

		if($userId > 0)
		{
			$add = $token !== null;

			$dbRes = static::getList(array(
				'filter' => array(
					"=USER_ID" => $userId,
				),
				'select' => array('SECRET'),
			));
			while($userToken = $dbRes->fetch())
			{
				if($add && $token === $userToken["SECRET"])
				{
					$add = false;
				}
				else
				{
					$_SESSION[static::PUBLIC_JOIN_SESSION_KEY][$userToken["SECRET"]] = 1;
				}
			}

			if($add)
			{
				static::add(array(
					"USER_ID" => $userId,
					"SECRET" => $token,
				));
			}
		}
	}

	public static function getRegisteredSecret()
	{
		if(!is_array($_SESSION[static::PUBLIC_JOIN_SESSION_KEY]))
		{
			$_SESSION[static::PUBLIC_JOIN_SESSION_KEY] = array();
		}

		return $_SESSION[static::PUBLIC_JOIN_SESSION_KEY];
	}

	public static function onAfterUserRegister(&$userFields)
	{
		if(
			$userFields["RESULT_MESSAGE"]["TYPE"] == "OK"
			&& $userFields["RESULT_MESSAGE"]["ID"] > 0
			&& Main\ModuleManager::isModuleInstalled("b24network")
		)
		{
			$registeredSecret = static::getRegisteredSecret();
			foreach($registeredSecret as $token => $ok)
			{
				static::add(array(
					"USER_ID" => $userFields["RESULT_MESSAGE"]["ID"],
					"SECRET" => $token,
				));
			}
		}
	}
}