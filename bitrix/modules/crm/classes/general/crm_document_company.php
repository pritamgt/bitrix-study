<?
if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

class CCrmDocumentCompany extends CCrmDocument
	implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.' .
			strtolower($entityType).'.edit/component.php');

		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';
		$emailFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_EMAIL').')';
		$workPhoneFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_WORK_PHONE').')';
		$personalMobileFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_PERSONAL_MOBILE').')';

		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_TITLE_COMPANY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'LOGO' => array(
				'Name' => GetMessage('CRM_FIELD_LOGO'),
				'Type' => 'file',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'COMPANY_TYPE' => array(
				'Name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('COMPANY_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'INDUSTRY' => array(
				'Name' => GetMessage('CRM_FIELD_INDUSTRY'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('INDUSTRY'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EMPLOYEES' => array(
				'Name' => GetMessage('CRM_FIELD_EMPLOYEES'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('EMPLOYEES'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'REVENUE' => array(
				'Name' => GetMessage('CRM_FIELD_REVENUE'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'ASSIGNED_BY_EMAIL' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$emailFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'ASSIGNED_BY_WORK_PHONE' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$workPhoneFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'ASSIGNED_BY_PERSONAL_MOBILE' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$personalMobileFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'EMAIL' => array(
				'Name' => GetMessage('CRM_FIELD_EMAIL'),
				'Type' => 'email',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'PHONE' => array(
				'Name' => GetMessage('CRM_FIELD_PHONE'),
				'Type' => 'phone',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'WEB' => array(
				'Name' => GetMessage('CRM_FIELD_WEB'),
				'Type' => 'web',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'IM' => array(
				'Name' => GetMessage('CRM_FIELD_MESSENGER'),
				'Type' => 'im',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS' => array(
				'Name' => GetMessage('CRM_FIELD_ADDRESS'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ADDRESS_LEGAL' => array(
				'Name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'BANKING_DETAILS' => array(
				'Name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
				'Type' => 'text',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			"OPENED" => array(
				"Name" => GetMessage("CRM_FIELD_OPENED"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"LEAD_ID" => array(
				"Name" => GetMessage("CRM_FIELD_LEAD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGINATOR_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGINATOR_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGIN_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGIN_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CONTACT_ID" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_ID"),
				"Type" => "UF:crm",
				"Options" => array('CONTACT' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_COMPANY_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_COMPANY_EDIT_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			'WEBFORM_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_WEBFORM_ID'),
				'Type' => 'select',
				'Options' => static::getWebFormSelectOptions(),
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
		);

		$ar =  CCrmFieldMulti::GetEntityTypeList();
		foreach ($ar as $typeId => $arFields)
		{
			$arResult[$typeId.'_PRINTABLE'] = array(
				'Name' => GetMessage("CRM_FIELD_MULTI_".$typeId).$printableFieldNameSuffix,
				'Type' => 'string',
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			);
			foreach ($arFields as $valueType => $valueName)
			{
				$arResult[$typeId.'_'.$valueType] = array(
					'Name' => $valueName,
					'Type' => 'string',
					"Filterable" => true,
					"Editable" => false,
					"Required" => false,
				);
				$arResult[$typeId.'_'.$valueType.'_PRINTABLE'] = array(
					'Name' => $valueName.$printableFieldNameSuffix,
					'Type' => 'string',
					"Filterable" => true,
					"Editable" => false,
					"Required" => false,
				);
			}
		}

		global $USER_FIELD_MANAGER;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_COMPANY');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		return $arResult;
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception("Entity fields must be array");
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
			$arDocumentID['TYPE'] = $parentDocumentId;

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				CCrmDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "COMPANY_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_COMPANY', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if (isset($arFields['CONTACT_ID']) && !is_array($arFields['CONTACT_ID']))
			$arFields['CONTACT_ID'] = array($arFields['CONTACT_ID']);

		if(isset($arFields['COMMENTS']))
		{
			if(preg_match('/<[^>]+[\/]?>/i', $arFields['COMMENTS']) === 1)
			{
				$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['COMMENTS']);
			}
			$arFields['COMMENTS'] = str_replace(array("\r\n", "\r", "\n"), "<br>", $arFields['COMMENTS']);
		}

		if(isset($arFields['ADDRESS_LEGAL']))
		{
			$arFields['REG_ADDRESS'] = $arFields['ADDRESS_LEGAL'];
			unset($arFields['ADDRESS_LEGAL']);
		}

		$DB->StartTransaction();

		$CCrmEntity = new CCrmCompany(false);
		$ID = $CCrmEntity->Add(
			$arFields,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if ($ID <= 0)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		//region Try to create requisite
		if((isset($arFields['ADDRESS']) && $arFields['ADDRESS'] !== '') ||
			(isset($arFields['REG_ADDRESS']) && $arFields['REG_ADDRESS'] !== ''))
		{
			$presetID = \Bitrix\Crm\EntityRequisite::getDefaultPresetId(CCrmOwnerType::Company);
			if($presetID > 0)
			{
				$converter = new \Bitrix\Crm\Requisite\AddressRequisiteConverter(CCrmOwnerType::Company, $presetID, false);
				$converter->processEntity($ID);
			}
		}
		//endregion

		if (COption::GetOptionString('crm', 'start_bp_within_bp', 'N') == 'Y')
		{
			$CCrmBizProc = new CCrmBizProc(CCrmOwnerType::CompanyName);
			if ($CCrmBizProc->CheckFields(false, true) === false)
			{
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}

			if (!$CCrmBizProc->StartWorkflow($ID))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		$DB->Commit();
		return $ID;
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		global $DB;

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$dbDocumentList = CCrmCompany::GetList(
			array(),
			array('ID' => $arDocumentID['ID'], "CHECK_PERMISSIONS" => "N"),
			array('ID')
		);

		$arResult = $dbDocumentList->Fetch();
		if (!$arResult)
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$fieldType = $arDocumentFields[$key]["Type"];
			if (in_array($fieldType, array("phone", "email", "im", "web"), true))
			{
				CCrmDocument::PrepareEntityMultiFields($arFields, strtoupper($fieldType));
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);
			if ($fieldType == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($fieldType == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_COMPANY', $arFields, $key);
			}
			elseif ($fieldType == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($fieldType == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if (isset($arFields['CONTACT_ID']) && !is_array($arFields['CONTACT_ID']))
			$arFields['CONTACT_ID'] = array($arFields['CONTACT_ID']);

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", $arFields['COMMENTS']);
		}

		if(isset($arFields['ADDRESS_LEGAL']))
		{
			$arFields['REG_ADDRESS'] = $arFields['ADDRESS_LEGAL'];
			unset($arFields['ADDRESS_LEGAL']);
		}

		$DB->StartTransaction();
		$CCrmEntity = new CCrmCompany(false);

		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if (!$res)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('COMPANY');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		if ($res)
			$DB->Commit();
	}

	static public function PrepareDocument(array &$arFields)
	{
		$arFields['CONTACT_ID'] = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arFields['ID']);
	}

	public function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		return CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arDocumentID['ID'], false);
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::CompanyName,
			CCrmOwnerTypeAbbr::Company
		);
	}
}
