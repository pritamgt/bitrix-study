<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 */

\Bitrix\Main\Loader::includeModule('socialservices');
\Bitrix\Main\Loader::includeModule('oauth');

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if(isset($request['code']) && isset($request['state']))
{
	$state = array();
	parse_str($request['state'], $state);

	if($state['sessid'] === bitrix_sessid())
	{
		$appClient = \Bitrix\OAuth\Base::instance($state['client_id']);
		if($appClient)
		{
			$code = $request['code'];
			$auth = new \CBitrix24NetOAuthInterface();

			$auth->setCode($code);
AddMessage2Log($_REQUEST);
			if($auth->GetAccessToken($APPLICATION->GetCurPageParam('', array('code', 'domain', 'state'))))
			{
				$token = $auth->getToken();
				if($token)
				{
					$_SESSION['OAUTH_CURRENT_DATA'] = array(
						'client_id' => $state['client_id'],
					);

					$client = new \CBitrix24NetTransport($token);

					$networkProfile = $client->call('profile');
					$_SESSION['OAUTH_CURRENT_DATA']['profile'] = $networkProfile['result'];

					$networkPortalList = $client->call('admin.profile.list');

					if(array_key_exists("error", $networkPortalList))
					{
						showError($networkPortalList['error'].': '.$networkPortalList['error_description']);
					}
					else
					{
						$domainList = array();
						foreach($networkPortalList['result']['portal'] as $item)
						{
							$domainList[] = $item['TITLE'];
						}

						foreach($networkPortalList['result']['admin'] as $item)
						{
							$domainList[] = $item['TITLE'];
						}

						if(count($domainList) > 0)
						{
							$dbRes = \Bitrix\OAuth\ClientTable::getList(array(
								'filter' => array(
									'@TITLE' => array_unique($domainList),
									'=CLIENT_TYPE' => \Bitrix\OAuth\ClientTable::TYPE_BITRIX,
								),
								'select' => array(
									'ID', 'TITLE', 'REDIRECT_URI'
								),
							));

							$portalList = array();
							$portalIdList = array();
							while($portal = $dbRes->fetch())
							{
								if(strlen($portal['REDIRECT_URI']) <= 0)
								{
									continue;
								}

								$portal['INSTALLED'] = false;
								$portalList[$portal['ID']] = $portal;
								$portalIdList[] = $portal['ID'];
							}

							if(count($portalIdList) > 0)
							{
								$dbRes = \Bitrix\OAuth\ClientVersionInstallTable::getList(array(
									'filter' => array(
										'=CLIENT_ID' => $appClient->getClientId(),
										'=ACTIVE' => \Bitrix\OAuth\ClientVersionInstallTable::ACTIVE,
										'@INSTALL_CLIENT_ID' => $portalIdList,
									),
									'select' => array('INSTALL_CLIENT_ID')
								));
								while($installInfo = $dbRes->fetch())
								{
									$portalList[$installInfo['INSTALL_CLIENT_ID']]['INSTALLED'] = true;
								}

								$_SESSION['OAUTH_CURRENT_DATA']['portal'] = $portalList;

								LocalRedirect($APPLICATION->GetCurPageParam(http_build_query(array(
									'client_id' => $state['client_id'],
									'state' => $state['application_state'],
								)), array(
									'code', 'state', 'domain',
								)));
							}
						}
					}
				}
				else
				{
					showError('no token');
				}
			}
			else
			{
				showError('Unable to get authorization');
			}
		}
		else
		{
			showError('Unknown client');
		}
	}
	else
	{
		ShowError('session check failed');
	}
}
elseif(isset($request['client_id']))
{
	$appClient = \Bitrix\OAuth\Base::instance($request['client_id']);

	if($appClient)
	{
		if($request['relogin'])
		{
			unset($_SESSION['OAUTH_CURRENT_DATA']);
			LocalRedirect($APPLICATION->GetCurPageParam('', array('relogin')));
		}

		if(!is_array($_SESSION['OAUTH_CURRENT_DATA']) || $_SESSION['OAUTH_CURRENT_DATA']['client_id'] != $request['client_id'])
		{
			$auth = new \CBitrix24NetOAuthInterface();
			$auth->addScope('admin');

			$state = array(
				'client_id' => $request['client_id'],
				'application_state' => $request['state'],
				'sessid' => bitrix_sessid(),
			);

			$url = $auth->GetAuthUrl(CHTTP::URN2URI($APPLICATION->GetCurPage()), http_build_query($state), 'page');

			LocalRedirect($url);
		}
		else
		{
			$arResult['APP'] = $appClient->getClient();
			$arResult['DATA'] = $_SESSION['OAUTH_CURRENT_DATA'];

			$arResult['QUERY_AUTHORIZE'] = '/oauth/authorize/?'.http_build_query(array(
				'client_id' => $request['client_id'],
				'state' => $request['state'],
			));

			$arResult['QUERY_INSTALL'] = '/marketplace/detail/'.urlencode($arResult['APP']['TITLE']).'/';

			$this->includeComponentTemplate();
		}
	}
	else
	{
		showError('Unknown client');
	}
}
