<?
use \Bitrix\OAuth\ClientTable;
use \Bitrix\OAuth\CodeTable;

/**
 * Class COAuthManager
 *
 * @deprecated
 */
abstract class COAuthManager
{
	protected $conf = array();
	protected $bInternalCheck = false;
	protected $templateMode = false;

	/**
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		foreach ($config as $name => $value)
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
	 *  @return $this;
	 */
	public function setVariable($name, $value)
	{
		$this->conf[$name] = $value;
		return $this;
	}

	/**
	 * @param $client_id
	 * @return mixed
	 */
	abstract protected function getClient($client_id);

	/**
	 * @param $client_id
	 * @param null $client_secret
	 * @return mixed
	 */
	abstract protected function checkClientCredentials($client_id, $client_secret = null);

	/**
	 * @param $client_id
	 * @return mixed
	 */
	abstract protected function getRedirectUri($client_id);

	/**
	 * @param $client_id
	 * @return mixed
	 */
	abstract protected function getClientId($client_id);

	/**
	 * @param $oauth_token
	 * @return mixed
	 */
	abstract protected function getAccessToken($oauth_token);

	/**
	 * @param $oauth_token
	 * @param $client_id
	 * @param $expires
	 * @param null $scope
	 * @param int $userId
	 * @param $addParameters
	 * @return mixed
	 */
	abstract protected function setAccessToken($oauth_token, $client_id, $expires, $scope = null, $userId = 0, $addParameters);

	/**
	 * @return array
	 */
	protected function getSupportedGrantTypes()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected function getSupportedAuthResponseTypes()
	{
		return array(
			COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE,
			COAuthConstants::AUTH_RESPONSE_TYPE_ACCESS_TOKEN,
			//	COAuthConstants::AUTH_RESPONSE_TYPE_CODE_AND_TOKEN
		);
	}

	/**
	 * @param $client_id
	 * @return array
	 */
	protected function getSupportedScopes($client_id)
	{
		return array();
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
	 * @param $code
	 * @return null
	 */
	protected function getAuthCode($code)
	{
		return null;
	}

	/**
	 * @param $code
	 * @return null
	 */
	protected function getAuthCodeInfo($code)
	{
		return null;
	}

	/**
	 * @param $refreshToken
	 * @return null
	 */
	protected function getAuthRefreshTokenInfo($refreshToken)
	{
		return null;
	}

	/**
	 * @param $code
	 * @param $client_id
	 * @param $redirect_uri
	 * @param $expires
	 * @param array $scope
	 * @param int $userId
	 */
	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = array(), $userId = 0)
	{
	}

	/**
	 * @param $client_id
	 * @param $username
	 * @param $password
	 * @return bool
	 */
	protected function checkUserCredentials($client_id, $username, $password)
	{
		return false;
	}

	/**
	 * @param $client_id
	 * @param $assertion_type
	 * @param $assertion
	 * @return bool
	 */
	protected function checkAssertion($client_id, $assertion_type, $assertion)
	{
		return false;
	}

	/**
	 * @param $refresh_token
	 * @return null
	 */
	protected function getRefreshToken($refresh_token)
	{
		return null;
	}


	/**
	 * @param $refresh_token
	 * @param $client_id
	 * @param $expires
	 * @param $oauthTokenId
	 * @param array $scope
	 */
	protected function setRefreshToken($refresh_token, $client_id, $expires, $oauthTokenId, $scope = array())
	{
		return;
	}

	/**
	 * @param $refresh_token
	 */
	protected function unsetRefreshToken($refresh_token)
	{
		return;
	}

	/**
	 * @param $client_id
	 * @return bool
	 */
	protected function checkNoneAccess($client_id)
	{
		return false;
	}

	/**
	 * @return string
	 */
	protected function getDefaultAuthenticationRealm()
	{
		return "Service";
	}

