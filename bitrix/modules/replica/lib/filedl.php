<?php
namespace Bitrix\Replica;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class FileDlTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FILE_ID int mandatory
 * <li> FILE_SIZE int mandatory
 * <li> FILE_SRC string(500) mandatory
 * <li> FILE_UPDATE string(500) optional
 * <li> FILE_POS int mandatory
 * <li> PART_SIZE int mandatory
 * <li> STATUS bool optional default 'Y'
 * <li> ERROR_MESSAGE string(500) optional
 * </ul>
 *
 * @package Bitrix\Replica
 **/

class FileDlTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_replica_file_dl';
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
				'title' => Loc::getMessage('FILE_DL_ENTITY_ID_FIELD'),
			),
			'FILE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('FILE_DL_ENTITY_FILE_ID_FIELD'),
			),
			'FILE_SIZE' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('FILE_DL_ENTITY_FILE_SIZE_FIELD'),
			),
			'FILE_SRC' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateFileSrc'),
				'title' => Loc::getMessage('FILE_DL_ENTITY_FILE_SRC_FIELD'),
			),
			'FILE_UPDATE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateFileUpdate'),
				'title' => Loc::getMessage('FILE_DL_ENTITY_FILE_UPDATE_FIELD'),
			),
			'FILE_POS' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('FILE_DL_ENTITY_FILE_POS_FIELD'),
			),
			'PART_SIZE' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('FILE_DL_ENTITY_PART_SIZE_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FILE_DL_ENTITY_STATUS_FIELD'),
			),
			'ERROR_MESSAGE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateErrorMessage'),
				'title' => Loc::getMessage('FILE_DL_ENTITY_TMP_NAME_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for FILE_SRC field.
	 *
	 * @return array
	 */
	public static function validateFileSrc()
	{
		return array(
			new Main\Entity\Validator\Length(null, 500),
		);
	}
	/**
	 * Returns validators for TMP_NAME field.
	 *
	 * @return array
	 */
	public static function validateErrorMessage()
	{
		return array(
			new Main\Entity\Validator\Length(null, 500),
		);
	}
	/**
	 * Returns validators for FILE_UPDATE field.
	 *
	 * @return array
	 */
	public static function validateFileUpdate()
	{
		return array(
			new Main\Entity\Validator\Length(null, 500),
		);
	}

	/**
	 * Tries to put lock onto the table.
	 *
	 * @return boolean
	 */
	public static function lock()
	{
		$uniq = \CMain::getServerUniqID();
		$connection = \Bitrix\Main\Application::getConnection();

		$result = $connection->query("SELECT GET_LOCK('".$uniq.self::getTableName()."', 0) as L");
		$lock = $result->fetch();
		if ($lock && $lock["L"] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Unlocks the table.
	 *
	 * @return void
	 */
	public static function unlock()
	{
		$uniq = \CMain::getServerUniqID();
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->query("SELECT RELEASE_LOCK('".$uniq.self::getTableName()."')");
	}
}