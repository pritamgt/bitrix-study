<?php
namespace Bitrix\Oauth;

use Bitrix\Main;

/**
 * Class ClientVersionUriTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CLIENT_ID int mandatory
 * <li> VERSION_ID int mandatory
 * <li> REDIRECT_URI string(555) mandatory
 * </ul>
 *
 * @package Bitrix\Oauth
 **/
class ClientVersionUriTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_oauth_client_version_uri';
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
			'REDIRECT_URI' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CLIENT' => array(
				'data_type' => 'Bitrix\OAuth\ClientTable',
				'reference' => array('=this.CLIENT_ID' => 'ref.ID'),
			),
			'VERSION' => array(
				'data_type' => 'Bitrix\OAuth\ClientVersionTable',
				'reference' => array('=this.VERSION_ID' => 'ref.ID'),
			),
		);
	}

	public static function setVersionUri($clientId, $versionId, $redirectUri)
	{
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$query = "
UPDATE ".static::getTableName()."
SET REDIRECT_URI='".$helper->forSql($redirectUri)."'
WHERE CLIENT_ID='".intval($clientId)."'
AND VERSION_ID='".intval($versionId)."'
";

		return $connection->query($query);
	}


	public static function deleteByClient($clientId)
	{
		$connection = Main\Application::getConnection();
		return $connection->query("DELETE FROM ".static::getTableName()." WHERE CLIENT_ID='".intval($clientId)."'");
	}
}