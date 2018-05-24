<?php
define("NOT_CHECK_PERMISSIONS", true);
define("NO_KEEP_STATISTIC", true);
define("BX_SECURITY_SESSION_VIRTUAL", true);
define("SKIP_DISK_QUOTA_CHECK", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
/** @var CDatabase $DB */
/** @var CUser $USER */
if (!$USER->IsAuthorized())
{
	@session_destroy();
}

$connection = \Bitrix\Main\Application::getConnection();
$sqlHelper = $connection->getSqlHelper();
$postList = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList();

//TODO remove ASAP
if (!\Bitrix\Main\Loader::includeModule('replica'))
{
	\Bitrix\Main\ModuleManager::delete("dummy");
}
//THIS CODE CLEANS MODULES CACHE

if (\Bitrix\Main\Loader::includeModule('replica'))
{
	switch ($_GET["action"])
	{
	case "tear_down":
		try
		{
			if (defined("BX_REPLICA_TEST"))
			{
				$connection->query("DELETE FROM b_replica_map");
				$connection->query("DELETE FROM b_replica_log");

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("im"))
				{
					$connection->query("DELETE FROM b_im_message_param");
					$connection->query("DELETE FROM b_im_message");
					$connection->query("DELETE FROM b_im_relation");
					$connection->query("DELETE FROM b_im_chat");
					$connection->query("DELETE FROM b_im_status");
					$connection->query("DELETE FROM b_im_recent");
					COption::RemoveOption('im', 'disk_storage_id');

					DeleteDirFilesEx("/bitrix/cache/bx/imc");
				}

				if (function_exists('apc_delete'))
				{
					apc_delete(BX24_HOST_NAME."root");
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("disk"))
				{
					$connection->query("delete from b_disk_attached_object");
					$connection->query("delete from b_disk_cloud_import");
					$connection->query("delete from b_disk_deleted_log");
					$connection->query("delete from b_disk_edit_session");
					$connection->query("delete from b_disk_external_link");
					$connection->query("delete from b_disk_object");
					$connection->query("delete from b_disk_object_path");
					$connection->query("delete from b_disk_recently_used");
					$connection->query("delete from b_disk_right");
					$connection->query("delete from b_disk_sharing");
					$connection->query("delete from b_disk_simple_right");
					$connection->query("delete from b_disk_storage");
					$connection->query("delete from b_disk_tmp_file");
					if ($connection->isTableExists("b_disk_utm_sonet_comment_crm"))
						$connection->query("delete from b_disk_utm_sonet_comment_crm");
					if ($connection->isTableExists("b_disk_utm_sonet_log_crm"))
						$connection->query("delete from b_disk_utm_sonet_log_crm");
					$connection->query("delete from b_disk_version");

					$driver = \Bitrix\Disk\Driver::getInstance();
					$rightsManager = $driver->getRightsManager();
					$taskIdEdit = $rightsManager->getTaskIdByName($rightsManager::TASK_EDIT);
					$taskIdFull = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

					$commonStorage = $driver->addCommonStorage(
						array(
							'NAME' => "COMMON_DISK",
							'ENTITY_ID' => "shared_files_s1",
							'SITE_ID' => "s1"
						),
						array(
							array(
								'ACCESS_CODE' => 'G3', //Edit access for all employees
								'TASK_ID' => $taskIdEdit,
							),
						)
					);

					$driver->addUserStorage(1);
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("tasks"))
				{
					$connection->query("delete from b_tasks");
					$connection->query("delete from b_tasks_files_temporary");
					$connection->query("delete from b_tasks_dependence");
					$connection->query("delete from b_tasks_proj_dep");
					$connection->query("delete from b_tasks_file");
					$connection->query("delete from b_tasks_member");
					$connection->query("delete from b_tasks_tag");
					$connection->query("delete from b_tasks_template");
					$connection->query("delete from b_tasks_template_dep");
					$connection->query("delete from b_tasks_viewed");
					$connection->query("delete from b_tasks_log");
					$connection->query("delete from b_tasks_elapsed_time");
					$connection->query("delete from b_tasks_reminder");
					$connection->query("delete from b_tasks_filters");
					$connection->query("delete from b_tasks_checklist_items");
					$connection->query("delete from b_tasks_template_chl_item");
					$connection->query("delete from b_tasks_timer");
					$connection->query("delete from b_tasks_columns");
					$connection->query("delete from b_tasks_favorite");
					$connection->query("delete from b_tasks_msg_throttle");
					$connection->query("delete from b_tasks_sorting");
					$connection->query("delete from b_user_counter where CODE like 'tasks_%'");
					$connection->query("delete from b_uts_tasks_task");
					$connection->query("delete from b_utm_tasks_task");
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("socialnetwork"))
				{
					$connection->query("delete from b_sonet_log_comment");
					$connection->query("delete from b_sonet_log_counter");
					$connection->query("delete from b_sonet_log_right");
					$connection->query("delete from b_sonet_log_site");
					$connection->query("delete from b_utm_sonet_log");
					$connection->query("delete from b_uts_sonet_log");
					$connection->query("delete from b_utm_sonet_comment");
					$connection->query("delete from b_uts_sonet_comment");
					$connection->query("delete from b_sonet_log");
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("forum"))
				{
					$connection->query("delete from b_utm_forum_message");
					$connection->query("delete from b_uts_forum_message");
					$connection->query("delete from b_forum_message");
					$connection->query("delete from b_forum_user_topic");
					$connection->query("delete from b_forum_topic");
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("blog"))
				{
					$connection->query("delete from b_blog_post");
				}

				$userList = \Bitrix\Main\UserTable::getList(array(
					"select" => array("ID"),
					"filter" => array(
						'>ID' => 4,
					),
				));
				while ($userInfo = $userList->fetch())
				{
					$user = new CUser;
					$user->Delete($userInfo["ID"]);
					$connection->query("delete from b_user_counter where USER_ID = ".$userInfo["ID"]);
				}

				echo 'true';
			}
		}
		catch (Exception $e)
		{
			echo (string)$e;
		}
		break;
	case "execute":
		$EVENT = $postList->getRaw("EVENT");
		$SIGNATURE = $postList->getRaw("SIGNATURE");
		$NODE_FROM = $postList->getRaw("NODE_FROM");
		$NODE_TO = $postList->getRaw("NODE_TO");

		$signer = new \Bitrix\Main\Security\Sign\Signer();
		$signer->setKey(BX24_SECRET);
		$signature = $signer->getSignature($EVENT);

		if ($signature !== $SIGNATURE)
		{
			echo "Wrong signature";
		}
		elseif (isset($EVENT) && is_array($event = unserialize($EVENT)))
		{
			try
			{
				$connection->startTransaction();
				\Bitrix\Replica\Server\Event::execute($event["event"], $NODE_FROM, $NODE_TO);
				$connection->commitTransaction();
				echo "true";
			}
			catch (Exception $e)
			{
				$connection->rollbackTransaction();
				echo $e;
			}
		}
		else
		{
			echo "No event(s)";
		}
		break;
	}
}
else
{
	echo "Failed to include replica.";
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
