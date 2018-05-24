<?php
namespace Bitrix\OAuth;

use Bitrix\Main;

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> CLIENT_ID int optional
 * <li> INSTALL_CLIENT_ID int optional
 * <li> MESSAGE string(255) mandatory
 * <li> DETAIL string optional
 * <li> RESULT string(11) optional
 * </ul>
 *
 * @package Bitrix\Oauth
 **/
class LogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_log';
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
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'CLIENT_ID' => array(
				'data_type' => 'integer',
			),
			'INSTALL_CLIENT_ID' => array(
				'data_type' => 'integer',
			),
			'MESSAGE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'DETAIL' => array(
				'data_type' => 'text',
				'serialized' => true,
			),
			'ERROR' => array(
				'data_type' => 'string',
			),
			'RESULT' => array(
				'data_type' => 'string',
			),
		);
	}

	public static function onBeforeAdd(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult();
		$data = $event->getParameters();

		$modifyFields = array(
			"TIMESTAMP_X" => new Main\Type\DateTime(),
		);

		if(is_array($data['fields']['ERROR']))
		{
			$modifyFields['ERROR'] = implode(";\n", $data['fields']['ERROR']);
		}

		$result->modifyFields($modifyFields);

		return $result;
	}
}
