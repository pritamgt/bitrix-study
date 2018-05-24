<?php
namespace Bitrix\OAuth;

use Bitrix\Main\Config\Option;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\OAuth\Auth\AccessToken;
use Bitrix\OAuth\Auth\Code;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Security\Random;
use Bitrix\OAuth\Auth\RefreshToken;
use Bitrix\OAuth\Auth\Token;
use Bitrix\OAuth\Security\TokenExpiredException;


class Base
{
	/**
	 * The default duration in strtodate format of the access token lifetime.
	 */
	const ACCESS_TOKEN_LIFETIME = AccessToken::LIFETIME_TS;

	/**
	 * The default duration in strtodate format of the authorization code lifetime.
	 */
	const AUTH_CODE_LIFETIME = Code::LIFETIME_TS;

	/**
	 * The default duration in strtodate format of the refresh token lifetime.
	 */
	const REFRESH_TOKEN_LIFETIME = RefreshToken::LIFETIME_TS;

	protected $conf = array();
	protected $internalCheck = false;
	protected $templateMode = false;

	protected $clientId = null;
	protected $clientCache = array();

//region singleton

	/**
	 * Determines client type and returns handling object.
	 *
	 * @param string $clientId client_id
	 *
	 * @return Client\Application|Client\External|Client\Portal|Client\Site
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function instance($clientId)
	{
		$oauthClient = null;

		$dbRes = ClientTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => strval($clientId)
			),
			'select' => array('CLIENT_TYPE', 'CLIENT_ID'),
			'limit' => array(0, 1),
		));

		$client = $dbRes->fetch();

		if($client)
		{
			$oauthClient = static::getClientByType($client['CLIENT_TYPE']);

			if($oauthClient)
			{
				$oauthClient->setClient($client['CLIENT_ID']);
			}
		}

		return $oauthClient;
	}

	/**
	 * Determines client type and returns handling object.
	 *
	 * @param int $clientId b_oauth_client.ID
	 *
	 * @return Client\Application|Client\External|Client\Portal|Client\Site
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function instanceById($clientId)
	{
		$oauthClient = null;

		$dbRes = ClientTable::getList(array(
			'filter' => array(
				'=ID' => strval($clientId)
			),
			'select' => array('CLIENT_TYPE', 'CLIENT_ID'),
			'limit' => array(0, 1),
		));

		$client = $dbRes->fetch();

		if($client)
		{
			$oauthClient = static::getClientByType($client['CLIENT_TYPE']);

			if($oauthClient)
			{
				$oauthClient->setClient($client['CLIENT_ID']);
			}
		}

		return $oauthClient;
	}

	/**
	 * Determines client type and returns handling object.
	 *
	 * @param $accessToken
	 *
	 * @return Client\Application|Client\External|Client\Portal|Client\Site
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function instanceByToken($accessToken)
	{
		$oauthClient = null;

		$tokenGenerator = new AccessToken();
		$tokenGenerator->setTtl(static::ACCESS_TOKEN_LIFETIME);
		try
		{
			$tokenInfo = $tokenGenerator->getTokenData($accessToken);

			$dbRes = ClientTable::getById($tokenInfo['CLIENT_ID']);
			$clientData = $dbRes->fetch();

			$oauthClient = static::getClientByType($clientData['CLIENT_TYPE']);

			if($oauthClient)
			{
				$oauthClient->setClient($clientData['CLIENT_ID']);
			}
		}
		catch(SystemException $e)
		{
			$dbRes = TokenTable::getList(array(
				'filter' => array(
					'=OAUTH_TOKEN' => strval($accessToken)
				),
				'select' => array(
					'CLIENT_CLIENT_ID' => 'CLIENT.CLIENT_ID',
					'CLIENT_TYPE' => 'CLIENT.CLIENT_TYPE',
				),
				'limit' => array(0, 1),
			));

			$token = $dbRes->fetch();
			if($token)
			{
				$oauthClient = static::getClientByType($token['CLIENT_TYPE']);

				if($oauthClient)
				{
					$oauthClient->setClient($token['CLIENT_CLIENT_ID']);
				}
			}
		}

		return $oauthClient;
	}

	protected static function getClientByType($clientType)
	{
		$oauthClient = null;

		switch($clientType)
		{
			case ClientTable::TYPE_APPLICATION:
				$oauthClient = new Client\Application();
				break;
			case ClientTable::TYPE_EXTERNAL:
				$oauthClient = new Client\External();
				break;
			case ClientTable::TYPE_PORTAL:
				$oauthClient = new Client\Portal();
				break;
			case ClientTable::TYPE_SITE:
				$oauthClient = new Client\Site();
				break;
			case ClientTable::TYPE_BITRIX:
				$oauthClient = new Client\Bitrix();
				break;
			default:
				$event = new Event("oauth", "OnDetermineClientType", array($clientType));
				$event->send();

				if($event->getResults())
				{
					foreach($event->getResults() as $eventResult)
					{
						if($eventResult->getType() == EventResult::SUCCESS)
						{
							$handlerClass = $eventResult->getParameters();
							if(is_string($handlerClass) && class_exists($handlerClass))
							{
								$oauthClient = new $handlerClass();
							}
							break;
						}
					}
				}
				break;
		}

		return $oauthClient;
	}

//endregion

	/**
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		foreach($config as $name => $value)
		{
			$this->setVariable($name, $value);
		}
	}


	/**
	 * Returns the variable is stored.
	 *
	 * To avoid problems, pass the variable names are always in lower case.
	 *
	 * @param $name
	 * @param $default
	 * @return
	 *   The value of the variable.
	 */
	public function getVariable($name, $default = null)
	{
		return isset($this->conf[$name]) ? $this->conf[$name] : $default;
	}

