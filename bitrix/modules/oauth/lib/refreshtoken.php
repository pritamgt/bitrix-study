<?php
namespace Bitrix\OAuth;

use Bitrix\Main\Entity;

/**
 * Class RefreshTokenTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> REFRESH_TOKEN string(100) mandatory
 * <li> EXPIRES int mandatory
 * <li> USER_ID int mandatory
 * <li> OAUTH_TOKEN_ID int mandatory
 * </ul>
 *
 * @package Bitrix\OAuth
 **/

class RefreshTokenTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_refresh_token';
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
			'REFRESH_TOKEN' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateRefreshToken'),
			),
			'EXPIRES' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'OAUTH_TOKEN_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
			'TOKEN' => array(
				'data_type' => 'Bitrix\OAuth\TokenTable',
				'reference' => array('=this.OAUTH_TOKEN_ID' => 'ref.ID'),
			),
		);
	}

	public static function clearByUser($userId, $clientId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("DELETE FROM ".static::getTableName()." WHERE USER_ID='".intval($userId)."' AND CLIENT_ID='".intval($clientId)."'");
	}

	/**
	 * Returns validators for REFRESH_TOKEN field.
	 *
	 * @return array
	 */
	public static function validateRefreshToken()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
}