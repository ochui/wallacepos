/**
 *
 * wpos.js Provides core functions for the admin dashboard.
 *
 */

function changehash(hash) {
  document.location.hash = hash;
}

function setActiveMenuItem(secname) {
  // remove active from previous
  $(".nav-list li").removeClass("active");
  $(".submenu li").removeClass("active");
  // add active to clicked
  var li = $('a[href="#!' + secname + '"]').parent("li");
  $(li).addClass("active");
  // set the parent item if its a submenu
  if ($(li).parent("ul").hasClass("submenu")) {
    $(li).parent("ul").parent("li").addClass("active");
  }
}
var POS;
//On load page, init the timer which check if the there are anchor changes
$(function () {
  // initiate POS.object
  POS = new WPOSAdmin();
  // init
  POS.isLogged();
});
function WPOSAdmin() {
  // AJAX PAGE LOADER FUNCTIONS
  var currentAnchor = "0";
  var currentsec = "";
  var lastAnchor = null;
  // Are there anchor changes, if there are, calculate request and send
  this.checkAnchor = function () {
    //Check if it has changes
    if (currentAnchor != document.location.hash) {
      lastAnchor = currentAnchor;
      currentAnchor = document.location.hash;
      if (currentAnchor) {
        var splits = currentAnchor.substring(2).split("&");
        //Get the section
        sec = splits[0];
        // has the section changed
        if (sec == currentsec && currentAnchor.indexOf("&query") != -1) {
          // load some subcontent
        } else {
          // if we are leaving realtime dash, close socket connection
          if (currentsec == "realtime") {
            POS.stopSocket();
          }
          // set new current section
          currentsec = sec;
          // set menu items active
          setActiveMenuItem(sec);
          // close mobile menu
          if ($("#menu-toggler").is(":visible")) {
            $("#sidebar").removeClass("display");
          }
          // start the loader
          POS.util.showLoader();
          //Creates the  string callback. This converts the url URL/#! &amp;amp;id=2 in URL/?section=main&amp;amp;id=2
          delete splits[0];
          //Create the params string
          var params = splits.join("&");
          //Send the ajax request
          POS.loadPageContent(params);
        }
      } else {
        POS.goToHome();
      }
    }
  };
  var timerId;
  this.startPageLoader = function () {
    timerId = setInterval("POS.checkAnchor();", 300);
  };
  this.stopPageLoader = function () {
    currentAnchor = "0";
    clearInterval(timerId);
  };
  this.loadPageContent = function (query) {
    var contenturl;
    if (sec == "faq") {
      contenturl = "https://wallacepos.com/content/faq.php";
    } else {
      contenturl = "api/admin/content/" + sec + "";
    }
    $.get(
      contenturl,
      query,
      function (data) {
        if (data == "AUTH") {
          POS.sessionExpired();
        } else {
          $("#maincontent").html(data);
        }
      },
      "html"
    );
  };
  this.goToHome = function () {
    if (curuser.isadmin == 1 || curuser.sections.dashboard == "both" || curuser.sections.dashboard == "both") {
      changehash("!dashboard");
    } else {
      if (curuser.sections.dashboard == "realtime") {
        changehash("!realtime");
      } else {
        // load the first allowed section
        var secval;
        for (var i in curuser.sections) {
          if (i != "access" && i != "dashboard") {
            secval = curuser.sections[i];
            if (secval > 0) {
              changehash("!" + i);
              return;
            }
          }
        }
      }
    }
  };
  var curuser = "";
  // authentication
  this.isLogged = function () {
    POS.util.showLoader();
    getLoginStatus(function (user) {
      if (user != false) {
        if (user.isadmin == 1 || (user.sections != null && user.sections.access != "no")) {
          curuser = user;

          $.ajaxSetup({
            beforeSend: function (xhr, settings) {
              xhr.setRequestHeader("anti-csrf-token", curuser ? curuser.csrf_token : "");
            },
          });

          POS.initAdmin();
        } else {
          POS.notifications.error("You do not have permission to enter this area", "Access Denied", { delay: 0 });
        }
      }
      $("#loadingdiv").hide();
      $("#logindiv").show();
      $("#loginbutton").removeAttr("disabled", "disabled");
      POS.util.hideLoader();
    });
  };
  function getLoginStatus(callback) {
    return POS.getJsonDataAsync("hello", callback);
  }
  var sessionTimer = null;
  function startSessionCheck() {
    if (sessionTimer == null) {
      sessionTimer = setInterval(startSessionCheck, 630000);
      return;
    }
    getLoginStatus(function (user) {
      if (user == false) POS.sessionExpired();
    });
  }
  function stopSessionCheck() {
    clearInterval(sessionTimer);
    sessionTimer = null;
  }
  function showLoginDiv(message) {
    if (message) {
      $("#login-banner-txt").text(message);
      $("#login-banner").show();
    } else {
      $("#login-banner").hide();
    }
    $("#loginmodal").show();
  }
  function hideLoginDiv() {
    $("#loginmodal").hide();
    $("#loadingdiv").hide();
    $("#logindiv").show();
    $("#loginbutton").removeAttr("disabled", "disabled");
  }
  this.login = function () {
    POS.util.showLoader();
    performLogin();
  };
  function performLogin() {
    POS.util.showLoader();
    var loginbtn = $("#loginbutton");
    // disable login button
    $(loginbtn).attr("disabled", "disabled");
    $(loginbtn).val("Proccessing");
    // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
    // get form values
    var userfield = $("#loguser");
    var passfield = $("#logpass");
    var username = userfield.val();
    var password = passfield.val();
    // hash password
    password = POS.util.SHA256(password);
    // authenticate
    POS.sendJsonDataAsync("auth", JSON.stringify({ username: username, password: password }), function (user) {
      if (user !== false) {
        if (user.isadmin == 1 || (user.sections != null && user.sections.access != "no")) {
          curuser = user;

          $.ajaxSetup({
            beforeSend: function (xhr, settings) {
              xhr.setRequestHeader("anti-csrf-token", curuser ? curuser.csrf_token : "");
            },
          });

          POS.initAdmin();
        } else {
          POS.notifications.error("You do not have permission to enter this area", "Access Denied", { delay: 0 });
        }
      }
      passfield.val("");
      POS.util.hideLoader();
      $(loginbtn).val("Login");
      $(loginbtn).removeAttr("disabled", "disabled");
    });
  }
  this.logout = function () {
    POS.util.confirm("Are you sure you want to logout?", function () {
      POS.util.showLoader();
      performLogout();
    });
  };
  function performLogout() {
    POS.util.showLoader();
    POS.stopSocket();
    POS.stopPageLoader();
    stopSessionCheck();
    POS.getJsonData("logout");
    showLoginDiv();
    POS.util.hideLoader();
  }
  this.sessionExpired = function () {
    POS.stopPageLoader();
    POS.stopSocket();
    showLoginDiv("Your session has expired, please login again.");
    POS.util.hideLoader();
  };
  this.initAdmin = function () {
    // hide unallowed sections
    hidePermSections();
    // Load needed config
    fetchConfigTable();
    POS.startPageLoader();
    startSessionCheck();
    hideLoginDiv();
  };
  function hidePermSections() {
    // hide/show settings
    if (curuser.isadmin == 1) {
      $(".privmenuitem").show();
      return;
    } else {
      $("#menusettings").hide();
    }
    // hide/show dashboard
    var dash = $("#menudashboard");
    var real = $("#menurealtime");
    switch (curuser.sections.dashboard) {
      case "both":
        dash.show();
        real.show();
        break;
      case "standard":
        dash.show();
        real.hide();
        break;
      case "realtime":
        dash.hide();
        real.show();
        break;
      case "none":
        dash.hide();
        real.hide();
        break;
    }
    // hide/show sections
    curuser.sections.reports > 0 ? $("#menureports").show() : $("#menureports").hide();
    curuser.sections.graph > 0 ? $("#menugraph").show() : $("#menugraph").hide();
    curuser.sections.sales > 0 ? $("#menusales").show() : $("#menusales").hide();
    curuser.sections.invoices > 0 ? $("#menuinvoices").show() : $("#menuinvoices").hide();
    curuser.sections.items > 0 ? $("#menuitems").show() : $("#menuitems").hide();
    curuser.sections.stock > 0 ? $("#menustock").show() : $("#menustock").hide();
    curuser.sections.suppliers > 0 ? $("#menusuppliers").show() : $("#menusuppliers").hide();
    curuser.sections.customers > 0 ? $("#menucustomers").show() : $("#menucustomers").hide();
    // hide parent items menu if no submenu items visible
    if (curuser.sections.items == 0 && curuser.sections.stock == 0 && curuser.sections.suppliers == 0 && curuser.sections.customers == 0) {
      $("#menuparentitems").hide();
    } else {
      $("#menuparentitems").show();
    }
  }
  // data handling functions
  this.getJsonData = function (action) {
    return getJsonData(action);
  };
  function getJsonData(action) {
    // send request to server
    var response = $.ajax({
      url: "/api/" + action,
      type: "GET",
      dataType: "text",
      timeout: 10000,
      cache: false,
      async: false,
    });
    if (response.status == "200") {
      var json = JSON.parse(response.responseText);
      var errCode = json.errorCode;
      var err = json.error;
      if (err == "OK") {
        // echo warning if set
        if (json.hasOwnProperty("warning")) {
          POS.notifications.warning(json.warning, "Warning", { delay: 0 });
        }
        return json.data;
      } else {
        if (errCode == "auth") {
          POS.sessionExpired();
          return false;
        } else {
          POS.notifications.error(err, "Error", { delay: 0 });
          return false;
        }
      }
    }

    POS.notifications.error("There was an error connecting to the server: \n" + response.statusText, "Connection Error", { delay: 0 });
    return false;
  }

  this.getJsonDataAsync = function (action, callback) {
    // send request to server
    try {
      $.ajax({
        url: "/api/" + action,
        type: "GET",
        dataType: "json",
        timeout: 10000,
        cache: false,
        success: function (json) {
          var errCode = json.errorCode;
          var err = json.error;
          if (err == "OK") {
            // echo warning if set
            if (json.hasOwnProperty("warning")) {
              POS.notifications.warning(json.warning, "Warning", { delay: 0 });
            }
            if (callback) callback(json.data);
          } else {
            if (errCode == "auth") {
              POS.sessionExpired();
              return false;
            }
            POS.notifications.error(err, "Error", { delay: 0 });
            if (callback) callback(false);
          }
        },
        error: function (jqXHR, status, error) {
          POS.notifications.error(error, "Request Error", { delay: 0 });
          if (callback) callback(false);
        },
      });
    } catch (ex) {
      POS.notifications.error("Exception: " + ex, "Exception", { delay: 0 });
      if (callback) callback(false);
    }
  };

  this.sendJsonData = function (action, data) {
    // send request to server
    var response = $.ajax({
      url: "/api/" + action,
      type: "POST",
      data: { data: data },
      dataType: "text",
      timeout: 10000,
      cache: false,
      async: false,
    });
    if (response.status == "200") {
      var json = JSON.parse(response.responseText);
      if (json == null) {
        POS.notifications.error("Error: The response that was returned from the server could not be parsed!", "Parse Error", { delay: 0 });
        return false;
      }
      var errCode = json.errorCode;
      var err = json.error;
      if (err == "OK") {
        // echo warning if set
        if (json.hasOwnProperty("warning")) {
          POS.notifications.warning(json.warning, "Warning", { delay: 0 });
        }
        return json.data;
      } else {
        if (errCode == "auth") {
          POS.sessionExpired();
          return false;
        } else {
          POS.notifications.error(err, "Error", { delay: 0 });
          return false;
        }
      }
    }
    POS.notifications.error("There was an error connecting to the server: \n" + response.statusText, "Connection Error", { delay: 0 });
    return false;
  };

  this.sendJsonDataAsync = function (action, data, callback, errorCallback) {
    // send request to server
    try {
      $.ajax({
        url: "/api/" + action,
        type: "POST",
        data: { data: data },
        dataType: "json",
        timeout: 10000,
        cache: false,
        success: function (json) {
          var errCode = json.errorCode;
          var err = json.error;
          if (err == "OK") {
            // echo warning if set
            if (json.hasOwnProperty("warning")) {
              POS.notifications.warning(json.warning, "Warning", { delay: 0 });
            }
            callback(json.data);
          } else {
            if (errCode == "auth") {
              POS.sessionExpired();
            } else {
              if (typeof errorCallback == "function") return errorCallback(json.error);
              POS.notifications.error(err, "Error", { delay: 0 });
            }
            callback(false);
          }
        },
        error: function (jqXHR, status, error) {
          if (typeof errorCallback == "function") return errorCallback(error);

          POS.notifications.error(error, "Request Error", { delay: 0 });
          callback(false);
        },
      });
      return true;
    } catch (ex) {
      if (typeof errorCallback == "function") return errorCallback(error.message);

      POS.notifications.error(ex.message, "Exception", { delay: 0 });
      callback(false);
      return false;
    }
  };

  this.uploadFile = function (event, callback) {
    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening
    POS.util.showLoader();
    var files = event.target.files;
    // Create a formdata object and add the files
    var fd = new FormData();
    fd.append("file", files[0]);
    $.ajax({
      url: "/api/file/upload",
      type: "POST",
      data: fd,
      cache: false,
      dataType: "json",
      processData: false, // Don't process the files
      contentType: false, // Set content type to false as jQuery will tell the server its a query string request
      success: function (data, textStatus, jqXHR) {
        var errCode = data.errorCode;
        var err = data.error;
        if (err == "OK") {
          // Success so call function to process the form
          callback(data.data);
        } else {
          if (errCode == "auth") {
            POS.sessionExpired();
            return false;
          } else {
            POS.notifications.error(err, "Upload Error", { delay: 0 });
            return false;
          }
        }
        // hide loader
        POS.util.hideLoader();
        return true;
      },
      error: function (jqXHR, textStatus, errorThrown) {
        // Handle errors here
        POS.notifications.error("There was an error connecting to the server: \n" + textStatus, "Connection Error", { delay: 0 });
        // hide loader
        POS.util.hideLoader();
      },
    });
  };

  // function for event source processes
  this.startEventSourceProcess = function (url, dataCallback, errorCallback) {
    if (typeof EventSource === "undefined") {
      POS.notifications.error("Your browser does not support EventSource, please update your browser to continue.", "Browser Compatibility", { delay: 0 });
      return;
    }
    showModalLoader();
    var jsonStream = new EventSource(url);
    jsonStream.onmessage = function (e) {
      var message = JSON.parse(e.data);
      if (message.hasOwnProperty("error") || message.hasOwnProperty("result")) jsonStream.close();

      if (typeof dataCallback == "function") dataCallback(message);
    };
    jsonStream.onerror = function (e) {
      jsonStream.close();
      console.log("Stream closed on error");
      if (typeof errorCallback == "function") errorCallback(e);
    };
  };

  // socket control
  // Websocket updates & commands
  var socket = null;
  var socketon = false;
  var authretry = false;
  this.startSocket = function () {
    if (socket === null) {
      var proxy = this.getConfigTable().general.feedserver_proxy;
      var port = this.getConfigTable().general.feedserver_port;
      var socketPath = window.location.protocol + "//" + window.location.hostname + (proxy == false ? ":" + port : "");
      socket = io.connect("http://127.0.0.1:3000");
      socketon = true;
      socket.on("connect_error", socketError);
      socket.on("reconnect_error", socketError);
      socket.on("error", socketError);

      socket.on("updates", function (data) {
        console.log("Received update:", data);
        switch (data.a) {
          case "devices":
            onlinedev = JSON.parse(data.data);
            populateOnlineDevices(onlinedev);
            break;

          case "sale":
            //alert(data.data);
            processIncomingSale(data.data);
            break;

          case "regreq":
            socket.emit("reg", { deviceid: 0, username: curuser.username });
            break;

          case "config":
            if (data.type == "deviceconfig") {
              if (data.data.hasOwnProperty("a")) {
                if (data.data.a == "removed") delete POS.devices[data.id];
              } else {
                POS.devices[data.data.id] = data.data;
                POS.locations[data.data.locationid] = { name: data.data.locationname };
              }
            }
            break;

          case "error":
            if (!authretry && data.data.hasOwnProperty("code") && data.data.code == "auth") {
              authretry = true;
              POS.stopSocket();
              var result = POS.getJsonData("auth/websocket");
              if (result === true) {
                POS.startSocket();
                return;
              }
            }

            POS.notifications.error(data.data.message, "Authentication Error", { delay: 0 });
            break;
        }
        //alert(data.a);
      });
    } else {
      // This should never happen, kept for historic purposes
      socket.connect();
    }
  };

  function socketError() {
    if (socketon)
      // A fix for mod_proxy_wstunnel causing error on disconnect
      POS.notifications.error("Update feed could not be connected, \nyou will not receive realtime updates!", "Connection Error", { delay: 0 });
    socketon = false;
    authretry = false;
    //socket = null;
  }

  this.stopSocket = function () {
    if (socket !== null) {
      socketon = false;
      authretry = false;
      socket.disconnect();
      socket = null;
    }
  };

  window.onbeforeunload = function () {
    socketon = false;
  };

  // data & config
  var configtable;

  this.devices = null;
  this.locations = null;
  this.users = null;
  function fetchConfigTable() {
    configtable = getJsonData("adminconfig/get");
    POS.devices = configtable.devices;
    POS.locations = configtable.locations;
    POS.users = configtable.users;
  }

  this.getConfigTable = function () {
    if (configtable == null) {
      return false;
    }
    return configtable;
  };

  this.setConfigSet = function (key, data) {
    configtable[key] = data;
  };

  this.getTaxTable = function () {
    if (configtable == null) {
      return false;
    }
    return configtable.tax;
  };

  this.putTaxTable = function (taxtable) {
    configtable.tax = taxtable;
    this.transactions.refreshTaxSelects();
  };

  this.updateConfig = function (key, data) {
    key = key.split("~");
    switch (key.length) {
      case 1:
        configtable[key[0]] = data;
        return;
      case 2:
        configtable[key[0]][key[1]] = data;
        return;
      case 3:
        configtable[key[0]][key[1]][key[2]] = data;
        return;
    }
  };

  // CSV export functions
  this.initSave = function (filename, data) {
    var dlelem = $("#dlelem");
    dlelem.attr("href", "data:text/csv;charset=utf8," + encodeURIComponent(data)).attr("download", filename + ".csv");
    $("#dlemem").ready(function () {
      $("#dlelem").get(0).click();
    });
  };

  this.table2CSV = function (el) {
    var csvData = [];

    //header
    var tmpRow = []; // construct header avalible array

    $(el)
      .find("th")
      .each(function () {
        if (!$(this).hasClass("noexport")) tmpRow[tmpRow.length] = formatData($(this).html());
      });

    row2CSV(tmpRow);

    // actual data
    $(el)
      .find("tr")
      .each(function () {
        var tmpRow = [];
        $(this)
          .find("td")
          .each(function () {
            if (!$(this).hasClass("noexport")) tmpRow[tmpRow.length] = formatData($(this).text());
          });
        tmpRow = row2CSV(tmpRow);
        if (tmpRow != null) csvData[csvData.length] = tmpRow;
      });

    return csvData.join("\n");
  };

  this.data2CSV = function (headers, fields, data) {
    var csvData = [];

    //header
    csvData[csvData.length] = row2CSV(headers);

    for (var i in data) {
      if (data.hasOwnProperty(i)) {
        var record = data[i];
        var tmpRow = [];
        for (var x = 0; x < fields.length; x++) {
          var key = fields[x];
          if (typeof key === "object") {
            tmpRow[tmpRow.length] = formatData(key.func(record[key.key], record));
          } else {
            if (record.hasOwnProperty(key)) {
              tmpRow[tmpRow.length] = formatData(record[key]);
            } else {
              tmpRow[tmpRow.length] = "";
            }
          }
        }
        tmpRow = row2CSV(tmpRow);
        if (tmpRow != null) csvData[csvData.length] = tmpRow;
      }
    }

    return csvData.join("\n");
  };

  function row2CSV(tmpRow) {
    var tmp = tmpRow.join(""); // to remove any blank rows
    // alert(tmp);
    if (tmpRow.length > 0 && tmp != "") {
      return tmpRow.join(",");
    }
    return null;
  }

  function formatData(input) {
    if (typeof input === "number") return input;

    if (typeof input === "object") input = JSON.stringify(input);
    // replace " with â€œ
    var regexp = new RegExp(/["]/g);
    var output = input.replace(regexp, '""');
    //HTML
    regexp = new RegExp(/\<[^\<]+\>/g);
    output = output.replace(regexp, "");
    if (output == "") return "";
    return '"' + output + '"';
  }

  // Load globally accessible objects
  this.util = new WPOSUtil();
  this.transactions = new WPOSTransactions();
  this.customers = new WPOSCustomers();
  this.notifications = new WPOSNotifications();
}
