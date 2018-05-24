<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/personal.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?>
<?
$arEditableFields = array(
	0 => "LOGIN",
	1 => "NAME",
	2 => "SECOND_NAME",
	3 => "LAST_NAME",
	4 => "EMAIL",
	5 => "PASSWORD",
	6 => "PERSONAL_BIRTHDAY",
	7 => "PERSONAL_WWW",
	8 => "PERSONAL_GENDER",
	9 => "PERSONAL_PHOTO",
	11 => "PERSONAL_MOBILE",
	13 => "PERSONAL_CITY",
	14 => "WORK_PHONE",
	15 => "UF_PHONE_INNER",
	16 => "UF_SKYPE",
	17 => "UF_TWITTER",
	18 => "UF_FACEBOOK",
	19 => "UF_LINKEDIN",
	20 => "UF_XING",
	21 => "UF_SKILLS",
	22 => "UF_INTERESTS",
	23 => "UF_WEB_SITES",
	24 => "TIME_ZONE",
	25 => "GROUP_ID",
	26 => "WORK_POSITION"
);
if ($GLOBALS["USER"]->CanDoOperation("edit_all_users"))
	$arEditableFields[] = "UF_DEPARTMENT";

GetGlobalID();
$componentDateTimeFormat = CIntranetUtils::getCurrentDateTimeFormat();

