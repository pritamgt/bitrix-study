<?php
/**
 * Created by PhpStorm.
 * User: sigurd
 * Date: 14.12.17
 * Time: 15:08
 */

namespace Bitrix\OAuth\Auth;


class Code extends Token
{
	const LIFETIME_TS = '+30 second';
	const TOKEN_TYPE = 1;

	protected static $paramList = array(
		'CLIENT_ID' => 'N',
		'USER_ID' => 'N',
		'LOCAL_USER' => 'N',
		'TZ_OFFSET' => 's'
	);
}