<?php
/**
 * Created by PhpStorm.
 * User: sigurd
 * Date: 14.12.17
 * Time: 11:56
 */

namespace Bitrix\OAuth\Auth;


use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\OAuth\Security\TimeSignerShort;

abstract class Token
{
	const LIFETIME_TS = '';
	const TOKEN_TYPE = 0;

	protected $ttl = null;

	protected static $paramList = array();

	/**
	 * @var TimeSignerShort|null
	 */
	var $signer = null;

	public static function getParameterList()
	{
		return array_keys(static::$paramList);
	}

	public function __construct(TimeSignerShort $signer = null)
	{
		if($signer === null)
		{
			$signer = new TimeSignerShort(null, static::LIFETIME_TS);
		}

		$this->setSigner($signer);
	}

	public function getTokenType()
	{
		return static::TOKEN_TYPE;
	}

	public function getToken(array $tokenData, $key)
	{
		$signatureData = array();
		foreach(static::$paramList as $paramName => $type)
		{
			$signatureData[] = isset($tokenData[$paramName]) ? intval($tokenData[$paramName]) : 0;
		}
		$this->getSigner()->setPackMethod($this->getPackFormat());

		return $this->getSigner()->sign($signatureData, $key);
	}

	public function getTokenData($token)
	{
		$this->getSigner()->setPackMethod($this->getUnPackFormat());
		$unsignedResult = $this->getSigner()->getRawSignatureData($token);

		return $this->prepareData($unsignedResult);
	}

	public function checkToken($token, $key)
	{
		$this->getSigner()->setPackMethod($this->getUnPackFormat());
		$unsignedResult = $this->getSigner()->unsign($token, $key);

		return $this->prepareData($unsignedResult);
	}

	/**
	 * @return TimeSignerShort|null
	 */
	public function getSigner()
	{
		return $this->signer;
	}

	/**
	 * @param TimeSignerShort $signer
	 */
	public function setSigner(TimeSignerShort $signer)
	{
		$this->signer = $signer;
	}

	public function getTimestamp()
	{
		return $this->getSigner()->getTimestamp();
	}

	protected function prepareData(array $unsignedResult)
	{
		if(!is_array($unsignedResult))
		{
			throw new BadSignatureException('Wrong token');
		}

		return $unsignedResult;
	}

	protected function getPackFormat()
	{
		return implode('', static::$paramList);
	}

	protected function getUnPackFormat()
	{
		$format = array();

		foreach(static::$paramList as $param => $type)
		{
			$format[] = $type.$param;
		}

		return implode('/', $format);
	}

	/**
	 * @return string
	 */
	public function getTtl()
	{
		return $this->ttl === null ? static::LIFETIME_TS : $this->ttl;
	}

	/**
	 * @param string $ttl
	 */
	public function setTtl($ttl)
	{
		$this->ttl = $ttl;
		$this->getSigner()->setTimestamp($this->getTtl());
	}
}