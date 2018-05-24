<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage oauth
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\OAuth;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\OAuth\Auth\AccessToken;
use Bitrix\OAuth\Client\Application;
use Bitrix\OAuth\Client\Bitrix;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\OAuthException;
use Bitrix\Rest\RestException;

class Rest extends \IRestService
{
	const SCOPE_REST = "rest";
	const SCOPE_APPLICATION = "application";
	const SCOPE_APP = "app";

	const STATUS_LOCAL = 'L';
	const STATUS_FREE = 'F';
	const STATUS_PAID = 'P';
	const STATUS_TRIAL = 'T';
	const STATUS_DEMO = 'D';

	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE_APP => array(
				'app.debug' => array(
					'callback' => array(__CLASS__, 'appDebug'),
					'options' => array(
						'private' => true,
					),
				),
				'app.info' => array(
					'callback' => array(__CLASS__, 'appInfo'),
					'options' => array(),
				),
				'app.clients' => array(
					'callback' => array(__CLASS__, 'appClients'),
					'options' => array(),
				),
				'app.stat' => array(
					'callback' => array(__CLASS__, 'applicationStat'),
					'options' => array(),
				),
			),
			static::SCOPE_REST => array(
				'rest.code' => array(
					'callback' => array(__CLASS__, 'restCode'),
					'options' => array(),
				),
				'rest.authorize' => array(
					'callback' => array(__CLASS__, 'restAuthorize'),
					'options' => array(),
				),
				'rest.check' => array(
					'callback' => array(__CLASS__, 'restCheck'),
					'options' => array(),
				),
				'rest.event.call' => array(
					'callback' => array(__CLASS__, 'restEventCall'),
					'options' => array(),
				),
			),
			static::SCOPE_APPLICATION => array(
				'application.add' => array(
					'callback' => array(__CLASS__, 'applicationAdd'),
					'options' => array(),
				),
				'application.update' => array(
					'callback' => array(__CLASS__, 'applicationUpdate'),
					'options' => array(),
				),
				'application.delete' => array(
					'callback' => array(__CLASS__, 'applicationDelete'),
					'options' => array(),
				),
				'application.list' => array(
					'callback' => array(__CLASS__, 'applicationList'),
					'options' => array(),
				),
				'application.status' => array(
					'callback' => array(__CLASS__, 'applicationStatus'),
					'options' => array(
						'private' => true,
					),
				),
				'application.install' => array(
					'callback' => array(__CLASS__, 'applicationInstall'),
					'options' => array(),
				),
				'application.uninstall' => array(
					'callback' => array(__CLASS__, 'applicationUninstall'),
					'options' => array(),
				),
				'application.stat' => array(
					'callback' => array(__CLASS__, 'applicationStat'),
					'options' => array(),
				),
				'application.stat.ex' => array(
					'callback' => array(__CLASS__, 'applicationStatEx'),
					'options' => array(
						'private' => true,
					),
				),
				'application.stat.count' => array(
					'callback' => array(__CLASS__, 'applicationStatCount'),
					'options' => array(
						'private' => true,
					),
				),
				'application.version.add' => array(
					'callback' => array(__CLASS__, 'applicationVersionAdd'),
					'options' => array(
						'private' => true,
					),
				),
				// the same callback as with application.version.add
				'application.version.update' => array(
					'callback' => array(__CLASS__, 'applicationVersionAdd'),
					'options' => array(
						'private' => true,
					),
				),
				'application.version.delete' => array(
					'callback' => array(__CLASS__, 'applicationVersionDelete'),
					'options' => array(
						'private' => true,
					),
				),
				'application.version.get' => array(
					'callback' => array(__CLASS__, 'applicationVersionGet'),
					'options' => array(
						'private' => true,
					),
				),
			),
		);
	}

	/**********************
	 * app scope
	 **********************/
	public static function appDebug(array $params, $n, \CRestServer $server)
	{
		global $USER;

		$auth = $server->getAuth();
		$result = array(
			'user' => $USER->GetID(),
			'app' => $server->getClientId(),
			'query' => $params,
			'auth' => $auth,
		);

		if(isset($auth['access_token']) || isset($auth['auth']))
		{
			$token = isset($auth['access_token']) ? $auth['access_token'] : $auth['auth'];

			$tokenGenerator = new AccessToken();
			$result['token_data'] = $tokenGenerator->getTokenData($token);
			$result['token_expires'] = $tokenGenerator->getTimestamp();
			$result['token_expire_date'] = date('c', $tokenGenerator->getTimestamp());
		}

		return $result;
	}

	public static function appInfo(array $params, $n, \CRestServer $server)
	{
		$result = array(
			'client_id' => $server->getClientId()
		);

		$appClient = Base::instance($server->getClientId());

		$requestAuth = $server->getAuth();
		if(isset($requestAuth['auth']) || isset($requestAuth['access_token']))
		{
			$accessToken = isset($requestAuth['access_token'])
				? $requestAuth['access_token']
				: $requestAuth['auth'];

			try
			{
				$tokenGenerator = new AccessToken();
				$tokenData = $tokenGenerator->getTokenData($accessToken);

				$tokenInfo = array(
					'USER_ID' => $tokenData['USER_ID'],
					'SCOPE' => implode(', ', $appClient->getClientScope()),
					'EXPIRES' => $tokenGenerator->getTimestamp(),
					'PARAMETERS' => $tokenData,
				);
			}
			catch(SystemException $e)
			{
				$dbRes = TokenTable::getList(array(
					'filter' => array(
						'=OAUTH_TOKEN' => $accessToken
					),
					'select' => array(
						'SCOPE', 'USER_ID', 'PARAMETERS', 'EXPIRES',
					)
				));
				$tokenInfo = $dbRes->fetch();
			}

			$result['scope'] = $tokenInfo['SCOPE'];
			$result['expires'] = \CRestUtil::convertDateTime(convertTimeStamp($tokenInfo['EXPIRES'], 'FULL'));

			$installClient = Base::instanceById($tokenInfo['USER_ID']);

			$clientInfo = $installClient->getClient();

			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
				),
				'select' => array(
					'ACTIVE', 'STATUS', 'DATE_FINISH',
					'INSTALLED_VERSION' => 'VERSION.VERSION',
					'INSTALLED_VERSION_SCOPE' => 'VERSION.SCOPE',
				)
			));

			$installInfo = $dbRes->fetch();

			list($memberType, $memberXmlId) = explode("_", $clientInfo['EXTERNAL_ID'], 2);

			$uri = new Uri($clientInfo['REDIRECT_URI']);

			$result['install'] = array(
				'installed' => $installInfo['ACTIVE'] === ClientVersionInstallTable::ACTIVE,
				'version' => $installInfo['INSTALLED_VERSION'],
				'status' => $installInfo['STATUS'],
				'scope' => implode(',', $installInfo['INSTALLED_VERSION_SCOPE']),
				'domain' => $uri->getHost(),
				'uri' => $uri->getScheme().'://'.$uri->getHost(),
				'client_endpoint' => $uri->setPath('/rest/')->getLocator(),
				'member_id' => $clientInfo['MEMBER_ID'],
				'member_type' => $memberType,
			);

			if(
				$installInfo['STATUS'] == ClientVersionInstallTable::STATUS_PAID
				|| $installInfo['STATUS'] == ClientVersionInstallTable::STATUS_TRIAL
			)
			{
				if($installInfo['DATE_FINISH'])
				{
					/** @var DateTime $dateFinish */
					$dateFinish = $installInfo['DATE_FINISH'];
					$result['install']['date_finish'] = \CRestUtil::ConvertDate($dateFinish->toString());
				}
			}
		}
		else
		{
			$appClient = Base::instance($server->getClientId());
			$clientInfo = $appClient->getClient();

			$result['scope'] = implode(',', $clientInfo['SCOPE']);
			$result['type'] = $clientInfo['CLIENT_TYPE'];
		}

		return $result;
	}

	/**********************
	 * rest scope
	 **********************/

	public static function restCode(array $params, $n, \CRestServer $server)
	{
		if(empty($params["CLIENT_ID"]))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$installClient = Base::instance($server->getClientId());
		$appClient = Base::instance($params["CLIENT_ID"]);

		static::checkInstallClient($params, $installClient);

		$additionalParams = $params['PARAMS'];

		if($appClient)
		{
			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
					'=ACTIVE' => ClientVersionInstallTable::ACTIVE,
				),
				'select' => array(
					'VERSION_ID',
					'VERSION_SCOPE' => 'VERSION.SCOPE',
					'DOMAIN' => 'INSTALL_CLIENT.REDIRECT_URI',
					'CLIENT_MEMBER_ID' => 'INSTALL_CLIENT.UF_MEMBER_ID',
					'CLIENT_SCOPE' => 'CLIENT.SCOPE',
					'STATUS',
					'IS_TRIALED',
					'DATE_FINISH',
				)
			));

			$installInfo = $dbRes->fetch();
			if(!$installInfo)
			{
				throw new RestException("Application not installed", RestException::ERROR_OAUTH, \CRestServer::STATUS_UNAUTHORIZED);
			}

			$appClient->setVersionId($installInfo['VERSION_ID']);

			$tokenScope = $installInfo['CLIENT_SCOPE'];
			if(is_array($tokenScope))
			{
				$tokenScope = implode(',', $tokenScope);
			}

			$appState = $params['STATE'];

			$authParams = $appClient->getAuthorizeParamsInternal(
				$params["CLIENT_ID"],
				\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE,
				'',
				$appState,
				$tokenScope,
				$additionalParams,
				$installClient->getClientId()
			);

			if($authParams['error'])
			{
				return $authParams;
			}
			else
			{
				$uri = $appClient->getRedirectUri();

				$result = $authParams;

				$result['member_id'] = $installInfo['CLIENT_MEMBER_ID'];
				$result['scope'] = implode(',', $installInfo['VERSION_SCOPE']);
				$result['redirect_uri'] = $uri;

				return $result;
			}
		}
		else
		{
			throw new RestException('Unable to get application data', 'WRONG_APPLICATION_CLIENT');
		}

	}

	public static function restAuthorize(array $params, $n, \CRestServer $server)
	{
		if(empty($params["CLIENT_ID"]))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$installClient = Base::instance($server->getClientId());
		$appClient = Base::instance($params["CLIENT_ID"]);

		static::checkInstallClient($params, $installClient);

		if($appClient)
		{
			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
					'=ACTIVE' => ClientVersionInstallTable::ACTIVE,
				),
				'select' => array(
					'VERSION_SCOPE' => 'VERSION.SCOPE',
					'DOMAIN' => 'INSTALL_CLIENT.REDIRECT_URI',
					'CLIENT_SCOPE' => 'CLIENT.SCOPE',
					'STATUS',
					'IS_TRIALED',
					'DATE_FINISH',
					'CLIENT_ID', 'INSTALL_CLIENT_ID'
				)
			));

			$installInfo = $dbRes->fetch();
			if(!$installInfo)
			{
				throw new RestException("Application not installed", RestException::ERROR_OAUTH, \CRestServer::STATUS_UNAUTHORIZED);
			}

			$tokenScope = $installInfo['CLIENT_SCOPE'];
			if(is_array($tokenScope))
			{
				$tokenScope = implode(',', $tokenScope);
			}

			$additionalParams = isset($params["PARAMS"]) ? $params["PARAMS"] : array();

			$authParams = $appClient->getAuthorizeParamsInternal(
				$params["CLIENT_ID"],
				\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE,
				'',
				'',
				$tokenScope,
				$additionalParams,
				$installClient->getClientId()
			);

			if(is_array($authParams) && isset($authParams[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE]))
			{
				$tokenInfo = $appClient->grantAccessTokenInternal(
					$params["CLIENT_ID"],
					\COAuthConstants::GRANT_TYPE_AUTH_CODE,
					'',
					$authParams[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE],
					$tokenScope,
					null,
					'',
					$additionalParams,
					$installClient->getClientId()
				);

				if(!$tokenInfo["access_token"])
				{
					throw new OAuthException($tokenInfo);
				}

				$tokenInfo["scope"] = implode(',', $installInfo["VERSION_SCOPE"]);

				// compatibility hack. client_endpoint parameter is preferred
				$tokenInfo["domain"] = preg_replace('/^http[s]{0,1}:\/\//i', '', $installInfo["DOMAIN"]);
				$tokenInfo["status"] = $installInfo["STATUS"];

				if(
					$installInfo["STATUS"] == ClientVersionInstallTable::STATUS_TRIAL
					|| $installInfo["STATUS"] == ClientVersionInstallTable::STATUS_PAID
				)
				{
					if($installInfo['DATE_FINISH'])
					{
						$tokenInfo["date_finish"] = $installInfo['DATE_FINISH']->getTimestamp();
					}
				}

				unset($tokenInfo["user_id"]);

				return $tokenInfo;
			}
			else
			{
				throw new OAuthException($authParams);
			}
		}
		else
		{
			throw new RestException('Unable to get application data', 'WRONG_APPLICATION_CLIENT');
		}
	}

	public static function restCheck(array $params, $n, \CRestServer $server)
	{
		if(empty($params["TOKEN"]))
		{
			throw new ArgumentNullException("TOKEN");
		}

		$token = $params['TOKEN'];

		$installClient = Base::instance($server->getClientId());

		static::checkInstallClient($params, $installClient);

		$tokenInfo = false;

		try
		{
			$tokenGenerator = new AccessToken();
			$tokenData = $tokenGenerator->getTokenData($token);

			if($tokenData['USER_ID'] == $installClient->getClientId())
			{
				$tokenInfo = array(
					'CLIENT_ID' => $tokenData['CLIENT_ID'],
					'EXPIRES' => $tokenGenerator->getTimestamp(),
					'PARAMETERS' => $tokenData,
				);
			}
		}
		catch(SystemException $e)
		{
			$dbRes = TokenTable::getList(array(
				'filter' => array(
					'=USER_ID' => $installClient->getClientId(),
					'=OAUTH_TOKEN' => $token,
				),
				'select' => array(
					'CLIENT_ID', 'EXPIRES', 'PARAMETERS'
				),
			));

			$tokenInfo = $dbRes->fetch();
		}

		if(!$tokenInfo)
		{
			throw new RestException('Unable to get application by token', 'invalid_token');
		}

		$appClient = Base::instanceById($tokenInfo['CLIENT_ID']);
		if($appClient)
		{
			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
					'=ACTIVE' => ClientVersionInstallTable::ACTIVE,
				),
				'select' => array(
					'VERSION_SCOPE' => 'VERSION.SCOPE',
					'CLIENT_SCOPE' => 'CLIENT.SCOPE',
					'STATUS',
					'IS_TRIALED',
					'DATE_FINISH',
				)
			));

			$installInfo = $dbRes->fetch();
			if(!$installInfo)
			{
				throw new RestException("Application not installed", RestException::ERROR_OAUTH, \CRestServer::STATUS_UNAUTHORIZED);
			}

			$tokenScope = $installInfo['CLIENT_SCOPE'];
			$versionScope = $installInfo['VERSION_SCOPE'];

			$res = $appClient->verifyAccessTokenInternal($token, $tokenScope);

			if(!$res["error"])
			{
				unset($res['parameters']['USER_ID']);
				unset($res['parameters']['CLIENT_ID']);

				$result = array(
					'access_token' => $token,
					'expires_in' => $res['expired'],
					'expires' => $res['expires'],
					'scope' => implode(',', $versionScope),
					'client_id' => $res['client_id'],
					'parameters' => $res['parameters'],
					'status' => $installInfo["STATUS"],
				);

				if(
					$installInfo["STATUS"] == ClientVersionInstallTable::STATUS_TRIAL
					|| $installInfo["STATUS"] == ClientVersionInstallTable::STATUS_PAID
				)
				{
					if($installInfo["DATE_FINISH"])
					{
						/** @var DateTime $dateFinish */
						$dateFinish = $installInfo['DATE_FINISH'];
						$result["date_finish"] = $dateFinish->getTimestamp();
					}
				}

			}
			else
			{
				throw new OAuthException($res);
			}

			return $result;
		}
		else
		{
			throw new RestException('Unable to get application by token', 'invalid_token');
		}
	}

	public function restEventCall(array $params, $n, \CRestServer $server)
	{
		$queryItems = $params['QUERY'];

		if(!is_array($queryItems) || count($queryItems) <= 0)
		{
			throw new ArgumentNullException('QUERY');
		}

		$installClient = Base::instance($server->getClientId());
		$installClientData = $installClient->getClient();

		$installInfo = array();

		$query = array();

		foreach($queryItems as $item)
		{
			$applicationToken = null;

			if(is_array($item['auth']))
			{
				if(array_key_exists('application_token', $item['auth']))
				{
					$applicationToken = $item['auth']['application_token'];
					unset($item['auth']['application_token']);
				}

				// compatibility hack
				if(array_key_exists('EVENT_SESSION', $item['auth']) && is_array($item['auth']['EVENT_SESSION']))
				{
					$item['auth']['EVENT_SESSION'] = $item['auth']['EVENT_SESSION']['TTL'];
				}
			}

			if(isset($item['client_id']))
			{
				$appClient = Base::instance($item["client_id"]);
				if($appClient)
				{
					$tokenInfo = null;
					if(!array_key_exists($item['client_id'], $installInfo))
					{
						$dbRes = ClientVersionInstallTable::getList(array(
							'filter' => array(
								'=CLIENT_ID' => $appClient->getClientId(),
								'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
								'=ACTIVE' => ClientVersionInstallTable::ACTIVE,
							),
							'select' => array(
								'VERSION_SCOPE' => 'VERSION.SCOPE',
								'CLIENT_SCOPE' => 'CLIENT.SCOPE',
								'STATUS',
								'IS_TRIALED',
								'DATE_FINISH',
								'CLIENT_ID', 'INSTALL_CLIENT_ID'
							)
						));

						$installInfo[$item['client_id']] = $dbRes->fetch();
					}

					if($installInfo[$item['client_id']])
					{
						$additionalParams = isset($item["auth"]) ? $item["auth"] : array();

						if($item['additional']['sendAuth'] && $additionalParams['LOCAL_USER'] > 0)
						{
							$tokenScope = $installInfo[$item['client_id']]['CLIENT_SCOPE'];
							if(is_array($tokenScope))
							{
								$tokenScope = implode(',', $tokenScope);
							}

							//TODO: change this to a directly created access_token and refresh_token
							$authParams = $appClient->getAuthorizeParamsInternal(
								$item["client_id"],
								\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE,
								'',
								'',
								$tokenScope,
								$additionalParams,
								$installClient->getClientId()
							);

							if(is_array($authParams) && isset($authParams[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE]))
							{
								$tokenInfo = $appClient->grantAccessTokenInternal(
									$item["client_id"],
									\COAuthConstants::GRANT_TYPE_AUTH_CODE,
									'',
									$authParams[\COAuthConstants::AUTH_RESPONSE_TYPE_AUTH_CODE],
									$tokenScope,
									null,
									'',
									$additionalParams,
									$installClient->getClientId()
								);

								if($tokenInfo["access_token"])
								{
									// compatibility hack. client_endpoint parameter is preferred
									$tokenInfo["domain"] = preg_replace('/^http[s]{0,1}:\/\//i', '', $installClientData['REDIRECT_URI']);
									$tokenInfo["status"] = $installInfo[$item['client_id']]["STATUS"];

									if(
										$installInfo[$item['client_id']]["STATUS"] == ClientVersionInstallTable::STATUS_TRIAL
										|| $installInfo[$item['client_id']]["STATUS"] == ClientVersionInstallTable::STATUS_PAID
									)
									{
										if($installInfo[$item['client_id']]['DATE_FINISH'])
										{
											/** @var DateTime $dateFinish */
											$dateFinish = $installInfo[$item['client_id']]['DATE_FINISH'];
											$tokenInfo["date_finish"] = $dateFinish->getTimestamp();
										}
									}

									if(!$item['additional']['sendRefreshToken'])
									{
										unset($tokenInfo["refresh_token"]);
									}

									$tokenInfo['scope'] = implode(',', $installInfo[$item['client_id']]['VERSION_SCOPE']);
								}
							}
						}

						if(!is_array($tokenInfo))
						{
							$tokenInfo = static::getEventEmptyAuth($installClientData, $applicationToken);
						}

						if(!$tokenInfo["error"])
						{
							if($applicationToken !== null)
							{
								$tokenInfo['application_token'] = $applicationToken;
							}

							$item['query']['QUERY_DATA']['auth'] = $tokenInfo;

							$query[] = Sqs::queryItem($tokenInfo['domain'], $item['query']['QUERY_URL'], $item['query']['QUERY_DATA'], $item['additional']);
						}
					}
					elseif($item['query']['QUERY_DATA']['event'] === 'ONAPPUNINSTALL')
					{
						$tokenInfo = static::getEventEmptyAuth($installClientData, $applicationToken);

						$item['query']['QUERY_DATA']['auth'] = $tokenInfo;

						$query[] = Sqs::queryItem($tokenInfo['domain'], $item['query']['QUERY_URL'], $item['query']['QUERY_DATA'], $item['additional']);
					}
				}
			}
			else
			{
				$tokenInfo = static::getEventEmptyAuth($installClientData, $applicationToken);

				$item['query']['QUERY_DATA']['auth'] = $tokenInfo;

				$query[] = Sqs::queryItem($tokenInfo['domain'], $item['query']['QUERY_URL'], $item['query']['QUERY_DATA'], $item['additional']);
			}
		}

		if(count($query) > 0)
		{
			Sqs::query($query);
		}
	}

	protected static function getEventEmptyAuth($installClientData, $applicationToken)
	{
		$uri = new Uri($installClientData['REDIRECT_URI']);
		$uri->setPath('/rest/');

		$result = array(
			'domain' => $uri->getHost(),
			'client_endpoint' => $uri->getLocator(),
			'server_endpoint' => \CHTTP::URN2URI('/rest/'),
			'member_id' => $installClientData['MEMBER_ID'],
		);

		if($applicationToken !== null)
		{
			$result['application_token'] = $applicationToken;
		}

		return $result;
	}

	/**********************
	 * application scope
	 **********************/

	/**
	 * Registers application
	 *
	 * @param array $params
	 * @param \CRestServer $server
	 */
	public static function applicationAdd(array $params, $n, \CRestServer $server)
	{
		$title = trim($params["TITLE"]);

		if(strlen($title) <= 0)
		{
			throw new ArgumentNullException('TITLE');
		}

		$redirectUri = trim($params["REDIRECT_URI"]);
		if(strlen($redirectUri) > 0)
		{
			if(!static::checkUri($redirectUri))
			{
				throw new ArgumentException('Wrong redirect_uri value', 'REDIRECT_URI');
			}
		}

		$scope = $params["SCOPE"];

		$status = $params["STATUS"];
		$trialPeriod = intval($params["TRIAL_PERIOD"]);
		$public = $params["PUBLIC"] == 1;

		$local = (bool)$params["LOCAL"];

		$ownerClient = Base::instance($server->getAppId());
		$ownerClientData = $ownerClient->getClient();

		if(!$ownerClientData['PUBLISH'])
		{
			$local = true;
		}

		if($local)
		{
			$status = static::STATUS_LOCAL;
			$public = 0;
		}

		if(!in_array($status, array(
			static::STATUS_LOCAL,
			static::STATUS_FREE,
			static::STATUS_PAID,
			static::STATUS_TRIAL,
			static::STATUS_DEMO,
		)))
		{
			throw new ArgumentException("Wrong status", "STATUS");
		}

		static::checkInstallClient($params, $ownerClient);

		if(!is_array($scope))
		{
			$scope = explode(",", $scope);
		}

		$type = ClientTable::TYPE_APPLICATION;

		if(isset($params['CLIENT_ID']) && isset($params['CLIENT_SECRET']))
		{
			$dbRes = ClientTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $params['CLIENT_ID']
				)
			));
			if($dbRes->fetch())
			{
				throw new RestException('Application already exists', 'WRONG_CLIENT_ID');
			}

			$result = array(
				'client_id' => $params['CLIENT_ID'],
				'client_secret' => $params['CLIENT_SECRET'],
				'redirect_uri' => $redirectUri,
				'scope' => implode(',', $scope),
			);
		}
		else
		{
			$result = array(
				'client_id' => uniqid(ClientTable::getClientSuffix($type, $local), true),
				'client_secret' => Random::getString(50, true),
				'redirect_uri' => $redirectUri,
				'scope' => implode(',', $scope),
			);
		}

		$salt = Random::getString(8, true);
		$clientSecretSalted = $salt.md5($salt.$result['client_secret']);

		$res = ClientTable::add(array(
			'CLIENT_ID' => $result['client_id'],
			'CLIENT_SECRET' => $clientSecretSalted,
			'CLIENT_TYPE' => $type,
			'TITLE' => $title,
			'SCOPE' => explode(',', Option::get("oauth", "client_allow_scope_".$type, '')),
			'CLIENT_OWNER_ID' => $ownerClient->getClientId(),
			'UF_STATUS' => $status,
			'UF_TRIAL_PERIOD' => $trialPeriod,
			'UF_PUBLIC' => $public,
		));

		$ok = false;
		if($res->isSuccess())
		{
			if($local)
			{
				$oauthClientId = $res->getId();
				$res = ClientVersionTable::add(array(
					'CLIENT_ID' => $oauthClientId,
					'VERSION' => 1,
					'SCOPE' => $scope,
					'ACTIVE' => ClientVersionTable::ACTIVE,
				));

				if($res->isSuccess())
				{
					$versionId = $res->getId();

					if(array_key_exists("REDIRECT_URI", $params))
					{
						ClientVersionUriTable::add(array(
							'CLIENT_ID' => $oauthClientId,
							'VERSION_ID' => $versionId,
							'REDIRECT_URI' => $result['redirect_uri'],
						));
					}

					if($local)
					{
						ClientVersionInstallTable::add(array(
							'CLIENT_ID' => $oauthClientId,
							'VERSION_ID' => $versionId,
							'INSTALL_CLIENT_ID' => $ownerClient->getClientId(),
							'ACTIVE' => ClientVersionInstallTable::ACTIVE,
							'STATUS' => ClientVersionInstallTable::STATUS_LOCAL,
						));
					}

					$ok = true;
				}
				else
				{
					ClientTable::delete($oauthClientId);
				}
			}
			else
			{
				$ok = true;
			}
		}

		LogTable::add(array(
			'CLIENT_ID' => $res->isSuccess() ? $res->getId() : '',
			'INSTALL_CLIENT_ID' => $ownerClientData['ID'],
			'MESSAGE' => "APPLICATION_ADD",
			'DETAIL' => $params,
			'ERROR' => $res->isSuccess() ? '' : $res->getErrorMessages(),
			'RESULT' => $ok,
		));

		if(!$ok)
		{
			throw new RestException('Error occured while processing your request. Try again later');
		}

		return $result;
	}

	public static function applicationUpdate(array $params, $n, \CRestServer $server)
	{
		if(empty($params["CLIENT_ID"]))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$appClient = Base::instance($params["CLIENT_ID"]);
		$ownerClient = Base::instance($server->getAppId());
		$ownerClientData = $ownerClient->getClient();

		static::checkInstallClient($params, $ownerClient);

		if($appClient)
		{
			$appClientData = $appClient->getClient();
			if($appClientData['CLIENT_OWNER_ID'] !== $ownerClient->getClientId())
			{
				$appClient = false;

				LogTable::add(array(
					'CLIENT_ID' => $appClientData['ID'],
					'INSTALL_CLIENT_ID' => $ownerClientData['ID'],
					'MESSAGE' => "APPLICATION_UPDATE",
					'DETAIL' => $params,
					'ERROR' => 'Wrong owner',
					'RESULT' => 'ERROR!',
				));
			}
		}

		if(!$appClient)
		{
			throw new RestException("Application not found", 'APPLICATION_NOT_FOUND', \CRestServer::STATUS_NOT_FOUND);
		}

		if(array_key_exists("REDIRECT_URI", $params))
		{
			if(strlen($params["REDIRECT_URI"]) > 0)
			{
				if(!static::checkUri($params["REDIRECT_URI"]))
				{
					throw new ArgumentException('Wrong redirect_uri value', 'REDIRECT_URI');
				}
			}
		}

		$updateFields = array();

		if(array_key_exists("TITLE", $params))
		{
			$updateFields["TITLE"] = trim($params["TITLE"]);
		}

		if($ownerClientData['PUBLISH'])
		{
			if(array_key_exists("STATUS", $params))
			{
				if(!in_array($params["STATUS"], array(
					static::STATUS_LOCAL,
					static::STATUS_FREE,
					static::STATUS_PAID,
					static::STATUS_TRIAL,
					static::STATUS_DEMO,
				)))
				{
					throw new ArgumentException("Wrong status", "STATUS");
				}

				$updateFields["UF_STATUS"] = $params['STATUS'];
			}

			if(array_key_exists("TRIAL_PERIOD", $params))
			{
				$updateFields['UF_TRIAL_PERIOD'] = intval($params["TRIAL_PERIOD"]);
			}

			if(array_key_exists("PUBLIC", $params))
			{
				$updateFields['UF_PUBLIC'] = intval($params["PUBLIC"]);
			}
		}

		if(count($updateFields) > 0)
		{
			$updateResult = ClientTable::update($appClient->getClientId(), $updateFields);

			LogTable::add(array(
				'CLIENT_ID' => $updateResult->isSuccess() ? $updateResult->getId() : '',
				'INSTALL_CLIENT_ID' => $ownerClientData['ID'],
				'MESSAGE' => "APPLICATION_UPDATE",
				'DETAIL' => $params,
				'ERROR' => $updateResult->isSuccess() ? '' : $updateResult->getErrorMessages(),
				'RESULT' => $updateResult->isSuccess(),
			));

			if(!$updateResult->isSuccess())
			{
				throw new RestException($updateResult->getErrorMessages(), RestException::ERROR_CORE, \CRestServer::STATUS_INTERNAL);
			}
		}

		if(
			array_key_exists("REDIRECT_URI", $params)
			|| array_key_exists("SCOPE", $params)
		)
		{
			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $ownerClient->getClientId(),
				),
				'select' => array('VERSION_ID')
			));
			$versionInfo = $dbRes->fetch();
			if($versionInfo)
			{
				if(array_key_exists("REDIRECT_URI", $params))
				{
					ClientVersionUriTable::setVersionUri($appClient->getClientId(), $versionInfo["VERSION_ID"], trim($params["REDIRECT_URI"]));
				}

				if(array_key_exists("SCOPE", $params))
				{
					if(!is_array($params["SCOPE"]))
					{
						$params["SCOPE"] = explode(",", $params["SCOPE"]);
					}

					ClientVersionTable::update(
						$versionInfo["VERSION_ID"],
						array('SCOPE' => $params["SCOPE"])
					);
				}
			}
		}

		return true;
	}

	public static function applicationDelete(array $params, $n, \CRestServer $server)
	{
		if(empty($params["CLIENT_ID"]))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$ownerClient = Base::instance($server->getAppId());
		$appClient = Base::instance($params["CLIENT_ID"]);

		$ownerClientData = $ownerClient->getClient();

		static::checkInstallClient($params, $ownerClient);


		if($appClient)
		{
			$appClientData = $appClient->getClient();
			if($appClientData['CLIENT_OWNER_ID'] !== $ownerClient->getClientId())
			{
				LogTable::add(array(
					'CLIENT_ID' => $appClientData['ID'],
					'INSTALL_CLIENT_ID' => $ownerClientData['ID'],
					'MESSAGE' => "APPLICATION_DELETE",
					'DETAIL' => $params,
					'ERROR' => 'Wrong owner',
					'RESULT' => 'ERROR!',
				));

				$appClient = false;
			}
		}

		if(!$appClient)
		{
			throw new RestException("Application not found", RestException::ERROR_NOT_FOUND, \CRestServer::STATUS_NOT_FOUND);
		}

		ClientVersionInstallTable::deleteByClient($appClient->getClientId());
		ClientVersionUriTable::deleteByClient($appClient->getClientId());
		ClientVersionTable::deleteByClient($appClient->getClientId());

		$deleteResult = ClientTable::delete($appClient->getClientId());

		LogTable::add(array(
			'CLIENT_ID' => $appClientData['ID'],
			'INSTALL_CLIENT_ID' => $ownerClientData['ID'],
			'MESSAGE' => "APPLICATION_DELETE",
			'DETAIL' => $params,
			'ERROR' => $deleteResult->getErrorMessages(),
			'RESULT' => $deleteResult->isSuccess(),
		));

		return true;
	}

	public static function applicationList(array $params, $n, \CRestServer $server)
	{
		$installClient = Base::instance($server->getAppId());
		static::checkInstallClient($params, $installClient);

		$result = array();

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
				'!=STATUS' => ClientVersionInstallTable::STATUS_LOCAL,
			),
			'select' => array(
				'APP_CLIENT_ID' => 'CLIENT.CLIENT_ID',
				'CODE' => 'CLIENT.TITLE',
				'VER' => 'VERSION.VERSION',
				'SCOPE' => 'VERSION.SCOPE',
				'STATUS', 'DATE_FINISH', 'ACTIVE'
			)
		));

		while($install = $dbRes->fetch())
		{
			$entry = array(
				'code' => $install['CODE'],
				'client_id' => $install['APP_CLIENT_ID'],
				'version' => $install['VER'],
				'scope' => implode(',', $install['SCOPE']),
				'status' => $install['STATUS'],
				'active' => $install['ACTIVE'] == ClientVersionInstallTable::ACTIVE,
			);

			if($install['DATE_FINISH'])
			{
				$entry['date_finish'] = $install['DATE_FINISH']->getTimestamp();
			}

			$result[] = $entry;
		}

		return $result;
	}

	public static function applicationInstall(array $params, $n, \CRestServer $server)
	{
		$clientId = $params["CLIENT_ID"];
		$clientVersion = intval($params["VERSION"]);
		$active = isset($params['ACTIVE']) && in_array($params['ACTIVE'], array(ClientVersionInstallTable::INACTIVE, ClientVersionInstallTable::ACTIVE))
			? $params['ACTIVE']
			: ClientVersionInstallTable::ACTIVE;

		if(empty($clientId))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		if($clientVersion <= 0)
		{
			throw new ArgumentNullException("VERSION");
		}

		$installClient = Base::instance($server->getAppId());
		$appClient = Base::instance($clientId);

		if(!$appClient || $appClient->getClientType() !== ClientTable::TYPE_APPLICATION)
		{
			throw new RestException("Application not found", RestException::ERROR_NOT_FOUND, \CRestServer::STATUS_NOT_FOUND);
		}

		static::checkInstallClient($params, $installClient);

		$installClientInfo = $installClient->getClient();
		$appClientInfo = $appClient->getClient();

		if($appClientInfo['CLIENT_OWNER_ID'] != $installClientInfo['ID'])
		{
			if($appClientInfo['CLIENT_OWNER_ID'] > 0)
			{
				$ownerCanPublish = false;

				$ownerClient = Base::instanceById($appClientInfo['CLIENT_OWNER_ID']);
				if($ownerClient)
				{
					$ownerClientData = $ownerClient->getClient();
					$ownerCanPublish = $ownerClientData['PUBLISH'];
				}

				if(!$ownerCanPublish)
				{
					LogTable::add(array(
						'CLIENT_ID' => $appClientInfo['ID'],
						'INSTALL_CLIENT_ID' => $installClientInfo['ID'],
						'MESSAGE' => "APPLICATION_INSTALL",
						'DETAIL' => $params,
						'ERROR' => 'Wrong owner',
						'RESULT' => 0,
					));

					throw new AccessException('Application owner check failed');
				}
			}
		}

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $appClient->getClientId(),
				'=INSTALL_CLIENT_ID' => $installClient->getClientId()
			),
			'select' => array('ID', 'INSTALLED_VERSION' => 'VERSION.VERSION')
		));
		$installInfo = $dbRes->fetch();

		$versionFilter = array(
			'=CLIENT_ID' => $appClient->getClientId(),
			'=VERSION' => $clientVersion,
			'=ACTIVE' => ClientVersionTable::ACTIVE,
		);

		if(
			isset($params["CHECK_HASH"]) && isset($params["INSTALL_HASH"])
			|| $installInfo && $installInfo['INSTALLED_VERSION'] === $clientVersion
			|| $clientId === 'app.552d288cc83c88.78059741'
		)
		{
			unset($versionFilter['=ACTIVE']);
		}

		$dbRes = ClientVersionTable::getList(array(
			'filter' => $versionFilter,
			'select' => array('ID', 'SCOPE', 'ACTIVE', 'VERSION')
		));

		$versionInfo = $dbRes->fetch();
		if(!$versionInfo)
		{
			throw new RestException("Application version not found", RestException::ERROR_NOT_FOUND, \CRestServer::STATUS_NOT_FOUND);
		}

		if(
			$clientId !== 'app.552d288cc83c88.78059741'
			&& (
				$versionInfo['ACTIVE'] !== ClientVersionTable::ACTIVE
				|| $appClientInfo['PUBLIC'] != 1
			)
			&& (
				!$installInfo
				|| $installInfo['INSTALLED_VERSION'] != $clientVersion
			)
			&& !static::checkInstallHash(
				$installClient,
				$appClient,
				$versionInfo,
				$params["CHECK_HASH"],
				$params["INSTALL_HASH"]
			)
		)
		{
			LogTable::add(array(
				'CLIENT_ID' => $appClientInfo['ID'],
				'INSTALL_CLIENT_ID' => $installClientInfo['ID'],
				'MESSAGE' => "APPLICATION_INSTALL",
				'DETAIL' => $params,
				'ERROR' => 'Install hash check failed',
				'RESULT' => 0,
			));

			throw new RestException("Application version not found", RestException::ERROR_NOT_FOUND, \CRestServer::STATUS_NOT_FOUND);
		}


		if($installInfo)
		{
			$result = ClientVersionInstallTable::update($installInfo['ID'], array(
				'VERSION_ID' => $versionInfo['ID'],
				'ACTIVE' => $active,
			));
		}
		else
		{
			$installFields = array(
				'CLIENT_ID' => $appClient->getClientId(),
				'VERSION_ID' => $versionInfo['ID'],
				'INSTALL_CLIENT_ID' => $installClient->getClientId(),
				'ACTIVE' => $active,
				'STATUS' => $appClientInfo["STATUS"]
			);

			if($installFields['STATUS'] == ClientVersionInstallTable::STATUS_PAID)
			{
				if(isset($params["CHECK_HASH"]) && isset($params["INSTALL_HASH"]))
				{
					$installFields['IS_TRIALED'] = ClientVersionInstallTable::TRIALED;
					$installFields['DATE_FINISH'] = new DateTime();
					$installFields['DATE_FINISH']->add('14D');
				}
				else
				{
					throw new RestException("Unable to install paid application without payment", "PAYMENT_REQUIRED", \CRestServer::STATUS_PAYMENT_REQUIRED);
				}
			}

			if($installFields['STATUS'] === ClientVersionInstallTable::STATUS_TRIAL)
			{
				$installFields['IS_TRIALED'] = ClientVersionInstallTable::TRIALED;
				$installFields['DATE_FINISH'] = new DateTime();
				$installFields['DATE_FINISH']->add(intval($appClientInfo['TRIAL_PERIOD']).'D');
			}

			$result = ClientVersionInstallTable::add($installFields);
		}

		if(!$result->isSuccess())
		{
			LogTable::add(array(
				'CLIENT_ID' => $appClientInfo['ID'],
				'INSTALL_CLIENT_ID' => $installClientInfo['ID'],
				'MESSAGE' => "APPLICATION_INSTALL",
				'DETAIL' => $params,
				'ERROR' => $result->getErrorMessages(),
				'RESULT' => 0,
			));

			throw new RestException(implode('; ', $result->getErrorMessages()), RestException::ERROR_CORE, \CRestServer::STATUS_INTERNAL);
		}

		$dbRes = ClientVersionInstallTable::getById($result->getId());
		$installInfo = $dbRes->fetch();

		$result = array(
			'client_id' => $clientId,
			'version' => $clientVersion,
			'scope' => $versionInfo['SCOPE'],
			'status' => $installInfo['STATUS'],
			'date_finish' => $installInfo['DATE_FINISH'] ? $installInfo['DATE_FINISH']->getTimestamp() : '',
		);

		LogTable::add(array(
			'CLIENT_ID' => $appClientInfo['ID'],
			'INSTALL_CLIENT_ID' => $installClientInfo['ID'],
			'MESSAGE' => "APPLICATION_INSTALL",
			'DETAIL' => $params,
			'ERROR' => '',
			'RESULT' => 1,
		));

		return $result;
	}

	public static function applicationUninstall(array $params, $n, \CRestServer $server)
	{
		$clientId = $params["CLIENT_ID"];

		if(empty($clientId))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$installClient = Base::instance($server->getAppId());
		$appClient = Base::instance($clientId);

		if(!$appClient || $appClient->getClientType() !== ClientTable::TYPE_APPLICATION)
		{
			throw new RestException("Application not found", RestException::ERROR_NOT_FOUND, \CRestServer::STATUS_NOT_FOUND);
		}

		static::checkInstallClient($params, $installClient);

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $appClient->getClientId(),
				'=INSTALL_CLIENT_ID' => $installClient->getClientId()
			),
			'select' => array('ID')
		));
		$installInfo = $dbRes->fetch();

		if($installInfo)
		{
			$result = ClientVersionInstallTable::update($installInfo['ID'], array(
				'ACTIVE' => ClientVersionInstallTable::INACTIVE,
			));

			if(!$result->isSuccess())
			{
				throw new RestException(implode('; ', $result->getErrorMessages()), RestException::ERROR_CORE, \CRestServer::STATUS_INTERNAL);
			}
		}

		$appClientInfo = $appClient->getClient();
		$installClientInfo = $installClient->getClient();

		LogTable::add(array(
			'CLIENT_ID' => $appClientInfo['ID'],
			'INSTALL_CLIENT_ID' => $installClientInfo['ID'],
			'MESSAGE' => "APPLICATION_UNINSTALL",
			'DETAIL' => $params,
			'ERROR' => '',
			'RESULT' => 1,
		));

		return true;
	}

	public static function applicationStat(array $params, $nav, \CRestServer $server)
	{
		$appClient = null;

		$showXmlId = false;

		$client = Base::instance($server->getAppId());
		if($client->getClientType() === ClientTable::TYPE_APPLICATION)
		{
			$appClient = $client;
		}
		elseif($client->getClientType() === ClientTable::TYPE_BITRIX)
		{
			if(empty($params['CLIENT_ID']))
			{
				throw new ArgumentNullException("CLIENT_ID");
			}

			$appClient = Base::instance($params['CLIENT_ID']);

			if(!$appClient)
			{
				throw new RestException("Application not found", 'APPLICATION_NOT_FOUND', \CRestServer::STATUS_NOT_FOUND);
			}

			$appClientInfo = $appClient->getClient();

			if($appClientInfo['CLIENT_OWNER_ID'] !== $client->getClientId())
			{
				throw new AccessException('Application owner check failed');
			}

			$ownerClientInfo = $client->getClient();
			$showXmlId = $ownerClientInfo['PUBLISH'] == 1;
		}
		else
		{
			throw new RestException("Method call not supported by client type", "METHOD_NOT_SUPPORTED");
		}

		$filter = array();

		if(array_key_exists('FILTER', $params) && is_array($params['FILTER']))
		{
			$filter = static::sanitizeStatsFilter($params['FILTER'], $showXmlId);
		}

		$filter['=CLIENT_ID'] = $appClient->getClientId();

		$select = array(
			'CREATED', 'CHANGED', 'ACTIVE', 'STATUS', 'DATE_FINISH',
			'DOMAIN' => 'INSTALL_CLIENT.TITLE',
			'INSTALL_VERSION' => 'VERSION.VERSION',
			'MEMBER_ID' => 'INSTALL_CLIENT.UF_MEMBER_ID',
		);

		if($showXmlId)
		{
			$select['CLIENT_EXTERNAL_ID'] = 'INSTALL_CLIENT.UF_EXTERNAL_ID';
		}

		$navParams = self::getNavData($nav, true);

		$dbRes = ClientVersionInstallTable::getList(array(
			'order' => array('CREATED' => 'DESC'),
			'filter' => $filter,
			'select' => $select,
			'count_total' => true,
			'limit' => $navParams['limit'],
			'offset' => $navParams['offset'],
		));

		$result = array();
		while($installInfo = $dbRes->fetch())
		{
			$row = array(
				'CLIENT' => $installInfo['DOMAIN'],
				'MEMBER_ID' => $installInfo['MEMBER_ID'],
				'VERSION' => $installInfo['INSTALL_VERSION'],
				'INSTALLED' => $installInfo['ACTIVE'] == ClientVersionInstallTable::ACTIVE,
				'STATUS' => $installInfo['STATUS'],
				'DATE_FINISH' => $installInfo['DATE_FINISH'] ? \CRestUtil::ConvertDate($installInfo['DATE_FINISH']->toString()) : '',
				'DATE_INSTALL' => $installInfo['CREATED'] ? \CRestUtil::ConvertDateTime($installInfo['CREATED']->toString()) : '',
				'DATE_CHANGE' => $installInfo['CHANGED'] ? \CRestUtil::ConvertDateTime($installInfo['CHANGED']->toString()) : '',
			);

			if($showXmlId)
			{
				list($row['INSTALL_CLIENT_TYPE'], $row['INSTALL_CLIENT_ID']) = explode('_', $installInfo['CLIENT_EXTERNAL_ID']);
			}

			$result[] = $row;
		}

		return self::setNavData(
			$result,
			array(
				"count" => $dbRes->getCount(),
				"offset" => $navParams['offset']
			)
		);
	}

	public static function applicationStatCount(array $params, $n, \CRestServer $server)
	{
		$lastToken = intval($params['ts']);
		$newLastToken = time();

		$ownerClient = Base::instance($server->getAppId());

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=CLIENT.CLIENT_OWNER_ID' => $ownerClient->getClientId(),
				'>=CHANGED' => DateTime::createFromTimestamp($lastToken),
			),
			'group' => array(
				'CLIENT_ID',
			),
			'select' => array(
				'CLIENT_ID',
				new ExpressionField('CNT', 'COUNT(*)')
			),
		));

		$clientList = array();
		while($install = $dbRes->fetch())
		{
			$clientList[] = $install['CLIENT_ID'];
		}

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=CLIENT.CLIENT_OWNER_ID' => $ownerClient->getClientId(),
				'@CLIENT_ID' => $clientList,
			),
			'group' => array(
				'CLIENT_ID',
			),
			'select' => array(
				'CLIENT_CLIENT_ID' => 'CLIENT.CLIENT_ID', 'CODE' => 'CLIENT.TITLE',
				new ExpressionField('CNT', 'COUNT(*)')
			),
		));

		$counters = array();
		while($client = $dbRes->fetch())
		{
			$counters[] = array(
				'client_id' => $client['CLIENT_CLIENT_ID'],
				'code' => $client['CODE'],
				'count' => $client['CNT'],
			);
		}

		return array(
			'counter' => $counters,
			'ts' => $newLastToken
		);
	}

	public static function applicationStatEx(array $params, $nav, \CRestServer $server)
	{
		$ownerClient = Base::instance($server->getClientId());

		$ownerClientData = $ownerClient->getClient();
		if(!$ownerClientData['PUBLISH'])
		{
			throw new AccessException('Only publisher can use this method');
		}

		if(empty($params['FILTER']) || !is_array($params['FILTER']))
		{
			throw new ArgumentNullException('FILTER');
		}

		$filter = static::sanitizeStatsFilter($params['FILTER'], true);

		$filter['=CLIENT.CLIENT_OWNER_ID'] = $ownerClient->getClientId();

		if(isset($params['FILTER']['PUBLIC']))
		{
			$filter['=CLIENT.UF_PUBLIC'] = $params['FILTER']['PUBLIC'] == 1;
		}

		if(!empty($params['FILTER']['CLIENT_ID']))
		{
			$filter['CLIENT.CLIENT_ID'] = $params['FILTER']['CLIENT_ID'];
		}

		if(isset($params['FILTER']['EXTERNAL_ID']) && is_array($params['FILTER']['EXTERNAL_ID']))
		{
			$filter['@INSTALL_CLIENT.UF_EXTERNAL_ID'] = $params['FILTER']['EXTERNAL_ID'];
		}

		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => $filter,
			'select' => array(
				'CREATED', 'CHANGED', 'DATE_FINISH', 'ACTIVE', 'STATUS',
				'CLIENT_CODE' => 'CLIENT.TITLE',
				'CLIENT_CLIENT_ID' => 'CLIENT.CLIENT_ID',
				'INSTALL_CLIENT_DOMAIN' => 'INSTALL_CLIENT.TITLE',
				'INSTALL_CLIENT_URI' => 'INSTALL_CLIENT.REDIRECT_URI',
				'INSTALL_CLIENT_XML_ID' => 'INSTALL_CLIENT.UF_EXTERNAL_ID',
			)
		));

		$result = array();
		while($row = $dbRes->fetch())
		{
			$result[] = array(
				'CLIENT' => $row['INSTALL_CLIENT_DOMAIN'],
				'CLIENT_URL' => $row['INSTALL_CLIENT_URI'],
				'CLIENT_XML_ID' => $row['INSTALL_CLIENT_XML_ID'],
				'APP_CODE' => $row['CLIENT_CODE'],
				'APP_ID' => $row['CLIENT_CLIENT_ID'],
				'ACTIVE' => $row['ACTIVE'],
				'STATUS' => $row['STATUS'],
				'DATE_INSTALL' => $row['CREATED'] ? $row['CREATED']->getTimestamp() : '',
				'DATE_CHANGE' => $row['CREATED'] ? $row['CHANGED']->getTimestamp() : '',
				'DATE_FINISH' => $row['DATE_FINISH'] ? $row['DATE_FINISH']->getTimestamp() : '',
			);
		}

		return $result;
	}


	public static function applicationStatus(array $params, $n, \CRestServer $server)
	{
		$clientId = $params["CLIENT_ID"];
		$installClientType = $params['INSTALL_CLIENT_TYPE'];
		$installClientExternalId = $params['INSTALL_CLIENT_ID'];

		$installStatus = $params['STATUS'];

		if(empty($clientId))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		if(!in_array($installStatus, array(
			ClientVersionInstallTable::STATUS_PAID,
			ClientVersionInstallTable::STATUS_TRIAL,
			ClientVersionInstallTable::STATUS_DEMO,
			ClientVersionInstallTable::STATUS_FREE,
		)))
		{
			throw new ArgumentException("Wrong client type value", "STATUS");
		}

		if(empty($installClientExternalId))
		{
			throw new ArgumentNullException("INSTALL_CLIENT_ID");
		}

		if(!in_array($installClientType, array(LicenseVerify::TYPE_BITRIX24, LicenseVerify::TYPE_CP)))
		{
			throw new ArgumentException("Wrong client type value", "INSTALL_CLIENT_TYPE");
		}

		if(!empty($params['DATE_FINISH']))
		{
			$installDateFinish = DateTime::createFromTimestamp(strtotime($params['DATE_FINISH']));
		}
		else
		{
			$installDateFinish = '';
		}

		$ownerClient = Base::instance($server->getAppId());
		$ownerClientData = $ownerClient->getClient();
		if(!$ownerClientData['PUBLISH'])
		{
			throw new AccessException('Only publisher can change installation status');
		}

		$appClient = Base::instance($clientId);
		if(!$appClient)
		{
			throw new RestException("Application not found", 'APPLICATION_NOT_FOUND', \CRestServer::STATUS_NOT_FOUND);
		}

		$appClientData = $appClient->getClient();

		if($appClientData['CLIENT_OWNER_ID'] !== $ownerClient->getClientId())
		{
			throw new AccessException('Application owner check failed');
		}

		$dbClient = ClientTable::getList(array(
			'filter' => array(
				'=CLIENT_TYPE' => ClientTable::TYPE_BITRIX,
				'=UF_EXTERNAL_ID' => $installClientType."_".$installClientExternalId,
			),
			'select' => array('ID')
		));

		$clientFound = false;

		while($res = $dbClient->fetch())
		{
			$clientFound = true;

			$installClient = Base::instanceById($res['ID']);

			$dbRes = ClientVersionInstallTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=INSTALL_CLIENT_ID' => $installClient->getClientId(),
				),
				'select' => array('ID')
			));
			$installInfo = $dbRes->fetch();

			if($installInfo)
			{
				$result = ClientVersionInstallTable::update($installInfo['ID'], array(
					'STATUS' => $installStatus,
					'DATE_FINISH' => $installDateFinish,
					'IS_TRIALED' => ClientVersionInstallTable::TRIALED,
				));
			}
			else
			{
				$lastVersion = ClientVersionTable::getLastVersion($appClient->getClientId());

				if(!$lastVersion)
				{
					throw new RestException("Application has no versions", "WRONG_APPLICATION");
				}

				$result = ClientVersionInstallTable::add(array(
					'CLIENT_ID' => $appClient->getClientId(),
					'VERSION_ID' => $lastVersion['ID'],
					'INSTALL_CLIENT_ID' => $installClient->getClientId(),
					'ACTIVE' => ClientVersionInstallTable::INACTIVE,
					'STATUS' => $installStatus,
					'DATE_FINISH' => $installDateFinish,
					'IS_TRIALED' => ClientVersionInstallTable::TRIALED,
				));
			}

			if(!$result->isSuccess())
			{
				LogTable::add(array(
					'CLIENT_ID' => $appClient->getClientId(),
					'INSTALL_CLIENT_ID' => $installClient->getClientId(),
					'MESSAGE' => "APPLICATION_SET_STATUS",
					'DETAIL' => $params,
					'ERROR' => $result->getErrorMessages(),
					'RESULT' => 0,
				));

				throw new RestException(implode(';', $result->getErrorMessages()), \CRestServer::STATUS_INTERNAL);
			}

			LogTable::add(array(
				'CLIENT_ID' => $appClient->getClientId(),
				'INSTALL_CLIENT_ID' => $installClient->getClientId(),
				'MESSAGE' => "APPLICATION_SET_STATUS",
				'DETAIL' => $params,
				'ERROR' => '',
				'RESULT' => 1,
			));
		}

		if(!$clientFound)
		{
			throw new RestException("Install client not found", "INSTALL_CLIENT_NOT_FOUND");
		}

		return true;
	}

	public static function applicationVersionAdd(array $params, $n, \CRestServer $server)
	{
		if(empty($params["CLIENT_ID"]))
		{
			throw new ArgumentNullException("CLIENT_ID");
		}

		$ownerClient = Base::instance($server->getAppId());
		$ownerClientData = $ownerClient->getClient();

		if(!$ownerClientData['PUBLISH'])
		{
			throw new AccessException("Only publishers can manage versions");
		}

		$appClient = Base::instance($params["CLIENT_ID"]);

		if($appClient)
		{
			$appClientData = $appClient->getClient();
			if($appClientData['CLIENT_OWNER_ID'] !== $ownerClient->getClientId())
			{
				$appClient = false;
			}
		}

		if(!$appClient)
		{
			throw new RestException("Application not found", 'APPLICATION_NOT_FOUND', \CRestServer::STATUS_NOT_FOUND);
		}

		$version = intval($params['VERSION']);

		if($version <= 0)
		{
			throw new ArgumentNullException("VERSION");
		}

		$scope = $params['SCOPE'];
		if(empty($scope))
		{
			$scope = array();
		}
		elseif(!is_array($scope))
		{
			$scope = explode(",", $scope);
		}

		$redirectUri = trim($params['REDIRECT_URI']);
		if(strlen($redirectUri) > 0)
		{
			if(!static::checkUri($redirectUri))
			{
				throw new ArgumentException('Wrong redirect_uri value', 'REDIRECT_URI');
			}
		}

		$dbRes = ClientVersionTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $appClient->getClientId(),
				'=VERSION' => $version,
			),
			'select' => array('ID', 'ACTIVE')
		));
		$existingVersion = $dbRes->fetch();

		if($existingVersion)
		{
			$active = isset($params['ACTIVE']) ? (bool)$params['ACTIVE'] : $existingVersion['ACTIVE'] == ClientVersionTable::ACTIVE;

			$versionFields = array(
				'SCOPE' => $scope,
				'ACTIVE' => $active ? ClientVersionTable::ACTIVE : ClientVersionTable::INACTIVE,
			);

			$result = ClientVersionTable::update($existingVersion['ID'], $versionFields);
		}
		else
		{
			$active = isset($params['ACTIVE']) ? (bool)$params['ACTIVE'] : false;

			$versionFields = array(
				'CLIENT_ID' => $appClient->getClientId(),
				'VERSION' => $version,
				'SCOPE' => $scope,
				'ACTIVE' => $active ? ClientVersionTable::ACTIVE : ClientVersionTable::INACTIVE,
			);

			$result = ClientVersionTable::add($versionFields);
		}

		if(!$result->isSuccess())
		{
			throw new RestException(implode("\n", $result->getErrorMessages()), RestException::ERROR_CORE);
		}

		$versionId = $result->getId();

		if($existingVersion)
		{
			$dbRes = ClientVersionUriTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $appClient->getClientId(),
					'=VERSION_ID' => $versionId,
				),
				'select' => array('ID', 'REDIRECT_URI')
			));
			$existingUri = $dbRes->fetch();
		}
		else
		{
			$existingUri = false;
		}

		if($existingUri)
		{
			if($existingUri['REDIRECT_URI'] !== $redirectUri)
			{
				ClientVersionUriTable::update($existingUri['ID'], array('REDIRECT_URI' => $redirectUri));
			}
		}
		else
		{
			ClientVersionUriTable::add(array(
				'CLIENT_ID' => $appClient->getClientId(),
				'VERSION_ID' => $versionId,
				'REDIRECT_URI' => $redirectUri
			));
		}

		return true;
	}

	protected static function checkUri($uri)
	{
		$uriInfo = parse_url($uri);

		return is_array($uriInfo)
			&& ($uriInfo['scheme'] === 'http' || $uriInfo['scheme'] === 'https')
			&& !empty($uriInfo['host']);
	}

	protected static function checkInstallHash(Base $installClient, Application $appClient, array $versionInfo, $checkHash, $installHash)
	{
		$installClientData = $installClient->getClient();
		$appClientData = $appClient->getClient();

		$installHost = 'http://'.$installClientData['TITLE'];
		$check = md5($installHost.'|'.$versionInfo['VERSION'].'|'.$appClientData["TITLE"]);
		if($check !== $checkHash)
		{
			$installHost = 'https://'.$installClientData['TITLE'];
			$check = md5($installHost.'|'.$versionInfo['VERSION'].'|'.$appClientData["TITLE"]);
			if($check !== $checkHash)
			{
				return false;
			}
		}

		$check = static::bxSign($appClientData['CLIENT_ID'].'|'.$checkHash);
		if($check !== $installHash)
		{
			return false;
		}

		return true;
	}

	protected static function sanitizeStatsFilter(array $filter, $showXmlId = false)
	{
		$resultFilter = array();

		$allowedFields = array(
			'DATE_INSTALL' => 'CREATED',
			'DATE_CHANGE' => 'CHANGED',
			'DATE_FINISH' => 'DATE_FINISH',
			'CLIENT' => 'INSTALL_CLIENT.TITLE',
			'STATUS' => 'STATUS',
			'ACTIVE' => 'ACTIVE',
			'VERSION' => 'VERSION.VERSION',
		);

		if($showXmlId)
		{
			$allowedFields['EXTERNAL_ID'] = 'INSTALL_CLIENT.UF_EXTERNAL_ID';
		}

		foreach($filter as $key => $value)
		{
			if(!is_array($value))
			{
				if(preg_match('/^([^A-Z_]*)([A-Z_]+)/i', $key, $matches))
				{
					$operation = $matches[1];
					$field = $matches[2];

					if(array_key_exists($field, $allowedFields))
					{
						if(!in_array($operation, array('%', '<', '>', '<=', '>=', '!=')))
						{
							if(strpos($value, '%') !== false)
							{
								$operation = '';
							}
							else
							{
								$operation = '=';
							}
						}

						$filterValue = null;
						switch($allowedFields[$field])
						{
							case 'CREATED':
							case 'CHANGED':
							case 'DATE_FINISH':
								$filterValue = DateTime::createFromTimestamp(strtotime($value));

								break;

							case 'VERSION':
								$filterValue = intval($value);

								break;

							case 'STATUS':
								$filterValue = in_array($value, array(
									ClientVersionInstallTable::STATUS_LOCAL,
									ClientVersionInstallTable::STATUS_PAID,
									ClientVersionInstallTable::STATUS_TRIAL,
									ClientVersionInstallTable::STATUS_DEMO,
									ClientVersionInstallTable::STATUS_FREE,
								)) ? $value : null;

								break;

							case 'ACTIVE':
								$filterValue = in_array($value, array(
									ClientVersionInstallTable::INACTIVE,
									ClientVersionInstallTable::ACTIVE,
								)) ? $value : null;

								break;

							default:
								$filterValue = $value;
						}

						if($filterValue !== null)
						{
							$resultFilter[$operation.$allowedFields[$field]] = $filterValue;
						}
					}
				}
			}
		}

		return $resultFilter;
	}

	protected static function checkInstallClient(array $params, Base $installClient)
	{
		if(
			array_key_exists('client_redirect_uri', $params)
			&& !empty($params['client_redirect_uri'])
			&& $installClient instanceof Bitrix
		)
		{
			$installClientData = $installClient->getClient();

			if($installClientData['REDIRECT_URI'] != $params['client_redirect_uri'])
			{
				$uriInfo = parse_url($params['client_redirect_uri']);
				if(is_array($uriInfo) && !empty($uriInfo['host']))
				{
					ClientTable::update($installClient->getClientId(), array(
						'TITLE' => $uriInfo['host'],
						'REDIRECT_URI' => $params['client_redirect_uri']
					));
				}
			}
		}
	}

	protected static function bxSign($str)
	{
		if(!function_exists('bx_sign'))
		{
			return md5(md5(str_rot13($str)));
		}
		else
		{
			return bx_sign($str);
		}
	}
}