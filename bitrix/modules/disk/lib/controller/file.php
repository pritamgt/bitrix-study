<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class File extends BaseObject
{
	public function getAction(Disk\File $file)
	{
		return $this->get($file);
	}

	protected function get(Disk\BaseObject $file)
	{
		if (!($file instanceof Disk\File))
		{
			throw new ArgumentTypeException('file', Disk\File::class);
		}

		$data = parent::get($file);
		$data['file'] = $data['object'];
		unset($data['object']);

		$data['file'] = array_merge($data['file'], [
			'typeFile' => $file->getTypeFile(),
			'globalContentVersion' => $file->getGlobalContentVersion(),
			'fileId' => $file->getFileId(),
			'etag' => $file->getEtag(),
			'extra' => [
				'downloadUri' => $this->getActionUri('download', ['fileId' => $file->getId(),]),
			],
		]);

		return $data;
	}

	public function createByContentAction(Disk\Folder $folder, $filename, Disk\Bitrix24Disk\TmpFile $content, $generateUniqueName = false)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $folder->getStorage()->getSecurityContext($currentUserId);
		if (!$folder->canAdd($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));
			$content->delete();

			return;
		}

		if ($content->isCloud() && $content->getContentType())
		{
			$fileId = \CFile::saveFile([
				'name' => $content->getFilename(),
				'tmp_name' => $content->getAbsolutePath(),
				'type' => $content->getContentType(),
				'width' => $content->getWidth(),
				'height' => $content->getHeight(),
				], Driver::INTERNAL_MODULE_ID, true, true);
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if (!$fileId)
			{
				$this->errorCollection[] = new Error('Could not save file data by \CFile::saveFile');
				$content->delete();

				return;
			}

			//it's crutch to be similar @see \Bitrix\Disk\Folder::uploadFile()
			$filename = Disk\Ui\Text::correctFilename($filename);
			$file = $folder->addFile([
				'NAME' => $filename,
				'FILE_ID' => $fileId,
				'SIZE' => $content->getSize(),
				'CREATED_BY' => $currentUserId,
			]);
		}
		else
		{
			$file = $folder->uploadFile(
				\CFile::makeFileArray($content->getAbsolutePath()),
				[
					'NAME' => $filename,
					'CREATED_BY' => $currentUserId,
				],
				[],
				$generateUniqueName
			);
		}

		$content->delete();

		if (!$file)
		{
			$this->errorCollection->add($folder->getErrors());

			return;
		}

		return $this->getAction($file);
	}

	public function downloadAction(Disk\File $file)
	{
		$fileData = $file->getFile();

		\CFile::viewByUser(
			$fileData,
			[
				'force_download' => true,
				'cache_time' => 0,
				'attachment_name' => $file->getName()
			]
		);
	}

	public function markDeletedAction(Disk\File $file)
	{
		return $this->markDeleted($file);
	}

	public function deleteAction(Disk\File $file)
	{
		return $this->deleteFile($file);
	}

	public function restoreAction(Disk\File $file)
	{
		return $this->restore($file);
	}

	public function restoreFromVersionAction(Disk\File $file, Disk\Version $version)
	{
		if ($version->getObjectId() != $file->getId())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FILE_C_ERROR_INVALID_FILE_VERSION'));

			return;
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if (!$file->canRestore($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$file->restoreFromVersion($version, $this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($file->getErrors());

			return;
		}

		return $this->getAction($file);
	}

	public function generateExternalLinkAction(Disk\File $file)
	{
		return $this->generateExternalLink($file);
	}

	public function disableExternalLinkAction(Disk\File $file)
	{
		return $this->disableExternalLink($file);
	}

	public function addSharingAction(Disk\File $file, $entity, $taskName)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $file->getStorage()->getSecurityContext($currentUserId);
		if (!$file->canShare($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($file, $currentUserId);

		if ($rightsManager->pseudoCompareTaskName($taskName, $maxTaskName) > 0)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$sharing = Disk\Sharing::add(
			[
				'FROM_ENTITY' => Disk\Sharing::CODE_USER . $currentUserId,
				'REAL_OBJECT' => $file,
				'CREATED_BY' => $currentUserId,
				'CAN_FORWARD' => false,
				'TO_ENTITY' => $entity,
				'TASK_NAME' => $taskName,
			],
			$this->errorCollection
		);

		if(!$sharing)
		{
			return;
		}

		return [
			'sharing' => [
				'id' => $sharing->getId(),
			],
		];
	}

	public function showSharingEntitiesAction(Disk\File $file)
	{
		$currentUserId = $this->getCurrentUser()->getId();
		$securityContext = $file->getStorage()->getSecurityContext($currentUserId);
		$rightsManager = Driver::getInstance()->getRightsManager();

		$entityList = [];
		//user has only read right. And he can't see on another sharing
		if(!$file->canShare($securityContext) && !$file->canChangeRights($securityContext))
		{
			/** @var Disk\User $user */
			$user = Disk\User::getById($currentUserId);

			$pseudoMaxTaskByObjectForUser = $rightsManager->getPseudoMaxTaskByObjectForUser($file, $currentUserId);
			$entityList = [
				[
					'entity' => [
						'id' => Disk\Sharing::CODE_USER . $currentUserId,
						'name' => $user->getFormattedName(),
						'avatar' => $user->getAvatarSrc(),
						'type' => 'users',
					],
					'sharing' => [
						'right' => $pseudoMaxTaskByObjectForUser,
						'name' => $rightsManager->getTaskTitleByName($pseudoMaxTaskByObjectForUser),
					],
				]
			];
		}
		else
		{
			foreach ($file->getMembersOfSharing() as $entity)
			{
				$entityList[] = [
					'entity' => [
						'id' => $entity['entityId'],
						'name' => $entity['name'],
						'avatar' => $entity['avatar'],
						'type' => $entity['type'],
					],
					'sharing' => [
						'id' => $entity['sharingId'],
						'taskName' => $entity['right'],
						'name' => $rightsManager->getTaskTitleByName($entity['right']),
						'canDelete' => true,
						'canChange' => true,
					],
				];
			}
		}

		return $entityList;
	}
}