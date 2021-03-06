(function() {

"use strict";

BX.namespace("BX.CRM.Kanban");

/**
 *
 * @param options
 * @extends {BX.Kanban.Item}
 * @constructor
 */
BX.CRM.Kanban.Item = function(options)
{
	BX.Kanban.Item.apply(this, arguments);

	/** @var {Element} **/
	this.container = null;
	this.timer = null;
	this.popupTooltip = null;
	this.plannerCurrent = null;
};

BX.CRM.Kanban.Item.prototype = {
	__proto__: BX.Kanban.Item.prototype,
	constructor: BX.CRM.Kanban.Item,
	lastPosition: {
		columnId: null,
		targetId: null
	},

	/**
	 * Add <span> for last word in title.
	 * @param {String} fullTitle
	 * @returns {String}
	 */
	clipTitle: function (fullTitle)
	{
		var title = fullTitle;
		var arrTitle = title.split(" ");
		var lastWord = "<span>" + arrTitle[arrTitle.length - 1] + "</span>";

		arrTitle.splice(arrTitle.length - 1);

		title = arrTitle.join(" ") + " " + lastWord;

		return title;
	},

	/**
	 * Store key in current data.
	 * @param {String} key
	 * @param {String} val
	 * @returns {void}
	 */
	setDataKey: function(key, val)
	{
		var data = this.getData();
		data[key] = val;
		this.setData(data);
	},

	/**
	 * Get key value from current data.
	 * @param {String} key
	 * @returns {String}
	 */
	getDataKey: function(key)
	{
		var data = this.getData();
		return data[key];
	},

	/**
	 * Add or remove class for element.
	 * @param {DOMNode} el
	 * @param {String} className
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchClass: function(el, className, mode)
	{
		if (mode)
		{
			BX.addClass(el, className);
		}
		else
		{
			BX.removeClass(el, className);
		}
	},

	/**
	 * Show or hide element.
	 * @param {DOMNode} el
	 * @param {Boolean} mode
	 * @returns {void}
	 */
	switchVisible: function(el, mode)
	{
		if (mode)
		{
			el.style.display = "";
		}
		else
		{
			BX.hide(el);
		}
	},

	/**
	 * Get last position of item.
	 * @returns {void}
	 */
	getLastPosition: function()
	{
		return this.lastPosition;
	},


	/**
	 * Set last position of otem.
	 * @returns {void}
	 */
	setLastPosition: function()
	{
		var column = this.getColumn();
		var sibling = column.getNextItemSibling(this);

		this.lastPosition = {
			columnId: column.getId(),
			targetId: sibling ? sibling.getId() : 0
		};
	},

	/**
	 * Return full node for item.
	 * @returns {DOMNode}
	 */
	render: function()
	{
		if (!this.container)
		{
			this.createLayout();
		}

		var data = this.getData();
		var column = this.getColumn();
		var color = column.getColor();
		var rgb = BX.util.hex2rgb(color);
		var rgba = "rgba(" + rgb.r + "," + rgb.g + "," + rgb.b + "," + ".7)";

		// border color
		BX.style(this.container, "border-left", "3px solid " + rgba);
		// item link
		this.link.innerHTML = this.clipTitle(data.name);
		this.link.setAttribute(
			"href",
			data.link
		);
		// price
		if (this.totalPrice)
		{
			this.totalPrice.innerHTML = data.price_formatted;
		}
		// date
		this.date.textContent = data.date;
		// contact / company name
		if (data.contactId && data.contactName)
		{
			this.contactName.textContent = BX.util.htmlspecialcharsback(data.contactName);
			this.contactName.setAttribute(
				"href",
				data.contactLink
			);
			this.switchVisible(this.contactName, true);
		}
		else
		{
			this.switchVisible(this.contactName, false);
		}
		// planner
		if (this.planner)
		{
			this.switchPlanner();
		}
		// phone/mail/chat exist or not
		var contactTypes = ["Phone", "Email", "Im"];
		for (var i = 0, c = contactTypes.length; i < c; i++)
		{
			var type = contactTypes[i];
			var disabledClass = "crm-kanban-item-contact-" + type.toLowerCase() + "-disabled";
			BX.unbindAll(this["contact" + type]);
			if (data[type.toLowerCase()])
			{
				BX.bind(this["contact" + type], "click", BX.delegate(this.clickContact, this));
				this.switchClass(this["contact" + type], disabledClass, false);
			}
			else
			{
				BX.bind(this["contact" + type], "mouseover", BX.delegate(function()
				{
					var type = BX.data(BX.proxy_context, "type");
					this.showTooltip(
						BX.message("CRM_KANBAN_NO_" + type.toUpperCase()),
						BX.proxy_context
					);
				}, this));
				BX.bind(this["contact" + type], "mouseout", BX.delegate(this.hideTooltip, this));
				this.switchClass(this["contact" + type], disabledClass, true);
			}
		}

		return this.container;
	},

	/**
	 * Create layout for one item.
	 * @returns {void}
	 */
	createLayout: function()
	{
		var gridData = this.getGridData();

		// common container

		this.container = BX.create("div", {
			props: {
				className: (gridData.entityType === "INVOICE" || gridData.entityType === "QUOTE")
							? "crm-kanban-item crm-kanban-item-invoice" : "crm-kanban-item"
			},
			events: {
				dblclick: function()
				{
					var data = this.getData();
					window.location.href = data.link;
				}.bind(this),
				mouseleave: function()
				{
					this.removeHoverClass(this.container);
				}.bind(this)
			}
		});

		// title link
		this.link = BX.create("a", {
			props: {
				className: "crm-kanban-item-title"
			}
		});
		this.container.appendChild(this.link);
		// price
		if (gridData.entityType !== "LEAD")
		{
			this.totalPrice = BX.create("div", {
				props: {
					className: "crm-kanban-item-total-price"
				}
			});
			this.total = BX.create("div", {
				props: {
					className: "crm-kanban-item-total"
				},
				children: [
					this.totalPrice
				]
			});
			this.container.appendChild(this.total);
		}
		// contact / company name
		this.contactName = BX.create("a", {
			props: {
				className: "crm-kanban-item-contact"
			}
		});
		this.container.appendChild(this.contactName);
		// date
		this.date = BX.create("div", {
			props: {
				className: "crm-kanban-item-date"
			}
		});
		this.container.appendChild(this.date);
		// plan
		if (gridData.showActivity)
		{
			this.activityExist = BX.create("span", {
				props: {
					className: "crm-kanban-item-activity"
				},
				events: {
					click: BX.delegate(this.showCurrentPlan, this)
				}
			});
			this.activityEmpty = BX.create("span", {
				props: {
					className: "crm-kanban-item-activity"
				},
				events: {
					click: BX.delegate(function()
					{
						this.showTooltip(
							this.getMessage(gridData.entityType),
							BX.proxy_context,
							true
						);
					}, this)
					// mouseout: BX.delegate(this.hideTooltip, this)

				}
			});
			this.activityPlan = BX.create("span", {
				props: {
					className: "crm-kanban-item-plan"
				},
				text: '+ ' + BX.message("CRM_KANBAN_ACTIVITY_TO_PLAN"),
				events: {
					click: BX.delegate(this.showPlannerMenu, this)
				}
			});
			this.planner = BX.create("span", {
				props: {
					className: "crm-kanban-item-planner"
				},
				children: [
					this.activityEmpty,
					this.activityExist,
					this.activityPlan
				]
			});
			this.container.appendChild(this.planner);
		}
		// phone, mail, chat
		this.contactPhone = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-phone crm-kanban-item-contact-phone-disabled"
			},
			attrs: {
				"data-type": "phone"
			}
		});
		this.contactEmail = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-email crm-kanban-item-contact-email-disabled"
			},
			attrs: {
				"data-type": "email"
			}
		});
		this.contactIm = BX.create("span", {
			props: {
				className: "crm-kanban-item-contact-im crm-kanban-item-contact-im-disabled"
			},
			attrs: {
				"data-type": "im"
			}
		});
		this.contactBlock = BX.create("div", {
			props: {
				className: "crm-kanban-item-connect"
			},
			children: [
				this.contactPhone,
				this.contactEmail,
				this.contactIm
			]
		});
		this.container.appendChild(this.contactBlock);
		// hover / shadow
		this.container.appendChild(this.createShadow());
	},

	getMessage: function(type) {
		var content = BX.create("span");
		content.innerHTML = BX.message("CRM_KANBAN_ACTIVITY_CHANGE_" + type);

		var eventLink = content.querySelector('.crm-kanban-item-activity-link');
		BX.bind(eventLink, 'click', function() {
			this.showPlannerMenu(this.activityPlan);
			this.popupTooltip.destroy();
		}.bind(this));
		return content
	},

	/**
	 * Get preloader for popup.
	 * @returns {String}
	 */
	getPreloader: function()
	{
		return "<div class=\"crm-kanban-preloader-wapper\">\n\
								<div class=\"crm-kanban-preloader\">\n\
									<svg class=\"crm-kanban-circular\" viewBox=\"25 25 50 50\">\n\
										<circle class=\"crm-kanban-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-width=\"1\" stroke-miterlimit=\"10\"/>\n\
									</svg>\n\
								</div>\n\
						</div>";
	},

	/**
	 * Load current plan for item.
	 * @returns {void}
	 */
	loadCurrentPlan: function()
	{
		this.getGrid().ajax({
				action: "activities",
				entity_id: this.getId()
			},
			function(data)
			{
				this.plannerCurrent.setContent(data);
				this.plannerCurrent.adjustPosition();
			}.bind(this),
			function(error)
			{
				BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
			}.bind(this),
			"html"
		);
	},

	/**
	 * Show current plan items.
	 * @returns {void}
	 */
	showCurrentPlan: function()
	{
		this.plannerCurrent = BX.PopupWindowManager.create(
			"kanban_planner_current",
			BX.proxy_context,
			{
				closeIcon : false,
				autoHide: true,
				className: "crm-kanban-popup-plan",
				closeByEsc : true,
				contentColor: "white",
				angle: true,
				offsetLeft: 15,
				overlay: {
					backgroundColor: "transparent",
					opacity: "0"
				},
				events: {
					onAfterPopupShow: BX.delegate(this.loadCurrentPlan, this),
					onPopupClose: function()
					{
						this.plannerCurrent.destroy();
						BX.removeClass(this.container, "crm-kanban-item-hover");
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
					}.bind(this)
				}
			}
		);
		this.plannerCurrent.setContent(this.getPreloader());
		this.plannerCurrent.show();
		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
	},

	/**
	 * Click on phone/email/chat.
	 * @returns {void}
	 */
	clickContact: function()
	{
		var type = BX.data(BX.proxy_context, "type");
		var data = this.getData();
		var fields = data[type];

		if (typeof fields === "undefined")
		{
			return;
		}

		if (Array.isArray(fields) && fields.length > 1)
		{
			var menuItems = [];
			for (var i = 0, c = fields.length; i < c; i++)
			{
				menuItems.push({
					value: fields[i]["value"],
					type: type,
					text: fields[i]["value"] + " (" + fields[i]["title"] + ")",
					onclick: BX.proxy(this.clickContactItem, this)
				});
			}
			BX.PopupMenu.show(
				"kanban_contact_menu_" + type + this.getId(),
				BX.proxy_context,
				menuItems,
				{
					autoHide: true,
					zIndex: 1200,
					offsetLeft: 20,
					angle: true,
					closeByEsc : true,
					events: {
						onPopupClose: function()
						{
							BX.removeClass(this.container, "crm-kanban-item-hover");
							BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
						}.bind(this)
					}
				}
			);
			BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
		}
		else
		{
			if (!Array.isArray(fields))
			{
				fields = [fields];
			}
			this.clickContactItem(0, {
				value: fields[0]["value"],
				type: type
			});
		}
	},

	/**
	 * Click on phone/email/chat (one item).
	 * @param {Integer} i
	 * @param {Object} item
	 * @returns {void}
	 */
	clickContactItem: function(i, item)
	{
		var data = this.getData();

		if (item.type === "phone" && typeof(BXIM) !== "undefined")
		{
			BXIM.phoneTo(item.value, {
				ENTITY_TYPE: data.contactType,
				ENTITY_ID: data.contactId
			});
		}
		else if (item.type === "im" && typeof(BXIM) !== "undefined")
		{
			BXIM.openMessengerSlider(item.value, {RECENT: 'N', MENU: 'N'});
		}
		else if (item.type === "email")
		{
			var hasActivityEditor = BX.CrmActivityEditor && BX.CrmActivityEditor.items['kanban_activity_editor'];
			var hasSlider = top.BX.Bitrix24 && top.BX.Bitrix24.Slider;
			if (hasActivityEditor && BX.CrmActivityProvider && hasSlider)
			{
				var gridData = this.getGridData();

				// @TODO: fix communication entity
				BX.CrmActivityEditor.items['kanban_activity_editor'].addEmail({
					'ownerType': gridData.entityType,
					'ownerID': data.id,
					'communications': [{
						'type': 'EMAIL',
						'value': item.value,
						'entityId': data.id,
						'entityType': gridData.entityType,
						'entityTitle': data.name
					}],
					'communicationsLoaded': true
				});
			}
			else
			{
				//@tmp
				top.location.href = "mailto:" + item.value;
			}
		}
	},

	/**
	 * Click one the item of plan menu
	 * @param {Integer} i
	 * @param {Object} item
	 * @returns {void}
	 */
	selectPlannerMenu: function(i, item)
	{
		var gridData = this.getGridData();

		if (item.type === "meeting" || item.type === "call")
		{
			(new BX.Crm.Activity.Planner()).showEdit({
				TYPE_ID: BX.CrmActivityType[item.type],
				OWNER_TYPE: gridData.entityType,
				OWNER_ID: this.getId()
			});
		}
		else if (item.type === "task")
		{
			if (typeof window["taskIFramePopup"] !== "undefined")
			{
				var taskData = {
					UF_CRM_TASK: [BX.CrmOwnerTypeAbbr.resolve(gridData.entityType) + "_" + this.getId()],
					TITLE: "CRM: ",
					TAGS: "crm"
				};
				window["taskIFramePopup"].add(taskData);
			}
		}
		else if (item.type === "visit")
		{
			var visitParams = gridData.visitParams;
			visitParams.OWNER_TYPE = gridData.entityType;
			visitParams.OWNER_ID = this.getId();
			BX.CrmActivityVisit.create(visitParams).showEdit();
		}
		
		var menu = BX.PopupMenu.getCurrentMenu();
		if (menu)
		{
			menu.close();
		}
	},

	/**
	 * Get menu for planner.
	 * @returns {Object}
	 */
	getPlannerMenu: function()
	{
		return [
			{
				type: "call",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_CALL"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			},
			{
				type: "meeting",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_MEETING"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			},
			{
				type: "visit",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_VISIT"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			},
			{
				type: "task",
				text: BX.message("CRM_KANBAN_ACTIVITY_PLAN_TASK"),
				onclick: BX.delegate(this.selectPlannerMenu, this)
			}
		];
	},

	/**
	 * Plan new activity.
	 * @returns {void}
	 */
	showPlannerMenu: function(node)
	{
		var popupMenu = BX.PopupMenu.create(
			"kanban_planner_menu_" + this.getId(),
			node.isNode ? node : this.activityPlan,
			this.getPlannerMenu(),
			{
				className: "crm-kanban-planner-popup-window",
				autoHide: true,
				offsetLeft: 50,
				angle: true,
				overlay: {
					backgroundColor: "transparent",
					opacity: "0"
				},
				events: {
					onPopupClose: function()
					{
						BX.removeClass(this.container, "crm-kanban-item-hover");
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
						popupMenu.destroy()
					}.bind(this)
				}
			}
		);
		popupMenu.show();
		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));
	},

	/**
	 * Show / hide planner.
	 * @returns {void}
	 */
	switchPlanner: function()
	{
		var data = this.getData();
		var column = this.getColumn();
		var columnData = column.getData();

		if (data.activityProgress > 0)
		{
			this.switchVisible(this.activityExist, true);
			this.switchVisible(this.activityEmpty, false);
			this.activityExist.innerHTML = BX.message("CRM_KANBAN_ACTIVITY_MY") +
											(data.activityErrorTotal && columnData.type === "PROGRESS" ? " <span>" + data.activityErrorTotal + "</span>" : "");
		}
		else
		{
			this.switchVisible(this.activityExist, false);
			this.switchVisible(
				this.activityPlan,
				true
			);
			this.switchVisible(
				this.activityEmpty, 
				true
			);
			this.activityEmpty.innerHTML = BX.message("CRM_KANBAN_ACTIVITY_MY") + 
											(columnData.type === "PROGRESS" ? " <span>1</span>" : "");
		}
	},

	/**
	 * Show some tooltip.
	 * @param {String} message
	 * @returns {void}
	 */
	showTooltip: function(message, context, white)
	{
		this.popupTooltip = new BX.PopupWindow(
			"kanban_tooltip",
			BX.proxy_context,
			{
				className: white ? "crm-kanban-without-tooltip crm-kanban-without-tooltip-white" : "crm-kanban-without-tooltip crm-kanban-tooltip-animate",
				offsetLeft: 14,
				darkMode: white ? false : true,
				overlay: white ? {background: 'black', opacity: 0} : null,
				closeByEsc: true,
				angle : true,
				autoHide: true,
				content: message,
				events: {
					onPopupClose: function()
					{
						BX.unbind(window, "scroll", BX.proxy(this.adjustPopup, this));
					}.bind(this)
				}
			}
		);

		BX.bind(window, "scroll", BX.proxy(this.adjustPopup, this));

		this.popupTooltip.show();
	},

	/**
	 * Hide tooltip.
	 * @returns {void}
	 */
	hideTooltip: function()
	{
		this.popupTooltip.destroy();
	},

	/**
	 * Add shadow to item.
	 * @returns {DOMNode}
	 */
	createShadow: function ()
	{
		return BX.create("div", {
			props: { className: "crm-kanban-item-shadow" }
		});
	},

	/**
	 * Remove hover from item.
	 * @param {DOMNode} itemBlock
	 * @returns {void}
	 */
	removeHoverClass: function (itemBlock)
	{
		BX.removeClass(itemBlock, "crm-kanban-item-event");
		BX.removeClass(itemBlock, "crm-kanban-item-hover");
	},

	/**
	 * Adjust position of current popup.
	 * @returns {void}
	 */
	adjustPopup: function()
	{
		var popup = BX.PopupWindowManager.getCurrentPopup();
		if (!popup)
		{
			if(menu)
			{
				var menu = BX.PopupMenu.getCurrentMenu();
				popup = menu.getPopupWindow();
			}
		}
		if (popup)
		{
			popup.adjustPosition();
		}
	}

};

})();