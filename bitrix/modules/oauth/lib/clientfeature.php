<?php
namespace Bitrix\OAuth;

use Bitrix\Main;
use Bitrix\Main\Config\Option;

/**
 * Class ClientFeatureTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> FEATURE string(50) mandatory
 * </ul>
 *
 * @package Bitrix\Oauth
 **/

class ClientFeatureTable extends Main\Entity\DataManager
{
	const ENABLED = "Y";
	const DISABLED = "N";

	const REPLICA = 'replica';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_client_feature';
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
			'FEATURE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(self::DISABLED, self::ENABLED),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
		);
	}

	public static function isEnabledGlobal($feature, $clientType)
	{
		return Option::get("oauth", "oauth_feature_".$feature."_".$clientType, static::DISABLED) === static::ENABLED;
	}
}