<?php
namespace Bitrix\ReplicaServer;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class StopTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> STOP_DATE datetime mandatory
 * <li> NODE_TO string(100) mandatory
 * </ul>
 *
 * @package Bitrix\ReplicaServer
 **/

class StopTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_replica_stop';
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
				'title' => Loc::getMessage('STOP_ENTITY_ID_FIELD'),
			),
			'STOP_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('STOP_ENTITY_STOP_DATE_FIELD'),
			),
			'NODE_TO' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateNodeTo'),
				'title' => Loc::getMessage('STOP_ENTITY_NODE_TO_FIELD'),
			),
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
			new Entity\Validator\Length(null, 100),
		);
	}
}