{"version":3,"sources":["sequenceactivity.js"],"names":["_SequenceActivityCurClick","_SequenceActivityClick","act_i","i","AddActivity","CreateActivity","Properties","Title","HTMLEncode","arAllActivities","Type","Children","_SequenceActivityMyActivityClick","isn","arUserParams","BX","type","isArray","SequenceActivity","ob","BizProcActivity","childsContainer","iHead","LineMouseOver","e","this","parentNode","style","backgroundImage","LineMouseOut","OnClick","jsMnu_WFAct","groupId","oSubMenu","arAllActGroups","hasOwnProperty","activityGroupId","rootActivity","push","ICON","TEXT","ONCLICK","ind","length","MENU","icon","name","BPMESS","window","jsPopup_WFAct","PopupHide","PopupMenu","ShowMenu","lastDrop","ondragging","X","Y","childActivities","arrow","rows","cells","childNodes","pos","left","right","top","bottom","onmouseover","onmouseout","h1id","DragNDrop","AddHandler","ondrop","oActivity","obj","parentActivity","ctrlKey","pa","Name","d","s","alert","deleteRow","j","pop","h2id","ActivityRemoveChild","RemoveChild","ch","onclick","RemoveResources","self","RemoveHandler","removeChild","c","insertRow","insertCell","align","vAlign","Draw","CreateLine","BPTemplateIsModified","height","background","appendChild","document","createElement","src","width","ActivityDraw","container","_crt","className","id","parseInt","AfterSDraw"],"mappings":"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","file":""}