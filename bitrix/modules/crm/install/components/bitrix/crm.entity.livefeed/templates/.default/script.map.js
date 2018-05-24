{"version":3,"sources":["script.js"],"names":["BX","CrmEntityLiveFeed","this","_settings","_id","_prefix","_menuContainer","_addMessageButton","_addCallButton","_addMeetingButton","_addEmailButton","_activityEditor","_eventEditor","_enableTaskProcessing","_activities","prototype","initialize","id","settings","getSetting","_resolveElement","bind","delegate","_onAddMessageButtonClick","_onAddCallButtonClick","_onAddMeetingButtonClick","_onAddEmailButtonClick","_addTaskButton","_onAddTaskButtonClick","eventEditorId","type","isNotEmptyString","CrmSonetEventEditor","items","activityEditorId","CrmActivityEditor","addActivityChangeHandler","_onActivityChange","namespace","Crm","Activity","Planner","Manager","setCallback","form","window","ReloadActiveTab","oLF","refresh","location","reload","name","defaultVal","hasOwnProperty","setSetting","val","setActivityCompleted","activityId","completed","elementId","e","showEditor","showEdit","TYPE_ID","CrmActivityType","call","OWNER_TYPE","OWNER_ID","addCall","meeting","addMeeting","addEmail","addTask","sender","action","origin","typeId","parseInt","undefined","task","setTimeout","create","self"],"mappings":"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","file":""}