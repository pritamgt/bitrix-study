<?php
namespace Bitrix\Replica\Client;

class BaseHandler
{
	public $insertIgnore = false;

	protected $tableName = "";
	protected $moduleId = "";
	protected $className = "";
	protected $primary = array();
	protected $predicates = array();
	protected $translation = array();
	protected $children = array();

	/**
	 * Method will be invoked before new database record inserted.
	 * When an array returned the insert will be cancelled and map for
	 * returned record will be added.
	 *
	 * @param array &$newRecord All fields of inserted record.
	 *
	 * @return null|array
	 */
	public function beforeInsertTrigger(array &$newRecord)
	{
		return null;
	}

	/**
	 * Method will be invoked after new database record inserted.
	 *
	 * @param array $newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	public function afterInsertTrigger(array $newRecord)
	{
	}

	/**
	 * Method will be invoked before an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array &$newRecord All fields after update.
	 *
	 * @return void
	 */
	public function beforeUpdateTrigger(array $oldRecord, array &$newRecord)
	{
	}

	/**
	 * Method will be invoked after an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array $newRecord All fields after update.
	 *
	 * @return void
	 */
	public function afterUpdateTrigger(array $oldRecord, array $newRecord)
	{
	}

	/**
	 * Method will be invoked after an database record deleted.
	 *
	 * @param array $oldRecord All fields before delete.
	 *
	 * @return void
	 */
	public function afterDeleteTrigger(array $oldRecord)
	{
	}

	/**
	 * Method will be invoked after writing an missed record.
	 *
	 * @param array $record All fields of the record.
	 *
	 * @return void
	 */
	public function afterWriteMissing(array $record)
	{
	}

	/**
	 * Registers event handlers for database operations like add new, update or delete.
	 *
	 * @return void
	 */
	public function initDataManagerEvents()
	{
		$this->initDataManagerAdd();
		$this->initDataManagerUpdate();
		$this->initDataManagerDelete();
	}

	/**
	 * Registers event handler for insert a record.
	 *
	 * @return void
	 */
	public function initDataManagerAdd()
	{
		$this->initDataManagerEvent(\Bitrix\Main\Entity\DataManager::EVENT_ON_AFTER_ADD, "addEventHandler");
	}

	/**
	 * Registers event handler for update a record.
	 *
	 * @return void
	 */
	public function initDataManagerUpdate()
	{
		$this->initDataManagerEvent(\Bitrix\Main\Entity\DataManager::EVENT_ON_AFTER_UPDATE, "updateEventHandler");
	}

	/**
	 * Registers event handler for delete a record.
	 *
	 * @return void
	 */
	public function initDataManagerDelete()
	{
		$this->initDataManagerEvent(\Bitrix\Main\Entity\DataManager::EVENT_ON_AFTER_DELETE, "deleteEventHandler");
	}

	/**
	 * Returns name of the table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * Returns array describing primary key.
	 *
	 * @return array
	 */
	public function getPrimary()
	{
		return $this->primary;
	}

	/**
	 * Returns array describing predicates.
	 *
	 * @return array
	 */
	public function getPredicates()
	{
		return $this->predicates;
	}

	/**
	 * Returns array describing fields to be translated.
	 *
	 * @return array
	 */
	public function getTranslation()
	{
		return $this->translation;
	}

	/**
	 * Returns translation relation for given field.
	 *
	 * @param string $fieldName Table field.
	 * @return string|null
	 */
	public function getTranslationByField($fieldName)
	{
		return $this->translation[$fieldName];
	}

	/**
	 * Returns array describing children relations.
	 *
	 * @return array
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Helps to associate this object method with an D7 event.
	 *
	 * @param string $eventName D7 event identifier.
	 * @param string $method Method of this object to be called.
	 *
	 * @return void
	 */
	protected function initDataManagerEvent($eventName, $method)
	{
		if ($this->moduleId && $this->className)
		{
			$eventClassName = preg_replace("/Table\$/i", "", $this->className);
			\Bitrix\Main\EventManager::getInstance()->addEventHandler(
				$this->moduleId,
				$eventClassName."::".$eventName,
				array($this, $method)
			);
		}
	}

	/**
	 * Default insert event handler.
	 *
	 * @param \Bitrix\Main\Entity\Event $event D7 event.
	 *
	 * @return void
	 */
	public function addEventHandler(\Bitrix\Main\Entity\Event $event)
	{
		$entity = $event->getEntity();
		$tableName = $entity->getDBTableName();
		$primaryField = $entity->getPrimary();
		$primaryValue = $event->getParameter("primary");

		\Bitrix\Replica\Db\Operation::writeInsert($tableName, $primaryField, $primaryValue);

	}

	/**
	 * Default update event handler.
	 *
	 * @param \Bitrix\Main\Entity\Event $event D7 event.
	 *
	 * @return void
	 */
	public function updateEventHandler(\Bitrix\Main\Entity\Event $event)
	{
		$entity = $event->getEntity();
		$tableName = $entity->getDBTableName();
		$primaryField = $entity->getPrimary();
		$primaryValue = $event->getParameter("primary");

		\Bitrix\Replica\Db\Operation::writeUpdate($tableName, $primaryField, $primaryValue);
	}

	/**
	 * Default delete event handler.
	 *
	 * @param \Bitrix\Main\Entity\Event $event D7 event.
	 *
	 * @return void
	 */
	public function deleteEventHandler(\Bitrix\Main\Entity\Event $event)
	{
		$entity = $event->getEntity();
		$tableName = $entity->getDBTableName();
		$primaryField = $entity->getPrimary();
		$primaryValue = $event->getParameter("primary");

		\Bitrix\Replica\Db\Operation::writeDelete($tableName, $primaryField, $primaryValue);
	}

	/**
	 * Called before insert operation log write. You may return false and not log write will take place.
	 *
	 * @param array $record Database record.
	 *
	 * @return boolean
	 */
	public function beforeLogInsert(array $record)
	{
		return true;
	}

	/**
	 * Called before update operation log write. You may return false and not log write will take place.
	 *
	 * @param array $record Database record.
	 *
	 * @return boolean
	 */
	public function beforeLogUpdate(array $record)
	{
		return true;
	}

	/**
	 * Called before record transformed for log writing.
	 *
	 * @param array &$record Database record.
	 *
	 * @return void
	 */
	public function beforeLogFormat(array &$record)
	{
	}
}
