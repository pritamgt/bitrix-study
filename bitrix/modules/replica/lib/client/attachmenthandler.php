<?php
namespace Bitrix\Replica\Client;

class AttachmentHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $moduleId = "";
	protected $relation = "";

	protected $executeEventEntity = "";
	protected $parentRelation = "";
	protected $diskConnectorString = "";

	protected $currentId = 0;
	protected $dataFields = array();
	protected $attachments = array();

	/**
	 * Adds attachment to user field value for given entity.
	 *
	 * @param integer $id Entity identifier.
	 * @param integer $diskAttachId Disk attachment identifier.
	 *
	 * @return void
	 */
	public static function updateUserField($id, $diskAttachId)
	{
	}

	/**
	 * Returns array of attachments for given entity.
	 *
	 * @param integer $id Entity identifier.
	 *
	 * @return array[]\Bitrix\Disk\AttachedObject
	 */
	public static function getUserField($id)
	{
		return array();
	}

	/**
	 * Remote event handler.
	 *
	 * @param \Bitrix\Main\Event $event Contains two parameters: 0 - id, 1 - data.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAfterAdd
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAfterUpdate
	 */
	public function onExecuteDescriptionFix(\Bitrix\Main\Event $event)
	{
	}

	/**
	 * Should be called after an record is added.
	 * Sends disk attachments (if any) to remote and checks if any text field with tag [DISK] should be fixed.
	 *
	 * @param integer $id Primary key value.
	 * @param array $data Database record data.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAttachmentAdd
	 * @see \Bitrix\Replica\Client\AttachmentHandler::hasNewFilesInFields
	 */
	public function onAfterAdd($id, $data)
	{
		$hasFiles = false;
		/**
		 * @var integer $attachId
		 * @var \Bitrix\Disk\AttachedObject $attachedObject
		 */
		foreach ($this->getUserField($id) as $attachId => $attachedObject)
		{
			$hasFiles = true;
			$addHandler = new static();
			$addHandler->onAttachmentAdd($id, $attachedObject);
		}

		if ($hasFiles && $this->hasNewFilesInFields($data))
		{
			$operation = new \Bitrix\Replica\Db\Execute();
			$operation->writeToLog(
				$this->executeEventEntity."DescriptionFix",
				array(
					array(
						"relation" => $this->parentRelation,
						"value" => $id,
					),
					array(
						"value" => $this->replaceNewFilesToGuids($id, $data),
					),
				)
			);
		}
	}

	protected $attachmentsBeforeUpdate = array();
	/**
	 * Should be called before an record is updated.
	 * Prepares and stores operations for potential disk attachments delete.
	 *
	 * @param integer $id Primary key value.
	 *
	 * @return void
	 */
	public function onBeforeUpdate($id)
	{
		/**
		 * @var integer $attachId
		 * @var \Bitrix\Disk\AttachedObject $attachedObject
		 */
		foreach ($this->getUserField($id) as $attachId => $attachedObject)
		{
			$deleteHandler = new static();
			$deleteHandler->prepareAttachmentDelete($id, $attachedObject);
			$this->attachmentsBeforeUpdate[$id][$attachId] = $deleteHandler;
		}
	}

	/**
	 * Should be called after an record is updated.
	 * Sends new disk attachments (if any) to remote. Then sends delete operations saved before in onBeforeUpdate method.
	 * After that checks if any text field with tag [DISK] should be fixed.
	 *
	 * @param integer $id Primary key value.
	 * @param array $data Database record data.
	 * @param array $nodes Remote nodes where operation will be executed.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAttachmentAdd
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onBeforeUpdate
	 * @see \Bitrix\Replica\Client\AttachmentHandler::hasNewFilesInFields
	 */
	public function onAfterUpdate($id, $data, $nodes)
	{
		$hasFiles = false;

		/**
		 * @var integer $attachId
		 * @var \Bitrix\Disk\AttachedObject $attachedObject
		 */
		/* ADD NEW FILES(ATTACHMENTS) OR NODES */
		foreach ($this->getUserField($id) as $attachId => $attachedObject)
		{
			$attHandler = new static();

			if (
				is_array($this->attachmentsBeforeUpdate[$id])
				&& is_object($this->attachmentsBeforeUpdate[$id][$attachId])
			)
			{
				/** @var static $attHandler */
				$attHandler = $this->attachmentsBeforeUpdate[$id][$attachId];
				$diff = array_diff($nodes, $attHandler->getAttachmentNodes($attachedObject));
			}
			else
			{
				$diff = $nodes;
			}

			if ($diff)
			{
				$attHandler->onAttachmentAdd($id, $attachedObject);
				$hasFiles = true;
			}

			unset($this->attachmentsBeforeUpdate[$id][$attachId]);
		}

		/* DELETE FILES(ATTACHMENTS) */
		if ($this->attachmentsBeforeUpdate[$id])
		{
			foreach ($this->attachmentsBeforeUpdate[$id] as $attachId => $nodes)
			{
				/** @var \Bitrix\Tasks\Replica\TaskAttachmentHandler $attHandler */
				$attHandler = $this->attachmentsBeforeUpdate[$id][$attachId];
				$attHandler->flushAttachmentDelete();
			}
			unset($this->attachmentsBeforeUpdate[$id]);
		}

		if ($hasFiles && $this->hasNewFilesInFields($data))
		{
			$operation = new \Bitrix\Replica\Db\Execute();
			$operation->writeToLog(
				$this->executeEventEntity."DescriptionFix",
				array(
					array(
						"relation" => $this->parentRelation,
						"value" => $id,
					),
					array(
						"value" => $this->replaceNewFilesToGuids($id, $data),
					),
				)
			);
		}
	}

	protected $attachmentOperation = array();
	/**
	 * Should be called before an record is deleted.
	 * Prepares and stores operations for potential disk attachments delete.
	 *
	 * @param integer $id Primary key value.
	 *
	 * @return void
	 */
	public function onBeforeDelete($id)
	{
		/**
		 * @var integer $attachId
		 * @var \Bitrix\Disk\AttachedObject $attachedObject
		 */
		foreach ($this->getUserField($id) as $attachId => $attachedObject)
		{
			$deleteHandler = new static();
			$deleteHandler->prepareAttachmentDelete($id, $attachedObject);
			$this->attachmentOperation[$id][$attachId] = $deleteHandler;
		}
	}

	/**
	 * Should be called before an record is deleted.
	 * Sends delete operations saved before in onBeforeDelete method.
	 *
	 * @param integer $id Primary key value.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onBeforeDelete
	 */
	public function onAfterDelete($id)
	{
		if ($this->attachmentOperation[$id])
		{
			/** @var static $deleteHandler */
			foreach ($this->attachmentOperation[$id] as $deleteHandler)
			{
				$deleteHandler->flushAttachmentDelete();
			}
			unset($this->attachmentOperation[$id]);
		}
	}

	/**
	 * Returns true if any of fields from $this->dataFields contains [DISK] tag.
	 *
	 * @param array $data Data record.
	 * @return boolean
	 */
	public function hasNewFilesInFields($data)
	{
		foreach ($this->dataFields as $fieldName)
		{
			if (isset($data[$fieldName]) && (strpos($data[$fieldName], "[DISK FILE ID=n") !== false))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns fields from $data enumerated in $this->dataFields with disk file identifier replaced with global identifier.
	 *
	 * @param integer $id Primary key value.
	 * @param array $data Database record data.
	 *
	 * @return array
	 */
	public function replaceNewFilesToGuids($id, $data)
	{
		$this->currentId = $id;
		unset($this->attachments[$id]);

		$result = array();
		foreach ($this->dataFields as $fieldName)
		{
			if (isset($data[$fieldName]) && (strpos($data[$fieldName], "[DISK FILE ID=n") !== false))
			{
				if (!isset($this->attachments[$id]))
				{
					$this->attachments[$id] = $this->getUserField($id);
				}
				$newStr = preg_replace_callback("/\\[DISK FILE ID=(n\\d+)\\]/", array($this, "replaceDiskIdToGuid"), $data[$fieldName]);
				if ($newStr !== null)
				{
					$result[$fieldName] = $newStr;
				}
			}
		}
		return $result;
	}

	/**
	 * Replaces disk file identifier in fields in $data enumerated in $this->dataFields with global identifier.
	 *
	 * @param integer $id Primary key value.
	 * @param array &$data Database record data.
	 *
	 * @return void
	 */
	public function replaceFilesWithGuids($id, &$data)
	{
		$this->currentId = $id;
		unset($this->attachments[$id]);

		foreach ($this->dataFields as $fieldName)
		{
			if (isset($data[$fieldName]) && (strpos($data[$fieldName], "[DISK FILE ID=") !== false))
			{
				if (!isset($this->attachments[$id]))
				{
					$this->attachments[$id] = $this->getUserField($id);
				}
				$newStr = preg_replace_callback("/\\[DISK FILE ID=(n?\\d+)\\]/", array($this, "replaceDiskIdToGuid"), $data[$fieldName]);
				if ($newStr !== null)
				{
					$data[$fieldName] = $newStr;
				}
			}
		}
	}

	/**
	 * Helper for replaceNewFilesToGuids and replaceFilesWithGuids methods.
	 *
	 * @param array $match This comes from preg_replace_callback.
	 *
	 * @return string
	 * @see \Bitrix\Replica\Client\AttachmentHandler::replaceNewFilesToGuids
	 * @see \Bitrix\Replica\Client\AttachmentHandler::replaceFilesWithGuids
	 */
	public function replaceDiskIdToGuid($match)
	{
		if (isset($this->attachments[$this->currentId][$match[1]]))
		{
			$guid = $this->getAttachmentGuid($this->attachments[$this->currentId][$match[1]]);
		}
		elseif (substr($match[1], 0, 1) === 'n' && $this->attachments[$this->currentId])
		{
			$objId = substr($match[1], 1);
			$guid = false;
			/**
			 * @var integer $id
			 * @var \Bitrix\Disk\AttachedObject $attachedObject
			 */
			foreach ($this->attachments[$this->currentId] as $id => $attachedObject)
			{
				if ($attachedObject->getObjectId() == $objId)
				{
					$guid = $this->getAttachmentGuid($id);
				}
			}
		}
		else
		{
			$guid = false;
		}

		if ($guid)
		{
			return "[DISK FILE ID=$guid]";
		}
		else
		{
			return $match[0];
		}
	}

	/**
	 * Replaces global identifiers in fields in $data enumerated in $this->dataFields with disk file identifier.
	 * Returns true if at least one replace was made.
	 *
	 * @param array &$data Database record data.
	 *
	 * @return boolean
	 */
	public function replaceGuidsWithFiles(&$data)
	{
		$replaced = false;
		foreach ($this->dataFields as $fieldName)
		{
			if (isset($data[$fieldName]))
			{
				if (strpos($data[$fieldName], "[DISK FILE ID=") !== false)
				{
					$newStr = preg_replace_callback("/\\[DISK FILE ID=([0-9a-f]+)\\]/", array($this, "replaceDiskGuidToId"), $data[$fieldName]);
					if ($newStr !== null)
					{
						$data[$fieldName] = $newStr;
						$replaced = true;
					}
				}
			}
		}
		return $replaced;
	}

	/**
	 * Helper for replaceGuidsWithFiles method.
	 *
	 * @param array $match This comes from preg_replace_callback.
	 *
	 * @return string
	 * @see \Bitrix\Replica\Client\AttachmentHandler::replaceGuidsWithFiles
	 */
	public function replaceDiskGuidToId($match)
	{
		$id = $this->getAttachmentIdByGuid($match[1]);
		if ($id)
		{
			return "[DISK FILE ID=$id]";
		}
		else
		{
			return $match[0];
		}
	}

	/**
	 * Registers event handlers for database operations like add new, update or delete.
	 *
	 * @return void
	 */
	public function initDataManagerEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecute".$this->executeEventEntity."AttachmentAdd",
			array($this, "onExecuteAttachmentAdd")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecute".$this->executeEventEntity."AttachmentDelete",
			array($this, "onExecuteAttachmentDelete")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecute".$this->executeEventEntity."DescriptionFix",
			array($this, "OnExecuteDescriptionFix")
		);
	}

	/**
	 * Returns array of replication nodes for disk attachment.
	 *
	 * @param \Bitrix\Disk\AttachedObject $attachedObject Disk attachment object.
	 *
	 * @return array[]string
	 */
	public function getAttachmentNodes($attachedObject)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->relation, false, $attachedObject->getId());
		if ($map)
		{
			return current($map);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Finds GUID of an attachment.
	 *
	 * @param integer|\Bitrix\Disk\AttachedObject $attachedObject Disk attachment object.
	 *
	 * @return string|false
	 */
	public function getAttachmentGuid($attachedObject)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->relation, false, is_object($attachedObject)? $attachedObject->getId(): $attachedObject);
		if ($map)
		{
			return key($map);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Finds attachment identifier by GUID.
	 *
	 * @param string $guid GUID.
	 *
	 * @return string|false
	 */
	public function getAttachmentIdByGuid($guid)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$result = $mapper->getByGuid($this->relation, $guid);
		return $result;
	}

	/**
	 * Executes remote operation for creating file, disk object, attachment object and bind this to entity.
	 *
	 * @param integer $id Entity identifier.
	 * @param \Bitrix\Disk\AttachedObject $attachedObject Disk attachment object.
	 *
	 * @return boolean
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onExecuteAttachmentAdd()
	 */
	function onAttachmentAdd($id, $attachedObject)
	{
		$attachedFile = $attachedObject->getFile();
		if (!$attachedFile)
		{
			return false;
		}

		$fileInfo = \CFile::getFileArray($attachedFile->getFileId());
		if (!$fileInfo)
		{
			return false;
		}

		$userId = $attachedObject->getCreatedBy();
		$guid = \Bitrix\Replica\Mapper::getInstance()->getLogGuid("b_user.ID", $userId);
		if ($guid === false)
		{
			AddMessage2Log("\\Bitrix\\Replica\\Client\\AttachmentHandler::onAttachmentAdd: can not resolve user id: $userId");
			return false;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$guid = $this->getAttachmentGuid($attachedObject);
		if (!$guid)
		{
			$guid = $mapper->generateGuid();
		}

		foreach ($mapper->getByPrimaryValue($this->parentRelation, false, $id) as $nodes)
		{
			foreach ($nodes as  $node)
			{
				$mapper->add($this->relation, $attachedObject->getId(), $node, $guid);
			}
		}

		$operation = new \Bitrix\Replica\Db\Execute();
		$operation->writeToLog(
			$this->executeEventEntity."AttachmentAdd",
			array(
				array(
					"relation" => $this->parentRelation,
					"value" => $id,
				),
				array(
					"value" => array(
						"src" => \CHTTP::urn2uri($fileInfo["SRC"]),
						"name" => $fileInfo["ORIGINAL_NAME"],
						"type" => $fileInfo["CONTENT_TYPE"],
						"size" => $fileInfo["FILE_SIZE"],
						"height" => $fileInfo["HEIGHT"],
						"width" => $fileInfo["WIDTH"],
						"description" => $fileInfo["DESCRIPTION"],
					),
				),
				array(
					"relation" => "b_user.ID",
					"value" => $attachedObject->getCreatedBy(),
				),
				array(
					"value" => $guid,
				),
			)
		);

		return true;
	}

	/**
	 * Handles disk attachment event.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAttachmentAdd()
	 */
	function onExecuteAttachmentAdd(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		$id = $parameters[0];
		$fileInfo = $parameters[1];
		$createdBy = $parameters[2];
		$guid = $parameters[3];

		if ($this->getAttachmentIdByGuid($guid))
		{
			return;
		}

		$fileLoader = \Bitrix\Replica\Client\FileLoader::getInstance();
		$partSize = $fileLoader->getPartSize($fileInfo["name"], $fileInfo["size"]);
		$useFileLoader = ($fileInfo["size"] > $partSize);

		if ($useFileLoader)
		{
			$file = array(
				"content" => "",
				"name" => $fileInfo["name"],
				"height" => $fileInfo["height"],
				"width" => $fileInfo["width"],
				"description" => $fileInfo["description"],
			);
		}
		else
		{
			$file = \CFile::makeFileArray($fileInfo["src"], $fileInfo["type"]);
			$file["name"] = $fileInfo["name"];
			$file["height"] = $fileInfo["height"];
			$file["width"] = $fileInfo["width"];
			$file["description"] = $fileInfo["description"];
			if (!$file["tmp_name"])
			{
				throw new \Bitrix\Replica\ServerException("New file failed. Failed to download file. [".$fileInfo["src"]."]");
			}
		}

		//Start Disk stuff
		if (!\Bitrix\Main\Loader::includeModule('disk'))
		{
			throw new \Bitrix\Replica\ServerException("New file failed. Failed to include disk module.");
		}

		$driver = \Bitrix\Disk\Driver::getInstance();
		$storage = $driver->getStorageByUserId($createdBy);
		if (!$storage)
		{
			throw new \Bitrix\Replica\ServerException("New file failed. Failed to find storage. [".implode($driver->getErrors())."]");
		}

		//Folder & file
		$folder = $storage->getFolderForUploadedFiles();
		$file = $folder->uploadFile($file, array(
			'NAME' => $file['name'],
			'CREATED_BY' => $createdBy,
		), array(), true);

		if (!$file)
		{
			throw new \Bitrix\Replica\ServerException("New file failed. Failed to save file. [".implode($folder->getErrors())."]");
		}

		//Attachment
		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($this->diskConnectorString); //TODO tasks_task ??

		$securityContext = $file->getStorage()->getSecurityContext($createdBy);
		$errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();

		$canUpdate = $file->canUpdate($securityContext);
		$attachedModel = \Bitrix\Disk\AttachedObject::add(array(
			'MODULE_ID' => $this->moduleId,
			'OBJECT_ID' => $file->getId(),
			'ENTITY_ID' => $id,
			'ENTITY_TYPE' => $connectorClass,
			'IS_EDITABLE' => (int)$canUpdate,
			//$_POST - hack. We know.
			'ALLOW_EDIT' => (int)$canUpdate,
			'CREATED_BY' => $createdBy,
		), $errorCollection);
		if(!$attachedModel || $errorCollection->hasErrors())
		{
			throw new \Bitrix\Replica\ServerException("New file failed. Failed to create attach object. [".implode('', $errorCollection)."]");
		}

		$this->updateUserField($id, $attachedModel->getId());

		//Add map
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		foreach ($mapper->getByPrimaryValue($this->parentRelation, false, $id) as $nodes)
		{
			foreach ($nodes as $node)
			{
				$mapper->add($this->relation, $attachedModel->getId(), $node, $guid);
			}
		}

		//Queue download task
		if ($useFileLoader)
		{
			$fileLoader->registerDownload(
				$fileInfo["src"],
				$fileInfo["size"],
				$file->getFileId(),
				$partSize,
				array(
					"FILE_SIZE" => $fileInfo["size"],
					"HEIGHT" => $fileInfo["height"],
					"WIDTH" => $fileInfo["width"],
					"CONTENT_TYPE" => $fileInfo["type"],
				)
			);
		}
	}

	/** @var \Bitrix\Replica\Db\Execute  */
	protected $deleteOp = null;

	/**
	 * Constructs an remote operation event.
	 *
	 * @param integer $id Entity identifier.
	 * @param \Bitrix\Disk\AttachedObject $attachedObject Disk attachment object.
	 *
	 * @return boolean
	 * @see \Bitrix\Replica\Client\AttachmentHandler::flushAttachmentDelete()
	 */
	public function prepareAttachmentDelete($id, $attachedObject)
	{
		$this->deleteOp = new \Bitrix\Replica\Db\Execute($this->executeEventEntity."AttachmentDelete", array(
			array(
				"relation" => $this->parentRelation,
				"value" => $id,
			),
			array(
				"relation" => $this->relation,
				"value" => $attachedObject->getId(),
			),
		));

		return true;
	}

	/**
	 * Writes disk object delete operation into replication log.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::prepareAttachmentDelete()
	 */
	public function flushAttachmentDelete()
	{
		if ($this->deleteOp)
		{
			$this->deleteOp->writeToLog();
		}
	}

	/**
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 * @see \Bitrix\Replica\Client\AttachmentHandler::flushAttachmentDelete()
	 */
	function onExecuteAttachmentDelete(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		$id = $parameters[0];
		$attachmentId = $parameters[1];

		//Start Disk stuff
		if (!\Bitrix\Main\Loader::includeModule('disk'))
		{
			throw new \Bitrix\Replica\ServerException("Delete attachment failed. Failed to include disk module.");
		}

		$attachedModel = \Bitrix\Disk\AttachedObject::loadById($attachmentId, array('OBJECT'));
		if($attachedModel)
		{
			$attachedModel->delete();

			//TODO
			//$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
			//if(!$userFieldManager->belongsToEntity($attachedModel, "TASKS_TASK", $id))
			//{
			//	throw new \Bitrix\Replica\ServerException("Delete attachment failed. Attachment model does not belong to given task.");
			//}
			//TODO

			$mapper = \Bitrix\Replica\Mapper::getInstance();
			$mapper->deleteByPrimaryValue($this->relation, false, $attachmentId);
		}
		else
		{
			AddMessage2Log("Delete attachment failed. Attachment model not found.");
		}
	}
}
