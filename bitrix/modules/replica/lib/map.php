<?php
namespace Bitrix\Replica;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class MapTable
 *
 * Fields:
 * <ul>
 * <li> TABLE_NAME string(50) mandatory
 * <li> ID_VALUE int mandatory
 * <li> NODE_TO string(50) mandatory
 * <li> GUID string(150) mandatory
 * </ul>
 *
 * @package Bitrix\Replica
 **/
class MapTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_replica_map';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'TABLE_NAME' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateTableName'),
				'title' => Loc::getMessage('MAP_ENTITY_TABLE_NAME_FIELD'),
			),
			'ID_VALUE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateIdValue'),
				'title' => Loc::getMessage('MAP_ENTITY_ID_VALUE_FIELD'),
			),
			'NODE_TO' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateNodeTo'),
				'title' => Loc::getMessage('MAP_ENTITY_NODE_TO_FIELD'),
			),
			'GUID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateGUID'),
				'title' => Loc::getMessage('MAP_ENTITY_GUID_FIELD'),
			),
		);
	}

	/**
	 * Returns validators for TABLE_NAME field.
	 *
	 * @return array
	 */
	public static function validateTableName()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for ID_VALUE field.
	 *
	 * @return array
	 */
	public static function validateIdValue()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for NODE_TO field.
	 *
	 * @return array
	 */
	public static function validateNodeTo()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for GUID field.
	 *
	 * @return array
	 */
	public static function validateGUID()
	{
		return array(
			new Entity\Validator\Length(null, 150),
		);
	}
}