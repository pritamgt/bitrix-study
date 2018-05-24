<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/classes/general/oauth_manager.php");

use \Bitrix\OAuth\ClientTable;
use \Bitrix\OAuth\CodeTable;
use \Bitrix\OAuth\TokenTable;
use \Bitrix\OAuth\RefreshTokenTable;

/**
 * Class COAuthBase
 *
 * @deprecated
 */
class COAuthBase extends COAuthManager
{
	protected $clientCache = array();

	protected function getClient($client_id)
	{
		if(!isset($this->clientCache[$client_id]))
		{
			$dbOAuthApp = ClientTable::getList(array('filter' => array("=CLIENT_ID" => $client_id)));
			$this->clientCache[$client_id] = $dbOAuthApp->fetch();
		}

		return $this->clientCache[$client_id];
	}

	protected function checkClientCredentials($client_id, $client_secret = null, $skipSecretCheck = false)
	{
		$oauthClient = $this->getClient($client_id);
		if($oauthClient)
		{
			$salt = substr($oauthClient["CLIENT_SECRET"], 0, 8);

			return ($skipSecretCheck || ($oauthClient["CLIENT_SECRET"] == $salt.md5($salt.$client_secret))) ? true : false;
		}
		return false;
	}

	protected function getClientId($client_id)
	{
		$oauthApp = $this->getClient($client_id);
		if($oauthApp)
		{
			return $oauthApp["ID"];
		}
		return 0;
	}

	protected function getApplicationId($clientId)
	{
		$dbOAuthApp = ClientTable::getByPrimary($clientId);
		$oauthApp = $dbOAuthApp->fetch();
		if($oauthApp)
		{
			return $oauthApp["CLIENT_ID"];
		}
		return '';
	}

	protected function getRedirectUri($client_id)
	{
		$oauthApp = $this->getClient($client_id);
		if($oauthApp)
		{
			if(isset($oauthApp["REDIRECT_URI"]))
			{
				return $oauthApp["REDIRECT_URI"];
			}
			return null;
		}
		return false;
	}

	protected function getAccessToken($oauth_token)
	{
		$dbOAuthToken = TokenTable::getList(array('filter' => array("=OAUTH_TOKEN" => $oauth_token)));
		if($oauthToken = $dbOAuthToken->Fetch())
		{
			if(intval($oauthToken["CLIENT_ID"]) > 0)
			{
				$oauthToken["CLIENT_ID"] = self::getApplicationId($oauthToken["CLIENT_ID"]);
			}

			return $oauthToken;
		}
		return null;
	}

	protected function setAccessToken($oauth_token, $client_id, $expires, $scope = null, $userId = 0, $addParameters = array())
	{
		$result = array(
			"OAUTH_TOKEN" => $oauth_token,
			"CLIENT_ID" => $this->getClientId($client_id),
			"EXPIRES" => $expires,
			"USER_ID" => $userId,
		);

		if($scope)
		{
			$result["SCOPE"] = $scope;
		}

		return TokenTable::add($result);
	}

	protected function getRefreshToken($refresh_token)
	{
		$dbOAuthRefreshToken = RefreshTokenTable::getList(array(
			'filter' =>array(
				"=REFRESH_TOKEN" => $refresh_token
			),
			'select' => array('*', 'APPLICATION_ID' => 'CLIENT.CLIENT_ID', 'SCOPE' => 'CLIENT.SCOPE')
		));
		$oauthRefreshToken = $dbOAuthRefreshToken->fetch();
		if($oauthRefreshToken)
		{
			return $oauthRefreshToken;
		}
		return null;
	}

	protected function setRefreshToken($refresh_token, $client_id, $expires, $oauthTokenId, $scope = null, $userId = 0)
	{
		$result = array(
			"REFRESH_TOKEN" => $refresh_token,
			"CLIENT_ID" => $this->getClientId($client_id),
			"EXPIRES" => $expires,
			"USER_ID" => $userId,
			"OAUTH_TOKEN_ID" => intval($oauthTokenId),
		);
		RefreshTokenTable::add($result);
	}

	protected function getSupportedGrantTypes()
	{
		return array(
			COAuthConstants::GRANT_TYPE_AUTH_CODE,
			COAuthConstants::GRANT_TYPE_REFRESH_TOKEN,
		);
	}

