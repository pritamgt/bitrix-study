{"version":3,"sources":["script.js"],"names":["BX","namespace","Tasks","KanbanComponent","ClickSort","event","item","order","params","hasClass","layout","menuItems","menuWindow","i","c","length","removeClass","addClass","ajax","method","dataType","url","ajaxHandlerPath","data","action","sessid","bitrix_sessid","ajaxParams","onsuccess","onCustomEvent","this","SetSort","enabled","selectorId","menuId","disabledClass","menu","PopupMenu","getMenuById","filterId","defaultPresetId","onReady","bind","delegate","tooltip","PopupWindow","closeByEsc","angle","offsetLeft","darkMode","autoHide","zIndex","content","message","show","addCustomEvent","canSortItem","newTaskOrder","roleId","filterManager","Main","getById","alert","fields","preset_id","additional","ROLEID","filterApi","getApi","setFilter","window","history","pushState","counterId","Number","STATUS","0","f","getFilterFieldsValues","hasOwnProperty","setFields","apply","PROBLEM","reg","str","getSlider","getUrl","test","confirm","denyAction"],"mappings":"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","file":""}