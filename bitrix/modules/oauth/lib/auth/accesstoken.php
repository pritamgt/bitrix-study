<?php
/**
 * Created by PhpStorm.
 * User: sigurd
 * Date: 14.12.17
 * Time: 15:09
 */

namespace Bitrix\OAuth\Auth;


class AccessToken extends Token
{
	const LIFETIME_TS = '+1 hour';
	const TOKEN_TYPE = 2;

	protected static $paramList = array(
		'CLIENT_ID' => 'N',
		'USER_ID' => 'N',
		'LOCAL_USER' => 'N',
		'TZ_OFFSET' => 's',
		'EVENT_SESSION' => 'C',
	);
}