	protected function getAuthCode($code)
	{
		$dbOAuthCode = CodeTable::getList(array(
			'filter' => array(
				"=CODE" => $code
			),
			'select' => array('*', 'APPLICATION_ID' => 'CLIENT.CLIENT_ID', 'SCOPE' => 'CLIENT.SCOPE')
		));

		$oauthCode = $dbOAuthCode->fetch();
		if($oauthCode)
		{
			if($oauthCode["USED"] === CodeTable::USED)
			{
				return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_TOKEN);
			}
			CodeTable::update($oauthCode["ID"], array(
				"USED" => CodeTable::USED
			));

			return $oauthCode;
		}

		return null;
	}

	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = array(), $userId = 0)
	{
		$arResult = array(
			"CODE" => $code,
			"CLIENT_ID" => $this->getClientId($client_id),
			"REDIRECT_URI" => $redirect_uri,
			"EXPIRES" => $expires,
			"USER_ID" => $userId,
		);

		CodeTable::add($arResult);
	}

	protected function getSupportedScopes($client_id)
	{
		$oauthApp = $this->getClient($client_id);
		if($oauthApp)
		{
			return $oauthApp["SCOPE"];
		}
		return array();
	}

	protected function getAuthCodeInfo($code)
	{
		$dbOAuthCode = CodeTable::getList(array('filter' => array("=CODE" => $code)));
		$oauthCode = $dbOAuthCode->fetch();
		if($oauthCode)
		{
			return $oauthCode;
		}
		return null;
	}

	protected function getAuthRefreshTokenInfo($refreshToken)
	{
		$dbOAuthRefreshToken = \Bitrix\OAuth\RefreshTokenTable::getList(array(
			'filter' => array("=REFRESH_TOKEN" => $refreshToken)
		));
		$oauthRefreshToken = $dbOAuthRefreshToken->fetch();
		if($oauthRefreshToken)
		{
			return $oauthRefreshToken;
		}
		return null;
	}

}

class COAuthConstants
{
	/**
	 * The default duration in seconds of the access token lifetime.
	 */
	const ACCESS_TOKEN_LIFETIME = 3600;

	/**
	 * The default duration in seconds of the authorization code lifetime.
	 */
	const AUTH_CODE_LIFETIME = 100;

	/**
	 * The default duration in seconds of the refresh token lifetime.
	 */
	const REFRESH_TOKEN_LIFETIME = 2419200;

	/**
	 * Denotes "token" authorization response type.
	 */
	const AUTH_RESPONSE_TYPE_ACCESS_TOKEN = "token";

	const ACCESS_TOKEN_LENGTH = 40;
	/**
	 * Denotes "code" authorization response type.
	 */
	const AUTH_RESPONSE_TYPE_AUTH_CODE = "code";

	const AUTH_RESPONSE_TYPE_CODE_AND_TOKEN = "code-and-token";

	const AUTH_RESPONSE_TYPE_REGEXP = "/^(code|code-and-token)$/";

	const GRANT_TYPE_AUTH_CODE = "authorization_code";

	const GRANT_TYPE_USER_CREDENTIALS = "password";

	const GRANT_TYPE_ASSERTION = "assertion";

	const GRANT_TYPE_REFRESH_TOKEN = "refresh_token";

	const GRANT_TYPE_NONE = "none";

	const GRANT_TYPE_REGEXP = "/^(authorization_code|password|assertion|refresh_token|none)$/";

	const CLIENT_ID_REGEXP = "/^[a-z0-9-_.]{3,50}$/i";

	const TOKEN_PARAM_NAME = "oauth_token";

	const HTTP_FOUND = "302 Found";

	const HTTP_BAD_REQUEST = "400 Bad Request";

	const HTTP_UNAUTHORIZED = "401 Unauthorized";

	const HTTP_PAYMENT_REQUIRED = "402 Payment Required";

	const HTTP_FORBIDDEN = "403 Forbidden";

	const ERROR_INVALID_REQUEST = "invalid_request";

	const ERROR_INVALID_CLIENT = "invalid_client";

	const ERROR_UNAUTHORIZED_CLIENT = "unauthorized_client";

	const ERROR_REDIRECT_URI_MISMATCH = "redirect_uri_mismatch";

	const ERROR_USER_DENIED = "access_denied";

	const ERROR_UNSUPPORTED_RESPONSE_TYPE = "unsupported_response_type";

	const ERROR_INVALID_SCOPE = "invalid_scope";

	const ERROR_INVALID_GRANT = "invalid_grant";

	const ERROR_UNSUPPORTED_GRANT_TYPE = "unsupported_grant_type";

	const ERROR_INVALID_TOKEN = "invalid_token";

	const ERROR_EXPIRED_TOKEN = "expired_token";

	const ERROR_INSUFFICIENT_SCOPE = "insufficient_scope";
}