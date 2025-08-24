/**
 *
 * installer.js Provides core functions for the installer.
 *
 */

var POS;
$(function () {
  // initiate POS.object
  POS = new POSInstaller();
});
function POSInstaller() {
  // AJAX PAGE LOADER FUNCTIONS
  var currentStep = "requirements";
  var currentsec = "";
  var lastStep = null;

  this.loadInstallerStep = function (step, query) {
    var contenturl;

    contenturl = "/api/installer/content/" + step;

    $.get(
      contenturl,
      query || {},
      function (data) {
        $("#maincontent").html(data);
      },
      "html"
    );
  };

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
      console.log(json);
      var errCode = json.errorCode;
      var err = json.error;
      if (err == "OK") {
        // echo warning if set
        if (json.hasOwnProperty("warning")) {
          POS.notifications.warning(json.warning, "Warning", { delay: 0 });
        }
        return json.data;
      } else {
        POS.notifications.error(err, "Error", { delay: 0 });
        return false;
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
      if (typeof errorCallback == "function") return errorCallback(ex.message);

      POS.notifications.error(ex.message, "Exception", { delay: 0 });
      callback(false);
      return false;
    }
  };

  // Helper method for sending form data to installer APIs
  this.sendFormDataAsync = function (action, formData, callback, errorCallback) {
    try {
      $.ajax({
        url: "/api/" + action,
        type: "POST",
        data: formData,
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
              POS.notifications.error(err, "Error", { delay: 0 });
              if (typeof errorCallback == "function") return errorCallback(json.error);
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
      if (typeof errorCallback == "function") return errorCallback(ex.message);

      POS.notifications.error(ex.message, "Exception", { delay: 0 });
      callback(false);
      return false;
    }
  };

  // Load globally accessible objects
  this.util = new POSUtil();
  this.notifications = new POSNotifications();

  // Initialize installer - check status first, then load appropriate step
  this.init = function () {
    // Check installation status first
    this.getJsonDataAsync("install/status", (statusData) => {
      if (statusData && statusData.installed) {
        // System is already installed
        const currentVersion = statusData.version || "Unknown";
        const latestVersion = statusData.latest_version || "Unknown";
        
        if (currentVersion !== latestVersion && latestVersion !== "Unknown") {
          // Update is available
          this.loadInstallerStep("update-available", {
            current_version: currentVersion,
            latest_version: latestVersion
          });
        } else {
          // Already installed and up to date
          this.loadInstallerStep("already-installed", {
            current_version: currentVersion,
            latest_version: latestVersion
          });
        }
      } else {
        // System not installed, proceed with normal installation
        this.loadInstallerStep("requirements");
      }
    });
  };

  // Auto-initialize on load
  $(document).ready(() => {
    this.init();
  });
}
