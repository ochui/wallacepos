/**
 *
 * core.js is the main object that provides base functionality to the WallacePOS terminal.
 * It loads other needed modules and provides authentication, storage and data functions.
 *
 */

function POS() {

    var initialsetup = false;
    this.initApp = function () {
        // set cache default to true
        $.ajaxSetup({
            cache: true
        });
        // check browser features, returns false if the browser does not support required features
        if (!checkAppCompatibility())
            return false;
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

    function checkAppCompatibility(){
        // Check local storage: required
        if (!('localStorage' in window && window.localStorage !== null)) {
            POS.notifications.error("Your browser does not support localStorage required to run the POS terminal.", "Browser Compatibility");
            return false;
        }
        // Check service worker support for offline functionality
        if (!('serviceWorker' in navigator)){
            POS.notifications.warning("Your browser does not support Service Workers and may not function optimally offline.", "Browser Compatibility");
        }
        return true;
    }

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
        // deploy scan apps
        deployDefaultScanApp();
        // init eftpos module if available
        if (POS.hasOwnProperty('eftpos'))
            POS.eftpos.initiate();
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
    function deployDefaultScanApp(){
        $.getScript('assets/js/jquery.scannerdetection.js').done(function(){
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
    function showLogin(message, lock) {
        $("#loadingdiv").hide();
        $("#logindiv").show();
        $('#loginbutton').removeAttr('disabled', 'disabled');
        setLoadingBar(0, "");
        $('body').css('overflow', 'hidden');

        if (message){
            $("#login-banner-txt").text(message);
            $("#login-banner").show();
        } else {
            $("#login-banner").hide();
        }
        var modal = $('#loginmodal');
        if (lock){
            // session is being locked. set opacity
            modal.css('background-color', "rgba(0,0,0,0.75)");
        } else {
            modal.css('background-color', "#000");
        }
        modal.show();
    }

    function hideLogin(){
        $('#loginmodal').hide();
        $('#loadingdiv').hide();
        $('#logindiv').show();
        $('body').css('overflow', 'auto');
    }

    var session_locked = false;
    this.lockSession = function(){
        $("#username").val(currentuser.username);
        showLogin("The session is locked, login to continue.", true);
        session_locked = true;
    };

    this.userLogin = function () {
        POS.util.showLoader();
        var loginbtn = $('#loginbutton');
        // disable login button
        $(loginbtn).prop('disabled', true);
        $(loginbtn).val('Proccessing');
        // get form values
        var userfield = $("#username");
        var passfield = $("#password");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = POS.util.SHA256(password);
        // authenticate
        authenticate(username, password, function(result){
            if (result === true) {
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
                    if (session_locked){
                        stopSocket();
                        startSocket();
                        session_locked = false;
                        hideLogin();
                    } else {
                        initData(true);
                    }
                }
            }
            passfield.val('');
            $(loginbtn).val('Login');
            $(loginbtn).prop('disabled', false);
            POS.util.hideLoader();
        });
    };

    this.logout = function () {
        var self = this;
        POS.util.confirm("Are you sure you want to logout?", function() {
            var sales = POS.sales.getOfflineSalesNum();
            if (sales > 0) {
                POS.util.confirm("You have offline sales that have not been uploaded to the server.\nWould you like to back them up?", function() {
                    self.backupOfflineSales();
                    POS.util.showLoader();
                    logout();
                    POS.util.hideLoader();
                }, function() {
                    POS.util.showLoader();
                    logout();
                    POS.util.hideLoader();
                });
            } else {
                POS.util.showLoader();
                logout();
                POS.util.hideLoader();
            }
        });
    };

    function logout(){
        POS.getJsonDataAsync("logout", function(result){
            if (result !== false){
                stopSocket();
                showLogin();
            }
        });
    }

    function authenticate(user, hashpass, callback) {
        // auth against server if online, offline table if not.
        if (online == true) {
            // send request to server
            POS.sendJsonDataAsync("auth", JSON.stringify({username: user, password: hashpass, getsessiontokens:true}), function(response){
                if (response !== false) {
                    // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
                    setCurrentUser(response);
                    updateAuthTable(response);

                    $.ajaxSetup({
                        beforeSend: function(xhr, settings) {
                            xhr.setRequestHeader("anti-csrf-token", (currentuser ? currentuser.csrf_token : ""));
                        }
                    });
                }
                if (callback)
                    callback(response!==false);
            });
        } else {
            if (callback)
                callback(offlineAuth(user, hashpass));
        }
    }

    function sessionRenew(){
        // send request to server
        var response = POS.sendJsonData("authrenew", JSON.stringify({username:currentuser.username, auth_hash:currentuser.auth_hash}));
        if (response !== false) {
            // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
            setCurrentUser(response);
            updateAuthTable(response);

            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    xhr.setRequestHeader("anti-csrf-token", (currentuser ? currentuser.csrf_token : ""));
                }
            });
            return true;
        } else {
            return false;
        }
    }

    function offlineAuth(username, hashpass) {
        if (localStorage.getItem("wpos_auth") !== null) {
            var jsonauth = $.parseJSON(localStorage.getItem("wpos_auth"));
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
        var devname = $("#newposdevice").val();
        var locid = $("#poslocations option:selected").val();
        var locname = $("#newposlocation").val();
        // check input
        if ((devid == null && devname == null) || (locid == null && locname == null)) {
            POS.notifications.warning("Please select a item from the dropdowns or specify a new name.", "Device Setup");
        } else {
            // call the setup function
            deviceSetup(devid, devname, locid, locname, function(result){
                if (result) {
                    currentuser = null;
                    initialsetup = false;
                    $("#setupdiv").dialog("close");
                    showLogin();
                } else {
                    POS.notifications.error("There was a problem setting up the device, please try again.", "Device Setup Failed");
                }
            });
        }
        POS.util.hideLoader();
    };

    function initSetup() {
        $("#loadingbartxt").text("Initializing setup");
        POS.util.showLoader();
        // get pos locations and devices and populate select lists using parallel requests
        var devicesPromise = new Promise(function(resolve, reject) {
            POS.getJsonDataAsync("devices/get", function(data) {
                if (data === false) {
                    reject(new Error("Failed to fetch devices"));
                } else {
                    resolve(data);
                }
            });
        });
        
        var locationsPromise = new Promise(function(resolve, reject) {
            POS.getJsonDataAsync("locations/get", function(data) {
                if (data === false) {
                    reject(new Error("Failed to fetch locations"));
                } else {
                    resolve(data);
                }
            });
        });
        
        Promise.all([devicesPromise, locationsPromise]).then(function(results) {
            var devices = results[0];
            var locations = results[1];

            for (var i in devices) {
                if (devices[i].disabled == 0 && devices[i].type!="kitchen_terminal"){ // do not add disabled devs
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
            $("#setupdiv").parent().css('z-index', "3200 !important");
            $("#setupdiv").dialog("open");
        }).catch(function(error) {
            console.error("Error loading setup data:", error);
            POS.notifications.error("Failed to load setup data: " + error.message, "Setup Data Error");
            POS.util.hideLoader();
        });
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
                // get customers
                setLoadingBar(60, "Getting customer accounts...");
                setStatusBar(4, "Updating customers...", statusmsg, 0);
                fetchCustTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    loadOnlineData(4, loginloader);
                });
                break;

            case 4:
                // get all sales (Will limit to the weeks sales in future)
                setLoadingBar(80, "Getting recent sales...");
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
                    var offline_num = POS.sales.getOfflineSalesNum();
                    if (offline_num>0){
                        $("#backup_btn").show();
                        // check for offline sales on login
                        setTimeout('if (POS.sales.uploadOfflineRecords()){ POS.setStatusBar(1, "POS.is online"); }', 2000);
                    } else {
                        $("#backup_btn").hide();
                    }
                });
                break;
        }
    }

    function initOfflineData(loginloader){
        // check records and initiate java objects
        setLoadingBar(50, "Loading offline data...");
        loadConfigTable();
        loadItemsTable();
        loadCustTable();
        loadSalesTable();
        POS.notifications.info("Your internet connection is not active and POS has started in offline mode.\nSome features are not available in offline mode but you can always make sales and alter transactions that are locally available. \nWhen a connection becomes available POS will process your transactions on the server.", "Offline Mode");
        initDataSuccess(loginloader);
    }

    function initDataSuccess(loginloader){
        if (loginloader){
            setLoadingBar(100, "Massaging the data...");
            $("title").text("WallacePOS - Your POS in the cloud");
            POS.initPlugins();
            populateDeviceInfo();
            setTimeout(hideLogin, 500);
        }
    }

    this.removeDeviceRegistration = function(){
        if (isUserAdmin()){
            POS.util.confirm("Are you sure you want to delete this devices registration?\nYou will be logged out and this device will need to be re registered.", function() {
                // show loader
                POS.util.showLoader();
                var regid = POS.getConfigTable().registration.id;
                POS.sendJsonDataAsync("devices/registrations/delete", '{"id":'+regid+'}', function(result){
                    if (result){
                        removeDeviceUUID();
                        logout();
                    }
                    // hide loader
                    POS.util.hideLoader();
                });
            });
            return;
        }
        POS.notifications.warning("Please login as an administrator to use this feature", "Admin Access Required");
    };

    this.resetLocalConfig = function(){
        if (isUserAdmin()){
            POS.util.confirm("Are you sure you want to restore local settings to their defaults?\n", function() {
                localStorage.removeItem("wpos_lconfig");
                POS.print.loadPrintSettings();
                setKeypad(true);
            });
            return;
        }
        POS.notifications.warning("Please login as an administrator to use this feature", "Admin Access Required");
    };

    this.clearLocalData = function(){
        var self = this;
        if (isUserAdmin()){
            POS.util.confirm("Are you sure you want to clear all local data?\nThis removes all locally stored data except device registration key.\nOffline Sales will be deleted.", function() {
                localStorage.removeItem("wpos_auth");
                localStorage.removeItem("wpos_config");
                localStorage.removeItem("wpos_csales");
                localStorage.removeItem("wpos_osales");
                localStorage.removeItem("wpos_items");
                localStorage.removeItem("wpos_customers");
                localStorage.removeItem("wpos_lconfig");
                // Also clear Service Worker cache
                self.clearServiceWorkerCache();
            });
            return;
        }
        POS.notifications.warning("Please login as an administrator to use this feature", "Admin Access Required");
    };
    
    this.clearServiceWorkerCache = function(){
        if (swManager) {
            swManager.clearCache().then(function() {
                console.log("Service Worker cache cleared");
            }).catch(function(error) {
                console.error("Failed to clear Service Worker cache:", error);
            });
        }
    };
    
    this.getServiceWorkerManager = function() {
        return swManager;
    };

    this.refreshRemoteData = function(){
        POS.util.confirm("Are you sure you want to reload data from the server?", function() {
            loadOnlineData(1, false);
        });
    };

    this.backupOfflineSales = function(){
        var offline_sales = localStorage.getItem('wpos_osales');

        var a = document.createElement('a');
        var blob = new Blob([offline_sales], {'type':"application/octet-stream"});
        window.URL = window.URL || window.webkitURL;
        a.href = window.URL.createObjectURL(blob);
        var date = new Date();
        var day = date.getDate();
        if (day.length==1) day = '0' + day;
        a.download = "wpos_offline_sales_"+date.getFullYear()+"-"+(date.getMonth()+1)+"-"+day+"_"+date.getHours()+"-"+date.getMinutes()+".json";
        document.body.appendChild(a);
        a.click();
        a.remove();
    };

    function populateDeviceInfo(){
        var config = POS.getConfigTable();
        $(".device_id").text(config.deviceid);
        $(".device_name").text(config.devicename);
        $(".location_id").text(config.locationid);
        $(".location_name").text(config.locationname);
        $(".devicereg_id").text(config.registration.id);
        $(".devicereg_uuid").text(config.registration.uuid);
        $(".devicereg_dt").text(config.registration.dt);
        $(".biz_name").text(config.general.bizname);
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

        var staticon = $("#wposstaticon");
        var statimg = $("#wposstaticon i");
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
        $("#wposstattxt").text(text);
        $("#wposstat").attr("title", tooltip);

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
            if (localStorage.getItem("wpos_auth") == null) {
                return false;
            }
            // check for machine settings etc.
            if (localStorage.getItem("wpos_config") == null) {
                return false;
            }
            return localStorage.getItem("wpos_items") != null;
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
            if (POS.sales.getOfflineSalesNum()>0)
                $(".backup_btn").show();
            return true;
        } else {
            // display error notice
            POS.notifications.error("There was an error connecting to the webserver & files needed to run offline are not present :( \nPlease check your connection and try again.", "Connection Error");
            showLogin();
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
        if (POS.sales.uploadOfflineRecords()){
            // set js and ui indicators
            online = true;
            // load fresh data
            initData(false);
            // initData();
            setStatusBar(1, "POS.is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        }
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
                    POS.notifications.warning(json.warning, "Warning");
                }
                return json.data;
            } else {
                if (errCode == "auth") {
                    if (sessionRenew()) {
                        // try again after authenticating
                        return POS.sendJsonData(action, data);
                    } else {
                        return false;
                    }
                } else {
                    POS.notifications.error(err, "Server Error");
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
            POS.notifications.error("There was an error sending data, switching to offline mode.\nException: "+ex.message, "Connection Error");
            return false;
        }
    };

    this.sendJsonDataAsync = function (action, data, callback) {
        // send request to server
        try {
            $.ajax({
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
                        callback(json.data);
                    } else {
                        if (errCode == "auth") {
                            if (sessionRenew()) {
                                // try again after authenticating
                                var result = POS.sendJsonData(action, data);
                                callback(result);
                            } else {
                                callback(false);
                            }
                        } else {
                            POS.notifications.error(err, "Server Error");
                            callback(false);
                        }
                    }
                },
                error   : function(jqXHR, status, error){
                    POS.notifications.error(error, "Connection Error");
                    callback(false);
                }
            });
        } catch (ex) {
            POS.notifications.error("Exception: "+ex.message, "Exception Error");
            callback(false);
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
                            POS.notifications.warning(json.warning, "Warning");
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
                        POS.notifications.error(err, "Server Error");
                        if (callback)
                            callback(false);
                    }
                },
                error   : function(jqXHR, status, error){
                    POS.notifications.error(error, "Connection Error");
                    if (callback)
                        callback(false);
                }
            });
        } catch (ex) {
            POS.notifications.error("Exception: "+ex.message, "Exception Error");
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
        if (localStorage.getItem("wpos_auth") !== null) {
            jsonauth = $.parseJSON(localStorage.getItem("wpos_auth"));
            jsonauth[jsonobj.username.toString()] = jsonobj;
        } else {
            jsonauth = { };
            jsonauth[jsonobj.username.toString()] = jsonobj;
        }
        localStorage.setItem("wpos_auth", JSON.stringify(jsonauth));
    }

    // DEVICE SETTINGS AND INFO
    var configtable;

    this.getConfigTable = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable;
    };

    this.refreshConfigTable = function () {
        fetchConfigTable();
    };

    this.isOrderTerminal = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable.hasOwnProperty('deviceconfig') && configtable.deviceconfig.type == "order_register";
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
                //console.log(data);
                if (data=="removed" || data=="disabled"){ // return false if dev is disabled
                    if (data=="removed")
                        removeDeviceUUID();
                    if (callback){
                        callback(false);
                        return;
                    }
                } else {
                    configtable = data;
                    localStorage.setItem("wpos_config", JSON.stringify(data));
                    setAppCustomization();
                }
            }
            if (callback)
                callback(data);
        });
    }

    function loadConfigTable() {
        var data = localStorage.getItem("wpos_config");
        if (data != null) {
            configtable = JSON.parse(data);
            return true;
        }
        configtable = {};
        return false;
    }

    function updateConfig(key, data){
        console.log("Processing config ("+key+") update");
        //console.log(data);

        if (key=='item_categories')
            return updateCategory(data);

        if (key=="deviceconfig"){
            if (data.id==configtable.deviceid) {
                if (data.hasOwnProperty('a') && (data.a == "removed" || data.a == "disabled")) {
                    // device removed
                    if (data.a == "removed")
                        removeDeviceUUID();
                    logout();
                    POS.notifications.error("This device has been " + data.a + " by the administrator,\ncontact your device administrator for help.", "Device Status");
                    return;
                }
                // update root level config values
                configtable.devicename = data.name;
                configtable.locationname = data.locationname;
                populateDeviceInfo();
            } else {
                if (data.data.hasOwnProperty('a')){
                    if (data.data.a=="removed")
                        delete configtable.devices[data.id];
                } else {
                    configtable.devices[data.id] = data;
                    configtable.locations[data.locationid] = {name: data.locationname};
                }
                return;
            }
        }

        configtable[key] = data; // write to current data
        localStorage.setItem("wpos_config", JSON.stringify(configtable));
        setAppCustomization();
    }

    function updateCategory(value){
        if (typeof value === 'object'){
            configtable.item_categories[value.id] = value;
        } else {
            if (typeof value === 'string') {
                var ids = value.split(",");
                for (var i=0; i<ids.length; i++){
                    delete configtable.item_categories[ids[i]];
                }
            } else {
                delete configtable.item_categories[value];
            }
        }
        POS.items.generateItemGridCategories();
        localStorage.setItem("wpos_config", JSON.stringify(configtable));
    }

    function setAppCustomization(){
        // initialize terminal mode (kitchen order views)
        if (configtable.hasOwnProperty('deviceconfig') && configtable.deviceconfig.type == "order_register") {
            $(".order_terminal_options").show();
            POS.sales.resetSalesForm();
        } else {
            $(".order_terminal_options").hide();
            $("#itemtable .order_row").remove(); // clears order row already in html
        }
        // setup checkout watermark
        var url = POS.getConfigTable().general.bizlogo;
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
        var lconfig = localStorage.getItem("wpos_lconfig");
        if (lconfig==null || lconfig==undefined){
            // put default config here.
            var defcon = {
                keypad: true,
                eftpos:{
                    enabled: false,
                    receipts:true,
                    provider: 'tyro',
                    merchrec:'ask',
                    custrec:'ask'
                }
            };
            updateLocalConfig(defcon);
            return defcon;
        }
        return JSON.parse(lconfig);
    }

    function setLocalConfigValue(key, value){
        var data = localStorage.getItem("wpos_lconfig");
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
        localStorage.setItem("wpos_lconfig", JSON.stringify(configobj));
    }

    /**
     * This function sets up the
     * @param {int} devid ; if not null, the newname var is ignored and the new uuid is merged with the device specified by devid.
     * @param {int} newdevname ; A new device name, if specified the
     * @param {int} locid ; if not null, the newlocname field is ignored and blah blah blah....
     * @param {int} newlocname ; if not null, the newlocname field is ignored and blah blah blah....
     * @returns {boolean}
     */
    function deviceSetup(devid, newdevname, locid, newlocname, callback) {
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
        POS.sendJsonDataAsync("devices/setup", JSON.stringify(data), function(configobj){
            if (configobj !== false) {
                localStorage.setItem("wpos_config", JSON.stringify(configobj));
                configtable = configobj;
            } else {
                removeDeviceUUID(true);
            }
            if (callback)
                callback(configobj !== false);
        });
    }

    /**
     * Returns the current devices UUID
     * @returns {String, Null} String if set, null if not
     */
    function getDeviceUUID() {
        // return the devices uuid; if null, the device has not been setup or local storage was cleared
        return localStorage.getItem("wpos_devuuid");
    }

    function removeDeviceUUID() {
        initialsetup = true;
        localStorage.removeItem("wpos_devuuid");
    }

    /**
     * Creates or clears device UUID and updates in local storage
     * @returns String uuid
     */
    function setDeviceUUID() {
        // generate a SHA UUID using datestamp and rand for entropy and return the result
        var date = new Date().getTime();
        var uuid = POS.util.SHA256((date * Math.random()).toString());
        localStorage.setItem("wpos_devuuid", uuid);
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

    this.updateSalesTable = function (ref, saleobj) {
        salestable[ref] = saleobj;
    };

    this.removeFromSalesTable = function (ref){
        delete salestable[ref];
    };

    function fetchSalesTable(callback) {
        return POS.sendJsonDataAsync("sales/get", JSON.stringify({deviceid: configtable.deviceid}), function(data){
            if (data) {
                salestable = data;
                localStorage.setItem("wpos_csales", JSON.stringify(data));
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadSalesTable() {
        var data = localStorage.getItem("wpos_csales");
        if (data !== null) {
            salestable = JSON.parse(data);
            return true;
        }
        return false;
    }

    // adds/updates a record in the current table
    function updateSalesTable(saleobject) {
        // delete the sale if ref supplied
        if (typeof saleobject === 'object'){
            salestable[saleobject.ref] = saleobject;
        } else {
            delete salestable[saleobject];
        }
        localStorage.setItem("wpos_csales", JSON.stringify(salestable));
    }

    // STORED ITEMS
    var itemtable;
    var stockindex;
    var categoryindex;

    this.getItemsTable = function () {
        if (itemtable == null) {
            loadItemsTable();
        }
        return itemtable;
    };

    this.getStockIndex = function () {
        if (stockindex === undefined || stockindex === null) {
            if (itemtable == null) {
                loadItemsTable(); // also generate stock index
            } else {
                generateItemIndex();
            }
        }
        return stockindex;
    };

    this.getCategoryIndex = function () {
        if (categoryindex === undefined || categoryindex === null) {
            if (itemtable == null) {
                loadItemsTable(); // also generate stock index
            } else {
                generateItemIndex();
            }
        }
        return categoryindex;
    };

    // fetches from server
    function fetchItemsTable(callback) {
        return POS.getJsonDataAsync("items/get", function(data){
            if (data) {
                itemtable = data;
                localStorage.setItem("wpos_items", JSON.stringify(data));
                generateItemIndex();
                POS.items.generateItemGridCategories();
            }
            if (callback)
                callback(data);
        });
    }

    function generateItemIndex() {
        stockindex = {};
        categoryindex = {};
        for (var key in itemtable) {
            stockindex[itemtable[key].code] = key;

            var categoryid = itemtable[key].hasOwnProperty('categoryid')?itemtable[key].categoryid:0;
            if (categoryindex.hasOwnProperty(categoryid)){
                categoryindex[categoryid].push(key);
            } else {
                categoryindex[categoryid] = [key];
            }
        }
    }

    // loads from local storage
    function loadItemsTable() {
        var data = localStorage.getItem("wpos_items");
        if (data != null) {
            itemtable = JSON.parse(data);
            // generate the stock index as well.
            generateItemIndex();
            POS.items.generateItemGridCategories();
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
            if (typeof itemobject === 'string') {
                var ids = itemobject.split(",");
                for (var i=0; i<ids.length; i++){
                    delete itemtable[ids[i]];
                }
            } else {
                delete itemtable[itemobject];
            }
        }
        localStorage.setItem("wpos_items", JSON.stringify(itemtable));
        generateItemIndex();
        POS.items.generateItemGridCategories();
    }

    // CUSTOMERS
    var custtable;
    var custindex = [];
    this.getCustTable = function () {
        if (custtable == null) {
            loadCustTable();
        }
        return custtable;
    };
    this.getCustId = function(email){
        if (custindex.hasOwnProperty(email)){
            return custindex[email];
        }
        return false;
    };
    // fetches from server
    function fetchCustTable(callback) {
        return POS.getJsonDataAsync("customers/get", function(data){
            if (data) {
                custtable = data;
                localStorage.setItem("wpos_customers", JSON.stringify(data));
                generateCustomerIndex();
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadCustTable() {
        var data = localStorage.getItem("wpos_customers");
        if (data != null) {
            custtable = JSON.parse(data);
            generateCustomerIndex();
            return true;
        }
        return false;
    }

    function generateCustomerIndex(){
        custindex = [];
        for (var i in custtable){
            custindex[custtable[i].email] = custtable[i].id;
        }
    }

    this.updateCustTable = function(id, data){
        updateCustTable(id, data);
    };

    // adds a record to the current table
    function updateCustTable(data) {
        if (typeof data === 'object'){
            custtable[data.id] = data;
            // add/update index
            custindex[data.email] = data.id;
        } else {
            delete custtable[data];
            for (var i in custindex){
                if (custindex.hasOwnProperty(i) && custindex[i]==data) delete custindex[i];
            }
        }
        // save to local store
        localStorage.setItem("wpos_customers", JSON.stringify(custtable));
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
            socket = io.connect('http://127.0.0.1:3000');
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
                        updateSalesTable(data.data);
                        break;

                    case "customer":
                        updateCustTable(data.data);
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

                    case "kitchenack":
                        POS.orders.kitchenTerminalAcknowledge(data.data);
                        break;

                    case "error":
                        if (!authretry && data.data.hasOwnProperty('code') && data.data.code=="auth"){
                            authretry = true;
                            stopSocket();
                            POS.getJsonDataAsync('auth/websocket', function(result){
                                if (result===true)
                                    startSocket();
                            });
                            return;
                        }

                        POS.notifications.error(data.data, "Socket Error");
                        break;
                }
                var statustypes = ['item', 'sale', 'customer', 'config', 'kitchenack'];
                if (statustypes.indexOf(data.a) > -1) {
                    var statustxt = data.a=="kitchenack" ? "Kitchen Order Acknowledged" : "Receiving "+ data.a + " update";
                    var statusmsg = data.a=="kitchenack" ? "The POS has received an acknowledgement that the last order was received in the kitchen" : "The POS has received updated "+ data.a + " data from the server";
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

    // Contructor code
    // load POSObjects
    this.items = new WPOSItems();
    this.sales = new WPOSSales();
    this.trans = new WPOSTransactions();
    this.reports = new WPOSReports();
    this.print = new WPOSPrint();
    this.orders = new WPOSOrders();
    this.util = new WPOSUtil();
    this.notifications = new WPOSNotifications();

    if (typeof(WPOSEftpos) === 'function')
        this.eftpos = new WPOSEftpos();
}
// UI widget functions & initialization
var toggleItemBox;
$(function () {
    // initiate core object
    POS= new POS();
    // initiate startup routine
    POS.initApp();

    $("#wrapper").tabs();

    $("#paymentsdiv").dialog({
        maxWidth : 380,
        width : 'auto',
        modal   : true,
        autoOpen: false,
        open    : function (event, ui) {
        },
        close   : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
            $(this).css("minWidth", "325px");
        }
    });

    $("#transactiondiv").dialog({
        width   : 'auto',
        maxWidth: 900,
        modal   : true,
        autoOpen: false,
        title_html: true,
        open    : function (event, ui) {
        },
        close   : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "900px");
        }
    });

    $("#setupdiv").dialog({
        width : 'auto',
        maxWidth        : 370,
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        dialogClass: 'setup-dialog',
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

    $("#formdiv").dialog({
        width : 'auto',
        maxWidth     : 370,
        stack        : true,
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        open         : function (event, ui) {
        },
        close        : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
        }
    });

    $("#voiddiv").dialog({
        width : 'auto',
        maxWidth        : 370,
        appendTo     : "#transactiondiv",
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        open         : function (event, ui) {
        },
        close        : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
        }
    });

    $("#custdiv").dialog({
        width : 'auto',
        maxWidth        : 370,
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        open         : function (event, ui) {
        },
        close        : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
        }
    });
    // item box
    var ibox = $("#ibox");
    var iboxhandle = $("#iboxhandle");
    var iboxopen = false;
    toggleItemBox = function(show){
        if (show){
            iboxopen = true;
            ibox.animate({width:"100%"}, 500);
        } else {
            iboxopen = false;
            ibox.animate({width:"0"}, 500);
        }
    };
    var isDragging = false;
    iboxhandle.on('mousedown', function() {
            $(window).on('mousemove touchmove', function() {
                isDragging = true;
                $(window).unbind("mousemove touchmove");
                $(window).on('mousemove touchmove', function(e) {
                    // get position
                    var parent = $("#iboxhandle").parent().parent();
                    //alert(parent);
                    if (parent.offset()!=undefined){
                        var parentOffset = parent.offset().left + parent.width();
                        var thisOffset = e.pageX;
                        // get width from the right side of the div.
                        var relX = (parentOffset - thisOffset);
                        // work out optimal size
                        if (relX>((parent.width()/2)+2)){
                            ibox.css('width', ibox.css('max-width')); // set max size max size
                        } else {
                            ibox.css('width', relX+"px");
                        }
                        //console.log(parent.offset().left);
                        // set box open indicator
                        iboxopen = (relX>0);
                    } else {
                        ibox.css('width', "0px");//closing too fast hide.
                    }
                });

            });
            $(window).on('mouseup touchcancel', function(){
                stopDragging();
            })
    });
    function stopDragging(){
        var wasDragging = isDragging;
        isDragging = false;
        $(window).unbind("mousemove");
        $(window).unbind("mouseup");
        $(window).unbind("touchmove");
        $(window).unbind("touchcancel");
        if (!wasDragging) { //was clicking
            if (iboxopen){
                toggleItemBox(false);
            } else {
                toggleItemBox(true);
            }
        }
    }
    // close on click outside item box
    $('html').on("click", function() {
        if (iboxopen) toggleItemBox(false); // hide if currently visible
    });
    ibox.on("click", function(event){
        event.stopPropagation();
    });
    // select text of number fields on click
    $(".numpad").on("click", function () {
        $(this).focus().select();
    });
    // keyboard field navigation & shortcuts
    $(document.documentElement).keydown(function (event) {
        // handle cursor keys
        var x;
        var keypad = $(".keypad-popup");
        var paymentsopen = $("#paymentsdiv").is(":visible");
        switch (event.which){
            /*case 37: // left arrow
                keypad.hide();
                x = $('input:not(:disabled), textarea:not(:disabled)');
                x.eq(x.index(document.activeElement) - 1).trigger('click').focus();
                break;
            case 39: // right arrow
                keypad.hide();
                x = $('input:not(:disabled), textarea:not(:disabled)');
                x.eq(x.index(document.activeElement) + 1).trigger('click').focus();
                break;*/
            case 45: // insert
                if ($(":focus").attr('id')=="codeinput"){
                    POS.items.addManualItemRow();
                } else {
                    $("#codeinput").trigger('click').focus();
                }
                break;
            case 46: // delete
                POS.sales.userAbortSale();
                break;
            case 36: // home

                break;
            case 35: // end
                if (paymentsopen) {
                    POS.sales.processSale();
                } else {
                    POS.sales.showPaymentDialog();
                }
                break;
        }
        if (paymentsopen) {
            switch (event.which){
                case 90:
                    POS.sales.addPayment('cash');
                    break;
                case 88:
                    POS.sales.addPayment('credit');
                    break;
                case 67:
                    POS.sales.addPayment('eftpos');
                    break;
                case 86:
                    POS.sales.addPayment('cheque');
                    break;
            }
        }
    });

    // dev/demo quick login
    if (document.location.host=="demo.wallacepos.com" || document.location.host=="alpha.wallacepos.com"){
        var login = $("#logindiv");
        login.append('<button class="btn btn-primary btn-sm" onclick="$(\'#username\').val(\'admin\');$(\'#password\').val(\'admin\'); POS.userLogin();">Demo Login</button>');
        if (document.location.host=="alpha.wallacepos.com")
            login.append('<button class="btn btn-primary btn-sm" onclick="$(\'#loginmodal\').hide();">Hide Login</button>');
    }

    // window size
    if (POS.getLocalConfig().hasOwnProperty("window_size"))
        $("#wrapper").css("max-width", POS.getLocalConfig()["window_size"]);

    // set padding for item list
    setItemListPadding();
    setStatusbarPadding();
    window.onresize = function(){
        setItemListPadding();
        setStatusbarPadding();
    };
});

function setStatusbarPadding(){
    var height = $("#statusbar").height();
    $("#totals").css("margin-bottom", (20+height)+"px");
}

function setItemListPadding(){
    var height = $("#totals").height();
    $("#items").css("margin-bottom", (80+height)+"px");
}

function expandWindow(){
    var wrapper = $("#wrapper");
    var maxWidth = wrapper.css("max-width");
    switch (maxWidth){
        case "960px":
            wrapper.css("max-width", "1152px");
            POS.setLocalConfigValue("window_size", "1152px");
            break;
        case "1152px":
            wrapper.css("max-width", "1280px");
            POS.setLocalConfigValue("window_size", "1280px");
            break;
        case "1280px":
            wrapper.css("max-width", "1366px");
            POS.setLocalConfigValue("window_size", "1366px");
            break;
        case "1366px":
            wrapper.css("max-width", "none");
            POS.setLocalConfigValue("window_size", "none");
            break;
        default:
            wrapper.css("max-width", "960px");
            POS.setLocalConfigValue("window_size", "960px");
            break;
    }
}
