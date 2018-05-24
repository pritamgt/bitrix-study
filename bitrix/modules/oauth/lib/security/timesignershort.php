<?php
namespace Bitrix\OAuth\Security;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\HmacAlgorithm;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Security\Sign\SigningAlgorithm;

/**
 * Class TimeSignerShort
 * @package Bitrix\OAuth\Security
 */

class TimeSignerShort extends Signer
{
	const SIGNATURE_ALGORITHM = 'md5';
	const SIGNATURE_LENGTH = 16;

	const PACK_METHOD = 'l*';
	const PACK_METHOD_INTERNAL = 'L';
	const UNPACK_METHOD_INTERNAL = 'Lts';

	protected $packMethod = null;

	protected $separator = '';
	protected $timestamp = 0;

	public function __construct(SigningAlgorithm $algorithm = null, $timestamp = 0)
	{
		if($algorithm === null)
		{
			$algorithm = new HmacAlgorithm(static::SIGNATURE_ALGORITHM);
		}

		if($algorithm->getHashAlgorithm() !== static::SIGNATURE_ALGORITHM)
		{
			throw new NotImplementedException('Only '.static::SIGNATURE_ALGORITHM.' signing algotitm is currently supported');
		}

		parent::__construct($algorithm);

		if($timestamp !== 0)
		{
			$this->setTimestamp($timestamp);
		}
	}

	/**
	 * @param array $value Plain array of integers
	 * @param string $salt
	 * @return string
	 */
	public function sign($value, $salt = null)
	{
		$value = $this->packValue($value);
		$signedResult = parent::sign($value, $salt);

		return $this->encodeResult($signedResult);
	}

	public function unsign($signedValue, $salt = null)
	{
		$decodedValue = $this->decodeResult($signedValue);

		$unsignResult = parent::unsign($decodedValue, $salt);
		$unpackResult = $this->unpackValue($unsignResult);

		$this->checkTimestamp();

		return $unpackResult;
	}

	public function getRawSignatureData($signedValue)
	{
		$decodedValue = $this->decodeResult($signedValue);
		$signatureData = $this->unpack($decodedValue);
		return $this->unpackValue($signatureData[0]);
	}

	public function unpack($value, $limit = 2)
	{
		if(function_exists('mb_substr'))
		{
			return array(
				mb_substr($value, 0, -static::SIGNATURE_LENGTH, '8bit'),
				mb_substr($value, -static::SIGNATURE_LENGTH, mb_strlen($value, '8bit'), '8bit'),
			);
		}
		else
		{
			return array(
				substr($value, 0, -static::SIGNATURE_LENGTH),
				substr($value, -static::SIGNATURE_LENGTH),
			);
		}
	}

	/**
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->timestamp;
	}

	/**
	 * @param string|int $timestamp
	 */
	public function setTimestamp($timestamp)
	{
		$this->timestamp = $this->parseTimeStamp($timestamp);
	}

	protected function packValue($value)
	{
		$this->checkDataForSign($value);

		$value = array_values($value);
		array_unshift($value, $this->getTimestamp());

		return pack($this->getPackMethod(), ...$value);
	}

	protected function unpackValue($packedValue)
	{
		$result = unpack($this->getUnPackMethod(), $packedValue);

		if(!is_array($result))
		{
			throw new BadSignatureException('Unable to process value');
		}

		$ts = array_shift($result);

		if($ts <= 0)
		{
			throw new BadSignatureException('Wrong signature timestamp');
		}

		$this->timestamp = $ts;

		return $result;
	}

	protected function checkDataForSign($value)
	{
		if(!is_array($value))
		{
			throw new ArgumentException('Value must be array of integers', 'value');
		}

		foreach($value as $item)
		{
			if(!is_int($item))
			{
				throw new ArgumentException('Value must be array of integers', 'value');
			}
		}
	}

	/**
	 * @return string
	 */
	public function getPackMethod()
	{
		$packMethod = $this->packMethod;
		if($packMethod === null)
		{
			$packMethod = static::PACK_METHOD;
		}

		return static::PACK_METHOD_INTERNAL.$packMethod;
	}

	/**
	 * @return string
	 */
	public function getUnPackMethod()
	{
		$packMethod = $this->packMethod;
		if($packMethod === null)
		{
			$packMethod = static::PACK_METHOD;
		}

		return static::UNPACK_METHOD_INTERNAL.'/'.$packMethod;
	}

	/**
	 * @param string $packMethod
	 */
	public function setPackMethod($packMethod)
	{
		$this->packMethod = $packMethod;
	}

	/**
	 * Return timestamp parsed from English textual datetime description
	 *
	 * @param string|int $time Timestamp or datetime description (presented in format accepted by strtotime).
	 * @return int
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function parseTimeStamp($time)
	{
		if(!is_string($time) && !is_int($time))
		{
			throw new ArgumentTypeException('time');
		}

		if(is_string($time) && !is_numeric($time))
		{
			$timestamp = strtotime($time);
			if(!$timestamp)
			{
				throw new ArgumentException(sprintf('Invalid time "%s" format. See "Date and Time Formats"', $time));
			}
		}
		else
		{
			$timestamp = (int)$time;
		}

		if($timestamp < time())
		{
			throw new ArgumentException(sprintf('Timestamp %d must be greater than now()', $timestamp));
		}

		return $timestamp;
	}

	protected function checkTimestamp()
	{
		if($this->getTimestamp() < time())
		{
			throw new TokenExpiredException(sprintf('Signature timestamp expired (%d < %d)', $this->getTimestamp(), time()));
		}
	}

	protected function encodeSignature($value)
	{
		return $value;
	}

	protected function decodeSignature($value)
	{
		return $value;
	}

	protected function encodeResult($value)
	{
		return bin2hex($value);
	}

	protected function decodeResult($value)
	{
		if(preg_match('#[^[:xdigit:]]#', $value))
		{
			throw new BadSignatureException('Signature must be hexadecimal string');
		}

		return hex2bin($value);
	}

}