<?php
namespace Bitrix\OAuth;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

if(!defined('OAUTH_LICENSE_SERVER'))
{
	define('OAUTH_LICENSE_SERVER', 'https://www.1c-bitrix.ru/buy_tmp/verify.php');
}

class LicenseVerify
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';

	const ERROR_LICENSE_NOT_FOUND = 'LICENSE_NOT_FOUND';
	const ERROR_WRONG_SIGN = 'WRONG_SIGN';
	const ERROR_LICENSE_DEMO = 'LICENSE_DEMO';
	const ERROR_LICENSE_NOT_ACTIVE = 'LICENSE_NOT_ACTIVE';
	const ERROR_FOUND = 'error';

	const CACHE_TTL = 604800; // one week
	const CACHE_PATH = '/bx/oauth/license/';

	const SOCKET_TIMEOUT = 5;
	const STREAM_TIMEOUT = 30;

	const PARAM_SKIP_CHECK_TYPE = 'BX_ALL';

	/** @var \Bitrix\OAuth\Error */
	private $error = null;

	private $validationUrl = OAUTH_LICENSE_SERVER;

	private $type = null;
	private $license = "";
	private $params = array();

	private $checkLicenseType = true;

	/**
	* Class for checking license.
	*
	* @param $type - Type of license. Must be self::TYPE_BITRIX24 or self::TYPE_CP.
	* @param $license - License string. For type self::TYPE_BITRIX24 - portal domain without protocol, for type self::TYPE_CP - LICENSE_KEY_HEAD.
	* @param $params - Parameters and signature.
	*
	*/
	public function __construct($type, $license, $params)
	{
		if (!in_array($type, array(self::TYPE_BITRIX24, self::TYPE_CP)))
		{
			$this->error = new Error(__METHOD__, 'INIT_TYPE_ERROR', 'Type error. Must be self::TYPE_BITRIX24 or self::TYPE_CP.');
		}
		if (empty($license))
		{
			$this->error = new Error(__METHOD__, 'INIT_LICENSE_ERROR', 'License string error. For type self::TYPE_BITRIX24 - portal domain without protocol, for type self::TYPE_CP - LICENSE_KEY_HEAD.');
		}
		if (!is_array($params) || empty($params))
		{
			$this->error = new Error(__METHOD__, 'INIT_PARAMS_ERROR', 'Parameters and signature isn\'t specified.');
		}

		if (!$this->error)
		{
			$this->type = $type;
			$this->license = $license;
			$this->params = $params;

			$this->error = new Error(null, '', '');
		}
	}

	public function setCheckLicenseType($value)
	{
		$this->checkLicenseType = (bool)$value;
	}

	/**
	 * Start check license, method support cache
	 *
	 * @return array|bool
	 */
	public function getResult()
	{
		$cacheName = $this->type==self::TYPE_BITRIX24 ? md5($this->license) : $this->license;
		$cachePath = self::CACHE_PATH.$this->type.'/';

		$cache = Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, $cacheName, $cachePath))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = $this->verify();
			if ($result)
			{
				$cache->startDataCache();
				$cache->endDataCache($result);
			}
		}

		return $result;
	}

	/**
	 * @return array|bool
	 */
	private function verify()
	{
		$result = false;

		$params = $this->params;

		if(!$this->checkLicenseType)
		{
			$params[self::PARAM_SKIP_CHECK_TYPE] = 'y';
		}

		$httpClient = new HttpClient(array(
			"socketTimeout" => self::SOCKET_TIMEOUT,
			"streamTimeout" => self::STREAM_TIMEOUT,
		));
		$httpClient->setHeader('User-Agent', 'Bitrix OAuth service');
		$answer = $httpClient->post($this->validationUrl, $params);

		if ($answer && $httpClient->getStatus() == "200")
		{
			try
			{
				$answer = Json::decode($httpClient->getResult());

				if ($answer['status'] == self::ERROR_FOUND)
				{
					$answer = array('error' => array(
						'code' => $answer['text'],
						'message' => 'Check license return error: '.$answer['text'],
						'data' => Array($this->validationUrl, $params, $answer)
					));
				}
				else
				{
					$result = $answer['result'];
				}
			}
			catch(ArgumentException $e)
			{
				$answer = array('error' => array(
					'code' => 'CONNECT_ERROR',
					'message' => 'Parse error or connect error from server.',
					'data' => Array($httpClient->getError(), $httpClient->getResult())
				));
			}
		}
		else
		{
			$answer = array('error' => array(
				'code' => 'CONNECT_ERROR',
				'message' => 'Parse error or connect error from server.',
				'data' => Array($httpClient->getError(), $httpClient->getResult())
			));
		}
		if(isset($answer['error']))
		{
			$this->error = new Error(__METHOD__, $answer['error']['code'], $answer['error']['message'], $answer['error']['data']);
		}

		return $result;
	}


	/**
	 * Return current error.
	 * @return \Bitrix\OAuth\Error
	 */
	public function getError()
	{
		return $this->error;
	}
}