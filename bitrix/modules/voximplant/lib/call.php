<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Model\ExternalLineTable;
use Bitrix\Voximplant\Model\QueueTable;

Loc::loadMessages(__FILE__);

/**
 * Class CallTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int optional
 * <li> SEARCH_ID string(255) mandatory
 * <li> CALL_ID string(255) mandatory
 * <li> CALLER_ID string(255) optional
 * <li> STATUS string(50) optional
 * <li> ACCESS_URL string(255) mandatory
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class CallTable extends Entity\DataManager
{
	const STATUS_WAITING = 'waiting';
	const STATUS_CONNECTING = 'connecting';
	const STATUS_CONNECTED = 'connected';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_call';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CALL_ENTITY_ID_FIELD'),
			),
			'CONFIG_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_CONFIG_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_USER_ID_FIELD'),
			),
			'TRANSFER_TYPE' => array(
				'data_type' => 'string',
			),
			'TRANSFER_USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_TRANSFER_USER_ID_FIELD'),
			),
			'TRANSFER_PHONE' => array(
				'data_type' => 'string',
			),
			'PORTAL_USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_PORTAL_USER_ID_FIELD'),
			),
			'CALL_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCallId'),
				'title' => Loc::getMessage('CALL_ENTITY_CALL_ID_FIELD'),
			),
			'INCOMING' => array(
				'data_type' => 'string',
				'title' => '',
			),
			'CALLER_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallerId'),
				'title' => Loc::getMessage('CALL_ENTITY_CALLER_ID_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateStatus'),
				'title' => Loc::getMessage('CALL_ENTITY_STATUS_FIELD'),
			),
			'CRM' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => '',
			),
			'CRM_LEAD' => array(
				'data_type' => 'integer',
				'title' => '',
			),
			'CRM_ENTITY_TYPE' => array(
				'data_type' => 'string',
				'title' => '',
			),
			'CRM_ENTITY_ID' => array(
				'data_type' => 'integer',
				'title' => '',
			),
			'CRM_ACTIVITY_ID' => array(
				'data_type' => 'integer',
				'title' => '',
			),
			'CRM_CALL_LIST' => array(
				'data_type' => 'integer',
				'title' => '',
			),
			'CRM_BINDINGS' => array(
				'data_type' => 'text',
				'serialized' => true
			),
			'ACCESS_URL' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateAccessUrl'),
				'title' => Loc::getMessage('CALL_ENTITY_ACCESS_URL_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CALL_ENTITY_DATE_CREATE_FIELD'),
			),
			'REST_APP_ID' => array(
				'data_type' => 'integer',
				'title' => ''
			),
			'EXTERNAL_LINE_ID' => array(
				'data_type' => 'integer',
			),
			'PORTAL_NUMBER' => array(
				'data_type' => 'string',
			),
			'QUEUE_ID' => array(
				'data_type' => 'integer',
				'title' => ''
			),
			'QUEUE_HISTORY' => array(
				'data_type' => 'text',
				'serialized' => true,
				'default_value' => array()
			),
			'SESSION_ID' => array(
				'data_type' => 'integer'
			),
			'CALLBACK_PARAMETERS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'default_value' => array()
			),
			'COMMENT' => array(
				'data_type' => 'text',
			),
			'WORKTIME_SKIPPED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => '',
			),
			'QUEUE' => new Entity\ReferenceField(
				'QUEUE',
				QueueTable::getEntity(),
				array('=this.QUEUE_ID' => 'ref.ID'),
				array('join_type' => 'left')
			),
			'CONFIG' => new Entity\ReferenceField(
				'CONFIG',
				ConfigTable::getEntity(),
				array('=this.CONFIG_ID' => 'ref.ID'),
				array('join_type' => 'left')
			),
			'EXTERNAL_LINE' => new Entity\ReferenceField(
				'EXTERNAL_LINE',
				ExternalLineTable::getEntity(),
				array('=this.EXTERNAL_LINE_ID' => 'ref.ID'),
				array('join_type' => 'left')
			)
		);
	}
	public static function validateCallId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCallerId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateStatus()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateAccessUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function getByCallId($callId)
	{
		return static::getList(array(
			'filter' => array(
				'=CALL_ID' => $callId
			)
		))->fetch();
	}

	public static function updateWithCallId($callId, array $fields)
	{
		$callId = (string)$callId;
		if($callId == '')
			return;

		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$update = $helper->prepareUpdate(self::getTableName(), $fields);
		$query = 'UPDATE '. $helper->quote(self::getTableName()) . ' SET ' . $update[0] . ' WHERE CALL_ID = \'' . $helper->forSql($callId) . '\'';

		$conn->queryExecute($query);
	}
}