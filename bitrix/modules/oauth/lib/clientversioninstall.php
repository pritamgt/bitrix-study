<?php
namespace Bitrix\OAuth;

use Bitrix\Main;

/**
 * Class ClientVersionInstallTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> VERSION_ID int mandatory
 * <li> INSTALL_CLIENT_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Oauth
 **/
class ClientVersionInstallTable extends Main\Entity\DataManager
{
	const INACTIVE = 'N';
	const ACTIVE = 'Y';

	const STATUS_FREE = 'F';
	const STATUS_DEMO = 'D';
	const STATUS_TRIAL = 'T';
	const STATUS_PAID = 'P';
	const STATUS_LOCAL = 'L';

	const NOT_TRIALED = 'N';
	const TRIALED = 'Y';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_client_version_install';
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
			'VERSION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'INSTALL_CLIENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CREATED' => array(
				'data_type' => 'datetime',
			),
			'CHANGED' => array(
				'data_type' => 'datetime',
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(self::INACTIVE, self::ACTIVE),
			),
			'STATUS' => array(
				'data_type' => 'enum',
				'values' => array(self::STATUS_FREE, self::STATUS_DEMO, self::STATUS_TRIAL, self::STATUS_PAID, self::STATUS_LOCAL),
			),
			'DATE_FINISH' => array(
				'data_type' => 'datetime',
			),
			'IS_TRIALED' => array(
				'data_type' => 'boolean',
				'values' => array(self::NOT_TRIALED, self::TRIALED),
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
			'VERSION' => array(
				'data_type' => 'Bitrix\OAuth\ClientVersionTable',
				'reference' => array('=this.VERSION_ID' => 'ref.ID'),
			),
			'INSTALL_CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.INSTALL_CLIENT_ID' => 'ref.ID'),
			),
		);
	}

	public static function deleteByClient($clientId)
	{
		$connection = Main\Application::getConnection();
		return $connection->query("DELETE FROM ".static::getTableName()." WHERE CLIENT_ID='".intval($clientId)."'");
	}

	public static function onBeforeAdd(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult();

		$result->modifyFields(array(
			"CREATED" => new Main\Type\DateTime(),
			"CHANGED" => new Main\Type\DateTime(),
		));

		return $result;
	}

	public static function onBeforeUpdate(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult();

		$result->modifyFields(array(
			"CHANGED" => new Main\Type\DateTime(),
		));

		return $result;
	}
}