$APPLICATION->IncludeComponent("bitrix:socialnetwork_user", ".default", Array(
	"ITEM_DETAIL_COUNT"	=>	"32",
	"ITEM_MAIN_COUNT"	=>	"6",
	"DATE_TIME_FORMAT" => $componentDateTimeFormat,
	"NAME_TEMPLATE" => "",
	"PATH_TO_GROUP" => "/workgroups/group/#group_id#/",
	"PATH_TO_GROUP_SUBSCRIBE" => "/workgroups/group/#group_id#/subscribe/",
	"PATH_TO_GROUP_SEARCH" => "/workgroups/group/search/",
	"PATH_TO_SEARCH_EXTERNAL" => "/company/index.php",
	"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"PATH_TO_GROUP_TASKS" => "/workgroups/group/#group_id#/tasks/",
	"PATH_TO_GROUP_TASKS_TASK" => "/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/",
	"PATH_TO_GROUP_TASKS_VIEW" => "/workgroups/group/#group_id#/tasks/view/#action#/#view_id#/",
	"PATH_TO_GROUP_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
	"PATH_TO_GROUP_PHOTO" => "/workgroups/group/#group_id#/photo/",
	"PATH_TO_GROUP_PHOTO_SECTION" => "/workgroups/group/#group_id#/photo/album/#section_id#/",
	"PATH_TO_GROUP_PHOTO_ELEMENT" => "/workgroups/group/#group_id#/photo/#section_id#/#element_id#/",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/company/personal/",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "Y",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"CACHE_TIME_LONG" => "604800",
	"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
	"PATH_TO_BLOG_SMILE" => "/bitrix/images/blog/smile/",
	"PATH_TO_FORUM_SMILE" => "/bitrix/images/forum/smile/",
	"PATH_TO_FORUM_ICON" => "/bitrix/images/forum/icon/",
	"SET_TITLE" => "Y",
	"SET_NAV_CHAIN" => "Y",
	"HIDE_OWNER_IN_TITLE" => "Y",
	"SHOW_RATING" => "",
	"RATING_TYPE" => "",
	"GROUP_THUMBNAIL_SIZE" => 100,
	"USER_FIELDS_MAIN" => array(
		0 => "PERSONAL_BIRTHDAY",
		1 => "WORK_POSITION",
		2 => "WORK_COMPANY",
		3 => "SECOND_NAME",
	),
	"USER_PROPERTY_MAIN" => array(
		0 => "UF_DEPARTMENT",
	),
	"USER_FIELDS_CONTACT" => array(
		0 => "EMAIL",
		1 => "PERSONAL_WWW",
		2 => "PERSONAL_MOBILE",
		3 => "WORK_PHONE",
	),
	"USER_PROPERTY_CONTACT" => array(
		0 => "UF_PHONE_INNER",
		1 => "UF_SKYPE",
		2 => "UF_TWITTER",
		3 => "UF_FACEBOOK",
		4 => "UF_LINKEDIN",
		5 => "UF_XING",
	),
	"USER_FIELDS_PERSONAL" => array(
		0 => "TIME_ZONE",
		1 => "PERSONAL_CITY",
	),
	"USER_PROPERTY_PERSONAL" => array(
		0 => "UF_SKILLS",
		1 => "UF_INTERESTS",
		2 => "UF_WEB_SITES",
	),
	"AJAX_LONG_TIMEOUT" => "60",
	"EDITABLE_FIELDS" => $arEditableFields,
	"SHOW_YEAR" => "M",
	"USER_FIELDS_SEARCH_SIMPLE" => array(
		0 => "PERSONAL_GENDER",
		1 => "PERSONAL_CITY",
	),
	"USER_PROPERTIES_SEARCH_SIMPLE" => array(
	),
	"USER_FIELDS_SEARCH_ADV" => array(
		0 => "PERSONAL_GENDER",
		1 => "PERSONAL_CITY",
	),
	"USER_PROPERTIES_SEARCH_ADV" => array(
	),
	"SONET_USER_FIELDS_LIST" => array(
		0 => "PERSONAL_BIRTHDAY",
		1 => "PERSONAL_GENDER",
		2 => "PERSONAL_CITY",
	),
	"SONET_USER_PROPERTY_LIST" => array(
	),
	"SONET_USER_FIELDS_SEARCHABLE" => array(
	),
	"SONET_USER_PROPERTY_SEARCHABLE" => array(
	),
	"BLOG_GROUP_ID" => $GLOBAL_BLOG_GROUP[SITE_ID],
	"BLOG_COMMENT_AJAX_POST" => "Y",
	"BLOG_ALLOW_POST_CODE" => "N",
	"PATH_TO_GROUP_POST_EDIT" => "/workgroups/group/#group_id#/blog/edit/#post_id#/",
	"PATH_TO_GROUP_DRAFT" => "/workgroups/group/#group_id#/blog/draft/",
	"PATH_TO_GROUP_BLOG" => "/workgroups/group/#group_id#/blog/",
	"PATH_TO_GROUP_MICROBLOG_POST" => "/workgroups/group/#group_id#/microblog/#post_id#/",
	"PATH_TO_GROUP_POST" => "/workgroups/group/#group_id#/blog/#post_id#/",
	"PATH_TO_GROUP_MICROBLOG" => "/workgroups/group/#group_id#/microblog/",

	"FORUM_ID" => $GLOBAL_FORUM_ID["USERS_AND_GROUPS"],//"#FORUM_ID#",
	"CALENDAR_ALLOW_SUPERPOSE" => "Y",
	"CALENDAR_ALLOW_RES_MEETING" => "Y",
	// "CALENDAR_IBLOCK_TYPE"	=>	"events",
	// "CALENDAR_USER_IBLOCK_ID"	=>	$GLOBAL_IBLOCK_ID["calendar_employees"],//"#CALENDAR_USER_IBLOCK_ID#",
	// "CALENDAR_WEEK_HOLIDAYS"	=>	array(
		// 0	=>	"5",
		// 1	=>	"6",
	// ),
	// "CALENDAR_YEAR_HOLIDAYS"	=>	"1.01, 2.01, 7.01, 23.02, 8.03, 1.05, 9.05, 12.06, 4.11, 12.12",
	// "CALENDAR_WORK_TIME_START" => "9",
	// "CALENDAR_WORK_TIME_END" => "19",
	// "CALENDAR_ALLOW_SUPERPOSE" => "Y",
	// "CALENDAR_SUPERPOSE_CAL_IDS" => array(
	// ),
	// "CALENDAR_SUPERPOSE_CUR_USER_CALS" => "Y",
	// "CALENDAR_SUPERPOSE_USERS_CALS" => "Y",
	// "CALENDAR_SUPERPOSE_GROUPS_CALS" => "Y",
	// "CALENDAR_SUPERPOSE_GROUPS_IBLOCK_ID" => $GLOBAL_IBLOCK_ID["calendar_groups"],//"#CALENDAR_GROUPS_IBLOCK_ID#",
	// "CALENDAR_ALLOW_RES_MEETING" => "Y",
	// "CALENDAR_RES_MEETING_IBLOCK_ID" => $GLOBAL_IBLOCK_ID["meeting_rooms"],//"#CALENDAR_RES_IBLOCK_ID#",
	// "CALENDAR_PATH_TO_RES_MEETING" => "/services/?page=meeting&meeting_id=#id#",
	// "CALENDAR_RES_MEETING_USERGROUPS" => array("1"),
	// "CALENDAR_ALLOW_VIDEO_MEETING" => "Y",
	// "CALENDAR_VIDEO_MEETING_IBLOCK_ID" => $GLOBAL_IBLOCK_ID["video-meeting"],//"#CALENDAR_RES_VIDEO_IBLOCK_ID#",
	// "CALENDAR_PATH_TO_VIDEO_MEETING_DETAIL" => "/services/video/detail.php?ID=#ID#",
	// "CALENDAR_PATH_TO_VIDEO_MEETING" => "/services/video/",
	// "CALENDAR_VIDEO_MEETING_USERGROUPS" => array("1"),
	"TASK_FORUM_ID" => $GLOBAL_FORUM_ID["intranet_tasks"],//"#TASKS_FORUM_ID#",
	"FILES_USER_IBLOCK_TYPE"	=>	"library",
	"FILES_USER_IBLOCK_ID"	=> $GLOBAL_IBLOCK_ID["user_files"],//"#FILES_USER_IBLOCK_ID#",
	"FILES_USE_AUTH"	=>	"Y",
	"NAME_FILE_PROPERTY"	=>	"FILE",
	"FILES_UPLOAD_MAX_FILESIZE"	=>	"1024",
	"FILES_UPLOAD_MAX_FILE"	=>	"4",
	"FILES_USE_COMMENTS" => "Y",
	"FILES_FORUM_ID" => $GLOBAL_FORUM_ID["GROUPS_AND_USERS_FILES_COMMENTS"],//"#FILES_FORUM_ID#",
	"PHOTO_USER_IBLOCK_TYPE"	=>	"photos",
	"PHOTO_USER_IBLOCK_ID"	=> $GLOBAL_IBLOCK_ID["user_photogallery"],//"#PHOTO_USER_IBLOCK_ID#",
	"PHOTO_UPLOAD_MAX_FILESIZE"	=>	"64",
	"PHOTO_UPLOAD_MAX_FILE"	=>	"4",
	"PHOTO_ORIGINAL_SIZE" => "1024",
	"PHOTO_UPLOADER_TYPE" => "form",
	"PHOTO_USE_RATING"	=>	"Y",
	"PHOTO_DISPLAY_AS_RATING" => "vote_avg",
	"PHOTO_USE_COMMENTS" => "Y",
	"PHOTO_FORUM_ID" => $GLOBAL_FORUM_ID["PHOTOGALLERY_COMMENTS"],//"#PHOTO_FORUM_ID#",
	"PHOTO_USE_CAPTCHA" => "N",
	"PHOTO_GALLERY_AVATAR_SIZE" => "50",
	"PHOTO_ALBUM_PHOTO_THUMBS_SIZE" => "150",
	"PHOTO_ALBUM_PHOTO_SIZE" => "150",
	"PHOTO_THUMBS_SIZE" => "250",
	"PHOTO_PREVIEW_SIZE" => "700",
	"PHOTO_JPEG_QUALITY1" => "95",
	"PHOTO_JPEG_QUALITY2" => "95",
	"PHOTO_JPEG_QUALITY" => "90",
	"BLOG_COMMENT_ALLOW_VIDEO" => "Y",
	"BLOG_COMMENT_ALLOW_IMAGE_UPLOAD" => "Y",
	"SEF_URL_TEMPLATES"	=>	array(
		"index"	=>	"index.php",
		"user"	=>	"user/#user_id#/",
		"user_friends"	=>	"user/#user_id#/friends/",
		"user_friends_add"	=>	"user/#user_id#/friends/add/",
		"user_friends_delete"	=>	"user/#user_id#/friends/delete/",
		"user_groups"	=>	"user/#user_id#/groups/",
		"user_groups_add"	=>	"user/#user_id#/groups/add/",
		"group_create"	=>	"user/#user_id#/groups/create/",
		"user_profile_edit"	=>	"user/#user_id#/edit/",
		"user_settings_edit"	=>	"user/#user_id#/settings/",
		"user_features"	=>	"user/#user_id#/features/",
		"group_request_group_search"	=>	"group/#user_id#/group_search/",
		"group_request_user"	=>	"group/#group_id#/user/#user_id#/request/",
		"search"	=>	"search.php",
		"message_form"	=>	"messages/form/#user_id#/",
		"message_form_mess"	=>	"messages/form/#user_id#/#message_id#/",
		"user_ban"	=>	"messages/ban/",
		"messages_chat"	=>	"messages/chat/#user_id#/",
		"messages_input"	=>	"messages/input/",
		"messages_input_user"	=>	"messages/input/#user_id#/",
		"messages_output"	=>	"messages/output/",
		"messages_output_user"	=>	"messages/output/#user_id#/",
		"messages_users"	=>	"messages/",
		"messages_users_messages"	=>	"messages/#user_id#/",
		"user_photo"	=>	"user/#user_id#/photo/",
		"user_calendar"	=>	"user/#user_id#/calendar/",
		"user_files"	=>	"user/#user_id#/files/lib/#path#",
		"user_blog"	=>	"user/#user_id#/blog/",
		"user_blog_post_edit"	=>	"user/#user_id#/blog/edit/#post_id#/",
		"user_blog_rss"	=>	"user/#user_id#/blog/rss/#type#/",
		"user_blog_draft"	=>	"user/#user_id#/blog/draft/",
		"user_blog_post"	=>	"user/#user_id#/blog/#post_id#/",
		"user_tasks" => "user/#user_id#/tasks/",
		"user_tasks_task" => "user/#user_id#/tasks/task/#action#/#task_id#/",
		"user_tasks_view" => "user/#user_id#/tasks/view/#action#/#view_id#/",
		"user_security" => "user/#user_id#/security/",
		"user_passwords" => "user/#user_id#/passwords/",
	),
	"LOG_THUMBNAIL_SIZE" => 100,
	"LOG_COMMENT_THUMBNAIL_SIZE" => 100,
	"LOG_NEW_TEMPLATE" => "Y"
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>