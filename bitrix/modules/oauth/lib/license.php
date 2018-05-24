<?php
namespace Bitrix\OAuth;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

class License
{
	const LICENSE_CHECK = "Y";
	const LICENSE_SKIP = "N";

	const LICENSE_SERVER = "https://www.1c-bitrix.ru/buy_tmp/check_key.php";

	public static function checkHash($keyHash)
	{
		if(static::needCheck())
		{
			$h = new HttpClient();
			$res = $h->get(static::LICENSE_SERVER."?lkey=".urlencode($keyHash));

			try
			{
				$result = Json::decode($res);
			}
			catch(ArgumentException $e)
			{
				return false;
			}

			if(is_array($result) && $result['status'] == "active")
			{
				return true;
			}

			return false;
		}

		return true;
	}

	public static function needCheck()
	{
		return Option::get("oauth", "check_client_license", static::LICENSE_CHECK) === static::LICENSE_CHECK;
	}
}
