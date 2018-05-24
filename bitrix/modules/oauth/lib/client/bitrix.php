<?php
namespace Bitrix\OAuth\Client;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\OAuth\Base;
use Bitrix\OAuth\ClientTable;
use Bitrix\OAuth\License;
use Bitrix\OAuth\LicenseVerify;
use Bitrix\Oauth\LogTable;

class Bitrix extends Base
{
	const PARAM_KEY = "key";

	const PARAM_TYPE = "BX_TYPE";
	const PARAM_LICENSE = "BX_LICENCE";

	const ACCESS_TOKEN_LIFETIME = '+1 day'; // 1 day
	const REFRESH_TOKEN_LIFETIME = '+90 day'; // 90 days

	const VERIFY_LIFETIME = 86400;// 1 day

	const ERROR_VERIFICATION = "verification_needed";

	public function __construct($config = array())
	{
		$this->setVariable("display_error", true);

		return parent::__construct($config);
	}

	public function needAuth()
	{
		return false;
	}

	public function getClientData($clientId)
	{
		$dbOAuthApp = ClientTable::getList(array(
			'filter' => array(
				"=CLIENT_ID" => $clientId
			),
			'select' => array(
				'*',
				'LAST_VERIFY' => 'UF_LAST_VERIFY', 'PUBLISH' => 'UF_PUBLISH',
				'MEMBER_ID' => 'UF_MEMBER_ID', 'EXTERNAL_ID' => 'UF_EXTERNAL_ID',
			)
		));

		$clientData = $dbOAuthApp->fetch();
		if($clientData && empty($clientData['MEMBER_ID']))
		{
			if($clientData['CLIENT_TYPE'] === ClientTable::TYPE_BITRIX)
			{
				$clientData['MEMBER_ID'] = md5($clientData['CLIENT_ID']);
			}
		}

		return $clientData;
	}

	protected function validateClient(array $result, array $client = array())
	{
		$clientInfo = $this->getClient();

		$checkKeyResult = $this->checkKey();
		if($checkKeyResult === true)
		{
			$clientInfo['LAST_VERIFY'] = new DateTime();
			ClientTable::update($clientInfo['ID'], array(
				'UF_LAST_VERIFY' => $clientInfo['LAST_VERIFY']
			));
		}

		$ts = $clientInfo['LAST_VERIFY'] ? $clientInfo['LAST_VERIFY']->getTimestamp() : 0;

		return $ts + static::VERIFY_LIFETIME > time()
			? true
			: $checkKeyResult;
	}

	protected function validateToken(array $tokenInfo, array $client = array())
	{
		if(!$this->internalCheck)
		{
			$checkResult = $this->checkKey();
			if($checkResult !== true)
			{
				return $checkResult;
			}
		}

		return $tokenInfo;
	}

	protected function checkKey()
	{
		$request = Context::getCurrent()->getRequest();

		$checkResult = true;
		if(License::needCheck())
		{
			$checkResult = false;
			if(isset($request[static::PARAM_KEY]))
			{
				if(License::checkHash($request[static::PARAM_KEY]))
				{
					$checkResult = true;
				}
			}
			elseif(isset($request[static::PARAM_TYPE]) && isset($request[static::PARAM_LICENSE]))
			{
				$verify = new LicenseVerify($request[static::PARAM_TYPE], $request[static::PARAM_LICENSE], $request->toArray());
				$verify->setCheckLicenseType(false);

				$verifyResult = $verify->getResult();

				if($verifyResult)
				{
					$this->updateClientKeys($request[static::PARAM_TYPE], $verifyResult);
					$checkResult = true;
				}
				else
				{
					LogTable::add(array(
						'INSTALL_CLIENT_ID' => $this->getClientId(),
						'MESSAGE' => 'LICENSE_VERIFY',
						'DETAIL' => array(
							'URI' => $request->getRequestedPage(),
							'REQUEST' => $request->toArray(),
							'ERROR' => $verify->getError()->toArray(),
						),
						'ERROR' => $verify->getError()->code.': '.$verify->getError()->msg,
						'RESULT' => 0,
					));
				}
			}
		}

		if(!$checkResult)
		{
			return array(
				"ERROR_STATUS" => \COAuthConstants::HTTP_FORBIDDEN,
				"ERROR_CODE" => static::ERROR_VERIFICATION,
				"ERROR_MESSAGE" => "License check failed."
			);
		}

		return true;
	}

	protected function updateClientKeys($type, $verifyResult)
	{
		$clientInfo = $this->getClient();

		$updateFields = array();

		if($clientInfo['EXTERNAL_ID'] !== $type.'_'.$verifyResult['ID'])
		{
			$updateFields['UF_EXTERNAL_ID'] = $type.'_'.$verifyResult['ID'];
		}

		if($type == LicenseVerify::TYPE_CP)
		{
			$updateFields['UF_MEMBER_ID'] = md5($clientInfo['CLIENT_ID']);
		}

		if(count($updateFields) > 0)
		{
			ClientTable::update($this->getClientId(), $updateFields);
			unset($this->clientCache[$this->clientId]);
		}
	}
}