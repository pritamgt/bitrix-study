<?php
namespace Bitrix\Oauth;

use Bitrix\Main;

/**
 * Class ClientVersionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> VERSION int optional
 * </ul>
 *
 * @package Bitrix\Oauth
 **/
class ClientVersionTable extends Main\Entity\DataManager
{
	const INACTIVE = 'N';
	const ACTIVE = 'Y';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_client_version';
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
			'VERSION' => array(
				'data_type' => 'integer',
			),
			'SCOPE' => array(
				'data_type' => 'string',
				'serialized' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(static::INACTIVE, static::ACTIVE),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
		);
	}

	public static function deleteByClient($clientId)
	{
		$connection = Main\Application::getConnection();
		return $connection->query("DELETE FROM ".static::getTableName()." WHERE CLIENT_ID='".intval($clientId)."'");
	}

	public static function getLastVersion($clientId, $checkActive = true)
	{
		$filter = array('=CLIENT_ID' => $clientId);
		if($checkActive)
		{
			$filter['=ACTIVE'] = static::ACTIVE;
		}

		$dbRes = static::getList(array(
			'order' => array(
				'VERSION' => 'DESC',
			),
			'filter' => $filter,
			'select' => array('ID', 'VERSION', 'SCOPE'),
			'limit' => array(0, 1)
		));
		return $dbRes->fetch();
	}
}