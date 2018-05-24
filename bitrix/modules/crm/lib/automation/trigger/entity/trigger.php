<?php
namespace Bitrix\Crm\Automation\Trigger\Entity;

use Bitrix\Main;

class TriggerTable extends Main\Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_automation_trigger';
	}

	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('primary' => true, 'data_type' => 'integer'),
			'NAME' => array('data_type' => 'string'),
			'CODE' => array('data_type' => 'string'),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer'),
			'ENTITY_STATUS' => array('data_type' => 'string'),
			'APPLY_RULES' => array(
				'data_type' => 'string',
				'serialized' => true
			)
		);
	}
}