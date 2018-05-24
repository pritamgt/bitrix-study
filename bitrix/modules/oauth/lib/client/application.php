<?php
namespace Bitrix\OAuth\Client;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\OAuth\Base;
use Bitrix\OAuth\ClientTable;
use Bitrix\OAuth\ClientVersionInstallTable;
use Bitrix\Oauth\ClientVersionUriTable;
use Bitrix\Rest\AppTable;

class Application extends Base
{
	const ERROR_NOT_INSTALLED = 'NOT_INSTALLED';
	const ERROR_PAYMENT_REQUERED = 'PAYMENT_REQUIRED';

	protected $versionId = null;

	protected $additionalTokenParameters = array(
		'LOCAL_USER', 'TZ_OFFSET'
	);

	public function __construct($config = array())
	{
		$this->setVariable("display_error", true);

		return parent::__construct($config);
	}

	public function getClientData($clientId)
	{
		$dbOAuthApp = ClientTable::getList(array(
			'filter' => array(
				"=CLIENT_ID" => $clientId
			),
			'select' => array(
				'*',
				'STATUS' => 'UF_STATUS',
				'TRIAL_PERIOD' => 'UF_TRIAL_PERIOD',
				'PUBLIC' => 'UF_PUBLIC',
			)
		));

		return $dbOAuthApp->fetch();
	}

	protected function validateClient(array $result, array $client = array())
	{
		return true;
	}

	protected function validateToken(array $tokenInfo, array $client = array())
	{
		$installClient = Base::instanceById($tokenInfo['user_id']);
		unset($tokenInfo['user_id']);

		if($installClient)
		{
			$status = $this->checkAppStatus($installClient->getClientId());

			if(is_array($status))
			{
				return array(
					"ERROR_STATUS" => \COAuthConstants::HTTP_UNAUTHORIZED,
					"ERROR_CODE" => $status['error'],
					"ERROR_MESSAGE" => $status['error_description'],
				);
			}

			$tokenInfo["status"] = $status;

			$clientInfo = $installClient->getClient($tokenInfo['user_id']);
			$tokenInfo['client_endpoint'] = $clientInfo['REDIRECT_URI'].'/rest/';
			$tokenInfo['member_id'] = $clientInfo['MEMBER_ID'];

			if(!empty($tokenInfo['parameters']['LOCAL_USER']))
			{
				$tokenInfo['user_id'] = $tokenInfo['parameters']['LOCAL_USER'];
			}
		}

		return $tokenInfo;
	}

	private function checkAppStatus($installClientId)
	{
		$dbRes = ClientVersionInstallTable::getList(array(
			'filter' => array(
				'=CLIENT_ID' => $this->getClientId(),
				'=INSTALL_CLIENT_ID' => $installClientId,
				'=ACTIVE' => ClientVersionInstallTable::ACTIVE,
			),
			'select' => array('STATUS', 'DATE_FINISH'),
		));

		$installInfo = $dbRes->fetch();
		if(!$installInfo)
		{
			return array(
				'error' => static::ERROR_NOT_INSTALLED,
				'error_description' => "Application not installed",
			);
		}
		else
		{
			/** @var DateTime $statusFinish */
			$statusFinish = $installInfo['DATE_FINISH'];
			$statusInfo = AppTable::getAppStatusInfo(
				array(
					'STATUS' => $installInfo['STATUS'],
					'DATE_FINISH' => $statusFinish ? $statusFinish->toString() : '',
					'CODE' => 'fake',
				), ''
			);

			if($statusInfo['PAYMENT_ALLOW'] === 'N')
			{
				return array(
					'error' => static::ERROR_PAYMENT_REQUERED,
					'error_description' => "Payment required",
				);
			}

			return $installInfo['STATUS'];
		}
	}

	public function setVersionId($versionId)
	{
		$this->versionId = $versionId;
	}

	public function getRedirectUri($clientId = null)
	{
		if($clientId === null)
		{
			$clientId = $this->clientId;
		}

		if($this->versionId)
		{
			$dbRes = ClientVersionUriTable::getList(array(
				'filter' => array(
					'=CLIENT_ID' => $this->getClientId($clientId),
					'=VERSION_ID' => $this->versionId,
				),
				'select' => array('REDIRECT_URI'),
			));
		}
		else
		{
			$dbRes = ClientVersionUriTable::getList(array(
				'order' => array('VERSION.VERSION' => 'DESC'),
				'filter' => array(
					'=CLIENT_ID' => $this->getClientId($clientId),
				),
				'select' => array('REDIRECT_URI'),
				'limit' => array(0,1),
			));
		}

		$uriInfo = $dbRes->fetch();

		if($uriInfo && !empty($uriInfo["REDIRECT_URI"]))
		{
			return $uriInfo["REDIRECT_URI"];
		}

		return null;
		// we should not return false here!
	}

}