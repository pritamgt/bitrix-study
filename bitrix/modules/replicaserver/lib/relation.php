<?php
namespace Bitrix\ReplicaServer;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class RelationTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> START_DATE datetime mandatory
 * <li> NODE_FROM string(100) optional
 * <li> NODE_TO string(100) optional
 * </ul>
 *
 * @package Bitrix\ReplicaServer
 **/

class RelationTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_replica_relation';
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
				'title' => Loc::getMessage('RELATION_ENTITY_ID_FIELD'),
			),
			'START_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('RELATION_ENTITY_START_DATE_FIELD'),
			),
			'NODE_FROM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNodeFrom'),
				'title' => Loc::getMessage('RELATION_ENTITY_NODE_FROM_FIELD'),
			),
			'NODE_TO' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNodeTo'),
				'title' => Loc::getMessage('RELATION_ENTITY_NODE_TO_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for NODE_FROM field.
	 *
	 * @return array
	 */
	public static function validateNodeFrom()
	{
		return array(
			new Entity\Validator\Length(null, 100),
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