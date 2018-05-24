<?php
namespace Bitrix\OAuth;

use Bitrix\Main\Context;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;

if(!defined('REST_SQS_URL'))
{
	define('REST_SQS_URL', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions');
}

if(!defined('REST_SQS_URL_IMPORTANT'))
{
	define('REST_SQS_URL_IMPORTANT', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions_important');
}

if(!defined('REST_SQS_URL_BOT'))
{
	define('REST_SQS_URL_BOT', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions_bot');
}

if(!defined('REST_SQS_URL_CRM'))
{
	define('REST_SQS_URL_CRM', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions_crm');
}

if(!defined('REST_SQS_URL_BIZPROC'))
{
	define('REST_SQS_URL_BIZPROC', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions_bizproc');
}

if(!defined('REST_SQS_URL_TELEPHONY'))
{
	define('REST_SQS_URL_TELEPHONY', 'https://sqs.us-east-1.amazonaws.com/577024403807/bx24_mp_actions_telephony');
}

class Sqs
{
	const ACTION_CALL = "SendMessage";

	const DATA_CHARSET = "utf-8";

	const CATEGORY_DEFAULT = "default";
	const CATEGORY_IMPORTANT = "important";
	const CATEGORY_BOT = "bot";
	const CATEGORY_CRM = "crm";
	const CATEGORY_BIZPROC = "bizproc";
	const CATEGORY_TELEPHONY = "telephony";

	protected static $services = array(
		self::CATEGORY_DEFAULT => array(
			"SERVICE_URL" => REST_SQS_URL,
		),
		self::CATEGORY_IMPORTANT => array(
			"SERVICE_URL" => REST_SQS_URL_IMPORTANT,
		),
		self::CATEGORY_BOT => array(
			"SERVICE_URL" => REST_SQS_URL_BOT,
		),
		self::CATEGORY_CRM => array(
			"SERVICE_URL" => REST_SQS_URL_CRM,
		),
		self::CATEGORY_BIZPROC => array(
			"SERVICE_URL" => REST_SQS_URL_BIZPROC,
		),
		self::CATEGORY_TELEPHONY => array(
			"SERVICE_URL" => REST_SQS_URL_TELEPHONY,
		),
	);

	public static function query(array $items)
	{
		$query = array();
		foreach($items as $item)
		{
			$category = $item["additional"]["category"];
			if(!$category)
			{
				$category = static::CATEGORY_DEFAULT;
			}

			if(!is_array($query[$category]))
			{
				$query[$category] = array();
			}

			$query[$category][] = $item["query"];
		}

		foreach($query as $category => $queryItems)
		{
			static::call(static::ACTION_CALL, static::prepareMessage($queryItems), $category);
		}
	}

	public static function queryItem($domain, $url, $data, array $additional = array())
	{
		if(is_array($data))
		{
			$data = Encoding::convertEncoding($data, LANG_CHARSET, static::DATA_CHARSET);
		}

		return array(
			'additional' => $additional,
			'query' => array(
				'DOMAIN' => $domain,
				'QUERY_URL' => $url,
				'QUERY_DATA' => http_build_query($data),
				'REMOTE_ADDR' => Context::getCurrent()->getRequest()->getRemoteAddress(),
			),
		);
	}

	protected static function call($action, $messageBody, $category)
	{
		$h = new HttpClient();

		$service = static::getService($category);

		if(array_key_exists("SERVICE_LOGIN", $service) && array_key_exists("SERVICE_PASSWORD", $service))
		{
			$h->setAuthorization($service["SERVICE_LOGIN"], $service["SERVICE_PASSWORD"]);
		}

		return $h->post($service["SERVICE_URL"], array(
			"Action" => $action,
			"MessageBody" => $messageBody,
		));
	}

	protected static function prepareMessage(array $message)
	{
		return base64_encode(serialize($message));
	}

	protected static function getService($category)
	{
		return array_key_exists($category, static::$services)
			? static::$services[$category]
			: static::$services[static::CATEGORY_DEFAULT];
	}
}