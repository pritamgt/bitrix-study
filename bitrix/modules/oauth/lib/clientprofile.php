<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage oauth
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\OAuth;

use \Bitrix\Main\Entity;

class ClientProfileTable extends Entity\DataManager
{
	const ACTIVE = 'Y';
	const INACTIVE = 'N';

	const STATUS_CREATOR = 'C';
	const STATUS_ADMIN = 'A';
	const STATUS_USER = 'U';
	const STATUS_EXTRANET = 'E';

	const ACCEPTED = 'Y';
	const NOT_ACCEPTED = 'N';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_oauth_client_profile';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CLIENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CLIENT_PROFILE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CLIENT_PROFILE_ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(self::INACTIVE, self::ACTIVE),
			),
			'CONFIRM_CODE' => array(
				'data_type' => 'string',
			),
			'CLIENT_PROFILE_STATUS' => array(
				'data_type' => 'enum',
				'values' => array(self::STATUS_USER, self::STATUS_EXTRANET, self::STATUS_ADMIN, self::STATUS_CREATOR),
			),
			'ACCEPTED' => array(
				'data_type' => 'boolean',
				'values' => array(self::NOT_ACCEPTED, self::ACCEPTED),
			),
			'LAST_AUTHORIZE' => array(
				'data_type' => 'datetime',
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
		);

		return $fieldsMap;
	}
}
