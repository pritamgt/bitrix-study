{"version":3,"file":"logic.min.js","sources":["logic.js"],"names":["BX","namespace","Tasks","Component","EmployeePlan","extend","options","pageSize","userProfileUrl","userNameTemplate","gutterOffset","zoomLevel","sys","code","methods","construct","this","callConstruct","vars","lastPage","lastCount","lastFilter","option","queryLock","subInstance","Util","Collection","keyField","load","initSelectors","initRouter","initGrid","onSearch","debounce","filter","SelectBox","scope","control","items","selected","TASK","STATUS","notSelectedLabel","message","bindEvent","delegate","statusChanged","export","MEMBER","DEPARTMENT","departmentChanged","us","ComboBox","getDepartmentUsers","USER","userChanged","setFilterHandlerFabric","value","#IX","DateRange","datesChanged","updateUserSelector","dep","result","deps","uD","D","getByKey","find","field","L","R","each","item","IX","ix","DISPLAY","toLowerCase","split","map","iVal","trim","push","VALUE","toString","clear","from","to","DATE_RANGE","FROM","TO","instances","router","Router","FILTER_OWNER","updateQueryString","f","data","setQueryString","calendarSettings","grid","Scheduler","View","headerText","renderTo","currentDatetime","parseDate","datetimeFormat","dateFormat","parseInt","weekends","WEEK_END","holidays","HOLIDAYS","firstWeekDay","WEEK_START","worktime","HOURS","events","onGutterResize","userOptions","save","onZoomChange","appendGridData","DATA","setGridVerticalCountTotal","COUNT_TOTAL","redrawGrid","bindEvents","bindControlThis","onSearchMore","showTaskPopup","Singletons","iframePopup","view","config","entityId","adaptGridData","gridData","users","userIx","tasks","k","USERS","length","user","id","ID","name","formatName","NAME","LAST_NAME","SECOND_NAME","LOGIN","parentId","DEPARTMENT_ID","parentDepartment","link","replace","TASKS","task","resourceId","RESPONSIBLE_ID","ACCOMPLICE_ID","TITLE","startDate","START_DATE_PLAN","endDate","END_DATE_PLAN","ACTION","READ","onclick","className","resetGrid","clearAll","render","count","type","isNumber","showBtn","optionInteger","getDepartments","depsData","depUsers","noDepUsers","getResourceStore","getEventStore","toggleSearchLoading","way","showNextGridPage","callRemote","nav","PAGE","parameters","GET_COUNT_TOTAL","then","r","errors","TYPE","isEmpty","alert","getErrors","reload","getUserCollection","d2u","RemoteCollection","source","depIds","transformer","ids","shift","Widget","controlBind","datePlanLimit","DatePicker","displayFormat","showSelector","onBorderChange","changeCSSFlag","fromChanged","toChanged","changeLock","getTimeStamp","fromAfter","toAfter","limit","Math","abs","tmp","node","setTimeStamp","hintManager","showDisposable","fireEvent","getValue","call"],"mappings":"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"}