	/**
	 * Check that a valid access token has been provided.
	 *
	 * The scope parameter defines any required scope that the token must have.
	 * If a scope param is provided and the token does not have the required
	 * scope, we bounce the request.
	 *
	 * Some implementations may choose to return a subset of the protected
	 * resource (i.e. "public" data) if the user has not provided an access
	 * token or if the access token is invalid or expired.
	 *
	 * The IETF spec says that we should send a 401 Unauthorized header and
	 * bail immediately so that's what the defaults are set to.
	 *
	 * @param $scope
	 *   A space-separated string of required scope(s), if you want to check
	 *   for scope.
	 * @param $exit_not_present
	 *   If TRUE and no access token is provided, send a 401 header and exit,
	 *   otherwise return FALSE.
	 * @param $exit_invalid
	 *   If TRUE and the implementation of getAccessToken() returns NULL, exit,
	 *   otherwise return FALSE.
	 * @param $exit_expired
	 *   If TRUE and the access token has expired, exit, otherwise return FALSE.
	 * @param $exit_scope
	 *   If TRUE the access token does not have the required scope(s), exit,
	 *   otherwise return FALSE.
	 * @param $realm
	 *   If you want to specify a particular realm for the WWW-Authenticate
	 *   header, supply it here.
	 *
	 * 	 * @return bool|void
	 */
	public function verifyAccessToken($scope = null, $exit_not_present = true, $exit_invalid = true, $exit_expired = true, $exit_scope = true, $realm = null)
	{
		$token_param = $this->getAccessTokenParams();
		if($token_param === false) // Access token was not provided
		return $exit_not_present ? $this->sendErrorHeader(COAuthConstants::HTTP_BAD_REQUEST, $realm, COAuthConstants::ERROR_INVALID_REQUEST, 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.', null, $scope) : false;
		// Get the stored token data (from the implementing subclass)
		$token = $this->getAccessToken($token_param);
		if($token === null)
			return $exit_invalid ? $this->sendErrorHeader(COAuthConstants::HTTP_UNAUTHORIZED, $realm, COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.', null, $scope) : false;

		if((!isset($token["CLIENT_ID"]) || !$token["CLIENT_ID"] || !$this->getClientId($token["CLIENT_ID"])))
			return $exit_invalid ? $this->sendErrorHeader(COAuthConstants::HTTP_UNAUTHORIZED, $realm, COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.') : false;

		// Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
		if(isset($token["EXPIRES"]) && time() > $token["EXPIRES"])
			return $exit_expired ? $this->sendErrorHeader(COAuthConstants::HTTP_UNAUTHORIZED, $realm, COAuthConstants::ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', null, $scope) : false;

		// Check scope, if provided
		// If token doesn't have a scope, it's null/empty, or it's insufficient, then throw an error
		if($scope && (!isset($token["scope"]) || !$token["scope"] || !$this->checkScope($scope, $token["scope"])))
			return $exit_scope ? $this->sendErrorHeader(COAuthConstants::HTTP_FORBIDDEN, $realm, COAuthConstants::ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', null, $scope) : false;

		return true;
	}

	/**
	 * @param $token
	 * @param array $scope
	 * @return array
	 */
	public function verifyAccessTokenInternal($token, $scope = array())
	{
		$this->bInternalCheck = true;
		$token = $this->getAccessToken($token);

		if(isset($token["EXPIRES"]) && time() > $token["EXPIRES"])
			return $this->sendError(COAuthConstants::HTTP_UNAUTHORIZED, COAuthConstants::ERROR_EXPIRED_TOKEN, 'The access token provided has expired.');

		if((!isset($token["CLIENT_ID"]) || !$token["CLIENT_ID"] || !$this->getClientId($token["CLIENT_ID"])))
			return $this->sendError(COAuthConstants::HTTP_UNAUTHORIZED, COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.');

		if($scope && (!isset($token["SCOPE"]) || !$token["SCOPE"] || !$this->checkScope($scope, $token["SCOPE"])))
			return $this->sendError(COAuthConstants::HTTP_FORBIDDEN, COAuthConstants::ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.');

		if(CModule::IncludeModule('bitrix24'))
		{
			if(self::checkPaymentStatus($token["CLIENT_ID"]) === false)
			{
				return $this->sendError(COAuthConstants::HTTP_PAYMENT_REQUIRED, "payment_required", "Payment required.");
			}
		}

		if(is_array($token) && isset($token["USER_ID"]) && isset($token["CLIENT_ID"]) && isset($token["EXPIRES"]))
		{
			$arResult = array("user_id" => $token["USER_ID"], "client_id" => $token["CLIENT_ID"], "expired" => ($token["EXPIRES"] - time()));
			if(isset($token["PARAMETERS"]) && strlen($token["PARAMETERS"]) > 0)
				$arResult["parameters"] = $token["PARAMETERS"];
			return $arResult;
		}
		return $this->sendError(COAuthConstants::HTTP_UNAUTHORIZED, COAuthConstants::ERROR_INVALID_TOKEN, 'The access token provided is invalid.');
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
			$required_scope = explode(",", $required_scope);

		if(!is_array($available_scope))
			$available_scope = explode(",", $available_scope);

		return (count(array_diff($required_scope, $available_scope)) == 0);
	}

	/**
	 * @return bool
	 */
	private function getAccessTokenParams()
	{
		$auth_header = $this->getAuthorizationHeader();

		if($auth_header !== false)
		{
			// Make sure only the auth header is set
			if(isset($_GET[COAuthConstants::TOKEN_PARAM_NAME]) || isset($_POST[COAuthConstants::TOKEN_PARAM_NAME]))
				$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'Auth token found in GET or POST when token present in header');

			$auth_header = trim($auth_header);

			// Make sure it's Token authorization
			if(strcmp(substr($auth_header, 0, 5), "OAuth ") !== 0)
				$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'Auth header found that doesn\'t start with "OAuth"');

			// Parse the rest of the header
			$matches = array();
			if(preg_match('/\s*OAuth\s*="(.+)"/', substr($auth_header, 5), $matches) == 0 || count($matches) < 2)
				$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'Malformed auth header');

			return $matches[1];
		}

		if(isset($_GET[COAuthConstants::TOKEN_PARAM_NAME]))
		{
			if(isset($_POST[COAuthConstants::TOKEN_PARAM_NAME])) // Both GET and POST are not allowed
			$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'Only send the token in GET or POST, not both');

			return $_GET[COAuthConstants::TOKEN_PARAM_NAME];
		}

		if(isset($_POST[COAuthConstants::TOKEN_PARAM_NAME]))
			return $_POST[COAuthConstants::TOKEN_PARAM_NAME];

		return false;
	}

	/**
	 * Grant or deny a requested access token.
	 */
	public function grantAccessToken()
	{
		$filter = $this->getFilter();

		$arResult = filter_input_array(INPUT_GET, $filter);
		$client = $this->getClientParameters($arResult);

		$userId = 0;
		switch ($arResult["grant_type"])
		{
			case COAuthConstants::GRANT_TYPE_AUTH_CODE:
				$arCodeInfo = $this->getAuthCodeInfo($arResult["code"]);
				break;
			case COAuthConstants::GRANT_TYPE_REFRESH_TOKEN:
				$arCodeInfo = $this->getAuthRefreshTokenInfo($arResult["refresh_token"]);
				$arResult["scope"] = $arCodeInfo["SCOPE"];
				break;
			default:
				$arCodeInfo = array();
		}
		if(is_array($arCodeInfo) && isset($arCodeInfo["USER_ID"]))
			$userId = intval($arCodeInfo["USER_ID"]);
		if(!$arResult["scope"])
			$arResult["scope"] = null;

		$token = $this->createAccessToken($client, $arResult["scope"], $userId);

		$this->sendJsonHeaders();
		echo json_encode($token);
		exit;
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
	 * @return array
	 */
	public function grantAccessTokenInternal($client_id = '', $grant_type = 'authorization_code', $redirectUri = '', $code = '', $scope = '', $clientSecret = null, $refresh_token = '', $addParameters = array(), $userId = 0)
	{
		$this->bInternalCheck = true;
		$userId = intval($userId);
		$arResult = array();

		$filter = $this->getFilter();

		$filter["client_id"] = array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => COAuthConstants::CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR);
		$arResult["redirect_uri"] = $redirectUri;
		$arResult["grant_type"] = $grant_type;
		$arResult["scope"] = $scope;
		$arResult["code"] = $code;
		$arResult["refresh_token"] = $refresh_token;
		$arResult["user_id"] = $userId;
		$arResult["client_id"] = $client_id;

		$arResult = filter_var_array($arResult, $filter);
		$arResult["client_secret"] = $clientSecret;

		$client = $this->getClientParameters($arResult);

		if(!$arResult["scope"])
			$arResult["scope"] = null;

		return $this->createAccessToken($client, $arResult["scope"], $userId, $addParameters);
	}

	private function getFilter()
	{
		return array(
			"grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => COAuthConstants::GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"client_id" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => COAuthConstants::CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"response_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => COAuthConstants::AUTH_RESPONSE_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
			"state" => array("flags" => FILTER_REQUIRE_SCALAR),
			"scope" => array("flags" => FILTER_REQUIRE_SCALAR),
			"code" => array("flags" => FILTER_REQUIRE_SCALAR),
			"redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
			"username" => array("flags" => FILTER_REQUIRE_SCALAR),
			"password" => array("flags" => FILTER_REQUIRE_SCALAR),
			"assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR),
			"assertion" => array("flags" => FILTER_REQUIRE_SCALAR),
			"refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
			"user_id" => array("filter" => FILTER_VALIDATE_INT),
		);
	}

	private function getClientParameters($arParams)
	{

		// Grant Type must be specified.
		if(!$arParams["grant_type"])
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');

		// Make sure we've implemented the requested grant type
		if(!in_array($arParams["grant_type"], $this->getSupportedGrantTypes()))
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_UNSUPPORTED_GRANT_TYPE);

		// Authorize the client
		if($this->bInternalCheck)
			$client = array($arParams["client_id"], $arParams["client_secret"]);
		else
			$client = $this->getClientCredentials();

		if(!is_array($client) || strlen($client[0]) <= 0)
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);

		if($this->checkClientCredentials($client[0], $client[1], $this->bInternalCheck) === false)
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_CLIENT);

		$redirect_uri = $this->getRedirectUri($client[0]);
		if($redirect_uri === null)
			$this->templateMode = true;

		if(!$this->checkRestrictedGrantType($client[0], $arParams["grant_type"]))
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);

