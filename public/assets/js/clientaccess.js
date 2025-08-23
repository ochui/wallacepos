/**
 * clientaccess.js is part of Wallace Point of Sale system (WPOS)
 *
 * clientaccess.js Provides base functionality for the customer login area.
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 15/1/13 12:01 PM
 */

function changehash(hash){
    document.location.hash = hash;
}

function setActiveMenuItem(secname){
    // remove active from previous
    $(".nav-list li").removeClass('active');
    $(".submenu li").removeClass('active');
    // add active to clicked
    var li = $('a[href="#!'+secname+'"]').parent('li');
    $(li).addClass('active');
    // set the parent item if its a submenu
    if ($(li).parent('ul').hasClass('submenu')){
        $(li).parent('ul').parent('li').addClass('active');
    }
}
var POS;
//On load page, init the timer which check if the there are anchor changes
$(function(){
    // initiate POSobject
    POS= new WPOSClientDash();
    // init
    POSisLogged();
});
function WPOSClientDash(){
    // AJAX PAGE LOADER FUNCTIONS
    var currentAnchor = '0';
    var currentsec = '';
    var lastAnchor = null;
    
    // Initialize notifications
    this.notifications = new WPOSNotifications();
    
    // Are there anchor changes, if there are, calculate request and send
    this.checkAnchor = function(){
        //Check if it has changes
        if((currentAnchor != document.location.hash)){
            lastAnchor = currentAnchor;
            currentAnchor = document.location.hash;
            if(currentAnchor){
                var splits = currentAnchor.substring(2).split('&');
                //Get the section
                sec = splits[0];
                // has the section changed
                if (sec==currentsec &&  currentAnchor.indexOf('&query')!=-1){
                    // load some subcontent
                } else {
                    // set new current section
                    currentsec=sec;
                    // set menu items active
                    setActiveMenuItem(sec);
                    // close mobile menu
                    if ($("#menu-toggler").is(":visible")){
                        $("#sidebar").removeClass("display");
                    }
                    // start the loader
                    POSutil.showLoader();
                    //Creates the  string callback. This converts the url URL/#! &amp;amp;id=2 in URL/?section=main&amp;amp;id=2
                    delete splits[0];
                    //Create the params string
                    var params = splits.join('&');
                    var query = params;
                    //Send the ajax request
                    POSloadPageContent(query);
                }
            } else {
                POSgoToHome();
            }
        }
    };
    var timerId;
    this.startPageLoader = function(){
        timerId = setInterval("POScheckAnchor();", 300);
    };
    this.stopPageLoader = function(){
        currentAnchor = '0';
        clearInterval(timerId);
    };
    this.loadPageContent = function(query){
        $.get("content/"+sec+"", query, function(data){
            if (data=="AUTH"){
                POSsessionExpired();
            } else {
                $("#maincontent").html(data);
            }
        }, "html");
    };
    this.goToHome = function(){
        changehash("!dashboard");
    };
    var curuser = false;
    // authentication
    this.isLogged = function(){
        POSutil.showLoader();
        var data = POSgetJsonData("hello");
        curuser = data.user;
        if (curuser!==false){
            POSinitCustomers();
        }
        $("#loginbizlogo").attr("src", data.bizlogo);
        $("title").text("My "+data.bizname+" Account");
        $("#headerbizname").text(data.bizname);
        $('#loadingdiv').hide();
        $('#logindiv').show();
        $("#loginbutton").removeAttr('disabled', 'disabled');
        POSutil.hideLoader();
    };
    this.getUser = function(){
        return curuser;
    };
    this.login = function () {
        POSutil.showLoader();
        performLogin();
    };
    function performLogin(){
        POSutil.showLoader();
        var loginbtn = $('#loginbutton');
        // disable login button
        $(loginbtn).attr('disabled', 'disabled');
        $(loginbtn).val('Proccessing');
        // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
        // get form values
        var userfield = $("#loguser");
        var passfield = $("#logpass");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = POSutil.SHA256(password);
        // authenticate
        curuser = POSsendJsonData("auth", JSON.stringify({username: username, password: password}));
        if (curuser!==false){
            POSinitCustomers();
        }
        passfield.val('');
        POSutil.hideLoader();
        $(loginbtn).val('Login');
        $(loginbtn).removeAttr('disabled', 'disabled');
    }
    this.logout = function () {
        POSutil.confirm("Are you sure you want to logout?", function() {
            POSutil.showLoader();
            performLogout();
        });
    };
    function performLogout(){
        POSutil.showLoader();
        POSstopPageLoader();
        POSgetJsonData("logout");
        $("#modaldiv").show();
        POSutil.hideLoader();
    }
    this.sessionExpired = function(){
        POSstopPageLoader();
        $("#modaldiv").show();
        POSnotifications.error("Your session has expired, please login again.", "Session Expired", {delay: 0});
        POSutil.hideLoader();
    };
    this.initCustomers = function(){
        fetchConfigTable();
        POSstartPageLoader();
        $("#modaldiv").hide();
    };
    // data handling functions
    this.getJsonData = function(action){
        return getJsonData(action)
    };
    function getJsonData(action) {
        // send request to server
        var response = $.ajax({
            url     : "/api/customer/"+action,
            type    : "GET",
            dataType: "text",
            timeout : 10000,
            cache   : false,
            async   : false
        });
        if (response.status == "200") {
            var json = JSON.parse(response.responseText);
            var errCode = json.errorCode;
            var err = json.error;
            if (err == "OK") {
                // echo warning if set
                if (json.hasOwnProperty('warning')){
                    POSnotifications.warning(json.warning, "Warning", {delay: 0});
                }
                return json.data;
            } else {
                if (errCode == "auth") {
                    POSsessionExpired();
                    return false;
                } else {
                    POSnotifications.error(err, "Error", {delay: 0});
                    return false;
                }
            }
        }

        POSnotifications.error("There was an error connecting to the server: \n"+response.statusText, "Connection Error", {delay: 0});
        return false;
    }

    this.sendJsonData = function  (action, data, returnfull) {
        // send request to server
        var response = $.ajax({
            url     : "/api/customer/"+action,
            type    : "POST",
            data    : {data: data},
            dataType: "text",
            timeout : 10000,
            cache   : false,
            async   : false
        });
        if (response.status == "200") {
            var json = JSON.parse(response.responseText);
            if (json == null) {
                POSnotifications.error("Error: The response that was returned from the server could not be parsed!", "Parse Error", {delay: 0});
                return false;
            }
            if (returnfull==true)
                return json;
            var errCode = json.errorCode;
            var err = json.error;
            if (err == "OK") {
                // echo warning if set
                if (json.hasOwnProperty('warning')){
                    POSnotifications.warning(json.warning, "Warning", {delay: 0});
                }
                return json.data;
            } else {
                if (errCode == "auth") {
                    POSsessionExpired();
                    return false;
                } else {
                    POSnotifications.error(err, "Error", {delay: 0});
                    return false;
                }
            }
        }
        POSnotifications.error("There was an error connecting to the server: \n"+response.statusText, "Connection Error", {delay: 0});
        return false;
    };
    // data & config
    var configtable;

    this.currency = function(){
        return configtable.general.curformat;
    };

    this.getTaxTable = function () {
        if (configtable == null) {
            return false;
        }
        return configtable.tax;
    };

    this.getConfigTable = function () {
        if (configtable == null) {
            return false;
        }
        return configtable;
    };

    function fetchConfigTable() {
        configtable = getJsonData("config");
    }

    // Load globally accessable objects
    this.util = new WPOSUtil();
    this.transactions = new WPOSCustomerTransactions();
}