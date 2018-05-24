<?php
namespace Bitrix\Replica;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional default 'CURRENT_TIMESTAMP'
 * <li> EVENT string mandatory
 * </ul>
 *
 * @package Bitrix\Replica
 **/
class LogTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_replica_log';
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
				'title' => Loc::getMessage('LOG_ENTITY_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => false,
				'title' => Loc::getMessage('LOG_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'EVENT' => array(
				'data_type' => 'text',
				'required' => true,
				'title' => Loc::getMessage('LOG_ENTITY_EVENT_FIELD'),
			),
		);
	}
}