	/**
	 * Sets a variable.
	 *
	 * To avoid problems, pass the variable names are always in lower case.
	 *
	 * @param $name
	 * @param $value
	 * @return $this;
	 */
	public function setVariable($name, $value)
	{
		$this->conf[$name] = $value;

		return $this;
	}

	public function setClient($clientId, $clientInfo = null)
	{
		$this->clientId = $clientId;

		if(is_array($clientInfo))
		{
			$this->clientCache[$this->clientId] = $clientInfo;
		}
	}

	public function needAuth()
	{
		return true;
	}

	public function allowJoin()
	{
		return false;
	}

	public function setPublicJoinToken($token = null)
	{
		global $USER;
		UserSecretTable::registerSecret($token, $USER->getId());
	}

	protected function checkPublicJoinToken($clientToken)
	{
		return array_key_exists($clientToken, UserSecretTable::getRegisteredSecret());
	}

	public function getClientJoinToken()
	{
		return null;
	}

	public function checkPublicJoin()
	{
		return $this->allowJoin();
	}

	public function check()
	{
		return true;
	}

	public function getClient($clientId = null)
	{
		if($clientId == null)
		{
			$clientId = $this->clientId;
		}

		if($clientId && !isset($this->clientCache[$clientId]))
		{
			$this->clientCache[$clientId] = $this->getClientData($clientId);
		}

		return $this->clientCache[$clientId];
	}

	public function getClientHost()
	{
		$clientData = $this->getClient();
		return parse_url($clientData["REDIRECT_URI"], PHP_URL_HOST);
	}

	public function getClientScope()
	{
		$clientData = $this->getClient();
		return $clientData['SCOPE'];
	}

	public function getClientData($clientId)
	{
		$dbOAuthApp = ClientTable::getList(array(
			'filter' => array(
				"=CLIENT_ID" => $clientId
			)
		));

		return $dbOAuthApp->fetch();
	}

