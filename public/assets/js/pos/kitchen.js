/**
 *
 * core.js is the main object that provides base functionality to the Pos terminal.
 * It loads other needed modules and provides authentication, storage and data functions.
 *
 */

function POSKitchen() {
    var POS= this;
    var initialsetup = false;
    this.initApp = function () {
        // set cache default to true
        $.ajaxSetup({
            cache: true
        });
        // check online status to determine start & load procedure.
        if (checkOnlineStatus()) {
            POS.checkCacheUpdate(); // check if application cache is updating or already updated
        } else {
            // check approppriate offline records exist
            if (switchToOffline()) {
                POS.initLogin();
            }
        }
    };
    var cacheloaded = 1;
    var swManager = null;
    
    this.checkCacheUpdate = function(){
        // Initialize Service Worker Manager
        swManager = new ServiceWorkerManager();
        
        swManager.init({
            onCacheReady: function() {
                console.log("Service Worker cache loaded for the first time, no need for reload.");
                POS.initLogin();
            },
            onUpdateAvailable: function() {
                console.log("Service Worker update available, applying...");
                setLoadingBar(100, "Loading...");
                swManager.applyUpdate();
            },
            onUpdateApplied: function() {
                console.log("Service Worker update applied, reloading...");
                location.reload(true);
            },
            onProgress: function(message) {
                if (message.includes('Installing')) {
                    setLoadingBar(1, "Installing application...");
                } else if (message.includes('Updating')) {
                    setLoadingBar(50, "Updating application...");
                }
            },
            onError: function(error) {
                console.error("Service Worker error:", error);
                // Fallback to normal initialization if SW fails
                POS.initLogin();
            }
        }).then(function() {
            // If service worker is already active, proceed with login
            if (navigator.serviceWorker.controller) {
                console.log("Service Worker already active");
                POS.initLogin();
            }
        }).catch(function(error) {
            console.error("Service Worker initialization failed:", error);
            // Fallback to normal initialization
            POS.initLogin();
        });
    };
    // Check for device UUID & present Login, initial setup is triggered if the device UUID is not present
    this.initLogin = function(){
        showLogin();
        if (getDeviceUUID() == null) {
            // The device has not been setup yet; User will have to login as an admin to setup the device.
            POS.notifications.warning("The device has not been setup yet, please login as an administrator to setup the device.", "Initial Setup Required");
            initialsetup = true;
            online = true;
            return false;
        }
        return true;
    };
    // Plugin initiation functions
    this.initPlugins = function(){
        // load keypad if set
        setKeypad(true);
        // load printer plugin
        POS.print.loadPrintSettings();
    };
    this.initKeypad = function(){
        setKeypad(false);
    };
    function setKeypad(setcheckbox){
        if (getLocalConfig().keypad == true ){
            POS.util.initKeypad();
            if (setcheckbox)
                $("#keypadset").prop("checked", true);
        } else {
            if (setcheckbox)
                $("#keypadset").prop("checked", false);
        }
        // set keypad focus on click
        $(".numpad").on("click", function () {
            $(this).focus().select();
        });
    }
    // removed due to https mixed content restrictions
    function deployDefaultScanApp(){
        $.getScript('/assets/js/jquery.scannerdetection.js').done(function(){
            // Init plugin
            $(window).scannerDetection({
                onComplete: function(barcode){
                    // switch to sales tab
                    $("#wrapper").tabs( "option", "active", 0 );
                    POS.items.addItemFromStockCode(barcode);
                }
            });
        }).error(function(){
            POS.notifications.error("Failed to load the scanning plugin.", "Scanner Plugin Error");
        });
    }

    // AUTH
    function showLogin() {
        $("#modaldiv").show();
        $("#logindiv").show();
        $("#loadingdiv").hide();
        $('#loginbutton').removeAttr('disabled', 'disabled');
        setLoadingBar(0, "");
        $('body').css('overflow', 'auto');
    }

    this.userLogin = function () {
        POS.util.showLoader();
        var loginbtn = $('#loginbutton');
        // disable login button
        $(loginbtn).prop('disabled', true);
        $(loginbtn).val('Proccessing');
        // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
        // get form values
        var userfield = $("#username");
        var passfield = $("#password");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = POS.util.SHA256(password);
        // authenticate
        if (authenticate(username, password) === true) {
            userfield.val('');
            passfield.val('');
            $("#logindiv").hide();
            $("#loadingdiv").show();
            // initiate data download/check
            if (initialsetup) {
                if (isUserAdmin()) {
                    initSetup();
                } else {
                    POS.notifications.error("You must login as an administrator for first time setup", "Admin Access Required");
                    showLogin();
                }
            } else {
                initData(true);
            }
        }
        passfield.val('');
        $(loginbtn).val('Login');
        $(loginbtn).prop('disabled', false);
        POS.util.hideLoader();
    };

    this.logout = function () {
        POS.util.confirm("Are you sure you want to logout?", function() {
            POS.util.showLoader();
            stopSocket();
            POS.getJsonData("logout");
            showLogin();
            POS.util.hideLoader();
        });
    };

    function authenticate(user, hashpass) {
        // auth against server if online, offline table if not.
        if (online == true) {
            // send request to server
            var response = POS.sendJsonData("auth", JSON.stringify({username: user, password: hashpass, getsessiontokens:true}));
            if (response !== false) {
                // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
                setCurrentUser(response);
                updateAuthTable(response);
                return true;
            } else {
                return false;
            }
        } else {
            return offlineAuth(user, hashpass);
        }
    }

    function sessionRenew(){
        // send request to server
        var response = POS.sendJsonData("authrenew", JSON.stringify({username:currentuser.username, auth_hash:currentuser.auth_hash}));
        if (response !== false) {
            // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
            setCurrentUser(response);
            updateAuthTable(response);
            return true;
        } else {
            return false;
        }
    }

    function offlineAuth(username, hashpass) {
        if (localStorage.getItem("pos_auth") !== null) {
            var jsonauth = $.parseJSON(localStorage.getItem("pos_auth"));
            if (jsonauth[username] === null || jsonauth[username] === undefined) {
                POS.notifications.error("Sorry, your credentials are currently not available offline.", "Offline Authentication Error");
                return false;
            } else {
                var authentry = jsonauth[username];
                if (authentry.auth_hash == POS.util.SHA256(hashpass+authentry.token)) {
                    setCurrentUser(authentry);
                    return true;
                } else {
                    POS.notifications.error("Access denied!", "Authentication Failed");
                    return false;
                }
            }
        } else {
            POS.notifications.error("We tried to authenticate you without an internet connection but there are currently no local credentials stored.", "Offline Authentication Failed");
            return false;
        }
    }

    this.getCurrentUserId = function () {
        return currentuser.id
    };

    var currentuser;
    // set current user details
    function setCurrentUser(user) {
        currentuser = user;
    }

    function isUserAdmin() {
        return currentuser.isadmin == 1;
    }

    // initiate the setup process
    this.deviceSetup = function () {
        POS.util.showLoader();
        var devid = $("#posdevices option:selected").val();
        var devname = $("#neposdevice").val();
        var locid = $("#poslocations option:selected").val();
        var locname = $("#neposlocation").val();
        // check input
        if ((devid == null && devname == null) || (locid == null && locname == null)) {
            POS.notifications.warning("Please select a item from the dropdowns or specify a new name.", "Device Setup");
        } else {
            // call the setup function
            if (deviceSetup(devid, devname, locid, locname)) {
                currentuser = null;
                initialsetup = false;
                $("#setupdiv").dialog("close");
                showLogin();
            } else {
                POS.notifications.error("There was a problem setting up the device, please try again.", "Device Setup Failed");
            }
        }
        POS.util.hideLoader();
    };

    function initSetup() {
        POS.util.showLoader();
        // get pos locations and devices and populate select lists
        var devices = POS.getJsonData("devices/get");
        var locations = POS.getJsonData("locations/get");

        for (var i in devices) {
            if (devices[i].disabled==0 && devices[i].type=="kitchen_terminal"){ // only show kitchen devices which aren't disabled
                $("#posdevices").append('<option value="' + devices[i].id + '">' + devices[i].name + ' (' + devices[i].locationname + ')</option>');
            }
        }
        for (i in locations) {
            if (locations[i].disabled == 0){
                $("#poslocations").append('<option value="' + locations[i].id + '">' + locations[i].name + '</option>');
            }
        }
        POS.util.hideLoader();
        // show the setup dialog
        $("#setupdiv").dialog("open");
    }

    // get initial data for pos startup.
    function initData(loginloader) {
        if (loginloader){
            $("#loadingprogdiv").show();
            $("#loadingdiv").show();
        }
        if (online) {
            loadOnlineData(1, loginloader);
        } else {
            initOfflineData(loginloader);
        }
    }

    function loadOnlineData(step, loginloader){
        var statusmsg = "The POS is updating data and switching to online mode.";
        switch (step){
            case 1:
                $("#loadingbartxt").text("Loading online resources");
                // get device info and settings
                setLoadingBar(10, "Getting device settings...");
                setStatusBar(4, "Updating device settings...", statusmsg, 0);
                fetchConfigTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    loadOnlineData(2, loginloader);
                });
                break;

            case 2:
                // get stored items
                setLoadingBar(30, "Getting stored items...");
                setStatusBar(4, "Updating stored items...", statusmsg, 0);
                fetchItemsTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    loadOnlineData(3, loginloader);
                });
                break;

            case 3:
                // get all sales (Will limit to the weeks sales in future)
                setLoadingBar(60, "Getting recent sales...");
                setStatusBar(4, "Updating sales...", statusmsg, 0);
                fetchSalesTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    // start websocket connection
                    startSocket();
                    setStatusBar(1, "POS.is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
                    initDataSuccess(loginloader);
                    // check for offline sales on login
                    //setTimeout('if (POS.sales.getOfflineSalesNum()){ if (POS.sales.uploadOfflineRecords()){ POS.setStatusBar(1, "POS.is online"); } }', 2000);
                });
                break;
        }
    }

    function initOfflineData(loginloader){
        // check records and initiate java objects
        setLoadingBar(50, "Loading offline data...");
        loadConfigTable();
        loadItemsTable();
        loadSalesTable();
        POS.notifications.info("Your internet connection is not active and POS.has started in offline mode.\nSome features are not available in offline mode but you can always make sales and alter transactions that are locally available. \nWhen a connection becomes available POS will process your transactions on the server.", "Offline Mode");
        initDataSuccess(loginloader);
    }

    function initDataSuccess(loginloader){
        if (loginloader){
            setLoadingBar(100, "Initializing the awesome...");
            $("title").text("Pos - Your POS in the cloud");
            POS.initPlugins();
            setTimeout('$("#modaldiv").hide();', 500);
        }
        POS.kitchen.populateOrders();
    }

    function setLoadingBar(progress, status) {
        var loadingprog = $("#loadingprog");
        var loadingstat = $("#loadingstat");
        $(loadingstat).text(status);
        $(loadingprog).css("width", progress + "%");
    }

    /**
     * Update the pos status text and icon
     * @param statusType (1=Online, 2=Uploading, 3=Offline, 4=Downloading)
     * @param text
     * @param tooltip
     * @param timeout
     */
    this.setStatusBar = function(statusType, text, tooltip, timeout){
        setStatusBar(statusType, text, tooltip, timeout);
    };

    var defaultStatus = {type:1, text:"", tooltip:""};
    var statusTimer = null;

    function setDefaultStatus(statusType, text, tooltip){
        defaultStatus.type = statusType;
        defaultStatus.text = text;
        defaultStatus.tooltip = tooltip;
    }

    function setStatusBar(statusType, text, tooltip, timeout){
        if (timeout===0){
            setDefaultStatus(statusType, text, tooltip);
        } else if (timeout > 0 && statusTimer!=null){
            clearTimeout(statusTimer);
        }

        var staticon = $("#posstaticon");
        var statimg = $("#posstaticon i");
        switch (statusType){
            // Online icon
            case 1: $(staticon).attr("class", "badge badge-success");
                $(statimg).attr("class", "icon-ok");
                break;
            // Upload icon
            case 2: $(staticon).attr("class", "badge badge-info");
                $(statimg).attr("class", "icon-cloud-upload");
                break;
            // Offline icon
            case 3: $(staticon).attr("class", "badge badge-warning");
                $(statimg).attr("class", "icon-exclamation");
                break;
            // Download icon
            case 4: $(staticon).attr("class", "badge badge-info");
                $(statimg).attr("class", "icon-cloud-download");
                break;
            // Feed server disconnected
            case 5: $(staticon).attr("class", "badge badge-warning");
                $(statimg).attr("class", "icon-ok");
        }
        $("#posstattxt").text(text);
        $("#posstat").attr("title", tooltip);

        if (timeout > 0){
            statusTimer = setTimeout(resetStatusBar, timeout);
        }
    }

    // reset status bar to the current default status
    function resetStatusBar(){
        clearTimeout(statusTimer);
        statusTimer = null;
        setStatusBar(defaultStatus.type, defaultStatus.text, defaultStatus.tooltip);
    }

    var online = false;

    this.isOnline = function () {
        return online;
    };

    function checkOnlineStatus() {
        try {
            var res = $.ajax({
                timeout : 3000,
                url     : "/api/hello",
                type    : "GET",
                cache   : false,
                dataType: "text",
                async   : false
            }).status;
            online = res == "200";
        } catch (ex){
            online = false;
        }
        return online;
    }

    // OFFLINE MODE FUNCTIONS
    function canDoOffline() {
        if (getDeviceUUID()!==null) { // can't go offline if device hasn't been setup
            // check for auth table
            if (localStorage.getItem("pos_auth") == null) {
                return false;
            }
            // check for machine settings etc.
            if (localStorage.getItem("pos_config") == null) {
                return false;
            }
            return localStorage.getItem("pos_items") != null;
        }
        return false;
    }

    var checktimer;

    this.switchToOffline = function(){
        return switchToOffline();
    };

    function switchToOffline() {
        if (canDoOffline()==true) {
            // set js indicator: important
            online = false;
            setStatusBar(3, "POS.is Offline", "The POS is offine and will store sale data locally until a connection becomes available.", 0);
            // start online check routine
            checktimer = setInterval(doOnlineCheck, 60000);
            return true;
        } else {
            // display error notice
            POS.notifications.error("There was an error connecting to the webserver & files needed to run offline are not present :( \nPlease check your connection and try again.", "Connection Error");
            $("#modaldiv").show();
            ('#loginbutton').prop('disabled', true);
            setLoadingBar(100, "Error switching to offine mode");
            return false;
        }
    }

    function doOnlineCheck() {
        if (checkOnlineStatus()==true) {
            clearInterval(checktimer);
            switchToOnline();
        }
    }

    function switchToOnline() {
        // upload offline sales
        //if (POS.sales.uploadOfflineRecords()){
            // set js and ui indicators
            online = true;
            // load fresh data
            initData(false);
            // initData();
        setStatusBar(1, "POS.is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        //}
    }

    // GLOBAL COM FUNCTIONS
    this.sendJsonData = function (action, data) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "POST",
                data    : {data: data},
                dataType: "text",
                timeout : 10000,
                cache   : false,
                async   : false
            });
            if (response.status == "200") {
                var json = $.parseJSON(response.responseText);
                if (json == null) {
                    POS.notifications.error("Error: The response that was returned from the server could not be parsed!", "Parse Error");
                    return false;
                }
                var errCode = json.errorCode;
                var err = json.error;
                if (err == "OK") {
                    // echo warning if set
                    if (json.hasOwnProperty('warning')){
                        POS.notifications.warning(json.warning, "Warning", {delay: 0});
                    }
                    return json.data;
                } else {
                    if (errCode == "auth") {
                        if (sessionRenew()) {
                            // try again after authenticating
                            return POS.sendJsonData(action, data);
                        } else {
                            //alert(err);
                            return false;
                        }
                    } else {
                        POS.notifications.error(err, "Error", {delay: 0});
                        return false;
                    }
                }
            } else {
                switchToOffline();
                POS.notifications.error("There was an error connecting to the server: \n"+response.statusText+", \n switching to offline mode", "Connection Error");
                return false;
            }
        } catch (ex) {
            switchToOffline();
            POS.notifications.error("There was an error sending data, switching to offline mode", "Connection Error");
            return false;
        }
    };

    this.sendJsonDataAsync = function (action, data, callback, callbackref) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "POST",
                data    : {data: data},
                dataType: "json",
                timeout : 10000,
                cache   : false,
                success : function(json){
                    var errCode = json.errorCode;
                    var err = json.error;
                    if (err == "OK") {
                        // echo warning if set
                        if (json.hasOwnProperty('warning')){
                            POS.notifications.warning(json.warning, "Warning");
                        }
                        callback(json.data, callbackref);
                    } else {
                        if (errCode == "auth") {
                            if (sessionRenew()) {
                                // try again after authenticating
                                callback(POS.sendJsonData(action, data), callbackref);
                            } else {
                                //alert(err);
                                callback(false, callbackref);
                            }
                        } else {
                            POS.notifications.error(err, "Server Error");
                            callback(false, callbackref);
                        }
                    }
                },
                error   : function(jqXHR, status, error){
                    POS.notifications.error(error, "Connection Error");
                    callback(false, callbackref);
                }
            });
            return true;
        } catch (ex) {
            return false;
        }
    };

    this.getJsonData = function (action) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "GET",
                dataType: "text",
                timeout : 10000,
                cache   : false,
                async   : false
            });
            if (response.status == "200") {
                var json = $.parseJSON(response.responseText);
                var errCode = json.errorCode;
                var err = json.error;
                if (err == "OK") {
                    // echo warning if set
                    if (json.hasOwnProperty('warning')){
                        POS.notifications.warning(json.warning, "Warning");
                    }
                    return json.data;
                } else {
                    if (errCode == "auth") {
                        if (sessionRenew()) {
                            // try again after authenticating
                            return POS.getJsonData(action);
                        } else {
                            //alert(err);
                            return false;
                        }
                    } else {
                        POS.notifications.error(err, "Error", {delay: 0});
                        return false;
                    }
                }
            } else {
                POS.notifications.error("There was an error connecting to the server: \n"+response.statusText, "Connection Error");
                return false;
            }
        } catch (ex){
            return false;
        }
    };

    this.getJsonDataAsync = function (action, callback) {
        // send request to server
        try {
            $.ajax({
                url     : "/api/"+action,
                type    : "GET",
                dataType: "json",
                timeout : 10000,
                cache   : false,
                success : function(json){
                    var errCode = json.errorCode;
                    var err = json.error;
                    if (err == "OK") {
                        // echo warning if set
                        if (json.hasOwnProperty('warning')){
                            POS.notifications.warning(json.warning, "Warning", {delay: 0});
                        }
                        if (callback)
                            callback(json.data);
                    } else {
                        if (errCode == "auth") {
                            if (sessionRenew()) {
                                // try again after authenticating
                                var result = POS.sendJsonData(action, data);
                                if (result){
                                    if (callback)
                                        callback(result);
                                    return;
                                }
                            }
                        }
                        POS.notifications.error(err, "Error", {delay: 0});
                        if (callback)
                            callback(false);
                    }
                },
                error   : function(jqXHR, status, error){
                    POS.notifications.error(error, "Request Error", {delay: 0});
                    if (callback)
                        callback(false);
                }
            });
        } catch (ex) {
            POS.notifications.error("Exception: "+ex, "Exception", {delay: 0});
            if (callback)
                callback(false);
        }
    };

    // AUTHENTICATION & USER SETTINGS
    /**
     * Update the offline authentication table using the json object provided. This it returned on successful login.
     * @param {object} jsonobj ; user record returned by authentication
     */
    function updateAuthTable(jsonobj) {
        var jsonauth;
        if (localStorage.getItem("pos_auth") !== null) {
            jsonauth = $.parseJSON(localStorage.getItem("pos_auth"));
            jsonauth[jsonobj.username.toString()] = jsonobj;
        } else {
            jsonauth = { };
            jsonauth[jsonobj.username.toString()] = jsonobj;
        }
        localStorage.setItem("pos_auth", JSON.stringify(jsonauth));
    }

    // DEVICE SETTINGS AND INFO
    var configtable;

    this.getConfigTable = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable;
    };
    /**
     * Fetch device settings from the server using UUID
     * @return boolean
     */
    function fetchConfigTable(callback) {
        var data = {};
        data.uuid = getDeviceUUID();
        return POS.sendJsonDataAsync("config/get", JSON.stringify(data), function(data){
            if (data) {
                console.log(data);
                if (data.hasOwnProperty("remdev")){ // return false if dev is disabled
                    initialsetup = true;
                    if (callback){
                        callback(false);
                        return;
                    }
                } else {
                    configtable = data;
                    localStorage.setItem("pos_kitchen_config", JSON.stringify(data));
                    setAppCustomization();
                }
            }
            if (callback)
                callback(data);
        });
    }

    function loadConfigTable() {
        var data = localStorage.getItem("pos_kitchen_config");
        if (data != null) {
            configtable = JSON.parse(data);
            return true;
        }
        return false;
    }

    function updateConfig(key, value){
        configtable[key] = value; // write to current data
        localStorage.setItem("pos_kitchen_config", JSON.stringify(configtable));
        setAppCustomization();
    }

    function setAppCustomization(){
        var url = POS.getConfigTable().general.bizlogo;
        console.log(url);
        $("#watermark").css("background-image", "url('"+url+"')");
    }

    this.getTaxTable = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable.tax;
    };

    // Local Config
    this.setLocalConfigValue = function(key, value){
        setLocalConfigValue(key, value);
    };

    this.getLocalConfig = function(){
        return getLocalConfig();
    };

    function getLocalConfig(){
        var lconfig = localStorage.getItem("pos_kitchen_lconfig");
        if (lconfig==null || lconfig==undefined){
            // put default config here.
            var defcon = {
                keypad: true
            };
            updateLocalConfig(defcon);
            return defcon;
        }
        return JSON.parse(lconfig);
    }

    function setLocalConfigValue(key, value){
        var data = localStorage.getItem("pos_kitchen_lconfig");
        if (data==null){
            data = {};
        } else {
            data = JSON.parse(data);
        }
        data[key] = value;
        updateLocalConfig(data);
        if (key == "keypad"){
            setKeypad(false);
        }
    }

    function updateLocalConfig(configobj){
        localStorage.setItem("pos_kitchen_lconfig", JSON.stringify(configobj));
    }

    /**
     * This function sets up the
     * @param {int} devid ; if not null, the newname var is ignored and the new uuid is merged with the device specified by devid.
     * @param {int} newdevname ; A new device name, if specified the
     * @param {int} locid ; if not null, the newlocname field is ignored and blah blah blah....
     * @param {int} newlocname ; if not null, the newlocname field is ignored and blah blah blah....
     * @returns {boolean}
     */
    function deviceSetup(devid, newdevname, locid, newlocname) {
        var data = {};
        data.uuid = setDeviceUUID(false);
        if (devid === "") {
            data.devicename = newdevname;
        } else {
            data.deviceid = devid;
        }
        if (locid === "") {
            data.locationname = newlocname;
        } else {
            data.locationid = locid;
        }
        var configobj = POS.sendJsonData("devices/setup", JSON.stringify(data));
        if (configobj) {
            localStorage.setItem("pos_config", JSON.stringify(configobj));
            configtable = configobj;
            return true;
        } else {
            setDeviceUUID(true);
            return false;
        }
    }

    /**
     * Returns the current devices UUID
     * @returns {String, Null} String if set, null if not
     */
    function getDeviceUUID() {
        // return the devices uuid; if null, the device has not been setup or local storage was cleared
        return localStorage.getItem("pos_kitchen_devuuid");
    }

    /**
     * Creates or clears device UUID and updates in local storage
     * @param clear If true, the current UUID is detroyed
     * @returns {String, Null} String uuid if set, null if cleared
     */
    function setDeviceUUID(clear) {
        var uuid = null;
        if (clear) {
            localStorage.removeItem("pos_kitchen_devuuid");
        } else {
            // generate a md5 UUID using datestamp and rand for entropy and return the result
            var date = new Date().getTime();
            uuid = POS.util.SHA256((date * Math.random()).toString());
            localStorage.setItem("pos_kitchen_devuuid", uuid);
        }
        return uuid;
    }

    // RECENT SALES
    var salestable;

    this.getSalesTable = function () {
        if (salestable == null) {
            loadSalesTable();
        }
        return salestable;
    };

    function fetchSalesTable(callback) {
        return POS.sendJsonDataAsync("sales/get", JSON.stringify({deviceid: configtable.deviceid}), function(data){
            if (data) {
                salestable = data;
                localStorage.setItem("pos_csales", JSON.stringify(data));
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadSalesTable() {
        var data = localStorage.getItem("pos_csales");
        if (data !== null) {
            salestable = JSON.parse(data);
            return true;
        }
        return false;
    }

    this.updateSalesTable = function(saleobject){
        updateSalesTable(saleobject);
    };
    function updateSalesTable(saleobject) {
        // delete the sale if ref supplied
        if (typeof saleobject === 'object'){
            salestable[saleobject.ref] = saleobject;
        } else {
            delete salestable[saleobject];
        }
        localStorage.setItem("pos_csales", JSON.stringify(salestable));
    }
    // STORED ITEMS
    var itemtable;

    this.getItemsTable = function () {
        if (itemtable == null) {
            loadItemsTable();
        }
        return itemtable;
    };

    // fetches from server
    function fetchItemsTable(callback) {
        return POS.getJsonDataAsync("items/get", function(data){
            if (data) {
                itemtable = data;
                localStorage.setItem("pos_items", JSON.stringify(data));
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadItemsTable() {
        var data = localStorage.getItem("pos_items");
        if (data != null) {
            itemtable = JSON.parse(data);
            return true;
        }
        return false;
    }

    // adds/edits a record to the current table
    function updateItemsTable(itemobject) {
        // delete the sale if id/ref supplied
        if (typeof itemobject === 'object'){
            itemtable[itemobject.id] = itemobject;
        } else {
            delete itemtable[itemobject];
        }
        localStorage.setItem("pos_items", JSON.stringify(itemtable));
    }

    // Websocket updates & commands
    var socket = null;
    var socketon = false;
    var authretry = false;
    function startSocket(){
        if (socket==null){
            var proxy = POS.getConfigTable().general.feedserver_proxy;
            var port = POS.getConfigTable().general.feedserver_port;
            var socketPath = window.location.protocol+'//'+window.location.hostname+(proxy==false ? ':'+port : '');
            socket = io.connect(socketPath);
            socket.on('connection', onSocketConnect);
            socket.on('reconnect', onSocketConnect);
            socket.on('connect_error', socketError);
            socket.on('reconnect_error', socketError);
            socket.on('error', socketError);

            socket.on('updates', function (data) {
                switch (data.a){
                    case "item":
                        updateItemsTable(data.data);
                        break;

                    case "sale":
                        console.log("Sale data received:");
                        console.log(data.data);
                        POS.kitchen.processOrder(data.data);
                        break;

                    case "config":
                        updateConfig(data.type, data.data);
                        break;

                    case "regreq":
                        socket.emit('reg', {deviceid: configtable.deviceid, username: currentuser.username});
                        break;

                    case "msg":
                        POS.notifications.info(data.data, "Message");
                        break;

                    case "reset":
                        resetTerminalRequest();
                        break;

                    case "error":
                        if (!authretry && data.data.hasOwnProperty('code') && data.data.code=="auth"){
                            authretry = true;
                            stopSocket();
                            var result = POS.getJsonData('auth/websocket');
                            if (result===true){
                                startSocket();
                                return;
                            }
                        }

                        POS.notifications.error(data.data, "Error");
                        break;
                }
                var statustypes = ['item', 'sale', 'customer', 'config'];
                if (statustypes.indexOf(data.a) > -1) {
                    var statustxt = data.a=="sale" ? "Kitchen order received" : "Receiving "+ data.a + " update";
                    var statusmsg = data.a=="sale" ? "The Kitchen terminal has received an order from a POS register" : "The terminal has received updated "+ data.a + " data from the server";
                    setStatusBar(4, statustxt, statusmsg, 5000);
                }
                //alert(data.a);
            });
        } else {
            socket.connect();
        }
    }

    function onSocketConnect(){
        socketon = true;
        if (POS.isOnline() && defaultStatus.type != 1){
            setStatusBar(1, "POS.is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        }
    }

    function socketError(){
        if (POS.isOnline())
            setStatusBar(5, "Update Feed Offline", "The POS is running in online mode.\nThe feed server is disconnected and this terminal will not receive realtime updates.", 0);
        socketon = false;
        authretry = false;
    }

    this.sendAcknowledgement = function(deviceid, ref){
        if (socket) {
            var data = {include: null, data: {a: "kitchenack", data: ref}};
            if (deviceid) {
                data.include = {};
                data.include[deviceid] = true;
            }
            socket.emit('send', data);
        }
        console.log("Order acknowledgement sent!");
    };

    function stopSocket(){
        if (socket!=null){
            socketon = false;
            authretry = false;
            socket.disconnect();
            socket = null;
        }
    }

    window.onbeforeunload = function(){
        socketon = false;
    };

    // Reset terminal
    function resetTerminalRequest(){
        // Set timer
        var reset_timer = setTimeout("window.location.reload(true);", 10000);
        var reset_interval = setInterval('var r=$("#resettimeval"); r.text(r.text()-1);', 1000);
        $("#resetdialog").removeClass('hide').dialog({
            width : 'auto',
            maxWidth        : 370,
            modal        : true,
            closeOnEscape: false,
            autoOpen     : true,
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "370px");
            },
            buttons: [
                {
                    html: "<i class='icon-check bigger-110'></i>&nbsp; Ok",
                    "class": "btn btn-success btn-xs",
                    click: function () {
                        window.location.reload(true);
                    }
                },
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class": "btn btn-xs",
                    click: function () {
                        clearTimeout(reset_timer);
                        clearInterval(reset_interval);
                        $("#resetdialog").dialog('close');
                        $("#resettimeval").text(10);
                    }
                }
            ]
        });
    }
    // TODO: On socket error, start a timer to reconnect
    // Contructor code
    // load POSObjects
    this.print = new POSPrint(true); // kitchen mode
    this.trans = new POSTransactions();
    this.util = new POSUtil();
    this.kitchen = new POSKitchenMod();

    return this;
}
function POSKitchenMod(){
    var ordercontain = $("#ordercontainer");
    var orderhistcontain = $("#orderhistcontainer");
    // populate orders in the UI
    this.populateOrders = function(){
        var sales = POS.getSalesTable();
        for (var ref in sales){
            var sale = sales[ref];
            if (sale.hasOwnProperty('orderdata'))
                for (var o in sale.orderdata){
                    insertOrder(sale, o);
                }
        }
    };
    // refresh orders in the UI
    this.refreshOrders = function(reload){
        ordercontain.html('');
        orderhistcontain.html('');
        this.populateOrders();
    };
    // insert an order into the UI
    function insertOrder(saleobj, orderid){
        var order = saleobj.orderdata[orderid];
        var elem = $("#orderbox_template").clone().removeClass('hide').attr('id', 'order_box_'+saleobj.ref+'-'+order.id);
        elem.find('.orderbox_orderid').text(order.id);
        elem.find('.orderbox_saleref').text(saleobj.ref);
        elem.find('.orderbox_orderdt').text(POS.util.getDateFromTimestamp(order.processdt));
        var itemtbl = elem.find('.orderbox_items');
        for (var i in order.items){
            var item = saleobj.items[order.items[i]]; // the items object links the item id with it's index in the data
            var modStr = "";
            if (item.hasOwnProperty('mod')){
                for (var x=0; x<item.mod.items.length; x++){
                    var mod = item.mod.items[x];
                    modStr+= '<br/>'+(mod.hasOwnProperty('qty')?((mod.qty>0?'+ ':'')+mod.qty+' '):'')+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+POS.util.currencyFormat(mod.price)+')';
                }
            }
            itemtbl.append('<tr><td style="width:10%;"><strong>'+item.qty+'</strong></td><td><strong>'+item.name+'</strong>'+modStr+'<br/></td></tr>');
        }
        ordercontain.prepend(elem);
    }
    this.removeOrder = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).remove();
    };
    this.moveOrderToHistory = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).detach().prependTo(orderhistcontain);
    };
    this.moveOrderToCurrent = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).detach().prependTo(ordercontain);
    };
    // process an incoming order from the websocket
    this.processOrder = function(data){
        var olddata;
        var modcount = 0;
        var deviceid = null;
        var ref;
        if (typeof data === "object") {
            ref = data.ref;
            // check for old data, if none available process as new orders
            if (POS.getSalesTable().hasOwnProperty(ref)) {
                olddata = POS.getSalesTable()[ref];
                if (data.hasOwnProperty('orderdata')){
                    for (var i in data.orderdata){
                        if (olddata.orderdata.hasOwnProperty(i)){
                            // the moddt param exists the order may have been modified, check further
                            if (data.orderdata[i].hasOwnProperty('moddt')){
                                // if the moddt flag doesn't exist on the old order moddt or is smaller than the new value
                                if (!olddata.orderdata[i].hasOwnProperty('moddt') || data.orderdata[i].moddt>olddata.orderdata[i].moddt) {
                                    processUpdatedOrder(data, i);
                                    modcount++;
                                }
                            }
                        } else {
                            processNewOrder(data, i);
                            modcount++;
                        }
                    }
                } else {
                    // no order data exists in the new data, remove all
                    if (olddata.hasOwnProperty('orderdata'))
                        for (var r in olddata.orderdata){
                            processDeletedOrder(olddata, r);
                            modcount++;
                        }
                }
            } else {
                if (data.hasOwnProperty('orderdata'))
                    for (var o in data.orderdata) {
                        processNewOrder(data, o);
                        modcount++;
                    }
            }
            deviceid = data.devid;
        } else {
            ref = data;
            // process removed orders if they exists in the system
            if (POS.getSalesTable().hasOwnProperty(ref)){
                olddata = POS.getSalesTable()[ref];
                if (olddata.hasOwnProperty('orderdata'))
                    for (var d in olddata.orderdata){
                        processDeletedOrder(olddata, d);
                        modcount++;
                    }
            }
        }
        // save new sales data
        POS.updateSalesTable(data);

        if (modcount)
            POS.sendAcknowledgement(deviceid, ref);
    };

    this.onPrintButtonClick = function(element){
        var ref = $(element).parent().find('.orderbox_saleref').text();
        var ordernum = $(element).parent().find('.orderbox_orderid').text();
        var data = POS.getSalesTable()[ref];
        if (data)
            POS.print.printOrderTicket("orders", data, ordernum);

        console.log(data);
    };

    function processNewOrder(saleobj, orderid){
        console.log("Processed new order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        insertOrder(saleobj, orderid);
        playChime();
        switch (POS.getLocalConfig().printing.recask) {
            case "ask":
                POS.util.confirm("Print order ticket?", function() {
                    POS.print.printOrderTicket("orders", saleobj, orderid, null);
                });
                break;
            case "print":
                POS.print.printOrderTicket("orders", saleobj, orderid, null);
        }
    }

    function processUpdatedOrder(saleobj, orderid){
        console.log("Processed updated order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        // remove old record that may be present
        POS.kitchen.removeOrder(saleobj.ref, orderid);
        insertOrder(saleobj, order.id);
        playChime();
        switch (POS.getLocalConfig().printing.recask) {
            case "ask":
                POS.util.confirm("Print order ticket?", function() {
                    POS.print.printOrderTicket("orders", saleobj, orderid, "ORDER UPDATED");
                });
                break;
            case "print":
                POS.print.printOrderTicket("orders", saleobj, orderid, "ORDER UPDATED");
        }
    }

    function processDeletedOrder(saleobj, orderid){
        console.log("Processed deleted order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        // remove old record that may be present
        POS.kitchen.moveOrderToHistory(saleobj.ref, orderid);
        playChime();
        switch (POS.getLocalConfig().printing.recask) {
            case "ask":
                POS.util.confirm("Print order ticket?", function() {
                    POS.print.printOrderTicket("orders", saleobj, orderid, "ORDER CANCELLED");
                });
                break;
            case "print":
                POS.print.printOrderTicket("orders", saleobj, orderid, "ORDER CANCELLED");
        }
    }

    var audio = new Audio('/assets/sounds/bell_modern.mp3');
    function playChime(){
        audio.play();
    }

    return this;
}
var POS;
$(function () {
    // initiate core object
    POS= new POSKitchen();
    // initiate startup routine
    POS.initApp();

    $("#wrapper").tabs();

    $("#transactiondiv").dialog({
        width   : 'auto',
        maxWidth: 600,
        modal   : true,
        autoOpen: false,
        title_html: true,
        open    : function (event, ui) {
            var tdiv = $("#transdivcontain");
            tdiv.css("width", tdiv.width()+"px");
        },
        close   : function (event, ui) {
            $("#transdivcontain").css("width", "100%");
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "600px");
        }
    });

    $("#setupdiv").dialog({
        width : 'auto',
        maxWidth        : 370,
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        open         : function (event, ui) {
            $(".ui-dialog-titlebar-close").hide();
        },
        close        : function (event, ui) {
            $(".ui-dialog-titlebar-close").show();
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
        }
    });
    // keyboard navigation
    $(document.documentElement).keydown(function (event) {
        // handle cursor keys
        var e = jQuery.Event("keydown");
        var x;
        if (event.keyCode == 37) {
            $(".keypad-popup").hide();
            x = $('input:not(:disabled), textarea:not(:disabled)');
            x.eq(x.index(document.activeElement) - 1).focus().trigger('click');

        } else if (event.keyCode == 39) {
            $(".keypad-popup").hide();
            x = $('input:not(:disabled), textarea:not(:disabled)');
            x.eq(x.index(document.activeElement) + 1).focus().trigger('click');
        }
    });

    // dev/demo quick login
    if (document.location.host=="demo.wallacepos.com" || document.location.host=="alpha.wallacepos.com"){
        $("#logindiv").append('<button class="btn btn-primary btn-sm" onclick="$(\'#username\').val(\'admin\');$(\'#password\').val(\'admin\'); POS.userLogin();">Demo Login</button>');
    }
});
