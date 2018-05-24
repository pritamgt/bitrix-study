<?php

namespace Bitrix\Disk\Internals\Engine;

use Bitrix\Disk\Internals\Error\ErrorCollection;
use \Bitrix\Main\Engine;
use Bitrix\Main\Engine\CurrentUser;

class Controller extends Engine\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_C_22001';
	const ERROR_COULD_NOT_FIND_VERSION = 'DISK_C_22005';
	const ERROR_COULD_NOT_UPDATE_FILE  = 'DISK_C_22006';

	/** @var  ErrorCollection */
	protected $errorCollection;

	protected function init()
	{
		parent::init();
		$this->errorCollection = new ErrorCollection();
		
		Engine\Binder::registerParameterDependsOnName(
			\Bitrix\Disk\Bitrix24Disk\TmpFile::class,
			function($className, $token) {
				/** @var \Bitrix\Disk\Bitrix24Disk\TmpFile $className */
				$filter = [
					'=TOKEN' => (string)$token
				];
				$userId = CurrentUser::get()->getId();
				if ($userId)
				{
					$filter['CREATED_BY'] = $userId;
				}

				return $className::load($filter);
			}
		);

		Engine\Binder::registerParameterDependsOnName(
			\Bitrix\Disk\Internals\Model::class,
			function($className, $id) {
				/** @var \Bitrix\Disk\Internals\Model $className */
				return $className::getById($id);
			}
		);		
	}

	protected function processAfterAction(Engine\Action $action, $result)
	{
		if ($this->errorCollection->getErrorByCode(Engine\ActionFilter\Csrf::ERROR_INVALID_CSRF))
		{
			return Engine\Response\AjaxJson::createDenied()->setStatus('403 Forbidden');
		}

		return $result;
	}
}
