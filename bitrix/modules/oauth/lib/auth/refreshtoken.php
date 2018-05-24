<?php
/**
 * Created by PhpStorm.
 * User: sigurd
 * Date: 14.12.17
 * Time: 15:09
 */

namespace Bitrix\OAuth\Auth;


class RefreshToken extends Token
{
	const LIFETIME_TS = '+30 day';
	const TOKEN_TYPE = 3;

	protected static $paramList = array(
		'CLIENT_ID' => 'N',
		'USER_ID' => 'N',
		'LOCAL_USER' => 'N',
		'TZ_OFFSET' => 's',
		'EVENT_SESSION' => 'C',
	);
}