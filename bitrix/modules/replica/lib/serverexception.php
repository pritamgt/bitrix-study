<?php
namespace Bitrix\Replica;

class ServerException extends \Bitrix\Main\SystemException
{
	/**
	 * Creates exception object.
	 *
	 * @param string $message Error message.
	 * @param \Exception $previous Exception backtrace.
	 *
	 * @see \Bitrix\Main\SystemException
	 */
	public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 500, '', 0, $previous);
	}
}
