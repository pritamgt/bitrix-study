; /* /bitrix/js/rest/client.min.js?15269825315795*/
; /* /bitrix/js/rest/applayout.min.js?152698253112374*/

; /* Start:"a:4:{s:4:"full";s:44:"/bitrix/js/rest/client.min.js?15269825315795";s:6:"source";s:25:"/bitrix/js/rest/client.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function(){"use strict";BX.namespace("BX.rest");var e="/rest";if(!!BX.rest.callMethod){return}BX.rest.callMethod=function(e,r,n,s){return t({method:e,data:r,callback:n,sendCallback:s})};BX.rest.callBatch=function(e,r,n,s){var a=BX.type.isArray(e)?[]:{};var i=0;var o=function(e){t.batch(e,r,n,s)};for(var u in e){var l=null,c=null;if(!!e[u]&&e.hasOwnProperty(u)){if(BX.type.isArray(e[u])){l=e[u][0];c=e[u][1]}else if(!!e[u].method){l=e[u].method;c=e[u].params}if(!!l){i++;a[u]=[l,c]}}}if(i>0){var f=function(e){return function(t){a[e]=a[e][0]+"?"+t;if(--i<=0)o(a)}};for(var p in a){if(a.hasOwnProperty(p)){t.prepareData(a[p][1],"",f(p))}}}};var t=function(n){var s=!!n.callback&&BX.type.isFunction(n.callback);var a=s?null:new BX.Promise;var i=n.sendCallback||function(){};var o=t.xhr();var u=e+"/"+t.escape(n.method)+".json";o.open("POST",u);o.setRequestHeader("Content-Type","application/x-www-form-urlencoded");var l=false;o.onprogress=function(){};o.ontimeout=function(){};o.timeout=0;o.onload=function(){if(l)return;o.onload=BX.DoNothing;var e=t.isSuccess(o);var i=o.status;if(e){var u=o.responseText;if(u.length>0){try{u=JSON.parse(u)}catch(t){e=false}}else if(i==200){u={result:{}}}else if(i==0){u={result:{},error:"ERROR_NETWORK",error_description:"A network error occurred while the request was being executed."}}else{u={result:{},error:"BLANK_ANSWER_WITH_ERROR_CODE",error_description:"Blank answer with error http code: "+i}}}o=null;if(e){var c=new r(u,n,i);if(s){n.callback.apply(window,[c])}else{if(c.error()){a.reject(c)}else{a.fulfill(c)}}}else{var c=new r({error:"ERROR_UNEXPECTED_ANSWER",error_description:"Server returned an unexpected response.",ex:{}},n,0);if(s){n.callback.apply(window,[c])}else{a.reject(c)}}};o.onerror=function(e){var t=new r({error:"ERROR_NETWORK",error_description:"A network error occurred while the request was being executed.",ex:e},n,0);if(s){n.callback.apply(window,[t])}else{a.reject(t)}};var c="sessid="+BX.bitrix_sessid();if(typeof n.start!=="undefined"){c+="&start="+parseInt(n.start)}if(!!n.data){t.prepareData(n.data,"",function(e){c+="&"+e;o.send(c);i(o)})}else{o.send(c);i(o)}return s?o:a};t.batch=function(e,n,s,a){return t({method:"batch",data:{halt:!!s?1:0,cmd:e},callback:function(t,s,a){if(!!n){var i=t.error();var o=t.data();var u=BX.type.isArray(e)?[]:{};for(var l in e){if(!!e[l]&&e.hasOwnProperty(l)){if(BX.type.isString(e[l])){var c=e[l].split("?")}else{c=[BX.type.isArray(e[l])?e[l][0]:e[l].method,BX.type.isArray(e[l])?e[l][1]:e[l].data]}if(o&&(typeof o.result[l]!=="undefined"||typeof o.result_error[l]!=="undefined")){u[l]=new r({result:typeof o.result[l]!=="undefined"?o.result[l]:{},error:o.result_error[l]||undefined,total:o.result_total[l],time:o.result_time[l],next:o.result_next[l]},{method:c[0],data:c[1],callback:n},t.status)}else if(i){u[l]=new r({result:{},error:{error:i.ex.error,description:i.ex.error_description},total:0},{method:c[0],data:c[1],callback:n},t.status)}}}n.apply(window,[u])}},sendCallback:a})};t.xhr=function(){return new XMLHttpRequest};t.escape=function(e){return BX.util.urlencode(e)};t.prepareData=function(e,r,n){var a="",i=[];if(BX.type.isString(e)||e===null){n.call(document,e||"")}else{for(var o in e){if(!e.hasOwnProperty(o)){continue}var u=t.escape(o);if(r)u=r+"["+u+"]";if(typeof e[o]==="object"){i.push([u,e[o]])}else{if(a.length>0){a+="&"}if(typeof e[o]==="boolean"){a+=u+"="+(e[o]?1:0)}else{a+=u+"="+t.escape(e[o])}}}var l=i.length;if(l>0){var c=function(e){a+=(!!e?"&":"")+e;if(--l<=0){n.call(document,a)}};var f=l;for(var o=0;o<f;o++){if(BX.type.isDomNode(i[o][1])){if(i[o][1].tagName.toUpperCase()==="INPUT"&&i[o][1].type==="file"){if(s.canUse()){s(i[o][1],function(e){return function(r){if(BX.type.isArray(r)&&r.length>0){c(e+"[0]="+t.escape(r[0])+"&"+e+"[1]="+t.escape(r[1]))}else{c(e+"=")}}}(i[o][0]))}}else if(typeof i[o][1].value!=="undefined"){c(i[o][0]+"="+t.escape(i[o][1].value))}else{c("")}}else if(BX.type.isDate(i[o][1])){c(i[o][0]+"="+t.escape(i[o][1].toJSON()))}else if(BX.type.isArray(i[o][1])&&i[o][1].length<=0){c(i[o][0]+"=")}else{t.prepareData(i[o][1],i[o][0],c)}}}else{n.call(document,a)}}};t.isSuccess=function(e){return typeof e.status==="undefined"||e.status>=200&&e.status<300||e.status===304||e.status>=400&&e.status<500||e.status===1223||e.status===0};var r=function(e,t,r){this.answer=e;this.query=BX.clone(t);this.status=r;if(typeof this.answer.next!=="undefined"){this.answer.next=parseInt(this.answer.next)}if(typeof this.answer.error!=="undefined"){this.answer.ex=new n(this.status,typeof this.answer.error==="string"?this.answer:this.answer.error)}};r.prototype.data=function(){return this.answer.result};r.prototype.time=function(){return this.answer.time};r.prototype.error=function(){return this.answer.ex};r.prototype.error_description=function(){return this.answer.error_description};r.prototype.more=function(){return!isNaN(this.answer.next)};r.prototype.total=function(){return parseInt(this.answer.total)};r.prototype.next=function(e){if(this.more()){this.query.start=this.answer.next;if(!!e&&BX.type.isFunction(e)){this.query.callback=e}return t(this.query)}return false};var n=function(e,t){this.status=e;this.ex=t};n.prototype.getError=function(){return this.ex};n.prototype.getStatus=function(){return this.status};n.prototype.toString=function(){return this.ex.error+(!!this.ex.error_description?": "+this.ex.error_description:"")+" ("+this.status+")"};var s=function(e,t){if(s.canUse()){var r=e.files,n=0,a=e.multiple?[]:null;for(var i=0,o;o=r[i];i++){var u=new window.FileReader;u.BXFILENAME=r[i].name;u.onload=function(e){e=e||window.event;var r=[this.BXFILENAME,btoa(e.target.result)];if(a===null)a=r;else a.push(r);if(--n<=0){t(a)}};u.readAsBinaryString(o)}n=i;if(n<=0){t(a)}}};s.canUse=function(){return!!window.FileReader}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:48:"/bitrix/js/rest/applayout.min.js?152698253112374";s:6:"source";s:28:"/bitrix/js/rest/applayout.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function(){BX.namespace("BX.rest");if(!!BX.rest.AppLayout){return}BX.rest.AppLayout=function(e){this.params={firstRun:!!e.firstRun,appHost:e.appHost,appProto:e.appProto,authId:e.authId,authExpires:e.authExpires,refreshId:e.refreshId,placement:e.placement,formName:e.formName,frameName:e.frameName,loaderName:e.loaderName,layoutName:e.layoutName,ajaxUrl:e.ajaxUrl,controlUrl:e.controlUrl,isAdmin:!!e.isAdmin,staticHtml:!!e.staticHtml,id:e.id,appId:e.appId,appV:e.appV,appI:e.appI,appSid:e.appSid,memberId:e.memberId,restPath:e.restPath,proto:e.proto,userOptions:e.userOptions,appOptions:e.appOptions,placementOptions:e.placementOptions};this.userSelectorControl=[null,null];this.userSelectorControlCallback=null;this.bAccessLoaded=false;this._appOptionsStack=[];this._inited=false;this._destroyed=false;this.deniedInterface=[];this.selectUserCallback_1_value=[];this.messageInterface=new(BX.rest.AppLayout.initializePlacement(this.params.placement));BX.bind(window,"message",BX.proxy(this.receiveMessage,this))};BX.rest.AppLayout.openApplication=function(e,t,s,a){var i=BX.message("REST_APPLICATION_URL").replace("#id#",parseInt(e));i=BX.util.add_url_param(i,{_r:Math.random()});var n={ID:e,PLACEMENT_OPTIONS:t,POPUP:1};if(!!s){if(typeof s.PLACEMENT!=="undefined"){n.PLACEMENT=s.PLACEMENT}if(typeof s.PLACEMENT_ID!=="undefined"){n.PLACEMENT_ID=s.PLACEMENT_ID}}BX.SidePanel.Instance.open(i,{cacheable:false,contentCallback:function(e){var t=new top.BX.Promise;top.BX.ajax.post(e.url,{sessid:BX.bitrix_sessid(),site:BX.message("SITE_ID"),PARAMS:{template:"",params:n}},function(e){t.fulfill(e)});return t},events:{onClose:function(){if(!!a){a()}}}});var r=top.BX.SidePanel.Instance.getTopSlider();top.BX.addCustomEvent(top,"Rest:AppLayout:ApplicationInstall",function(i,n){n.redirect=false;r.close(false,function(){BX.rest.AppLayout.openApplication(e,t,s,a)})})};BX.rest.AppLayout.prototype={init:function(){if(!this._inited&&!!document.forms[this.params.formName]){var e=BX(this.params.loaderName);BX.bind(BX(this.params.frameName),"load",function(){BX.addClass(e,"app-loading-msg-loaded");BX.removeClass(this,"app-loading");setTimeout(function(){BX.remove(e)},300)});if(this.params.staticHtml){BX(this.params.frameName).src=document.forms[this.params.formName].action}else{document.forms[this.params.formName].submit()}this._inited=true}},destroy:function(){BX.unbind(window,"message",BX.proxy(this.receiveMessage,this));BX(this.params.frameName).parentNode.removeChild(BX(this.params.frameName));this._destroyed=true},query:function(e,t){var s={sessid:BX.bitrix_sessid(),site:BX.message("SITE_ID"),PARAMS:{template:"",params:{ID:this.params.id}}};if(!!e){s=BX.mergeEx(s,e)}return BX.ajax({dataType:"json",method:"POST",url:this.params.ajaxUrl,data:s,onsuccess:t})},receiveMessage:function(t){t=t||window.event;if(t.origin!=this.params.appProto+"://"+this.params.appHost||!t.data){return}var s=e(t.data,":"),a=[];if(s[3]!=this.params.appSid){return}if(s[1]){a=JSON.parse(s[1])}if(!!this.messageInterface[s[0]]&&!BX.util.in_array(s[0],this.deniedInterface)){var i=s[2];var n=!i?BX.DoNothing:BX.delegate(function(e){var t=BX(this.params.frameName);if(!!t&&!!t.contentWindow){t.contentWindow.postMessage(i+":"+(typeof e=="undefined"?"":JSON.stringify(e)),this.params.appProto+"://"+this.params.appHost)}},this);this.messageInterface[s[0]].apply(this,[a,n])}},denyInterface:function(e){this.deniedInterface=BX.util.array_merge(this.deniedInterface,e)},allowInterface:function(e){var t=[];for(var s=0;s<this.deniedInterface.length;s++){if(!BX.util.in_array(this.deniedInterface[s],e)){t.push(this.deniedInterface[s])}}this.deniedInterface=t},sendAppOptions:function(){if(this._appOptionsStack.length>0){var e=this._appOptionsStack;this._appOptionsStack=[];var t=[];for(var s=0;s<e.length;s++){t.push({name:e[s][0],value:e[s][1]})}var a={action:"set_option",options:t};this.query(a,function(t){for(var s=0;s<e.length;s++){e[s][2](t)}})}},loadControl:function(e,t,s){if(!t){t={}}t.control=e;t.sessid=BX.bitrix_sessid();BX.ajax({method:"POST",url:this.params.controlUrl,data:t,processScriptsConsecutive:true,onsuccess:s})},reInstall:function(){BX.proxy(this.messageInterface.setInstallFinish,this)({value:false})},selectUserCallback_0:function(e){var t=BX.util.array_values(e);if(!!t&&t.length>0){BX.defer(this.userSelectorControl[0].close,this.userSelectorControl[0])();if(!!this.userSelectorControlCallback){this.userSelectorControlCallback.apply(this,[t[0]])}}},selectUserCallback_1:function(e){if(e===true){var t=BX.util.array_values(this.selectUserCallback_1_value);BX.defer(this.userSelectorControl[1].close,this.userSelectorControl[1])();if(!!this.userSelectorControlCallback){this.userSelectorControlCallback.apply(this,[t])}}else{this.selectUserCallback_1_value=e}},hideUpdate:function(e,t){BX.userOptions.save("app_options","params_"+this.params.appId+"_"+this.params.appV,"skip_update_"+e,1);t()}};BX.rest.AppLayout.initizalizePlacementInterface=function(e){var t=function(){};BX.extend(t,e);t.prototype.events=BX.clone(t.superclass.events);return t};BX.rest.AppLayout.initializePlacement=function(e){e=(e+"").toUpperCase();if(!BX.rest.AppLayout.placementInterface[e]){BX.rest.AppLayout.placementInterface[e]=BX.rest.AppLayout.initizalizePlacementInterface(e==="DEFAULT"?BX.rest.AppLayout.MessageInterface:BX.rest.AppLayout.MessageInterfacePlacement)}return BX.rest.AppLayout.placementInterface[e]};BX.rest.AppLayout.initializePlacementByEvent=function(e,t){BX.addCustomEvent(t,function(t){var s=BX.rest.AppLayout.initializePlacement(e);if(!!t.events){for(var a=0;a<t.events.length;a++){s.prototype.events.push(t.events[a])}}for(var i in t){if(i!=="events"&&t.hasOwnProperty(i)){s.prototype[i]=t[i]}}})};BX.rest.AppLayout.MessageInterface=function(){};BX.rest.AppLayout.MessageInterface.prototype={events:[],getInitData:function(e,t){t({LANG:BX.message("LANGUAGE_ID"),DOMAIN:location.host,PROTOCOL:this.params.proto,PATH:this.params.restPath,AUTH_ID:this.params.authId,AUTH_EXPIRES:this.params.authExpires,REFRESH_ID:this.params.refreshId,MEMBER_ID:this.params.memberId,FIRST_RUN:this.params.firstRun,IS_ADMIN:this.params.isAdmin,INSTALL:this.params.appI,USER_OPTIONS:this.params.userOptions,APP_OPTIONS:this.params.appOptions,PLACEMENT:this.params.placement,PLACEMENT_OPTIONS:this.params.placementOptions});this.params.firstRun=false},getInterface:function(e,t){var s={command:[],event:[]};for(var a in this.messageInterface){if(a!=="events"&&a!=="constructor"&&!BX.rest.AppLayout.MessageInterfacePlacement.prototype[a]&&!BX.util.in_array(a,this.deniedInterface)){s.command.push(a)}}for(var i=0;i<this.messageInterface.events.length;i++){s.event.push(this.messageInterface.events[i])}t(s)},refreshAuth:function(e,t){e={action:"access_refresh"};this.query(e,BX.delegate(function(e){if(!!e["access_token"]){this.params.authId=e["access_token"];this.params.authExpires=e["expires_in"];this.params.refreshId=e["refresh_token"];t({AUTH_ID:this.params.authId,AUTH_EXPIRES:this.params.authExpires,REFRESH_ID:this.params.refreshId})}else{alert("Unable to get new token! Reload page, please!")}},this))},resizeWindow:function(e,t){var s=BX(this.params.layoutName);e.width=e.width=="100%"?e.width:(parseInt(e.width)||100)+"px";e.height=parseInt(e.height);if(!!e.width){s.style.width=e.width}if(!!e.height){s.style.height=e.height+"px"}var a=BX.pos(s);t({width:a.width,height:a.height})},setTitle:function(e,t){BX.ajax.UpdatePageTitle(e.title);t(e)},setScroll:function(e,t){if(!!e&&typeof e.scroll!="undefined"&&e.scroll>=0){window.scrollTo(BX.GetWindowScrollPos().scrollLeft,parseInt(e.scroll))}t(e)},setUserOption:function(e,t){this.params.userOptions[e.name]=e.value;BX.userOptions.save("app_options","options_"+this.params.appId,e.name,e.value);t(e)},setAppOption:function(e,t){if(this.params.isAdmin){this._appOptionsStack.push([e.name,e.value,t]);BX.defer(this.sendAppOptions,this)()}},setInstall:function(e,t){BX.userOptions.save("app_options","params_"+this.params.appId+"_"+this.params.appV,"install",!!e["install"]?1:0);t(e)},setInstallFinish:function(e,t){var s={action:"set_installed",v:typeof e.value=="undefined"||e.value!==false?"Y":"N"};this.query(s,BX.delegate(function(e){var t={redirect:true};top.BX.onCustomEvent(top,"Rest:AppLayout:ApplicationInstall",[s.v,t],false);if(t.redirect){window.location=BX.util.add_url_param(window.location.href,{install_finished:!!e.result?"Y":"N"})}},this))},selectUser:function(e,t){this.userSelectorControlCallback=t;var s=parseInt(e.mult+0);if(s){if(this.userSelectorControl[s]){this.userSelectorControl[s].close();this.userSelectorControl[s].destroy();this.userSelectorControl[s]=null}}else if(!!this.userSelectorControl[s]){this.userSelectorControl[s].show();return}var a={name:"USER_"+s,onchange:"user_selector_cb_"+parseInt(Math.random()*1e5),site_id:BX.message("SITE_ID")};if(s){a.mult=true}window[a.onchange]=BX.delegate(this["selectUserCallback_"+s],this);this.loadControl("user_selector",a,BX.delegate(function(t){this.userSelectorControl[s]=BX.PopupWindowManager.create("app-user-popup-"+s,null,{autoHide:true,content:t,zIndex:2e3});if(s){this.userSelectorControl[s].setButtons([new BX.PopupWindowButton({text:BX.message("REST_ALT_USER_SELECT"),className:"popup-window-button-accept",events:{click:function(){window[a.onchange](true)}}})])}this.userSelectorControl[parseInt(e.mult+0)].show();BX("USER_"+s+"_selector_content").style.display="block"},this))},selectAccess:function(e,t){if(!this.bAccessLoaded){this.loadControl("access_selector",{},BX.defer(function(){this.bAccessLoaded=true;BX.defer(this.messageInterface.selectAccess,this)(e,t)},this))}else{BX.Access.Init({groups:{disabled:true}});e.value=e.value||[];var s={};for(var a=0;a<e.value.length;a++){s[e.value[a]]=true}BX.Access.SetSelected(s);BX.Access.ShowForm({callback:function(e){var s=[];for(var a in e){if(e.hasOwnProperty(a)){for(var i in e[a]){if(e[a].hasOwnProperty(i)){s.push(e[a][i])}}}}t(s)}})}},selectCRM:function(e,t,s){if(!s){this.loadControl("crm_selector",{entityType:e.entityType,multiple:!!e.multiple?"Y":"N",value:e.value},BX.delegate(function(){BX.defer(this.messageInterface.selectCRM,this)(e,t,true)},this));return}if(!window.obCrm){setTimeout(BX.delegate(function(){BX.proxy(this.messageInterface.selectCRM,this)(e,t,true)},this),500)}else{obCrm["restCrmSelector"].Open();obCrm["restCrmSelector"].AddOnSaveListener(function(e){t(e);obCrm["restCrmSelector"].Clear()})}},reloadWindow:function(){window.location.reload()},imCallTo:function(e){BXIM.callTo(e.userId,!!e.video)},imPhoneTo:function(e){BXIM.phoneTo(e.phone)},imOpenMessenger:function(e){BXIM.openMessenger(e.dialogId)},imOpenHistory:function(e){BXIM.openHistory(e.dialogId)},openApplication:function(e,t){BX.rest.AppLayout.openApplication(this.params.id,e,{},t)},closeApplication:function(e,t){if(top.BX.SidePanel.Instance.isOpen()&&top.BX.SidePanel.Instance.getTopSlider().url.match(new RegExp("^"+BX.message("REST_APPLICATION_URL")))){top.BX.SidePanel.Instance.close(false,t)}}};BX.rest.AppLayout.MessageInterfacePlacement=BX.rest.AppLayout.initizalizePlacementInterface(BX.rest.AppLayout.MessageInterface);BX.rest.AppLayout.MessageInterfacePlacement.prototype.placementBindEvent=function(e,t){if(!!e.event&&BX.util.in_array(e.event,this.messageInterface.events)){var s=BX.delegate(function(){if(!this._destroyed){t.apply(this,arguments)}else{BX.removeCustomEvent(e.event,s)}},this);BX.addCustomEvent(e.event,s)}};BX.rest.layoutList={};BX.rest.placementList={};BX.rest.AppLayout.placementInterface={};BX.rest.AppLayout.get=function(e){return BX.rest.layoutList[e]};BX.rest.AppLayout.set=function(e,t,s){e=(e+"").toUpperCase();s.appSid=t;s.placement=e;BX.rest.layoutList[t]=new BX.rest.AppLayout(s);return BX.rest.layoutList[t]};BX.rest.AppLayout.getPlacement=function(e){return BX.rest.placementList[(e+"").toUpperCase()]};BX.rest.AppLayout.setPlacement=function(e,t){BX.rest.placementList[(e+"").toUpperCase()]=t};BX.rest.AppLayout.initialize=function(e,t){e=(e+"").toUpperCase();BX.rest.layoutList[e]=BX.rest.layoutList[t];BX.rest.layoutList[e].init()};BX.rest.AppLayout.destroy=function(e){var t=BX.rest.AppLayout.get(e);if(!!t){t.destroy()}BX.rest.layoutList[t.params.appSid]=null;if(!!BX.rest.AppLayout.placementInterface[e]){BX.rest.layoutList[e]=null}};function e(e,t){var s=e.split(t);return[s[0],s.slice(1,s.length-2).join(t),s[s.length-2],s[s.length-1]]}})();
/* End */
;