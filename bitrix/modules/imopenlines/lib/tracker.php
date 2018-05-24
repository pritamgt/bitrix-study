<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Imopenlines\Model\TrackerTable;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Tracker
{
	const FIELD_PHONE = 'PHONE';
	const FIELD_EMAIL = 'EMAIL';
	const FIELD_IM = 'IM';

	const ACTION_CREATE = 'CREATE';
	const ACTION_EXTEND = 'EXTEND';

	const MESSAGE_ERROR_CREATE = 'CREATE';
	const MESSAGE_ERROR_EXTEND = 'EXTEND';

	private $error = null;

	public function __construct()
	{
		$this->error = new Error(null, '', '');
	}

	private function checkMessage($messageText)
	{
		$result = Array(
			'PHONES' => Array(),
			'EMAILS' => Array(),
		);

		preg_match_all("/(\+)?([\d\-\(\) ]){6,}/i", $messageText, $matches);
		if ($matches)
		{
			foreach ($matches[0] as $phone)
			{
				$phoneNormalize = NormalizePhone(trim($phone), 6);
				if ($phoneNormalize)
				{
					$result['PHONES'][$phoneNormalize] = trim($phone);
				}
			}
		}

		preg_match_all("/[^\s]+@[^\s]+/i", $messageText, $matches);
		if ($matches)
		{
			foreach ($matches[0] as $email)
			{
				$email = trim($email);
				$result['EMAILS'][$email] = $email;
			}
		}

		return $result;
	}

	public function message($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		/* @var \Bitrix\ImOpenLines\Session $session */
		$session = $params['SESSION'];
		if (!($session instanceof \Bitrix\ImOpenLines\Session))
			return false;

		$messageOriginId = intval($params['MESSAGE']['ID']);
		$messageText = $this->prepareMessage($params['MESSAGE']['TEXT']);

		if (isset($params['MESSAGE']['ID']) && !$messageOriginId || strlen($messageText) <= 0)
			return false;

		if ($session->getConfig('CRM') != 'Y')
			return true;

		$limitRemainder = Limit::getTrackerLimitRemainder();
		if ($limitRemainder <= 0)
		{
			$this->sendLimitMessage(Array(
				'OPERATOR_ID' => $session->getData('OPERATOR_ID'),
				'CHAT_ID' => $session->getData('CHAT_ID'),
				'MESSAGE_TYPE' => self::MESSAGE_ERROR_EXTEND
			));

			return false;
		}

		$result = $this->checkMessage($messageText);
		$phones = $result['PHONES'];
		$emails = $result['EMAILS'];

		if (empty($phones) && empty($emails))
		{
			return false;
		}

		$crm = new Crm();

		$current = Array();
		$updateFm = Array();
		$addLog = Array();

		if (isset($params['CRM']['ENTITY_TYPE']) && isset($params['CRM']['ENTITY_ID']))
		{
			$current['ACTION'] = self::ACTION_EXTEND;
			$current['CRM_ENTITY_TYPE'] = $params['CRM']['ENTITY_TYPE'];
			$current['CRM_ENTITY_ID'] = $params['CRM']['ENTITY_ID'];

			if (
				$session->getData('SOURCE') == Connector::TYPE_LIVECHAT &&
				\Bitrix\Im\User::getInstance($session->getData('USER_ID'))->isConnector() &&
				\Bitrix\Im\User::getInstance($session->getData('USER_ID'))->getName() == ''
			)
			{
				$current['CHANGE_NAME'] = 'Y';
			}
			$communicationType = Crm::getCommunicationType($session->getData('USER_CODE'));
			$updateFm['IM_'.$communicationType] = 'imol|'.$session->getData('USER_CODE');
			$addLog['im'] = Array(
				'FIELD_TYPE' => self::FIELD_IM,
				'FIELD_VALUE' => $updateFm['IM_'.$communicationType]
			);

			$session->update(Array(
				'CRM_CREATE' => 'Y',
				'CRM' => 'Y',
				'CRM_ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $current['CRM_ENTITY_ID'],
			));

			$session->chat->setCrmFlag(Array(
				'ACTIVE' => 'Y',
				'ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
				'ENTITY_ID' => $current['CRM_ENTITY_ID'],
			));
		}
		else if ($session->getData('CRM') == 'Y')
		{
			$current['ACTION'] = self::ACTION_EXTEND;
			$current['CRM_ENTITY_TYPE'] = $session->getData('CRM_ENTITY_TYPE');
			$current['CRM_ENTITY_ID'] = $session->getData('CRM_ENTITY_ID');
		}
		else
		{
			$crmData = false;
			$count = 0;
			foreach ($phones as $phone)
			{
				$crmData = $crm->find(Crm::FIND_BY_PHONE, Array('PHONE' => $phone));

				$count++;
				if ($crmData || $count >= 5)
				{
					break;
				}
			}

			if (!$crmData)
			{
				$count = 0;
				foreach ($emails as $email)
				{
					$crmData = $crm->find(Crm::FIND_BY_EMAIL, Array('EMAIL' => $email));

					$count++;
					if ($crmData || $count >= 5)
					{
						break;
					}
				}
			}

			if ($crmData)
			{
				$current['ACTION'] = self::ACTION_EXTEND;
				$current['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE'];
				$current['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];

				if (
					$session->getData('SOURCE') == Connector::TYPE_LIVECHAT &&
					\Bitrix\Im\User::getInstance($session->getData('USER_ID'))->isConnector() &&
					\Bitrix\Im\User::getInstance($session->getData('USER_ID'))->getName() == ''
				)
				{
					$current['CHANGE_NAME'] = 'Y';
				}
				$communicationType = Crm::getCommunicationType($session->getData('USER_CODE'));
				$updateFm['IM_'.$communicationType] = 'imol|'.$session->getData('USER_CODE');
				$addLog['im'] = Array(
					'FIELD_TYPE' => self::FIELD_IM,
					'FIELD_VALUE' => $updateFm['IM_'.$communicationType]
				);
			}
			else
			{
				$current['CRM_ENTITY_ID'] = $crm->addLead(array(
					'CONFIG_ID' => $session->getData('CONFIG_ID'),
					'USER_CODE' => $session->getData('USER_CODE'),
					'USER_ID' => $session->getData('USER_ID'),
					'TITLE' => $session->chat->getData('TITLE'),
					'OPERATOR_ID' => $session->getData('OPERATOR_ID'),
				));
				$current['CRM_ENTITY_ID'] = intval($current['CRM_ENTITY_ID']);
				if ($current['CRM_ENTITY_ID'])
				{
					$current['ACTION'] = self::ACTION_CREATE;
					$current['CRM_ENTITY_TYPE'] = Crm::ENTITY_LEAD;
				}
			}

			$updateSession = Array(
				'CRM_CREATE' => 'Y',
				'CRM' => 'Y',
				'CRM_ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $current['CRM_ENTITY_ID'],
			);

			if ($session->getData('CRM_ACTIVITY_ID'))
			{
				if (
					$session->getData('CRM_ENTITY_TYPE') != $current['CRM_ENTITY_TYPE']
					|| $session->getData('CRM_ENTITY_ID') !=  $current['CRM_ENTITY_ID']
				)
				{
					$crm->updateActivity(Array(
						'ID' => $session->getData('CRM_ACTIVITY_ID'),
						'UPDATE' => Array(
							'CRM_ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
							'CRM_ENTITY_ID' => $current['CRM_ENTITY_ID'],
							'CRM_BINDINGS' => Array(array(
								'OWNER_ID' => $current['CRM_ENTITY_ID'],
								'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($current['CRM_ENTITY_TYPE'])
							)),
						)
					));
				}
			}
			else
			{
				$current['CRM_BINDINGS'][] = array(
					'OWNER_ID' => $current['CRM_ENTITY_ID'],
					'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($current['CRM_ENTITY_TYPE'])
				);

				$current['CRM_ACTIVITY_ID'] = $crm->addActivity(Array(
					'TITLE' => $session->chat->getData('TITLE'),
					'MODE' => $session->getData('MODE'),
					'USER_CODE' => $session->getData('USER_CODE'),
					'SESSION_ID' => $session->getData('SESSION_ID'),
					'COMPLETED' => 'N',
					'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
					'AUTHOR_ID' => $session->getData('OPERATOR_ID'),
					'RESPONSIBLE_ID' => $session->getData('OPERATOR_ID'),
					'CRM_ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $current['CRM_ENTITY_ID'],
					'CRM_BINDINGS' => $current['CRM_BINDINGS'],
					'ANSWERED' => $session->getData('ANSWERED') == 'Y'? 'Y': 'N',
				));

				$crm->executeAutomationTrigger($current['CRM_BINDINGS'], array(
					'CONFIG_ID' => $session->getData('CONFIG_ID')
				));

				$updateSession['CRM_ACTIVITY_ID'] = $current['CRM_ACTIVITY_ID'];
			}

			$session->update($updateSession);
			$session->chat->setCrmFlag(Array(
				'ACTIVE' => 'Y',
				'ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
				'ENTITY_ID' => $current['CRM_ENTITY_ID'],
			));
		}

		$entityData = $crm->get($current['CRM_ENTITY_TYPE'], $current['CRM_ENTITY_ID'], true);
		if (!$entityData)
		{
			return false;
		}

		if ($current['CHANGE_NAME'] == 'Y' && $entityData['NAME'] && $entityData['LAST_NAME'])
		{
			$user = new \CUser();
			$user->Update($session->getData('USER_ID'), Array(
				'NAME' => $entityData['NAME'],
				'LAST_NAME' => $entityData['LAST_NAME'],
			));

			$relations = \CIMChat::GetRelationById($session->getData('CHAT_ID'));
			\Bitrix\Pull\Event::add(array_keys($relations), Array(
				'module_id' => 'im',
				'command' => 'updateUser',
				'params' => Array(
					'user' => \Bitrix\Im\User::getInstance($session->getData('USER_ID'))->getFields()
				),
				'extra' => Array(
					'im_revision' => IM_REVISION,
					'im_revision_mobile' => IM_REVISION_MOBILE,
				),
			));
		}

		if (!empty($entityData['FM']['IM']) && !empty($updateFm))
		{
			foreach ($updateFm as $key => $updateCode)
			{
				foreach ($entityData['FM']['IM'] as $type)
				{
					foreach ($type as $code)
					{
						if (trim($updateCode) == trim($code))
						{
							unset($updateFm[$key]);
							unset($addLog['im']);
						}
					}
				}
			}
		}
		if (!empty($entityData['FM']['PHONE']))
		{
			foreach ($entityData['FM']['PHONE'] as $fmPhones)
			{
				foreach ($fmPhones as $phone)
				{
					$phone = NormalizePhone($phone, 6);
					if (isset($phones[$phone]))
					{
						unset($phones[$phone]);
					}
				}
			}
		}
		if (!empty($entityData['FM']['EMAIL']))
		{
			foreach ($entityData['FM']['EMAIL'] as $fmEmails)
			{
				foreach ($fmEmails as $email)
				{
					$email = trim($email);
					if (isset($emails[$email]))
					{
						unset($emails[$email]);
					}
				}
			}
		}

		if (!empty($phones))
		{
			$updateFm['PHONE_WORK'] = implode(';', $phones);
			foreach ($phones as $phone)
			{
				$addLog[] = Array(
					'FIELD_TYPE' => self::FIELD_PHONE,
					'FIELD_VALUE' => $phone
				);
			}
		}
		if (!empty($emails))
		{
			$updateFm['EMAIL_WORK'] = implode(';', $emails);
			foreach ($emails as $email)
			{
				$addLog[] = Array(
					'FIELD_TYPE' => self::FIELD_EMAIL,
					'FIELD_VALUE' => $email
				);
			}
		}

		if (!empty($updateFm))
		{
			$crm->update(
				$current['CRM_ENTITY_TYPE'],
				$current['CRM_ENTITY_ID'],
				$updateFm
			);
		}
		$attach = $crm->getEntityCard($current['CRM_ENTITY_TYPE'], $current['CRM_ENTITY_ID']);
		if ($current['ACTION'] == self::ACTION_CREATE)
		{
			$message =  Loc::getMessage('IMOL_TRACKER_'.$current['CRM_ENTITY_TYPE'].'_ADD');
			$keyboard = new \Bitrix\Im\Bot\Keyboard();
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CHANGE'),
				"FUNCTION" => "BX.MessengerCommon.linesChangeCrmEntity(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
				"CONTEXT" => "DESKTOP",
			));
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CANCEL'),
				"FUNCTION" => "BX.MessengerCommon.linesCancelCrmExtend(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
			));
		}
		else
		{
			$message =  Loc::getMessage('IMOL_TRACKER_'.$current['CRM_ENTITY_TYPE'].'_EXTEND');
			$keyboard = new \Bitrix\Im\Bot\Keyboard();
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CHANGE'),
				"FUNCTION" => "BX.MessengerCommon.linesChangeCrmEntity(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
				"CONTEXT" => "DESKTOP",
			));
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CANCEL'),
				"FUNCTION" => "BX.MessengerCommon.linesCancelCrmExtend(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
			));
		}

		$messageId = 0;
		if ($message)
		{
			if ($params['UPDATE_ID'])
			{
				$messageId = $params['UPDATE_ID'];

				\CIMMessenger::DisableMessageCheck();
				\CIMMessageParam::Set($messageId, Array('ATTACH' => $attach));
				\CIMMessenger::Update($messageId, $message, true, false);
				\CIMMessenger::EnableMessageCheck();
			}
			else
			{
				$userViewChat = \CIMContactList::InRecent($session->getData('OPERATOR_ID'), IM_MESSAGE_OPEN_LINE, $session->getData('CHAT_ID'));

				$messageId = Im::addMessage(Array(
					"TO_CHAT_ID" => $session->getData('CHAT_ID'),
					"MESSAGE" => '[b]'.$message.'[/b]',
					"SYSTEM" => 'Y',
					"ATTACH" => $attach,
					"KEYBOARD" => $keyboard,
					"RECENT_ADD" => $userViewChat? 'Y': 'N'
				));
			}
		}

		if (!empty($updateFm))
		{
			foreach ($addLog as $log)
			{
				TrackerTable::add(Array(
					'SESSION_ID' => $session->getData('ID'),
					'CHAT_ID' => $session->getData('CHAT_ID'),
					'MESSAGE_ID' => $messageId,
					'MESSAGE_ORIGIN_ID' => $messageOriginId,
					'USER_ID' => $session->getData('USER_ID'),
					'ACTION' => $current['ACTION'],
					'CRM_ENTITY_TYPE' => $current['CRM_ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $current['CRM_ENTITY_ID'],
					'FIELD_TYPE' => $log['FIELD_TYPE'],
					'FIELD_VALUE' => $log['FIELD_VALUE'],
				));
			}
		}
		\Bitrix\Imopenlines\Limit::increaseTracker();
		return true;
	}

	public function user($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$limitRemainder = Limit::getTrackerLimitRemainder();
		if ($limitRemainder <= 0)
		{
			$this->sendLimitMessage(Array(
				'CHAT_ID' => $params['CHAT_ID'],
				'MESSAGE_TYPE' => self::MESSAGE_ERROR_EXTEND
			));

			return false;
		}

		$user = \Bitrix\Im\User::getInstance($params['USER_ID']);

		$crm = new Crm();
		$crmData = $crm->find(Crm::FIND_BY_NAME, Array('NAME' => $user->getName(false), 'LAST_NAME' => $user->getLastName(false)));

		if (!$crmData && $user->getEmail())
		{
			$crmData = $crm->find(Crm::FIND_BY_EMAIL, Array('EMAIL' => $user->getEmail()));
		}
		if (!$crmData && $user->getPhone())
		{
			$crmData = $crm->find(Crm::FIND_BY_PHONE, Array('PHONE' => $user->getPhone()));
		}

		if ($crmData)
		{
			$entityData = $crm->get($crmData['ENTITY_TYPE'], $crmData['ENTITY_ID'], true);

			$keyboard = new \Bitrix\Im\Bot\Keyboard();
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CHANGE'),
				"FUNCTION" => "BX.MessengerCommon.linesChangeCrmEntity(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
				"CONTEXT" => "DESKTOP",
			));
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CANCEL'),
				"FUNCTION" => "BX.MessengerCommon.linesCancelCrmExtend(#MESSAGE_ID#);",
				"DISPLAY" => "LINE",
			));

			$userViewChat = \CIMContactList::InRecent($params['OPERATOR_ID'], IM_MESSAGE_OPEN_LINE, $params['SESSION_ID']);

			$messageId = Im::addMessage(Array(
				"TO_CHAT_ID" => $params['CHAT_ID'],
				"MESSAGE" => '[b]'.Loc::getMessage('IMOL_TRACKER_'.$crmData['ENTITY_TYPE'].'_EXTEND').'[/b]',
				"SYSTEM" => 'Y',
				"ATTACH" => $crm->getEntityCard($crmData['ENTITY_TYPE'], $crmData['ENTITY_ID']),
				"KEYBOARD" => $keyboard,
				"RECENT_ADD" => $userViewChat? 'Y': 'N'
			));

			$result = TrackerTable::add(Array(
				'SESSION_ID' => intval($params['SESSION_ID']),
				'CHAT_ID' => $params['CHAT_ID'],
				'MESSAGE_ID' => $messageId,
				'USER_ID' => $params['USER_ID'],
				'ACTION' => self::ACTION_EXTEND,
				'CRM_ENTITY_TYPE' => $crmData['ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $crmData['ENTITY_ID'],
				'FIELD_TYPE' => self::FIELD_IM,
				'FIELD_VALUE' => 'imol|'.$params['USER_CODE'],
			));
			$crmData['CRM_TRACK_ID'] = $result->getId();

			$updateFields = Array();

			$imTypeKey = 'IM_'. Crm::getCommunicationType($params['USER_CODE']);
			$updateFields[$imTypeKey] = 'imol|'.$params['USER_CODE'];

			if (!empty($entityData['FM']['IM']))
			{
				foreach ($entityData['FM']['IM'] as $fmIm)
				{
					foreach ($fmIm as $im)
					{
						if ($updateFields[$imTypeKey] == $im)
						{
							unset($updateFields[$imTypeKey]);
						}
					}
				}
			}

			if ($user->getEmail())
			{
				$updateFields['EMAIL_WORK'] = $user->getEmail();
				if (!empty($entityData['FM']['EMAIL']))
				{
					foreach ($entityData['FM']['EMAIL'] as $fmEmails)
					{
						foreach ($fmEmails as $email)
						{
							if (trim($updateFields['EMAIL_WORK']) == trim($email))
							{
								unset($updateFields['EMAIL_WORK']);
							}
						}
					}
				}
			}
			if ($user->getPhone())
			{
				$updateFields['PHONE_MOBILE'] = $user->getPhone();
				if (!empty($entityData['FM']['PHONE']))
				{
					foreach ($entityData['FM']['PHONE'] as $fmPhones)
					{
						foreach ($fmPhones as $phone)
						{
							if (NormalizePhone($updateFields['PHONE_MOBILE'], 6) == NormalizePhone($phone, 6))
							{
								unset($updateFields['PHONE_MOBILE']);
							}
						}
					}
				}
			}
			if ($user->getWebsite())
			{
				if (strlen($user->getWebsite()) > 255)
				{
					if ($user->getWebsite() != $entityData['SOURCE_DESCRIPTION'])
					{
						$entityData['SOURCE_DESCRIPTION'] = $entityData['SOURCE_DESCRIPTION'].' '.$user->getWebsite();
						$entityData['SOURCE_DESCRIPTION'] = trim($entityData['SOURCE_DESCRIPTION']);
					}
				}
				else
				{
					$updateFields['WEB_HOME'] = $user->getWebsite();
					if (!empty($entityData['FM']['WEB']))
					{
						foreach ($entityData['FM']['WEB'] as $fmWeb)
						{
							foreach ($fmWeb as $web)
							{
								if ($updateFields['WEB_HOME'] == $web)
								{
									unset($updateFields['WEB_HOME']);
								}
							}
						}
					}
				}
			}

			$crm->update($crmData['ENTITY_TYPE'], $crmData['ENTITY_ID'], $updateFields);
			\Bitrix\Imopenlines\Limit::increaseTracker();
		}

		return $crmData;
	}

	public function cancel($messageId)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$action = '';
		$entityType = '';
		$entityId = 0;
		$chatId = 0;
		$sessionId = 0;

		$updateCrm = true;

		$log = Array();
		$delete = Array();
		$orm = Model\TrackerTable::getList(Array(
			'filter' => Array('=MESSAGE_ID' => $messageId)
		));
		while ($row = $orm->fetch())
		{
			$action = $row['ACTION'];
			$entityType = $row['CRM_ENTITY_TYPE'];
			$entityId = $row['CRM_ENTITY_ID'];
			$chatId = $row['CHAT_ID'];
			$sessionId = $row['SESSION_ID'];

			$log[$row['FIELD_TYPE']][] = $row['FIELD_VALUE'];
			$delete[] = $row['ID'];
		}

		if (empty($delete))
			return false;

		$crm = new Crm();

		if ($action == self::ACTION_CREATE)
		{
			$entityData = $crm->get($entityType, $entityId);

			$currentTime = new \Bitrix\Main\Type\DateTime();
			$entityTime = new \Bitrix\Main\Type\DateTime($entityData['DATE_CREATE']);
			$entityTime->add('1 DAY');
			if ($currentTime < $entityTime)
			{
				$crm->delete($entityType, $entityId);

				$chat = new Chat($chatId);
				$chat->updateFieldData(Chat::FIELD_SESSION, Array(
					'CRM' => 'N',
					'CRM_ENTITY_TYPE' => Crm::ENTITY_NONE,
					'CRM_ENTITY_ID' => 0
				));

				Model\SessionTable::update($sessionId, Array(
					'CRM' => 'N',
					'CRM_CREATE' => 'N',
					'CRM_ENTITY_TYPE' => Crm::ENTITY_NONE,
					'CRM_ENTITY_ID' => 0
				));

				$updateCrm = false;
			}
		}

		if ($updateCrm)
		{
			foreach ($log as $type => $values)
			{
				foreach ($values as $value)
				{
					$crm->deleteMultiField($entityType, $entityId, $type, $value);
				}
			}
		}
		foreach ($delete as $id)
		{
			Model\TrackerTable::delete($id);
		}

		\CIMMessenger::DisableMessageCheck();
		\CIMMessenger::Delete($messageId, null, true);
		\CIMMessenger::EnableMessageCheck();

		return true;
	}

	public function change($messageId, $newEntityType, $newEntityId)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$messageId = intval($messageId);
		if ($messageId <= 0)
			return false;

		if (!in_array($newEntityType, Array(Crm::ENTITY_COMPANY, Crm::ENTITY_LEAD, Crm::ENTITY_CONTACT)))
			return false;

		$newEntityId = intval($newEntityId);
		if ($newEntityId <= 0)
			return false;

		$action = '';
		$entityType = '';
		$entityId = 0;
		$sessionId = 0;
		$messageOriginId = 0;

		$log = Array();
		$delete = Array();
		$orm = Model\TrackerTable::getList(Array(
			'filter' => Array('=MESSAGE_ID' => $messageId)
		));
		while ($row = $orm->fetch())
		{
			$action = $row['ACTION'];
			$messageOriginId = $row['MESSAGE_ORIGIN_ID'];
			$entityType = $row['CRM_ENTITY_TYPE'];
			$entityId = $row['CRM_ENTITY_ID'];
			$sessionId = $row['SESSION_ID'];

			$log[$row['FIELD_TYPE']][] = $row['FIELD_VALUE'];
			$delete[] = $row['ID'];
		}

		if ($newEntityType == $entityType && $newEntityId == $entityId)
			return false;

		if (empty($delete))
			return false;

		$crm = new Crm();

		$updateCrm = true;
		if ($action == self::ACTION_CREATE)
		{
			$entityData = $crm->get($entityType, $entityId, true);

			$currentTime = new \Bitrix\Main\Type\DateTime();
			$entityTime = new \Bitrix\Main\Type\DateTime($entityData['DATE_CREATE']);
			$entityTime->add('1 DAY');
			if ($currentTime < $entityTime)
			{
				$crm->delete($entityType, $entityId);
				$updateCrm = false;
			}
		}

		if ($updateCrm)
		{
			foreach ($log as $type => $values)
			{
				foreach ($values as $value)
				{
					$crm->deleteMultiField($entityType, $entityId, $type, $value);
				}
			}
		}

		foreach ($delete as $id)
		{
			Model\TrackerTable::delete($id);
		}

		if (!$messageOriginId)
		{
			return true;
		}

		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();

		$session = new Session();
		$result = $session->load(Array(
			'USER_CODE' => $sessionData['USER_CODE']
		));
		if ($result)
		{
			$messageData = \Bitrix\Im\Model\MessageTable::getById($messageOriginId)->fetch();
			$this->message(Array(
				'SESSION' => $session,
				'MESSAGE' => Array(
					'ID' => $messageData["ID"],
					'TEXT' => $messageData["MESSAGE"],
				),
				'UPDATE_ID' => $messageId,
				'CRM' => Array(
					'ENTITY_TYPE' => $newEntityType,
					'ENTITY_ID' => $newEntityId,
				)
			));
		}

		return true;
	}

	public function updateLog($params)
	{
		$id = intval($params['ID']);
		if ($id <= 0)
		{
			return false;
		}

		$update = $params['UPDATE'];
		if (!is_array($update))
		{
			return false;
		}

		$map = Model\TrackerTable::getMap();
		foreach ($update as $key => $value)
		{
			if (!isset($map[$key]))
			{
				unset($update[$key]);
			}
		}
		if (count($update) <= 0)
		{
			return false;
		}

		Model\TrackerTable::update($params['ID'], $params['UPDATE']);

		return true;
	}

	public function sendLimitMessage($params)
	{
		$chatId = intval($params['CHAT_ID']);
		if ($chatId <= 0)
			return false;

		if ($params['MESSAGE_TYPE'] == self::MESSAGE_ERROR_CREATE)
		{
			$message =  Loc::getMessage('IMOL_TRACKER_LIMIT_1');
		}
		else
		{
			$message =  Loc::getMessage('IMOL_TRACKER_LIMIT_2');
		}

		$message = str_replace(Array('#LINK_START#', '#LINK_END#'), '', $message);

		$keyboard = new \Bitrix\Im\Bot\Keyboard();
		$keyboard->addButton(Array(
			"TEXT" => Loc::getMessage('IMOL_TRACKER_LIMIT_BUTTON'),
			"LINK" => "/settings/license_all.php",
			"DISPLAY" => "LINE",
			"CONTEXT" => "DESKTOP",
		));

		$userViewChat = \CIMContactList::InRecent($params['OPERATOR_ID'], IM_MESSAGE_OPEN_LINE, $chatId);

		Im::addMessage(Array(
			"TO_CHAT_ID" => $chatId,
			"MESSAGE" => $message,
			"SYSTEM" => 'Y',
			"KEYBOARD" => $keyboard,
			"RECENT_ADD" => $userViewChat? 'Y': 'N'
		));

		return true;
	}

	private function prepareMessage($text)
	{
		$textParser = new \CTextParser();
		$textParser->allow = array("HTML" => "N", "USER" => "N",  "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		$text = preg_replace("/\[[buis]\](.*?)\[\/[buis]\]/i", "$1", $text);
		$text = $textParser->convertText($text);

		$text = preg_replace('/<([\w]+)[^>]*>(.*?)<\/\1>/i', "", $text);
		$text = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $text);
		$text = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $text);
		$text = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", " ", $text);
		$text = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", " ", $text);
		$text = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", " ", $text);
		$text = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", " ", $text);
		$text = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", " ", $text);
		$text = preg_replace("/\[ICON\=([^\]]*)\]/i", " ", $text);
		$text = preg_replace('#\-{54}.+?\-{54}#s', " ", str_replace(array("#BR#"), Array(" "), $text));

		return $text;
	}

	public function getError()
	{
		return $this->error;
	}
}
