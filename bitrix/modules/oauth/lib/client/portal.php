<?php
namespace Bitrix\OAuth\Client;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\OAuth\ClientProfileTable;
use Bitrix\OAuth\Base;
use Bitrix\OAuth\ClientTable;

Loc::loadMessages(__FILE__);

class Portal extends Base
{
	public function allowJoin()
	{
		$client = $this->getClient();
		return (bool) $client["REGISTER"];
	}

	public function checkPublicJoin()
	{
		$client = $this->getClient();

		return parent::checkPublicJoin()
			&& (
				$client["REGISTER_SECRET"] == ''
				|| $this->checkPublicJoinToken($client['REGISTER_SECRET'])
		);
	}

	public function getClientData($clientId)
	{
		$dbOAuthApp = ClientTable::getList(array(
			'filter' => array(
				"=CLIENT_ID" => $clientId
			),
			'select' => array(
				'*',
				'REGISTER' => 'UF_REGISTER',
				'REGISTER_TEXT' => 'UF_REGISTER_TEXT',
				'REGISTER_SECRET' => 'UF_REGISTER_SECRET',
			)
		));
		return $dbOAuthApp->fetch();
	}

	public function check()
	{
		global $USER;

		$message = parent::check();

		if($message === true)
		{
			$request = Context::getCurrent()->getRequest();
			$client = $this->getClient();

			$skipRedirect = isset($request['skip_redirect']) && $request['skip_redirect'] == '1';
			$dbRes = ClientProfileTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $client['ID'],
					'=USER_ID' => $USER->GetID(),
				),
				'select' => array('ID', 'CLIENT_PROFILE_ID', 'CLIENT_PROFILE_ACTIVE', 'ACCEPTED', 'CONFIRM_CODE'),
			));

			$userAuthInfo = Loader::includeModule("b24network")
				? \CB24Network::formatAuthInfo($USER->GetID())
				: array();

			$profileData = $dbRes->fetch();
			if($profileData)
			{
				if($profileData['CLIENT_PROFILE_ACTIVE'] === ClientProfileTable::INACTIVE)
				{
					if($this->checkPublicJoin() && $profileData["CONFIRM_CODE"] != '' && $profileData["ACCEPTED"] === ClientProfileTable::ACCEPTED)
					{
						$message = array(
							"TYPE" => "PLAIN",
							"MESSAGE" => Loc::getMessage("OAUTH_ERROR_PROFILE_NOT_CONFIRMED".$userAuthInfo["suffix"],
								array_merge(array(
									'#APP_ID#' => $client["TITLE"],
									"#USER#" => $USER->GetLogin()
								), $userAuthInfo)
							),
							"OAUTH_TITLE" => Loc::getMessage('OAUTH_TITLE'),
							"OAUTH_REGISTER_TEXT" => $client["REGISTER_TEXT"],
							"OAUTH_STOP" => "Y",
						);
					}
					else
					{
						$message = array(
							"TYPE" => "PLAIN",
							"MESSAGE" => Loc::getMessage("OAUTH_ERROR_PROFILE_INACTIVE",
								array(
									'#APP_ID#' => $client["TITLE"],
									"#USER#" => $USER->GetLogin()
								)
							),
							"OAUTH_TITLE" => Loc::getMessage('OAUTH_TITLE'),
							"OAUTH_REGISTER_TEXT" => $client["REGISTER_TEXT"],
							"OAUTH_LOGOUT" => "Y",
						);
					}
				}
				elseif($profileData['ACCEPTED'] === ClientProfileTable::NOT_ACCEPTED)
				{
					$message = array(
						"TYPE" => "PLAIN",
						"MESSAGE" => Loc::getMessage("OAUTH_ERROR_PROFILE_NOT_ACCEPTED",
							array(
								'#APP_ID#' => $client["TITLE"],
								"#USER#" => $USER->GetLogin(),
							)
						),
						"OAUTH_TITLE" => Loc::getMessage('OAUTH_TITLE'),
						"OAUTH_REGISTER_TEXT" => $client["REGISTER_TEXT"],
						"OAUTH_LOGOUT" => "Y",
					);
				}
				elseif($skipRedirect)
				{
					$message = array(
						"TYPE" => "ERROR",
						"MESSAGE" => Loc::getMessage("OAUTH_ERROR_SKIP_REDIRECT",
							array(
								'#APP_ID#' => $client["TITLE"],
								"#USER#" => $USER->GetLogin(),
								"#SUPPORT_URL#" => defined('B24NET_URL_SUPPORT') ? B24NET_URL_SUPPORT : "/passport24/",
							)
						),
						"OAUTH_TITLE" => Loc::getMessage('OAUTH_TITLE'),
						"OAUTH_REGISTER_TEXT" => $client["REGISTER_TEXT"],
					);
				}
			}
			else
			{
				$confirmed = \CB24Network::IsConfirmed();

				$messageAdvice = '';
				if($USER->GetEmail())
				{
					$messageAdvice = \CB24Network::authorizationAdvice($USER->GetEmail(), $this);
					if($messageAdvice)
					{
						$messageAdvice = '<br /><br />'.$messageAdvice;
					}
				}

				$message = array(
					"TYPE" => "PLAIN",
					"MESSAGE" => Loc::getMessage(
						(
							\CB24Network::IsConfirmed()
								? (
									$this->checkPublicJoin()
										? "OAUTH_ERROR_PROFILE_NOT_FOUND_JOIN"
										: "OAUTH_ERROR_PROFILE_NOT_FOUND"
								)
								: "OAUTH_ERROR_UNCONFIRMED"
						).$userAuthInfo["suffix"],
						array_merge(array(
							'#APP_ID#' => $client["TITLE"],
							'#USER_NAME#' => $USER->GetFormattedName(false, true),
							'#USER_EMAIL#' => $USER->GetLogin(),
						), $userAuthInfo)
					).$messageAdvice,
					"OAUTH_TITLE" => Loc::getMessage('OAUTH_TITLE'),
					"OAUTH_REGISTER_TEXT" => $client["REGISTER_TEXT"],
					"OAUTH_LOGOUT" => "Y",
					"OAUTH_REGISTER" => $confirmed && $this->checkPublicJoin(),
				);
			}

			if($message === true && is_array($profileData))
			{
				ClientProfileTable::update($profileData["ID"], array(
					'LAST_AUTHORIZE' => new DateTime(),
				));
			}
		}

		return $message;
	}

	protected function validateClient(array $result, array $client = array())
	{
		if($result['user_id'] > 0)
		{
			if(
				self::checkUserProfile($result['client_id'], $result['user_id']) === false
				&& !$this->allowJoin()
			)
			{
				return array(
					"ERROR_CODE" => \COAuthConstants::ERROR_USER_DENIED,
					"ERROR_MESSAGE" => "Link required."
				);
			}
		}

		return true;
	}

	protected function validateToken(array $tokenInfo, array $client = array())
	{
		$profileId = self::checkUserProfile($client["CLIENT_ID"], $tokenInfo["user_id"]);

		if($profileId === false && !$this->allowJoin())
		{
			return array(
				"ERROR_STATUS" => \COAuthConstants::HTTP_FORBIDDEN,
				"ERROR_CODE" => \COAuthConstants::ERROR_USER_DENIED,
				"ERROR_MESSAGE" => "Link required."
			);
		}

		$tokenInfo["profile"] = $profileId;

		return $tokenInfo;
	}

	private static function checkUserProfile($clientId, $userId)
	{
		$dbRes = ClientProfileTable::getList(array(
			'filter' => array(
				'USER_ID' => $userId,
				'CLIENT.CLIENT_ID' => $clientId,
				'CLIENT_PROFILE_ACTIVE' => ClientProfileTable::ACTIVE,
				'ACCEPTED' => ClientProfileTable::ACCEPTED,
			),
			'select' => array('CLIENT_PROFILE_ID'),
		));
		$res = $dbRes->fetch();
		if($res)
		{
			return $res['CLIENT_PROFILE_ID'];
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $is_authorized
	 * @param array $params
	 * @return mixed
	 */
	protected function getAuthorizationParams($is_authorized, $params = array())
	{
		$authParams = parent::getAuthorizationParams($is_authorized, $params);
		$authParams["query"]["user_lang"] = LANGUAGE_ID;

		return $authParams;
	}
}