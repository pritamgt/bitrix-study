<?php
/**
 * Created by PhpStorm.
 * User: sigurd
 * Date: 18.12.17
 * Time: 9:27
 */

namespace Bitrix\OAuth\Auth;


use Bitrix\OAuth\Base;
use Bitrix\OAuth\ClientTable;

class RestAuth
{
	const AUTH_TYPE_CLIENT = 'client';
	const AUTH_TYPE_TOKEN = 'token';

	protected static $authQueryParams = array(
		'auth', 'access_token'
	);

	public static function onRestCheckAuth(array $query, $scope, &$res)
	{
		$res = array('error' => '');

		$accessToken = null;
		foreach(static::$authQueryParams as $key)
		{
			if(array_key_exists($key, $query))
			{
				$accessToken = $query[$key];
				break;
			}
		}

		if($accessToken !== null)
		{
			$client = Base::instanceByToken($accessToken);

			if($client)
			{
				$res = $client->verifyAccessTokenInternal($accessToken, $scope == \CRestUtil::GLOBAL_SCOPE ? '' : $scope);

				if($client->getClientType() !== ClientTable::TYPE_APPLICATION && $res['user_id'] > 0)
				{
					if(!\CRestUtil::makeAuth($res))
					{
						$res = array('error' => 'authorization_error');
					}
				}

				$res['auth_type'] = static::AUTH_TYPE_TOKEN;
				$res['parameters_clear'] = static::$authQueryParams;

				return !array_key_exists('error', $res);
			}
		}
		elseif(
			array_key_exists('client_id', $query)
			&& array_key_exists('client_secret', $query)
		)
		{
			$res = array('error' => 'Invalid client');

			$client = Base::instance($query['client_id']);
			if($client)
			{
				$clientType = $client->getClientType();

				if($clientType == ClientTable::TYPE_PORTAL
					|| $clientType == ClientTable::TYPE_EXTERNAL
					|| $clientType == ClientTable::TYPE_SEO
					|| $clientType == ClientTable::TYPE_BITRIX
					|| (
						$clientType == ClientTable::TYPE_APPLICATION
						//&& $scope == \CRestUtil::GLOBAL_SCOPE
					)
				)
				{
					$res = $client->verifyClientCredentials(
						$query['client_id'],
						$query['client_secret'],
						$scope == \CRestUtil::GLOBAL_SCOPE ? '' : $scope
					);
				}
			}

			$res['auth_type'] = static::AUTH_TYPE_CLIENT;
			$res['parameters_clear'] = array('client_id', 'client_secret');

			return !array_key_exists('error', $res);
		}

		return null;
	}

}