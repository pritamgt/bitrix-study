<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage oauth
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\OAuth;

use \Bitrix\Main;

class ClientTable extends Main\Entity\DataManager
{
	const TYPE_APPLICATION = 'A'; // marketplace application
	const TYPE_PORTAL = 'P'; // bitrix24 portal
	const TYPE_SITE = 'S'; // site without client profile link
	const TYPE_EXTERNAL = 'E'; // external site (box)

	const PSEUDOTYPE_LOCAL = 'L';

	/*
	 * @deprecated
	 */
	const TYPE_SEO = 'G'; // seo client
	const TYPE_BITRIX = 'B'; // bitrix client

	protected static $suffix = array(
		self::TYPE_APPLICATION => "app",
		self::TYPE_PORTAL => "b24",
		self::TYPE_SITE => "site",
		self::TYPE_EXTERNAL => "ext",
		self::PSEUDOTYPE_LOCAL => "local",
		/*
		 * @deprecated
		 */
		self::TYPE_SEO => "seo",
		self::TYPE_BITRIX => "bx",
	);

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_oauth_client';
	}

	public static function getUfId()
	{
		return 'OAUTH_CLIENT';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CLIENT_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CLIENT_SECRET' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CLIENT_TYPE' => array(
				'data_type' => 'enum',
				'values' => array_keys(static::$suffix),
			),
			/**
			 * @deprecated
			 * use ClientVersionScopeTable
			 */
			'SCOPE' => array(
				'data_type' => 'string',
				'serialized' => true,
			),
			'TITLE' => array(
				'data_type' => 'string',
			),
			/**
			 * @deprecated
			 * use ClientVersionUriTable
			 */
			'REDIRECT_URI' => array(
				'data_type' => 'string',
			),
			'CLIENT_OWNER_ID' => array(
				'data_type' => 'integer',
			),
		);

		return $fieldsMap;
	}

	public static function getClientType($client_id)
	{
		$clientType = static::TYPE_APPLICATION;

		$p = strpos($client_id, ".");
		if($p > 0)
		{
			$suffix = substr($client_id, 0, $p);
			$check = array_reverse(static::$suffix);
			if(array_key_exists($suffix, $check))
			{
				$clientType = $check[$suffix];
			}
		}

		return $clientType;
	}

	public static function getClientSuffix($clientType, $local = false)
	{
		if($local)
		{
			$clientType = static::PSEUDOTYPE_LOCAL;
		}

		if(array_key_exists($clientType, static::$suffix))
		{
			return static::$suffix[$clientType].".";
		}

		return false;
	}
}
