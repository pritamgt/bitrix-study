<?php
namespace Bitrix\OAuth\Security;

use Bitrix\Main\Security\Sign\BadSignatureException;

class TokenExpiredException extends BadSignatureException
{

}