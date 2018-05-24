<?php
namespace Bitrix\OAuth;

use Bitrix\Main\Entity;

/**
 * Class CodeTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> CODE string(100) mandatory
 * <li> EXPIRES int mandatory
 * <li> USED bool optional default 'N'
 * <li> USER_ID int mandatory
 * </ul>
 *
 * @package Bitrix\OAuth
 **/

class CodeTable extends Entity\DataManager
{
	const USED = "Y";
	const NOT_USED = "N";

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_code';
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
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCode'),
			),
			'EXPIRES' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USED' => array(
				'data_type' => 'boolean',
				'values' => array(static::NOT_USED, static::USED),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => false,
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
		);
	}
	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
}
