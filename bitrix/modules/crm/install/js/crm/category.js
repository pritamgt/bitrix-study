BX.namespace("BX.Crm");

if(typeof(BX.Crm.DealCategoryChanger) === "undefined")
{
	BX.Crm.DealCategoryChanger = function()
	{
		this._id = "";
		this._settings = null;
		this._selector = null;
		this._selectListener = BX.delegate(this.onSelect, this);
		this._serviceUrl = "";
		this._entityId = 0;

		this._confirmationDialog = null;
		this._errorDialog = null;
	};

	BX.Crm.DealCategoryChanger.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");
			this._entityId = BX.prop.getInteger(this._settings, "entityId", 0);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.DealCategoryChanger.messages, name, name);
		},
		process: function()
		{
			this.openConfirmationDialog(
				BX.delegate(function(){ this.closeConfirmationDialog(); this.openSelector(); }, this),
				BX.delegate(function(){ this.closeConfirmationDialog(); }, this)
			);
		},
		openConfirmationDialog: function(onConfirm, onCancel)
		{
			this._confirmationDialog = new BX.PopupWindow(
				this._id + "_confirm",
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: this.getMessage("dialogTitle"),
					content: this.getMessage("dialogSummary"),
					className : "crm-text-popup",
					lightShadow : true,
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text : BX.message("JS_CORE_WINDOW_CONTINUE"),
								className : "ui-btn ui-btn-success ui-btn-lg",
								events: { click: onConfirm }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CANCEL"),
								className : "ui-btn ui-btn-link ui-btn ui-btn-lg",
								events: { click: onCancel }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onOpenConfirmationDialog, this)
					}
				}
			);
			this._confirmationDialog.show();
		},
		onOpenConfirmationDialog: function()
		{
			this._confirmationDialog.contentContainer.className = "ui-alert ui-alert-icon-warning";
		},
		closeConfirmationDialog: function()
		{
			if(this._confirmationDialog)
			{
				this._confirmationDialog.close();
				this._confirmationDialog.destroy();
				this._confirmationDialog = null;
			}
		},
		openErrorDialog: function(message)
		{
			this._errorDialog = new BX.PopupWindow(
				this._id + "_error",
				null,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					zIndex: 0,
					content: message,
					className : "crm-text-popup",
					lightShadow : true,
					buttons:
					[
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CLOSE"),
								className : "ui-btn ui-btn-lg",
								events: { click: BX.delegate(this.closeErrorDialog, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onOpenErrorDialog, this)
					}
				}
			);
			this._errorDialog.show();
		},
		onOpenErrorDialog: function()
		{
			this._errorDialog.contentContainer.className = "ui-alert ui-alert-warning";
		},
		closeErrorDialog: function()
		{
			if(this._errorDialog)
			{
				this._errorDialog.close();
				this._errorDialog.destroy();
				this._errorDialog = null;
			}
		},
		openSelector: function()
		{
			if(!this._selector)
			{
				this._selector = BX.CrmDealCategorySelectDialog.create(
					this._id,
					{
						value: -1,
						categoryIds: BX.prop.getArray(this._settings, "categoryIds", [])
					}
				);
				this._selector.addCloseListener(this._selectListener);
			}
			this._selector.open();
		},
		onSelect: function(sender, args)
		{
			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION": BX.prop.getString(this._settings, "action", "MOVE_TO_CATEGORY"),
							"ACTION_ENTITY_ID": this._entityId,
							"CATEGORY_ID": sender.getValue()
						},
					onsuccess: BX.delegate(this.onSuccess, this)
				}
			);
		},
		onSuccess: function(data)
		{
			var error = BX.prop.getString(data, "ERROR", "");
			if(error !== "")
			{
				this.openErrorDialog(error);
				return;
			}
			window.location.reload();
		}
	};

	if(typeof(BX.Crm.DealCategoryChanger.messages) === "undefined")
	{
		BX.Crm.DealCategoryChanger.messages = {};
	}

	BX.Crm.DealCategoryChanger.items = {};
	BX.Crm.DealCategoryChanger.getByEntityId = function(entityId)
	{
		for(var key in this.items)
		{
			if(!this.items.hasOwnProperty(key))
			{
				continue;
			}

			var item = this.items[key];
			if(item.getEntityId() === entityId)
			{
				return item;
			}
		}
		return null;
	};
	BX.Crm.DealCategoryChanger.processEntity = function(entityId)
	{
		var item = this.getByEntityId(entityId);
		if(item)
		{
			item.process();
		}
	};
	BX.Crm.DealCategoryChanger.create = function(id, settings)
	{
		var self = new BX.Crm.DealCategoryChanger();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
