<?php
namespace Bitrix\OAuth\Client;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\OAuth\ClientFeatureTable;
use Bitrix\OAuth\ClientScopeTable;
use Bitrix\OAuth\Base;

class External extends Base
{
	private $rejectedScope = null;

	public function check()
	{
		global $USER;

		$message = parent::check();
		if($message === true)
		{
			$request = Context::getCurrent()->getRequest();
			$client = $this->getClient();

			if($request->getRequestMethod() == 'POST'
				&& check_bitrix_sessid()
			)
			{
				if(isset($request['accept']))
				{
					$dbRes = ClientScopeTable::getList(array(
						'filter' => array(
							'=USER_ID' => $USER->GetID(),
							'=CLIENT_ID' => $client['ID']
						)
					));
					$scopeData = $dbRes->fetch();

					if($scopeData)
					{
						$newScope = $request["new_scope"];

						$scope = implode(",", array_unique(array_merge(
							explode(",", $newScope),
							explode(",", $scopeData['CLIENT_SCOPE'])
						)));

						ClientScopeTable::update($scopeData['ID'], array(
							'CLIENT_SCOPE' => $scope,
							'LAST_AUTHORIZE' => new DateTime(),
						));
					}
					else
					{
						ClientScopeTable::add(array(
							'USER_ID' => $USER->GetID(),
							'CLIENT_ID' => $client['ID'],
							'CLIENT_SCOPE' => $request['new_scope'],
							'LAST_AUTHORIZE' => new DateTime(),
						));
					}
				}
				elseif(isset($request["reject"]))
				{
					$this->rejectedScope = $request["new_scope"];
				}
			}
		}
		return $message;
	}

	protected function validateClient(array $result, array $client = array())
	{
		// allow access with client_id and client_secret - methods should check if user auth needed
		if(!empty($result['user_id']))
		{
			$scope = explode(",", $result['scope']);
			$checkResult = $this->checkUserScope($client['ID'], $result['user_id'], $scope);

			if($checkResult !== true)
			{
				return array(
					"REQUEST" => true,
					"SCOPE" => $scope,
					"NEW_SCOPE" => $checkResult,
					"CLIENT" => $client
				);
			}
		}

		return true;
	}

	protected function validateToken(array $tokenInfo, array $client = array())
	{
		$requestedScope = !empty($tokenInfo["scope"]) ? explode(",", $tokenInfo["scope"]) : array();
		$checkResult = $this->checkUserScope(
			$client["ID"],
			$tokenInfo["user_id"],
			$requestedScope
		);

		if($checkResult !== true)
		{
			$tokenInfo["scope"] = implode(",", array_diff($requestedScope, $checkResult));
		}

		return $tokenInfo;
	}

	private function checkUserScope($clientId, $userId, $scope = array())
	{
		$dbRes = ClientScopeTable::getList(array(
			'filter' => array(
				"=USER_ID" => $userId,
				"=CLIENT_ID" => $clientId,
			),
			'select' => array('ID', 'CLIENT_SCOPE'),
			'limit' => array(0,1)
		));

		$scopeData = $dbRes->fetch();
		if($scopeData)
		{
			$oldScope = explode(",", $scopeData['CLIENT_SCOPE']);

			if($this->rejectedScope)
			{
				$oldScope[] = $this->rejectedScope;
			}

			$check = array_diff($scope, $oldScope);
			if(count($check) <= 0)
			{
				ClientScopeTable::update($scopeData['ID'], array(
					'LAST_AUTHORIZE' => new DateTime(),
				));

				return true;
			}

			return $check;
		}

		return $scope;
	}

}