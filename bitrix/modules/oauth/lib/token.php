<?php
namespace Bitrix\OAuth;

use Bitrix\Main\Entity;

/**
 * Class TokenTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> OAUTH_TOKEN string(100) mandatory
 * <li> USER_ID int mandatory
 * <li> EXPIRES int mandatory
 * <li> SCOPE string(1000) optional
 * <li> PARAMETERS string(2000) optional
 * </ul>
 *
 * @package Bitrix\Oauth
 **/

class TokenTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_token';
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
			'CLIENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OAUTH_TOKEN' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'EXPIRES' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SCOPE' => array(
				'data_type' => 'string',
			),
			'PARAMETERS' => array(
				'data_type' => 'string',
				'serialized' => true,
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
			'INSTALL_CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);
	}

	public static function clearByUser($userId, $clientId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("DELETE FROM ".static::getTableName()." WHERE USER_ID='".intval($userId)."' AND CLIENT_ID='".intval($clientId)."'");
	}
}