	public function isFeatureEnabled($feature)
	{
		$result = ClientFeatureTable::isEnabledGlobal($feature, $this->getClientType());

		$dbRes = ClientFeatureTable::getList(array(
			"filter" => array(
				"=FEATURE" => $feature,
				"=CLIENT_ID" => $this->getClientId(),
				"=ACTIVE" => $result
					? ClientFeatureTable::DISABLED
					: ClientFeatureTable::ENABLED,
			)
		));
		if($dbRes->fetch())
		{
			$result = !$result;
		}

		return $result;

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

	public function getClientId($client_id = null)
	{
		if($client_id === null)
		{
			$client_id = $this->clientId;
		}

		$oauthApp = $this->getClient($client_id);
		if($oauthApp)
		{
			return $oauthApp["ID"];
		}
		return 0;
	}

	public function getClientType($client_id = null)
	{
		if($client_id === null)
		{
			$client_id = $this->clientId;
		}

		$oauthApp = $this->getClient($client_id);
		if($oauthApp)
		{
			return $oauthApp["CLIENT_TYPE"];
		}
		return null;
	}

	public function getRedirectUri($clientId = null)
	{
		if($clientId === null)
		{
			$clientId = $this->clientId;
		}

		$oauthApp = $this->getClient($clientId);
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

	private function getTokenKey(Token $tokenGenerator)
	{
		$clientData = $this->getClient();

		return $tokenGenerator->getTokenType().$clientData['CLIENT_SECRET'];
	}

	/**
	 * @param $token
	 * @param array $scope
	 * @return array
	 */
	public function verifyAccessTokenInternal($token, $scope = array())
	{
		$this->internalCheck = true;
		$token = $this->getAccessToken($token);

		if(isset($token["EXPIRES"]) && time() > $token["EXPIRES"])
		{
			return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_EXPIRED_TOKEN, 'The access token provided has expired.');
		}

		if((!isset($token["CLIENT_ID"]) || !$token["CLIENT_ID"] || !$this->getClientId($token["CLIENT_ID"])))
		{
			return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.');
		}

		if($scope && (!isset($token["SCOPE"]) || !$token["SCOPE"] || !$this->checkScope($scope, $token["SCOPE"])))
		{
			return $this->sendError(\COAuthConstants::HTTP_FORBIDDEN, \COAuthConstants::ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.');
		}

		$validateResult = $this->validateToken(
			array(
				'access_token' => $token["OAUTH_TOKEN"],
				'scope' => $token["SCOPE"],
				'user_id' => $token["USER_ID"],
				'expires' => $token["EXPIRES"],
				'client_id' => $token["CLIENT_ID"],
				'parameters' => $token["PARAMETERS"],
			),
			array(
				"CLIENT_ID" => $token["CLIENT_ID"],
			)
		);

		if(is_array($validateResult))
		{
			if(isset($validateResult["ERROR_CODE"]))
			{
				return $this->sendError(
					$validateResult["ERROR_STATUS"],
					$validateResult["ERROR_CODE"],
					$validateResult["ERROR_MESSAGE"]
				);
			}

			$token = $validateResult;
		}

		if(is_array($token) && isset($token["client_id"]))
		{
			$result = array(
				"client_id" => $token["client_id"],
				"expired" => ($token["expires"] - time()),
				"expires" => $token["expires"],
				"scope" => $token["scope"]
			);

			if(isset($token['user_id']))
			{
				$result['user_id'] = $token['user_id'];
			}

			if(isset($token["parameters"]) && is_array($token["parameters"]))
			{
				$result["parameters"] = $token["parameters"];
			}

			return $result;
		}

		return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.');
	}

	/**
	 * @param $clientId
	 * @param $clientSecret
	 * @param array $scope
	 * @return array
	 */
	public function verifyClientCredentials($clientId, $clientSecret, $scope = array())
	{
		$this->internalCheck = true;

		if(!$this->checkClientCredentials($clientId, $clientSecret))
		{
			return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_INVALID_CLIENT, 'Invalid client credentials.');
		}

		$validateResult = $this->validateClient(array(
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
			'scope' => $scope
		));

		if($validateResult !== true)
		{
			return $this->sendError($validateResult["ERROR_STATUS"], $validateResult["ERROR_CODE"], $validateResult['ERROR_MESSAGE']);
		}

		$oauthClient = $this->getClient($clientId);

		if($scope
			&&
			(
				!isset($oauthClient["SCOPE"])
				|| !$oauthClient["SCOPE"]
				|| !$this->checkScope($scope, $oauthClient["SCOPE"])
			)
		)
		{
			return $this->sendError(\COAuthConstants::HTTP_FORBIDDEN, \COAuthConstants::ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by client credentials.');
		}

		$result = array(
			"user_id" => 0,
			"client_id" => $clientId,
			"scope" => implode(',', $oauthClient['SCOPE']),
		);

		return $result;
	}


	protected function getAccessToken($oauth_token)
	{
		try
		{
			$clientData = $this->getClient();
			$tokenGenerator = new AccessToken();
			$tokenGenerator->setTtl(static::ACCESS_TOKEN_LIFETIME);

			try
			{
				$oauthToken = $tokenGenerator->checkToken($oauth_token, $this->getTokenKey($tokenGenerator));

				return array(
					'EXPIRES' => $tokenGenerator->getTimestamp(),
					'CLIENT_ID' => $this->clientId,
					'SCOPE' => $clientData['SCOPE'],
					'USER_ID' => $oauthToken['USER_ID'],
					'PARAMETERS' => $oauthToken,
				);
			}
			catch(TokenExpiredException $e)
			{
				return array(
					'EXPIRES' => $tokenGenerator->getTimestamp(),
				);
			}
		}
		catch(SystemException $e)
		{
			$dbOAuthToken = TokenTable::getList(array(
				'filter' => array(
					"=OAUTH_TOKEN" => $oauth_token
				),
				'select' => array(
					'*',
					'CLIENT_CLIENT_ID' => 'CLIENT.CLIENT_ID',
					'CLIENT_TYPE' => 'CLIENT.CLIENT_TYPE',
				)
			));
			if($oauthToken = $dbOAuthToken->Fetch())
			{
				$oauthToken['CLIENT_ID'] = $oauthToken['CLIENT_CLIENT_ID'];
				unset($oauthToken['CLIENT_CLIENT_ID']);

				// temporary hack
				if(!is_array($oauthToken["SCOPE"]) && substr($oauthToken["SCOPE"], -1) === "}")
				{
					$oauthToken["SCOPE"] = implode(",", unserialize($oauthToken["SCOPE"]));
				}

				return $oauthToken;
			}
		}
		return null;
	}


	/**
	 * @param $required_scope
	 * @param $available_scope
	 * @return bool
	 */
	private function checkScope($required_scope, $available_scope)
	{
		// The required scope should match or be a subset of the available scope
		if(!is_array($required_scope))
		{
			$required_scope = explode(",", $required_scope);
		}

		if(!is_array($available_scope))
		{
			$available_scope = explode(",", $available_scope);
		}

		return (count(array_diff($required_scope, $available_scope)) == 0);
	}


	/**
	 * Grant or deny a requested access token.
	 */
	public function grantAccessToken()
	{
		$filter = $this->getFilter();

		$result = filter_input_array(INPUT_GET, $filter);

		$client = $this->getClientParameters($result);

		$tokenParameters = array();
		$userId = 0;
		switch($result["grant_type"])
		{
			case \COAuthConstants::GRANT_TYPE_AUTH_CODE:

				$codeInfo = $this->getAuthCodeInfo($result["code"]);

				$result["scope"] = implode(',', $this->getSupportedScopes());
				$tokenParameters = $codeInfo;

				break;

			case \COAuthConstants::GRANT_TYPE_REFRESH_TOKEN:
				$codeInfo = $this->getAuthRefreshTokenInfo($result["refresh_token"]);

				$result["scope"] = implode(',', $this->getSupportedScopes());
				$tokenParameters = $codeInfo;

				break;

			default:
				$codeInfo = array();
		}

		if(is_array($codeInfo) && isset($codeInfo["USER_ID"]))
		{
			$userId = intval($codeInfo["USER_ID"]);
		}

		if(!$result["scope"])
		{
			$result["scope"] = null;
		}

		$token = $this->createAccessToken($client, $result["scope"], $userId, $tokenParameters);

		$this->sendJsonHeaders();
		echo Json::encode($token);
		\CMain::FinalActions();
		die();
	}

	/** Grant or deny a requested access token. For use inside the API.
	 * @param string $client_id
	 * @param string $grant_type
	 * @param string $redirectUri
	 * @param string $code
	 * @param string $scope
	 * @param null $clientSecret
	 * @param string $refresh_token
	 * @param array $addParameters
	 * @param int $userId
	 * @return array|bool
	 */
	public function grantAccessTokenInternal($client_id = '', $grant_type = 'authorization_code', $redirectUri = '', $code = '', $scope = '', $clientSecret = null, $refresh_token = '', $addParameters = array(), $userId = 0)
	{
		$this->internalCheck = true;
		$userId = intval($userId);
		$result = array();

		$filter = $this->getFilter();

		$filter["client_id"] = array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => \COAuthConstants::CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR);
		$result["redirect_uri"] = $redirectUri;
		$result["grant_type"] = $grant_type;
		$result["scope"] = is_array($scope) ? implode(",", $scope) : $scope;
		$result["code"] = $code;
		$result["refresh_token"] = $refresh_token;
		$result["user_id"] = $userId;
		$result["client_id"] = $client_id;

		$result = filter_var_array($result, $filter);
		$result["client_secret"] = $clientSecret;
		$client = $this->getClientParameters($result);

		if(!$result["scope"])
		{
			$result["scope"] = null;
		}

		if(!is_array($client))
		{
			return $this->createAccessToken($client, $result["scope"], $userId, $addParameters);
		}
		else
		{
			return false;
		}
	}

	private function getFilter()
	{
		return array(
			"grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => \COAuthConstants::GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"client_id" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => \COAuthConstants::CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"response_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => \COAuthConstants::AUTH_RESPONSE_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"state" => array("flags" => FILTER_REQUIRE_SCALAR),
			"scope" => array("flags" => FILTER_REQUIRE_SCALAR),
			"code" => array("flags" => FILTER_REQUIRE_SCALAR),
			"redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
			"refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
			"user_id" => array("filter" => FILTER_VALIDATE_INT),
		);
	}

	private function getClientParameters($params)
	{
		// Grant Type must be specified.
		if(!$params["grant_type"])
		{
			return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
		}

		// Make sure we've implemented the requested grant type
		if(!in_array($params["grant_type"], $this->getSupportedGrantTypes()))
		{
			return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_UNSUPPORTED_GRANT_TYPE);
		}

		// Authorize the client
		if($this->internalCheck)
		{
			$client = array($params["client_id"], $params["client_secret"]);
		}
		else
		{
			$client = $this->getClientCredentials();
		}

		if(!is_array($client) || strlen($client[0]) <= 0)
		{
			return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);
		}

