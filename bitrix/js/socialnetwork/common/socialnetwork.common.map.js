{"version":3,"sources":["socialnetwork.common.js"],"names":["BX","window","SocialnetworkUICommon","showRecallJoinRequestPopup","params","parseInt","RELATION_ID","type","isNotEmptyString","URL_REJECT_OUTGOING_REQUEST","isProject","PROJECT","successPopup","PopupWindow","width","autoHide","lightShadow","zIndex","overlay","content","create","children","text","message","props","className","events","click","delegate","event","_currentTarget","currentTarget","this","hideError","showButtonWait","ajax","url","method","dataType","data","action","max_count","checked_0","type_0","id_0","ajax_request","sessid","bitrix_sessid","onsuccess","deleteResponseData","hideButtonWait","MESSAGE","destroy","URL_GROUPS_LIST","top","location","href","ERROR_MESSAGE","length","showError","onfailure","closeByEsc","closeIcon","show","showGroupMenuPopup","bindElement","currentUserId","sonetGroupMenu","SonetGroupMenu","getInstance","tagName","addClass","menu","push","favoritesValue","title","id","onclick","setItemTitle","onCustomEvent","groupId","value","setFavoritesAjax","callback","success","name","NAME","URL","extranet","EXTRANET","failure","perms","canInitiate","urls","requestUser","canModify","edit","hideArchiveLinks","featuresItem","editFeaturesAllowed","features","B24","licenseInfoPopup","delete","canModerate","members","requests","requestsOut","userRole","initiatedByType","userRequestItem","isOpened","Waiter","menuPopup","close","userRequestGroup","groupID","save","responseData","hide","code","userIsMember","userIsAutoMember","userLeaveGroup","popup","PopupMenu","offsetTop","offsetLeft","offsetWidth","angle","onPopupClose","removeClass","item","getMenuItem","menuItem","layout","popupWindow","lang","SUCCESS","buttonNode","disabled","style","cursor","errorText","errorNode","innerHTML","reload","SidePanel","Instance","getSliderByWindow","showLoader","isOpen","getPageUrl","reloadBlock","blockId","promise","BLOCK_RELOAD","BLOCK_ID","then","CONTENT","setTimeout","processRequestData","closeGroupCardMenu","node","doc","ownerDocument","win","defaultView","parentWindow","instance","waitTimeout","waitPopup","prototype","timeout","proxy","html","setBindElement","clearTimeout","addCustomEvent"],"mappings":"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","file":""}