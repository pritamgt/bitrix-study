; /* /bitrix/js/main/spotlight/spotlight.min.js?15269824507619*/

; /* Start:"a:4:{s:4:"full";s:57:"/bitrix/js/main/spotlight/spotlight.min.js?15269824507619";s:6:"source";s:38:"/bitrix/js/main/spotlight/spotlight.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function(){"use strict";BX.SpotLight=function(t){this.container=null;this.popup=null;this.id="spotlight-"+BX.util.getRandomString().toLowerCase();this.options={};this.targetElement=null;this.targetElementRect=null;this.targetVertex="top-left";this.content=null;this.top=0;this.left=0;this.lightMode=false;this.autoSave=false;this.zIndex=null;this.observerTimeoutId=null;this.observerTimeout=1e3;this.setOptions(t);if(!this.targetElement){throw new Error("BX.SpotLight: 'targetElement' is not a DOMNode.")}this.handlePageResize=this.handlePageResize.bind(this)};BX.SpotLight.prototype={setOptions:function(t){t=BX.type.isPlainObject(t)?t:{};this.options=t;this.setTargetElement(t.renderTo);this.setTargetElement(t.targetElement);this.setTargetVertex(t.targetVertex);this.setZindex(t.zIndex);this.setLightMode(t.lightMode);this.setContent(t.content);this.setOffsetLeft(t.left);this.setOffsetTop(t.top);this.setAutoSave(t.autoSave);this.setObserverTimeout(t.observerTimeout);this.setId(t.id)},bindEvents:function(t){if(!BX.type.isPlainObject(t)){return}for(var e in t){var i=BX.type.isFunction(t[e])?t[e]:BX.getClass(t[e]);if(i){BX.addCustomEvent(this,this.getFullEventName(e),i)}}},unbindEvents:function(t){if(!BX.type.isPlainObject(t)){return}for(var e in t){var i=BX.type.isFunction(t[e])?t[e]:BX.getClass(t[e]);if(i){BX.removeCustomEvent(this,this.getFullEventName(e),i)}}},getOptions:function(){return this.options},getId:function(){return this.id},setId:function(t){if(BX.type.isNotEmptyString(t)){this.id=t}},getZindex:function(){if(this.zIndex!==null){return this.zIndex}return this.getGlobalIndex(this.getTargetElement())+1},getGlobalIndex:function(t){var e=0;do{e=BX.type.stringToInt(BX.style(t,"z-index"));t=t.offsetParent}while(t&&t.tagName!=="BODY");return e},setZindex:function(t){if(BX.type.isNumber(t)||t===null){this.zIndex=t}},getContent:function(){return this.content},setContent:function(t){if(BX.type.isNotEmptyString(t)||BX.type.isDomNode(t)||t===null){this.content=t}},getTargetElement:function(){return this.targetElement},setTargetElement:function(t){if(BX.type.isNotEmptyString(t)){t=document.querySelector(t)||BX(t)}if(BX.type.isDomNode(t)){this.targetElement=t;this.renderTo=t}},getOffsetLeft:function(){return this.left},setOffsetLeft:function(t){if(BX.type.isNumber(t)){this.left=t}},getOffsetTop:function(){return this.top},setOffsetTop:function(t){if(BX.type.isNumber(t)){this.top=t}},getLightMode:function(){return this.lightMode},setLightMode:function(t){if(BX.type.isBoolean(t)){this.lightMode=t}},getAutoSave:function(){return this.autoSave},setAutoSave:function(t){if(BX.type.isBoolean(t)){this.autoSave=t}},getObserverTimeout:function(){return this.observerTimeout},setObserverTimeout:function(t){if(BX.type.isNumber(t)&&t>=0){this.observerTimeout=t}},getTargetVertex:function(){return this.targetVertex},setTargetVertex:function(t){if(BX.type.isNotEmptyString(t)){this.targetVertex=t}},getPopup:function(){if(this.popup){return this.popup}this.popup=new BX.PopupWindow("spotlight-"+BX.util.getRandomString(),this.container,{className:"main-spot-light-popup",angle:{position:"top",offset:41},closeByEsc:true,overlay:true,content:this.getContent(),events:{onPopupShow:function(){this.fireEvent("onPopupShow")}.bind(this),onPopupClose:function(){this.close();this.fireEvent("onPopupClose")}.bind(this)},buttons:[new BX.PopupWindowCustomButton({text:BX.message("MAIN_SPOTLIGHT_UNDERSTAND"),className:"webform-small-button webform-small-button-blue",events:{click:function(){this.close();this.fireEvent("onPopupAccept");BX.onCustomEvent(this,"spotLightOk",[this.getTargetElement(),this])}.bind(this)}})]});return this.popup},getTargetContainer:function(){if(this.container){return this.container}this.container=BX.create("div",{attrs:{className:this.getLightMode()?"main-spot-light main-spot-light-white":"main-spot-light"},events:{mouseenter:this.handleTargetMouseEnter.bind(this),mouseleave:this.handleTargetMouseLeave.bind(this)}});return this.container},adjustPosition:function(){this.targetElementRect=BX.pos(this.getTargetElement());var t=this.getTargetElement();var e=Boolean(t.offsetWidth||t.offsetHeight||t.getClientRects().length);if(!e){this.container.hidden=true;return}var i=0;var n=0;var s=this.getTargetVertex();switch(s){case"top-left":default:i=this.targetElementRect.left;n=this.targetElementRect.top;break;case"top-center":i=this.targetElementRect.left+this.targetElementRect.width/2;n=this.targetElementRect.top;break;case"top-right":i=this.targetElementRect.right;n=this.targetElementRect.top;break;case"middle-left":i=this.targetElementRect.left;n=this.targetElementRect.top+this.targetElementRect.height/2;break;case"middle-center":i=this.targetElementRect.left+this.targetElementRect.width/2;n=this.targetElementRect.top+this.targetElementRect.height/2;break;case"middle-right":i=this.targetElementRect.right;n=this.targetElementRect.top+this.targetElementRect.height/2;break;case"bottom-left":i=this.targetElementRect.left;n=this.targetElementRect.bottom;break;case"bottom-center":i=this.targetElementRect.left+this.targetElementRect.width/2;n=this.targetElementRect.bottom;break;case"bottom-right":i=this.targetElementRect.right;n=this.targetElementRect.bottom;break}this.container.hidden=false;this.container.style.left=i+this.getOffsetLeft()+"px";this.container.style.top=n+this.getOffsetTop()+"px";this.container.style.zIndex=this.getZindex()},handlePageResize:function(){this.adjustPosition()},handleTargetMouseEnter:function(){this.fireEvent("onTargetEnter");if(this.getContent()){this.getPopup().show()}if(this.getAutoSave()){this.save()}},handleTargetMouseLeave:function(){this.fireEvent("onTargetLeave")},handleTargetElementResize:function(){var t=BX.pos(this.getTargetElement());if(t.left!==this.targetElementRect.left||t.right!==this.targetElementRect.right||t.top!==this.targetElementRect.top||t.bottom!==this.targetElementRect.bottom){this.adjustPosition()}},show:function(){if(!this.getTargetContainer().parentNode){BX.bind(window,"resize",this.handlePageResize);BX.bind(window,"load",this.handlePageResize);BX.addCustomEvent("onFrameDataProcessed",this.handlePageResize);this.bindEvents(this.getOptions().events);document.body.appendChild(this.getTargetContainer());if(this.getObserverTimeout()){this.observerTimeoutId=setInterval(this.handleTargetElementResize.bind(this),this.getObserverTimeout())}}this.fireEvent("onShow");this.adjustPosition()},close:function(){this.fireEvent("onClose");if(this.popup){this.popup.destroy();this.popup=null}if(this.observerTimeoutId){clearInterval(this.observerTimeoutId);this.observerTimeoutId=null}BX.unbind(window,"resize",this.handlePageResize);BX.unbind(window,"load",this.handlePageResize);BX.removeCustomEvent("onFrameDataProcessed",this.handlePageResize);this.unbindEvents(this.getOptions().events);BX.remove(this.container);this.container=null},save:function(){var t="view_date_"+this.getId();BX.userOptions.save("spotlight",t,null,Math.floor(Date.now()/1e3));BX.userOptions.send(null)},fireEvent:function(t){if(BX.type.isNotEmptyString(t)){BX.onCustomEvent(this,this.getFullEventName(t),[this])}},getFullEventName:function(t){return"BX.SpotLight:"+t}};BX.SpotLight.Manager={spotlights:{},create:function(t){t=BX.type.isPlainObject(t)?t:{};var e=t.id;if(!BX.type.isNotEmptyString(e)){throw new Error("'id' parameter is required.")}if(this.get(e)){throw new Error("The spotlight instance with the same 'id' already exists.")}var i=new BX.SpotLight(t);this.spotlights[e]=i;return i},get:function(t){return t in this.spotlights?this.spotlights[t]:null},remove:function(t){delete this.spotlights[t]}}})();
/* End */
;