		if($this->checkClientCredentials($client[0], $client[1], $this->internalCheck) === false)
		{
			return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_CLIENT);
		}

		$redirect_uri = $this->getRedirectUri($client[0]);
		if($redirect_uri === null)
		{
			$this->templateMode = true;
		}

		if(!$this->checkRestrictedGrantType($client[0], $params["grant_type"]))
		{
			return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);
		}

		// Do the granting
		switch($params["grant_type"])
		{
			case \COAuthConstants::GRANT_TYPE_AUTH_CODE:
				if(!$params["code"])
				{
					return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_REQUEST);
				}

				break;
			case \COAuthConstants::GRANT_TYPE_REFRESH_TOKEN:
				if(!$params["refresh_token"])
				{
					return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');
				}

				break;

			default:
				return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_GRANT);
				break;
		}

		return $client[0];
	}

	/**
	 * @param $client_id
	 * @param $grant_type
	 * @return bool
	 */
	protected function checkRestrictedGrantType($client_id, $grant_type)
	{
		return true;
	}

	/**
	 * @return array
	 */
	protected function getClientCredentials()
	{
		if(isset($_GET["client_id"]))
		{
			if(isset($_GET["client_secret"]))
			{
				return array($_GET["client_id"], $_GET["client_secret"]);
			}

			return array($_GET["client_id"], null);
		}

		if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["client_id"]))
		{
			if(isset($_POST["client_secret"]))
			{
				return array($_POST["client_id"], $_POST["client_secret"]);
			}

			return array($_POST["client_id"], null);
		}

		// No credentials were specified
		return $this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_CLIENT);
	}


	/**
	 * Pull data authorization request from the HTTP request.
	 * @param int $userId User ID to get params for.
	 *
	 * @return array
	 */
	public function getAuthorizeParams($userId = 0)
	{
		$filter = $this->getFilter();

		$arResult = filter_var_array($_GET, $filter);
		$userId = intval($userId);

		if($userId > 0)
		{
			$arResult['user_id'] = $userId;
		}

		return $this->validateParameters($arResult);
	}

	/** Pull data authorization request from the parameters passed.
	 * @param string $clientId
	 * @param string $responseType
	 * @param string $redirectUri
	 * @param string $state
	 * @param string $scope
	 * @param int $userId
	 *
	 * @return array|mixed
	 */
	public function getAuthorizeParamsInternal($clientId = '', $responseType = 'code', $redirectUri = '', $state = '', $scope = '', $addParameters = array(), $userId = 0)
	{
		$this->internalCheck = true;

		$filter = $this->getFilter();

		$result["client_id"] = $clientId;
		$result["redirect_uri"] = $redirectUri;
		$result["response_type"] = $responseType;
		$result["scope"] = $scope;
		$result["state"] = $state;
		$result["user_id"] = $userId;

		$result = filter_var_array($result, $filter);

		if(is_array($addParameters))
		{
			$result["parameters"] = $addParameters;
		}

		return $this->validateParametersInternal($result);
	}


	protected function validateClient(array $result, array $client = array())
	{
		return true;
	}

	protected function validateToken(array $tokenInfo, array $client = array())
	{
		return $tokenInfo;
	}

	protected function validateParameters(array $result)
	{
		$client = false;
		if(isset($result["client_id"]))
		{
			$client = $this->getClient($result["client_id"]);
		}

		// Make sure a valid client id was supplied
		if(!$client)
		{
			if($result["redirect_uri"])
			{
				$redirect_url = $result["redirect_uri"]
					.(strpos($result["redirect_uri"], '?') === false ? '?' : '&')
					."error=".\COAuthConstants::ERROR_INVALID_CLIENT;

				LocalRedirect($redirect_url, true);
			}

			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_CLIENT); // We don't have a good URI to use
		}
		// redirect_uri is not required if already established via other channels
		// check an existing redirect URI against the one supplied
		$redirect_uri = $this->getRedirectUri($result["client_id"]);

		// getRedirectUri() should return false if the given client ID is invalid
		// this probably saves us from making a separate db call, and simplifies the method set
		if($redirect_uri === false)
		{
			if($result["redirect_uri"])
			{
				$redirect_url = $result["redirect_uri"]."?error=".\COAuthConstants::ERROR_INVALID_CLIENT;
				LocalRedirect($redirect_url, true);
			}

			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_CLIENT);
		}
		elseif($redirect_uri === null || $redirect_uri === '')
		{
			$this->templateMode = true;
		}
		else
		{
			$result["redirect_uri"] = $redirect_uri;
		}

		$validateResult = $this->validateClient($result, $client);

		if(is_array($validateResult))
		{
			if(isset($validateResult["ERROR_CODE"]))
			{
				if($this->templateMode)
				{
					return array($validateResult["ERROR_CODE"].". ".$validateResult["ERROR_MESSAGE"]);
				}

				$this->errorDoRedirectUriCallback(
					$result["redirect_uri"], $validateResult["ERROR_CODE"], $validateResult["ERROR_MESSAGE"], null, $result["state"]
				);
			}
			else
			{
				return $validateResult;
			}
		}

		// type and client_id are required
		if(!$result["response_type"])
		{
			if($this->templateMode)
			{
				return array("ERROR_MESSAGE" => \COAuthConstants::ERROR_INVALID_REQUEST.". Invalid response type.");
			}

			$this->errorDoRedirectUriCallback($result["redirect_uri"], \COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid response type.', null, $result["state"]);
		}

		// Check requested auth response type against the list of supported types
		if(array_search($result["response_type"], $this->getSupportedAuthResponseTypes()) === false)
		{
			if($this->templateMode)
			{
				return array("ERROR_MESSAGE" => \COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE);
			}

			$this->errorDoRedirectUriCallback($result["redirect_uri"], \COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE, null, null, $result["state"]);
		}

		// Restrict clients to certain authorization response types
		if($this->checkRestrictedAuthResponseType($result["client_id"], $result["response_type"]) === false)
		{
			if($this->templateMode)
			{
				return array("ERROR_MESSAGE" => \COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE);
			}

			$this->errorDoRedirectUriCallback($result["redirect_uri"], \COAuthConstants::ERROR_UNAUTHORIZED_CLIENT, null, null, $result["state"]);
		}

		// Validate that the requested scope is supported
		if($result["scope"] && !$this->checkScope($result["scope"], $this->getSupportedScopes()))
		{
			if($this->templateMode)
			{
				return array("ERROR_MESSAGE" => \COAuthConstants::ERROR_INVALID_SCOPE);
			}

			$this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_SCOPE);
		}

		return $result;
	}


	/**
	 * @return array
	 */
	protected function getSupportedAuthResponseTypes()
	{
		return array(
			\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE,
			\COAuthConstants::AUTH_RESPONSE_TYPE_ACCESS_TOKEN,
		);
	}


	/**
	 * @param $client_id
	 * @param $response_type
	 * @return bool
	 */
	protected function checkRestrictedAuthResponseType($client_id, $response_type)
	{
		return true;
	}


	private function validateParametersInternal($result)
	{
		// Make sure a valid client id was supplied
		if(!isset($result["client_id"]) || !$this->getClientId($result["client_id"]))
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_CLIENT); // We don't have a good URI to use
		}

		// redirect_uri is not required if already established via other channels
		// check an existing redirect URI against the one supplied
		$redirect_uri = $this->getRedirectUri($result["client_id"]);

		// getRedirectUri() should return false if the given client ID is invalid
		// this probably saves us from making a separate db call, and simplifies the method set
		if($redirect_uri === false)
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_CLIENT);
		}
		elseif($redirect_uri === null || $redirect_uri === '')
		{
			$this->templateMode = true;
		}
		else
		{
			$result["redirect_uri"] = $redirect_uri;
		}

		// type and client_id are required
		if(!$result["response_type"])
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid response type.');
		}

		$validateResult = $this->validateClient($result);

		if(is_array($validateResult))
		{
			if(isset($validateResult["ERROR_CODE"]))
			{
				return $this->sendError($validateResult["ERROR_STATUS"], $validateResult["ERROR_CODE"]);
			}
			else
			{
				return $validateResult;
			}
		}

		// Check requested auth response type against the list of supported types
		if(array_search($result["response_type"], $this->getSupportedAuthResponseTypes()) === false)
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE, 'Invalid response type.');
		}

		// Restrict clients to certain authorization response types
		if($this->checkRestrictedAuthResponseType($result["client_id"], $result["response_type"]) === false)
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);
		}

		// Validate that the requested scope is supported
		if($result["scope"] && !$this->checkScope($result["scope"], $this->getSupportedScopes()))
		{
			return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_SCOPE);
		}

		return $this->sendAuthorizationParams(true, $result);
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

		if(is_array($addParameters) && !empty($addParameters))
		{
			$result["PARAMETERS"] = $addParameters;
		}

		$addResult = TokenTable::add($result);

		return $addResult->getId();
	}


	protected function getAuthorizationParams($is_authorized, $params = array())
	{
		$result = array(
			"query" => array()
		);

		if($is_authorized === false)
		{
			$result["query"]["error"] = \COAuthConstants::ERROR_USER_DENIED;
		}
		else
		{
			if(
				$params['response_type'] == \COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE
				|| $params['response_type'] == \COAuthConstants::AUTH_RESPONSE_TYPE_CODE_AND_TOKEN
			)
			{
				$result["query"]["code"] = $this->createAuthCode(
					$params['client_id'],
					$params['redirect_uri'],
					isset($params['scope']) ? $params['scope'] : null,
					$params['parameters'],
					intval($params['user_id']),
					$this->templateMode/* && !$this->internalCheck*/
				);
			}
		}

		if(isset($params["state"]))
		{
			$result["query"]["state"] = $params["state"];
		}

		$result["query"]["domain"] = Context::getCurrent()->getRequest()->getHttpHost();

		return $result;
	}

	/**
	 * @param $is_authorized
	 * @param array $params
	 * @return mixed
	 */
	public function sendAuthorizationParams($is_authorized, $params = array())
	{
		$result = $this->getAuthorizationParams($is_authorized, $params);

		if($this->internalCheck || $this->templateMode)
		{
			return $result["query"];
		}

		$this->doRedirectUriCallback($params['redirect_uri'], $result);

		return null;
	}

	/**
	 * @deprecated
	 */
	private function getStoredAuthCacheId($code)
	{
		return 'oauth_token_'.$code;
	}

	/**
	 * @deprecated
	 */
	protected function storeAuthorizationParams($params, $result)
	{
		if(Option::get('oauth', 'authorize_store', 'N') === 'Y' && !empty($result[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE]))
		{
			$cache = Application::getInstance()->getCache();
			if(!$cache->initCache(
				\COAuthConstants::AUTH_CODE_LIFETIME,
				$this->getStoredAuthCacheId($result[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE]),
				'/oauth/'
			))
			{
				$tokenInfo = $this->grantAccessTokenInternal(
					$params["client_id"],
					\COAuthConstants::GRANT_TYPE_AUTH_CODE,
					'',
					$result[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE],
					$params['scope'],
					null,
					'',
					array(),
					$params['user_id']
				);

				if($tokenInfo)
				{
					$clientData = $this->getClient();

					$clientSecret = $clientData['CLIENT_SECRET'];
					$salt = substr($clientSecret, 0, 8);
					$additionalSalt = Random::getString(8, true);

					$cacheData = array(
						'client_id' => $params['client_id'],
						'client_check' => $additionalSalt.$salt.md5($additionalSalt.$clientData['CLIENT_SECRET']),
						'expires' => time() + \COAuthConstants::AUTH_CODE_LIFETIME,
						'token' => $tokenInfo,
					);

					foreach(GetModuleEvents('oauth', 'OnOAuthStoreToken', true) as $eventHandler)
					{
						ExecuteModuleEventEx($eventHandler, array($result[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE], $cacheData));
					}

					$cache->startDataCache();
					$cache->endDataCache($cacheData);
				}
			}
		}
	}

	/**
	 * @deprecated
	 */
	protected function restoreAuthorizationParams($code)
	{
		if(Option::get('oauth', 'authorize_store', 'N') === 'Y')
		{
			$cache = Application::getInstance()->getCache();
			if($cache->initCache(
				\COAuthConstants::AUTH_CODE_LIFETIME,
				$this->getStoredAuthCacheId($code),
				'/oauth/'
			))
			{
				$cacheData = $cache->getVars();

				if($cacheData)
				{
					return $cacheData;
				}
			}

			return false;
		}

		return null;
	}

	/**
	 * @param $redirect_uri
	 * @param $params
	 */
	private function doRedirectUriCallback($redirect_uri, $params)
	{
		$redirect_uri = $this->filterHeaderParams($redirect_uri);
		if(isset($params["query"]) && is_array($params["query"]))
		{
			foreach($params["query"] as $key => $value)
			{
				$delimiter = (strpos($redirect_uri, '?') !== false) ? '&' : '?';
				$redirect_uri .= $delimiter.$key.'='.urlencode($value);
			}
		}

		if(isset($params["fragment"]) && is_array($params["fragment"]))
		{
			foreach($params["fragment"] as $value)
			{
				$redirect_uri .= "#".$value;
			}
		}

		LocalRedirect($redirect_uri, true);
		exit;
	}

	// http response splitting defence
	private function filterHeaderParams($parameter)
	{
		return str_replace(array("\r", "\n"), "", $parameter);
	}

	protected function getAuthCodeParameters($client_id, $userId = 0, $addParameters = array())
	{
		$client = $this->getClient($client_id);

		$tokenParameters = array(
			'CLIENT_ID' => $client['ID'],
			'USER_ID' => $userId
		);

		$tokenParametersList = Code::getParameterList();

		foreach($addParameters as $key => $value)
		{
			if(in_array($key, $tokenParametersList))
			{
				$tokenParameters[$key] = $value;
			}
		}

		return $tokenParameters;
	}

	protected function getAccessTokenParameters($client_id, $userId = 0, $addParameters = array())
	{
		$client = $this->getClient($client_id);

		$tokenParameters = array(
			'CLIENT_ID' => $client['ID'],
			'USER_ID' => $userId
		);

		$tokenParametersList = AccessToken::getParameterList();

		foreach($addParameters as $key => $value)
		{
			if(in_array($key, $tokenParametersList))
			{
				$tokenParameters[$key] = $value;
			}
		}

		return $tokenParameters;
	}

	protected function getRefreshTokenParameters($client_id, $userId = 0, $addParameters = array())
	{
		$client = $this->getClient($client_id);

		$tokenParameters = array(
			'CLIENT_ID' => $client['ID'],
			'USER_ID' => $userId
		);

		$tokenParametersList = RefreshToken::getParameterList();

		foreach($addParameters as $key => $value)
		{
			if(in_array($key, $tokenParametersList))
			{
				$tokenParameters[$key] = $value;
			}
		}

		return $tokenParameters;
	}

	/**
	 * @param $client_id
	 * @param null $scope
	 * @param int $userId
	 * @param array $addParameters
	 * @return array
	 */
	protected function createAccessToken($client_id, $scope = null, $userId = 0, $addParameters = array())
	{
		$request = Context::getCurrent()->getRequest();

		$client = $this->getClient($client_id);

		$accessTokenGenerator = new AccessToken();
		$accessTokenGenerator->setTtl(static::ACCESS_TOKEN_LIFETIME);
		$accessToken = $accessTokenGenerator->getToken(
			$this->getAccessTokenParameters($client_id, $userId, $addParameters),
			$this->getTokenKey($accessTokenGenerator)
		);

		$token = array(
			"access_token" => $accessToken,//$this->genAccessToken(),
			"expires" => $accessTokenGenerator->getTimestamp(),
			"expires_in" => $accessTokenGenerator->getTimestamp()-time(),
			"scope" => $scope,
			"user_id" => $userId,
			"domain" => $request->getHttpHost(),
			"server_endpoint" => \CHTTP::URN2URI('/rest/'),
			'parameters' => $addParameters,
		);

		$validateResult = $this->validateToken($token, $client);

		if(is_array($validateResult))
		{
			if(isset($validateResult["ERROR_CODE"]))
			{
				return $this->sendError(
					$validateResult["ERROR_STATUS"],
					$validateResult["ERROR_CODE"],
					$validateResult["ERROR_MESSAGE"]
				);
			}

			$token = $validateResult;
		}

		unset($token['parameters']);

		if(in_array(\COAuthConstants::GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes()))
		{
			$refreshTokenGenerator = new RefreshToken();
			$refreshTokenGenerator->setTtl(static::REFRESH_TOKEN_LIFETIME);
			$token['refresh_token'] = $refreshTokenGenerator->getToken(
				$this->getRefreshTokenParameters($client_id, $userId, $addParameters),
				$this->getTokenKey($refreshTokenGenerator)
			);
		}

		return $token;
	}

	/**
	 * @param $client_id
	 * @param $redirect_uri
	 * @param null $scope
	 * @param int $userId
	 * @param bool $templateMode
	 *
	 * @return string
	 */
	private function createAuthCode($client_id, $redirect_uri, $scope = null, $addParameters = array(), $userId = 0, $templateMode = false)
	{
		$tokenGenerator = new Code();
		$tokenGenerator->setTtl(static::AUTH_CODE_LIFETIME);
		$code = $tokenGenerator->getToken(
			$this->getAuthCodeParameters($client_id, $userId, $addParameters),
			$this->getTokenKey($tokenGenerator)
		);

		if($templateMode)
		{
			$code = $this->genAuthCode($templateMode);
			$this->setAuthCode($code, $client_id, '', $tokenGenerator->getTimestamp(), array(), $addParameters, $userId);
		}

		return $code;
	}

	/**
	 * @param bool $templateMode
	 * @return string
	 */
	protected function genAuthCode($templateMode = false)
	{
		if($templateMode)
		{
			return randString(6, "0123456789");
		}
		else
		{
			return Random::getString(32);
		}
	}

	/**
	 * Send out HTTP headers for JSON.
	 */
	private function sendJsonHeaders()
	{
		header("Content-Type: application/json");
		header("Cache-Control: no-store");
	}

	/**
	 * @param $redirect_uri
	 * @param $error
	 * @param null $error_description
	 * @param null $error_uri
	 * @param null $state
	 */
	private function errorDoRedirectUriCallback($redirect_uri, $error, $error_description = null, $error_uri = null, $state = null)
	{
		$result["query"]["error"] = $error;

		if($state)
		{
			$result["query"]["state"] = $state;
		}

		if($this->getVariable('display_error') && $error_description)
		{
			$result["query"]["error_description"] = $error_description;
		}

		if($this->getVariable('display_error') && $error_uri)
		{
			$result["query"]["error_uri"] = $error_uri;
		}

		$this->doRedirectUriCallback($redirect_uri, $result);
	}

	/**
	 * @param $http_status_code
	 * @param $error
	 * @param null $error_description
	 * @param null $error_uri
	 * @return array
	 */
	protected function sendError($http_status_code, $error, $error_description = null, $error_uri = null)
	{
		if(!$this->internalCheck)
		{
			self::sendJsonError($http_status_code, $error, $error_description, $error_uri);
		}

		return array(
			"error" => $error,
			"error_status" => $http_status_code,
			"error_description" => $error_description
		);
	}

	/**
	 * @param $http_status_code
	 * @param $error
	 * @param $error_description
	 * @param $error_uri
	 */
	private function sendJsonError($http_status_code, $error, $error_description, $error_uri)
	{
		$result['error'] = $error;

		if($this->getVariable('display_error') && $error_description)
		{
			$result["error_description"] = $error_description;
		}

		if($this->getVariable('display_error') && $error_uri)
		{
			$result["error_uri"] = $error_uri;
		}

		header("HTTP/1.1 ".$http_status_code);
		$this->sendJsonHeaders();

		echo Json::encode($result);

		exit;
	}

	protected function getRefreshToken($refresh_token)
	{
		$dbOAuthRefreshToken = RefreshTokenTable::getList(array(
			'filter' =>array(
				"=REFRESH_TOKEN" => $refresh_token,
				"=CLIENT_ID" => $this->getClientId(),
			),
			'select' => array(
				'*',
				'APPLICATION_ID' => 'CLIENT.CLIENT_ID',
				'SCOPE' => 'TOKEN.SCOPE',
				'PARAMETERS' => 'TOKEN.PARAMETERS'
			)
		));
		$oauthRefreshToken = $dbOAuthRefreshToken->fetch();
		if($oauthRefreshToken)
		{
			if($oauthRefreshToken['EXPIRES'] < time())
			{
				return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_EXPIRED_TOKEN, 'The authorization token provided has expired.');
			}

			return $oauthRefreshToken;
		}

		$this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_GRANT);

		return null;
	}

	/**
	 * @deprecated
	 */
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

	/**
	 * @deprecated
	 */
	protected function unsetRefreshToken($refresh_token)
	{
		$dbRes = RefreshTokenTable::getList(array(
			'filter' => array(
				'=REFRESH_TOKEN' => $refresh_token,
			),
			'select' => array(
				'ID', 'OAUTH_TOKEN_ID',
			),
		));
		$tokenInfo = $dbRes->fetch();
		if($tokenInfo)
		{
			RefreshTokenTable::delete($tokenInfo["ID"]);
			TokenTable::delete($tokenInfo["OAUTH_TOKEN_ID"]);
		}
	}


	protected function getSupportedGrantTypes()
	{
		return array(
			\COAuthConstants::GRANT_TYPE_AUTH_CODE,
			\COAuthConstants::GRANT_TYPE_REFRESH_TOKEN,
		);
	}

	protected function getAuthCode($code)
	{
		$dbOAuthCode = CodeTable::getList(array(
			'filter' => array(
				"=CODE" => $code,
				"=CLIENT_ID" => $this->getClientId()
			)
		));
		$oauthCode = $dbOAuthCode->fetch();
		if($oauthCode)
		{
			if($oauthCode["USED"] === CodeTable::USED)
			{
				return $this->sendError(\COAuthConstants::HTTP_FOUND, \COAuthConstants::ERROR_INVALID_TOKEN);
			}

			if($oauthCode['EXPIRES'] < time())
			{
				return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_EXPIRED_TOKEN, 'The authorization token provided has expired.');
			}

			CodeTable::update($oauthCode["ID"], array(
				"USED" => CodeTable::USED
			));

			return $oauthCode;
		}

		$this->sendError(\COAuthConstants::HTTP_BAD_REQUEST, \COAuthConstants::ERROR_INVALID_GRANT);

		return null;
	}

	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = array(), $addParameters = array(), $userId = 0)
	{
		$result = array(
			"CODE" => $code,
			"CLIENT_ID" => $this->getClientId($client_id),
			"EXPIRES" => $expires,
			"USER_ID" => $userId,
			"PARAMETERS" => $addParameters,
		);

		$addResult = CodeTable::add($result);

		return $addResult->getId();
	}

	protected function getSupportedScopes()
	{
		$client = $this->getClient();
		if($client)
		{
			return $client["SCOPE"];
		}

		return array();
	}

	protected function getAuthCodeInfo($code)
	{
		try
		{
			try
			{
				$tokenGenerator = new Code();
				$tokenGenerator->setTtl(static::AUTH_CODE_LIFETIME);
				$codeData = $tokenGenerator->checkToken($code, $this->getTokenKey($tokenGenerator));
			}
			catch(TokenExpiredException $e)
			{
				return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_EXPIRED_TOKEN, 'The authorization token provided has expired.');
			}

			return $codeData;
		}
		catch(SystemException $e)
		{
			$oauthCode = $this->getAuthCode($code);
			if($oauthCode)
			{
				// compatibility hack for templateMode
				if(is_array($oauthCode['PARAMETERS']))
				{
					foreach($oauthCode['PARAMETERS'] as $param => $value)
					{
						if(!array_key_exists($param, $oauthCode))
						{
							$oauthCode[$param] = $value;
						}
					}
				}

				return $oauthCode;
			}

			return null;
		}
	}

	protected function getAuthRefreshTokenInfo($refreshToken)
	{
		try
		{
			$tokenGenerator = new RefreshToken();
			$tokenGenerator->setTtl(static::REFRESH_TOKEN_LIFETIME);
			try
			{
				$tokenData = $tokenGenerator->checkToken($refreshToken, $this->getTokenKey($tokenGenerator));

				return $tokenData;
			}
			catch(TokenExpiredException $e)
			{
				return $this->sendError(\COAuthConstants::HTTP_UNAUTHORIZED, \COAuthConstants::ERROR_EXPIRED_TOKEN, 'The refresh token provided has expired.');
			}
		}
		catch(SystemException $e)
		{
			$oauthRefreshToken = $this->getRefreshToken($refreshToken);

			if($oauthRefreshToken)
			{
				// compatibility hack for templateMode
				if(is_array($oauthRefreshToken['PARAMETERS']))
				{
					foreach($oauthRefreshToken['PARAMETERS'] as $param => $value)
					{
						if(!array_key_exists($param, $oauthRefreshToken))
						{
							$oauthRefreshToken[$param] = $value;
						}
					}
				}
				return $oauthRefreshToken;
			}
		}

		return null;
	}
}