		// Do the granting
		switch ($arParams["grant_type"])
		{
			case COAuthConstants::GRANT_TYPE_AUTH_CODE:
				if(!$arParams["code"])
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST);

				$stored = $this->getAuthCode($arParams["code"]);

				// Ensure that the input uri starts with the stored uri
				if($stored === null || $client[0] != $stored["APPLICATION_ID"])
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_GRANT);

				if(intval($stored["EXPIRES"]) < intval(time()))
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_EXPIRED_TOKEN);

				break;
			case COAuthConstants::GRANT_TYPE_REFRESH_TOKEN:
				if(!$arParams["refresh_token"])
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');

				$stored = $this->getRefreshToken($arParams["refresh_token"]);
				if($stored === null || $client[0] != $stored["APPLICATION_ID"])
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_GRANT);

				if(intval($stored["EXPIRES"]) < time())
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_EXPIRED_TOKEN);

				// store the refresh token locally so we can delete it when a new refresh token is generated
				$this->setVariable('_old_refresh_token', $stored["REFRESH_TOKEN"]);

				break;
			case COAuthConstants::GRANT_TYPE_NONE:
				$stored = $this->checkNoneAccess($client[0]);

				if($stored === false)
					return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_REQUEST);
				break;
			default:
				return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_GRANT);
				break;
		}

		// Check scope, if provided
		if(
			$arParams["scope"]
			&& (
				!is_array($stored)
				|| !isset($stored["SCOPE"])
				|| !$this->checkScope($arParams["scope"], $stored["SCOPE"])
			)
		)
		{
			return $this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_SCOPE);
		}
		return $client[0];
	}
	/**
	 * @return array
	 */
	protected function getClientCredentials()
	{
		if(isset($_SERVER["PHP_AUTH_USER"]) && $_POST && isset($_POST["client_id"]))
			$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_CLIENT);

		// Try basic auth
		if(isset($_SERVER["PHP_AUTH_USER"]))
			return array($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);

		// Try POST
		if($_GET && isset($_GET["client_id"]))
		{
			if(isset($_GET["client_secret"]))
				return array($_GET["client_id"], $_GET["client_secret"]);

			return array($_GET["client_id"], null);
		}

		// No credentials were specified
		$this->sendError(COAuthConstants::HTTP_BAD_REQUEST, COAuthConstants::ERROR_INVALID_CLIENT);
	}

	/**
	 * @param $code
	 * @param $secret
	 * @return array|bool
	 */
	protected function getClientThroughCode($code, $secret)
	{
		if(strlen($secret) < 0)
		{
			$secret = null;
		}

		$dbClient = CodeTable::getList(array(
			'filter' => array(
				"=CODE" => $code
			),
			'select' => array(
				'CLIENT_ID_CHECKED' => 'CLIENT.ID'
			)
		));
		if($arClient = $dbClient->fetch())
		{
			return array($arClient["CLIENT_ID_CHECKED"], $secret);
		}

		return false;
	}

	/**
	 * Pull data authorization request from the HTTP request.
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
	 * @return array|mixed
	 */
	public function getAuthorizeParamsInternal($clientId = '', $responseType = 'code', $redirectUri = '', $state = '', $scope = '')
	{
		$this->bInternalCheck = true;

		$filter = $this->getFilter();

		$arResult["client_id"] = $clientId;
		$arResult["redirect_uri"] = $redirectUri;
		$arResult["response_type"] = $responseType;
		$arResult["scope"] = $scope;
		$arResult["state"] = $state;

		$arResult = filter_var_array($arResult, $filter);

		return $this->validateParametersInternal($arResult);

	}

	private function validateParameters($arResult)
	{
		$client = false;
		if(isset($arResult["client_id"]))
		{
			$client = $this->getClient($arResult["client_id"]);
		}

		// Make sure a valid client id was supplied
		if(!$client)
		{
			if($arResult["redirect_uri"])
			{
				$redirect_url = $arResult["redirect_uri"]
					.(strpos($arResult["redirect_uri"], '?') === false ? '?' : '&')
					."error=".COAuthConstants::ERROR_INVALID_CLIENT;

				LocalRedirect($redirect_url, true);
			}
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_CLIENT); // We don't have a good URI to use
		}
		// redirect_uri is not required if already established via other channels
		// check an existing redirect URI against the one supplied
		$redirect_uri = $this->getRedirectUri($arResult["client_id"]);

		// getRedirectUri() should return false if the given client ID is invalid
		// this probably saves us from making a separate db call, and simplifies the method set
		if($redirect_uri === false)
		{
			if($arResult["redirect_uri"])
			{
				$redirect_url = $arResult["redirect_uri"]."?error=".COAuthConstants::ERROR_INVALID_CLIENT;
				LocalRedirect($redirect_url, true);
			}
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_CLIENT);
		}
		elseif($redirect_uri === null || $redirect_uri === '')
			$this->templateMode = true;
		else
			$arResult["redirect_uri"] = $redirect_uri;

		// bitrix24
		if($client['CLIENT_TYPE'] == ClientTable::TYPE_APPLICATION && CModule::IncludeModule('bitrix24'))
		{
			if(self::checkPaymentStatus($arResult["client_id"]) === false)
			{
				if($this->templateMode)
					return array("ERROR_MESSAGE" => COAuthConstants::ERROR_USER_DENIED.". Payment required.");
				$this->errorDoRedirectUriCallback($arResult["redirect_uri"], COAuthConstants::ERROR_USER_DENIED, 'Payment required.', null, $arResult["state"]);
			}
		}
		// bitrix24.net
		elseif ($client['CLIENT_TYPE'] == ClientTable::TYPE_PORTAL)
		{
			if(self::checkUserProfile($arResult['client_id'], $arResult['user_id']) === false)
			{
				if($this->templateMode)
					return array("ERROR_MESSAGE" => COAuthConstants::ERROR_USER_DENIED.". Link required.");
				$this->errorDoRedirectUriCallback($arResult["redirect_uri"], COAuthConstants::ERROR_USER_DENIED, 'Link required.', null, $arResult["state"]);
			}
		}
		// bitrix24.net + external site
		elseif ($client['CLIENT_TYPE'] == ClientTable::TYPE_EXTERNAL)
		{
			$arScope = explode(",", $arResult['scope']);

			if(!self::checkUserScope($client['ID'], $arResult['user_id'], $arScope))
			{
				return array("SCOPE_REQUEST" => true, "SCOPE" => $arScope, "CLIENT" => $client);
			}
		}

		// type and client_id are required
		if(!$arResult["response_type"])
		{
			if($this->templateMode)
				return array("ERROR_MESSAGE" => COAuthConstants::ERROR_INVALID_REQUEST.". Invalid response type.");
			$this->errorDoRedirectUriCallback($arResult["redirect_uri"], COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid response type.', null, $arResult["state"]);
		}

		// Check requested auth response type against the list of supported types
		if(array_search($arResult["response_type"], $this->getSupportedAuthResponseTypes()) === false)
		{
			if($this->templateMode)
				return array("ERROR_MESSAGE" => COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE);
			$this->errorDoRedirectUriCallback($arResult["redirect_uri"], COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE, null, null, $arResult["state"]);
		}

		// Restrict clients to certain authorization response types
		if($this->checkRestrictedAuthResponseType($arResult["client_id"], $arResult["response_type"]) === false)
		{
			if($this->templateMode)
				return array("ERROR_MESSAGE" => COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE);
			$this->errorDoRedirectUriCallback($arResult["redirect_uri"], COAuthConstants::ERROR_UNAUTHORIZED_CLIENT, null, null, $arResult["state"]);
		}

		// Validate that the requested scope is supported
		if($arResult["scope"] && !$this->checkScope($arResult["scope"], $this->getSupportedScopes($arResult["client_id"])))
		{
			if($this->templateMode)
				return array("ERROR_MESSAGE" => COAuthConstants::ERROR_INVALID_SCOPE);
			$this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_SCOPE);
		}

		return $arResult;
	}

	private function validateParametersInternal($arResult)
	{
		// Make sure a valid client id was supplied
		if(!isset($arResult["client_id"]) || !$this->getClientId($arResult["client_id"]))
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_CLIENT); // We don't have a good URI to use

		// redirect_uri is not required if already established via other channels
		// check an existing redirect URI against the one supplied
		$redirect_uri = $this->getRedirectUri($arResult["client_id"]);

		// getRedirectUri() should return false if the given client ID is invalid
		// this probably saves us from making a separate db call, and simplifies the method set
		if($redirect_uri === false)
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_CLIENT);
		elseif($redirect_uri === null || $redirect_uri === '')
			$this->templateMode = true;
		else
			$arResult["redirect_uri"] = $redirect_uri;

		// type and client_id are required
		if(!$arResult["response_type"])
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_REQUEST, 'Invalid response type.');

		if(CModule::IncludeModule('bitrix24'))
		{
			if(self::checkPaymentStatus($arResult["client_id"]) === false)
			{
				return $this->sendError(COAuthConstants::HTTP_PAYMENT_REQUIRED, COAuthConstants::ERROR_USER_DENIED);
			}
		}

		// Check requested auth response type against the list of supported types
		if(array_search($arResult["response_type"], $this->getSupportedAuthResponseTypes()) === false)
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_UNSUPPORTED_RESPONSE_TYPE, 'Invalid response type.');
		// Restrict clients to certain authorization response types
		if($this->checkRestrictedAuthResponseType($arResult["client_id"], $arResult["response_type"]) === false)
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_UNAUTHORIZED_CLIENT);
		// Validate that the requested scope is supported
		if($arResult["scope"] && !$this->checkScope($arResult["scope"], $this->getSupportedScopes($arResult["client_id"])))
			return $this->sendError(COAuthConstants::HTTP_FOUND, COAuthConstants::ERROR_INVALID_SCOPE);

		return $this->sendAuthorizationParams(true, $arResult);
	}

	/**
	 * @param $is_authorized
	 * @param array $arParams
	 * @return mixed
	 */
	public function sendAuthorizationParams($is_authorized, $arParams = array())
	{
		$state = $scope = $userId = null;
		$result = array();
		if(isset($arParams['state']))
			$state = $arParams['state'];
		if(isset($arParams['scope']))
			$scope = $arParams['scope'];
		if(isset($arParams['user_id']))
			$userId = $arParams['user_id'];

		$clientId = $arParams['client_id'];
		$redirectUri = $arParams['redirect_uri'];
		$responseType = $arParams['response_type'];

		if($state !== null)
			$result["query"]["state"] = $state;

		if($is_authorized === false)
		{
			$result["query"]["error"] = COAuthConstants::ERROR_USER_DENIED;
		}
		else
		{
			if($responseType == COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE || $responseType == COAuthConstants::AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
				$result["query"]["code"] = $this->createAuthCode($clientId, $redirectUri, $scope, $userId, $this->templateMode);
		}
		$result["query"]["domain"] = $_SERVER["HTTP_HOST"];

		if(CModule::IncludeModule('bitrix24'))
		{
			$result["query"]["member_id"] = CBitrix24::getMemberId();
		}

		if($this->bInternalCheck || $this->templateMode)
		{
			return $result["query"];
		}
			$this->doRedirectUriCallback($redirectUri, $result);
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

	/**
	 * @param $uri
	 * @param $params
	 * @return string
	 */
	private function buildUri($uri, $params)
	{
		$parse_url = parse_url($uri);
		foreach ($params as $k => $v)
		{
			if(isset($parse_url[$k]))
				$parse_url[$k] .= "&" . http_build_query($this->filterHeaderParams($v));
			else
				$parse_url[$k] = http_build_query($this->filterHeaderParams($v));
		}

		return
			((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
			. ((isset($parse_url["user"])) ? $parse_url["user"] . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
			. ((isset($parse_url["host"])) ? $parse_url["host"] : "")
			. ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
			. ((isset($parse_url["path"])) ? $parse_url["path"] : "")
			. ((isset($parse_url["query"])) ? "?" . $parse_url["query"] : "")
			. ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
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
		$token = array(
			"access_token" => $this->genAccessToken(),
			"expires_in" => $this->getVariable('access_token_lifetime', COAuthConstants::ACCESS_TOKEN_LIFETIME),
			"scope" => $scope,
			//"user_id" => $userId,
		);

		$client = $this->getClient($client_id);

		if($client['CLIENT_TYPE'] == ClientTable::TYPE_APPLICATION && CModule::IncludeModule('bitrix24'))
		{
			$status = self::checkPaymentStatus($client_id);
			if($status === false)
			{
				return $this->sendError(COAuthConstants::HTTP_PAYMENT_REQUIRED, COAuthConstants::ERROR_USER_DENIED);
			}
			else
			{
				$token['status'] = $status;
			}
		}
		elseif($client['CLIENT_TYPE'] == ClientTable::TYPE_PORTAL)
		{
			$profileId = self::checkUserProfile($client_id, $userId);
			if($profileId === false)
			{
				return $this->sendError(COAuthConstants::HTTP_FORBIDDEN, COAuthConstants::ERROR_USER_DENIED);
			}
			else
			{
				$token['profile'] = $profileId;
			}
		}

		$oauthTokenId = $this->setAccessToken($token["access_token"], $client_id, time() + $this->getVariable('access_token_lifetime', COAuthConstants::ACCESS_TOKEN_LIFETIME), $scope, $userId, $addParameters);

		// Issue a refresh token also, if we support them
		if(in_array(COAuthConstants::GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes()))
		{
			$token["refresh_token"] = $this->genAccessToken();
			$this->setRefreshToken($token["refresh_token"], $client_id, time() + $this->getVariable('refresh_token_lifetime', COAuthConstants::REFRESH_TOKEN_LIFETIME), $oauthTokenId, $scope, $userId);
			// If we've granted a new refresh token, expire the old one
			if($this->getVariable('_old_refresh_token'))
				$this->unsetRefreshToken($this->getVariable('_old_refresh_token'));
		}
		$token["domain"] = $_SERVER["HTTP_HOST"];

		if(CModule::IncludeModule('bitrix24'))
		{
			$token['member_id'] = CBitrix24::getMemberId();
		}

		return $token;
	}

	/**
	 * @param $client_id
	 * @param $redirect_uri
	 * @param null $scope
	 * @param int $userId
	 * @return string
	 */
	private function createAuthCode($client_id, $redirect_uri, $scope = null, $userId = 0, $templateMode = false)
	{
		$code = $this->genAuthCode($templateMode);
		$this->setAuthCode($code, $client_id, $redirect_uri, time() + $this->getVariable('auth_code_lifetime', COAuthConstants::AUTH_CODE_LIFETIME), $scope, $userId);
		return $code;
	}

	/**
	 * @return string
	 */
	protected function genAccessToken()
	{
		return $this->genAuthCode();
	}


	/**
	 * @param bool $templateMode
	 * @return string
	 */
	protected function genAuthCode($templateMode = false)
	{
		global $APPLICATION;
		if($templateMode)
			return randString(6, "0123456789");
		else
			return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), $APPLICATION->GetServerUniqID())));
	}

	/**
	 * @return bool
	 */
	private function getAuthorizationHeader()
	{
		if(array_key_exists("HTTP_AUTHORIZATION", $_SERVER))
			return $_SERVER["HTTP_AUTHORIZATION"];

		if(function_exists("apache_request_headers"))
		{
			$headers = apache_request_headers();

			if(array_key_exists("Authorization", $headers))
				return $headers["Authorization"];
		}

		return false;
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
			$result["query"]["state"] = $state;

		if($this->getVariable('display_error') && $error_description)
			$result["query"]["error_description"] = $error_description;

		if($this->getVariable('display_error') && $error_uri)
			$result["query"]["error_uri"] = $error_uri;

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
		if(!$this->bInternalCheck)
			self::sendJsonError($http_status_code, $error, $error_description, $error_uri);
		return array("error" => $error, "error_description" => $error_description);
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
			$result["error_description"] = $error_description;

		if($this->getVariable('display_error') && $error_uri)
			$result["error_uri"] = $error_uri;

		header("HTTP/1.1 " . $http_status_code);
		$this->sendJsonHeaders();

		echo json_encode($result);

		exit;
	}

	/**
	 * @param $http_status_code
	 * @param $realm
	 * @param $error
	 * @param null $error_description
	 * @param null $error_uri
	 * @param null $scope
	 */
	private function sendErrorHeader($http_status_code, $realm, $error, $error_description = null, $error_uri = null, $scope = null)
	{
		$realm = $realm === null ? $this->getDefaultAuthenticationRealm() : $realm;

		$result = "WWW-Authenticate: OAuth realm='" . $realm . "'";

		if($error)
			$result .= ", error='" . $error . "'";

		if($this->getVariable('display_error') && $error_description)
			$result .= ", error_description='" . $error_description . "'";

		if($this->getVariable('display_error') && $error_uri)
			$result .= ", error_uri='" . $error_uri . "'";

		if($scope)
			$result .= ", scope='" . $scope . "'";

		$http_status_code = $this->filterHeaderParams($http_status_code);
		$result = $this->filterHeaderParams($result);

		header("HTTP/1.1 ". $http_status_code);
		header($result);

		exit;
	}

	// bitrix24 module should already be included for this method call
	private static function checkPaymentStatus($appId)
	{
		$info = array();
		$dbRes = CBitrix24App::GetList(array(), array('APP_ID' => $appId));
		if($arApp = $dbRes->Fetch())
			$info = CBitrix24App::getAppStatusInfo($arApp);

		if($info['PAYMENT_ALLOW'] != 'Y')
		{
			return false;
		}
		return $info['STATUS'];
	}

	private static function checkUserScope($clientId, $userId = null, $arScope = array())
	{
		if($userId === null)
		{
			$userId = $GLOBALS['USER']->getId();
		}

		$dbRes = \Bitrix\Oauth\ClientScopeTable::getList(array(
			'filter' => array(
				"=USER_ID" => $userId,
				"=CLIENT_ID" => $clientId,
			),
			'select' => array('CLIENT_SCOPE'),
			'limit' => array(0,1)
		));

		$arRes = $dbRes->fetch();
		if($arRes)
		{
			$arOldScope = explode(",", $arRes['CLIENT_SCOPE']);
			if(count(array_diff($arScope, $arOldScope)) <= 0)
			{
				return true;
			}
		}

		return false;
	}

	public static function checkUserProfile($clientId, $userId = null)
	{
		if($userId === null)
		{
			$userId = $GLOBALS['USER']->getId();
		}

		$dbRes = \Bitrix\OAuth\ClientProfileTable::getList(array(
			'filter' => array(
				'USER_ID' => $userId,
				'CLIENT.CLIENT_ID' => $clientId,
				'CLIENT_PROFILE_ACTIVE' => \Bitrix\OAuth\ClientProfileTable::ACTIVE,
				'ACCEPTED' => \Bitrix\OAuth\ClientProfileTable::ACCEPTED,
			),
			'select' => array('CLIENT_PROFILE_ID'),
		));

		$arRes = $dbRes->fetch();
		if($arRes)
		{
			return $arRes['CLIENT_PROFILE_ID'];
		}
		else
		{
			return false;
		}
	}
}