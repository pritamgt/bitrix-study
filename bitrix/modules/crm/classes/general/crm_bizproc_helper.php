<?php
class CCrmBizProcHelper
{
	public static function ResolveDocumentName($ownerTypeID)
	{
		$ownerTypeID = intval($ownerTypeID);

		$docName = '';
		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$docName = 'CCrmDocumentContact';
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$docName = 'CCrmDocumentCompany';
		}
		elseif($ownerTypeID === CCrmOwnerType::Lead)
		{
			$docName = 'CCrmDocumentLead';
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$docName = 'CCrmDocumentDeal';
		}

		return $docName;
	}
	public static function AutoStartWorkflows($ownerTypeID, $ownerID, $eventType, &$errors, $parameters = array())
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return false;
		}

		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;
		$eventType = (int)$eventType;

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return false;
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return false;
		}

		$documentId = array('crm', $docName, $ownerTypeName.'_'.$ownerID);

		if (!$parameters)
		{
			CBPDocument::AutoStartWorkflows(
				array('crm', $docName, $ownerTypeName),
				$eventType,
				$documentId,
				array(),
				$errors
			);
		}
		else
		{
			if (is_string($parameters))
			{
				$parameters = CBPDocument::unsignParameters($parameters);
			}

			$templates = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType(array('crm', $docName, $ownerTypeName), $eventType);
			foreach ($templates as $template)
			{
				$workflowParameters = isset($parameters[$template["ID"]]) && is_array($parameters[$template["ID"]])
					? $parameters[$template["ID"]] : array();

				\CBPDocument::StartWorkflow(
					$template["ID"],
					$documentId,
					$workflowParameters,
					$errors
				);
			}
		}

		return true;
	}
	public static function HasAutoWorkflows($ownerTypeID, $eventType)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return false;
		}

		$ownerTypeID = (int)$ownerTypeID;
		$eventType = (int)$eventType;

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return false;
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return false;
		}

		$ary = \CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType(array('crm', $docName, $ownerTypeName), $eventType);
		return !empty($ary);
	}
	public static function HasParameterizedAutoWorkflows($ownerTypeID, $eventType)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return false;
		}

		$ownerTypeID = (int)$ownerTypeID;
		$eventType = (int)$eventType;

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return false;
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return false;
		}

		$filter = array(
			'DOCUMENT_TYPE' => array('crm', $docName, $ownerTypeName),
			'AUTO_EXECUTE' => $eventType,
			'ACTIVE' => 'Y',
			'!PARAMETERS' => null
		);
		return (\CBPWorkflowTemplateLoader::getList(array(), $filter, array()) > 0);
	}
	public static function HasRunningWorkflows($ownerTypeID, $ownerID)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return false;
		}

		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return false;
		}

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return false;
		}

		$docID = "{$ownerTypeName}_{$ownerID}";
		return (CBPStateService::CountDocumentWorkflows(array('crm', $docName, $docID)) > 0);
	}
	public static function GetDocumentNames($ownerTypeID, $ownerID)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return false;
		}

		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return array();
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return array();
		}

		/*$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', $docName, $ownerTypeName),
			array('crm', $docName, $ownerTypeName.'_'.$ownerID)
		);*/

		$arDocumentStates = CBPStateService::GetDocumentStates(
			array('crm', $docName, $ownerTypeName.'_'.$ownerID)
		);

		$result = array();
		foreach ($arDocumentStates as $arDocumentState)
		{
			if($arDocumentState['ID'] !== '' && $arDocumentState['TEMPLATE_NAME'] !== '')
			{
				$result[] = $arDocumentState['TEMPLATE_NAME'];
			}
		}

		return $result;
	}
	public static function GetUserWorkflowTaskCount($workflowIDs, $userID = 0)
	{
		if(!is_array($workflowIDs))
		{
			return 0;
		}

		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled()))
		{
			return 0;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$filter = array('USER_ID' => $userID);
		$workflowQty = count($workflowIDs);
		if($workflowQty > 1)
		{
			$filter['@WORKFLOW_ID'] = $workflowIDs;
		}
		elseif($workflowQty === 1)
		{
			$filter['WORKFLOW_ID'] = $workflowIDs[0];
		}

		$result = CBPTaskService::GetList(array(), $filter, array(), false, array());
		return is_int($result) ? $result : 0;
	}
}

class CCrmBizProcEventType
{
	const Undefined = 0;
	const Create = 1; //CBPDocumentEventType::Create
	const Edit = 2; //CBPDocumentEventType::Edit
}
