/**
 *
 * customers.js Provides functions loading and viewing customer data/UI across all admin dash pages.
 *
 */

function POSCustomers() {
    var customers = {};
    var curcustid = 0;
    this.openAddCustomerDialog = function(){
        if (!uiinit) initUI();
        $('#addcustdialog').dialog('open');
    };
    this.openCustomerDialog = function(id) {
        if (!uiinit) initUI();
        if (customers.hasOwnProperty(id)==false){
            // try to load the customer record
            POS.util.showLoader();
            if (loadCustomer(id)==false){
                POS.util.hideLoader();
                return;
            }
        }
        var cust = customers[id];
        $("#custid").val(cust.id);
        $("#custemail").val(cust.email);
        $("#custname").val(cust.name);
        $("#custphone").val(cust.phone);
        $("#custmobile").val(cust.mobile);
        $("#custaddress").val(cust.address);
        $("#custsuburb").val(cust.suburb);
        $("#custpostcode").val(cust.postcode);
        $("#custstate").val(cust.state);
        $("#custcountry").val(cust.country);
        $("#custnotes").val(cust.notes);
        $("#custdisbtn").text((cust.disabled==1?"Enable":"Disable")+" Customer Access");
        curcustid = id;
        populateContactsTable();
        $("#editcustdialog").dialog("open");
        POS.util.hideLoader();
    };
    function populateContactsTable() {
        var curcontact = customers[curcustid].contacts;
        var conttable = $("#contactstable");

        if (Object.keys(curcontact).length > 0) {
            conttable.html('');
            for (var i in curcontact) {
                conttable.append('<tr><td>' + curcontact[i].name + '</td><td>' + curcontact[i].email + '</td><td style="text-align: right;"><div class="action-buttons"><a class="green" onclick="openEditContactDialog(\'' + i + '\');"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeContactItem(\'' + i + '\');"><i class="icon-trash bigger-130"></i></a></div></td></tr>');
            }
        } else {
            conttable.html('<tr><td colspan="3" style="text-align: center;">No customer contacts</td></tr>');
        }
    }
    // TODO: enable API to load single customer
    function loadCustomer(id){
        var customer = POS.sendJsonData("customers/get", JSON.stringify({id: id}));
        if (!customer.hasOwnProperty(id)){
            POS.notifications.error("Could not load the selected customer.", "Customer Load Error", {delay: 0});
            return false;
        }
        customers[id] = customer[id];
        return true;
    }
    this.setCustomers = function(custdata){
        customers = custdata;
    };
    this.loadCustomers = function(){
        customers = POS.getJsonData("customers/get");
    };
    this.getCustomers = function(){
        return customers;
    };
    this.getCustomer = function(id){
        if (!customers.hasOwnProperty(id)){
            if (loadCustomer(id)===false)
                return false;
        }
        return customers[id];
    };
    this.saveCustomer = function(isnewcustomer) {
        // show loader
        POS.util.showLoader();
        var customer = {};
        var result;
        if (isnewcustomer) {
            // adding a new item
            customer.email = $("#newcustemail").val();
            customer.name = $("#newcustname").val();
            customer.phone = $("#newcustphone").val();
            customer.mobile = $("#newcustmobile").val();
            customer.address = $("#newcustaddress").val();
            customer.suburb = $("#newcustsuburb").val();
            customer.postcode = $("#newcustpostcode").val();
            customer.state = $("#newcuststate").val();
            customer.country = $("#newcustcountry").val();
            result = POS.sendJsonData("customers/add", JSON.stringify(customer));
            if (result !== false) {
                if (Array.isArray(result)) {
                    result.forEach(function(item) {
                        customers[item.id] = item;
                    });
                } else if (typeof result === "object" && result !== null) {
                    // Handle object with numeric keys and possibly a 'contacts' key
                    Object.keys(result).forEach(function(key) {
                        if (!isNaN(key)) {
                            customers[result[key].id] = result[key];
                        }
                    });
                    // If result has an 'id' property, treat as single customer object
                    if (result.id) {
                        customers[result.id] = result;
                    }
                } else {
                    customers[result.id] = result;
                }
                reloadCustomerTables();
                $("#addcustdialog").dialog("close");
            }
        } else {
            // updating an item
            customer.id = $("#custid").val();
            customer.email = $("#custemail").val();
            customer.name = $("#custname").val();
            customer.phone = $("#custphone").val();
            customer.mobile = $("#custmobile").val();
            customer.address = $("#custaddress").val();
            customer.suburb = $("#custsuburb").val();
            customer.postcode = $("#custpostcode").val();
            customer.state = $("#custstate").val();
            customer.country = $("#custcountry").val();
            customer.notes = $("#custnotes").val();
            result = POS.sendJsonData("customers/edit", JSON.stringify(customer));
            if (result !== false) {
                if (Array.isArray(result)) {
                    result.forEach(function(item) {
                        customers[item.id] = item;
                    });
                } else if (typeof result === "object" && result !== null) {
                    // Handle object with numeric keys and possibly a 'contacts' key
                    Object.keys(result).forEach(function(key) {
                        if (!isNaN(key)) {
                            customers[result[key].id] = result[key];
                        }
                    });
                    // If result has an 'id' property, treat as single customer object
                    if (result.id) {
                        customers[result.id] = result;
                    }
                } else {
                    customers[result.id] = result;
                }
                reloadCustomerTables();
                $("#editcustdialog").dialog("close");
            }
        }
        // hide loader
        POS.util.hideLoader();
    };

    this.setOnlineAccess = function(){
        var customer = customers[curcustid];
        var disable = customer.disabled==1?0:1;
        POS.util.confirm('Are you sure you want to '+(disable==1?'disable':'enable')+' this customer from accessing their online account?', function() {
            var result = POS.sendJsonData("customers/setaccess", JSON.stringify({id:curcustid, disabled: disable}));
            if (result!==false){
                customers[curcustid].disabled = disable;
                $("#custdisbtn").text((disable==1?"Enable":"Disable")+" Customer Access");
            }
        });
    };

    this.setOnlinePassword = function(){
        var customer = customers[curcustid];
        var newpass = $("#newcustpass").val();
        if (newpass==""){
            POS.notifications.warning('Please enter a new password.', "Password Required", {delay: 0});
            return;
        }
        POS.util.confirm('Are you sure you want to set this users password and activate their account?', function() {
            var hash = POS.util.SHA256(newpass);
            var result = POS.sendJsonData("customers/setpassword", JSON.stringify({id:curcustid, hash: hash}));
            if (result!==false){
                customers['disabled'] = 0;
                $("#custdisbtn").text("Disable Customer Access");
                $("#newcustpass").val('');
            }
        });
    };

    this.sendResetEmail = function(){
        POS.util.confirm('Are you sure you want to send a password reset to this user?', function() {
            var result = POS.sendJsonData("customers/sendreset", JSON.stringify({id:curcustid}));
        });
    };

    this.deleteCustomer= function(id) {
        POS.util.confirm("Are you sure you want to delete this customer? We recommend backing up data before making deletions.", function() {
            // show loader
            POS.util.showLoader();
            if (POS.sendJsonData("customers/delete", '{"id":' + id + '}') !== false) {
                delete customers[id];
                reloadCustomerTables();
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    function reloadCustomerTables(){
        // Reload customer table if displayed
        if (typeof(reloadCustomerTable)=="function") reloadCustomerTable();
    }

    this.openEditContactDialog = function(contactid) {
        var contdialog = $("#custcontactdialog");
        if (contactid == null) {
            contdialog.dialog('option', "title", "Add Contact");
            $("#custcontactform")[0].reset();
            $("#contid").val(0)
        } else {
            var contacts = customers[curcustid].contacts;
            if (!contacts.hasOwnProperty(contactid)) {
                return;
            }
            var cont = contacts[contactid];
            $("#contid").val(contactid);
            $("#contname").val(cont.name);
            $("#contposition").val(cont.position);
            $("#contemail").val(cont.email);
            $("#contphone").val(cont.phone);
            $("#contmobile").val(cont.mobile);
            $("#contrecinv").prop("checked", (cont.receivesinv == "1"));
            contdialog.dialog('option', "title", "Edit Contact");
        }
        contdialog.dialog("open");
    };

    this.saveContactItem = function() {
        var contact = {};
        contact.customerid = curcustid;
        contact.name = $("#contname").val();
        contact.position = $("#contposition").val();
        contact.email = $("#contemail").val();
        contact.phone = $("#contphone").val();
        contact.mobile = $("#contmobile").val();
        contact.receivesinv = $("#contrecinv").prop("checked") == true ? 1 : 0;
        var a, id = $("#contid").val();
        if (id == 0) {
            a = "customers/contacts/add";
        } else {
            contact.id = id;
            a = "customers/contacts/edit";
        }
        var result = POS.sendJsonData(a, JSON.stringify(contact));
        if (result !== false) {
            customers[curcustid] = result;
            populateContactsTable();
            $("#custcontactdialog").dialog("close");
        }
    };

    this.removeContactItem = function(id) {
        POS.util.confirm("Are you sure you want to delete this contact? We recommend backing up data before making deletions.", function() {
            // show loader
            POS.util.showLoader();
            if (POS.sendJsonData("customers/contacts/delete", '{"id":' + id + '}')) {
                delete customers[curcustid].contacts[id];
                populateContactsTable();
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    var uiinit = false;
    function initUI(){
        uiinit = true;

        $( "#addcustdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Add Customer",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        POS.customers.saveCustomer(true);
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "400px");
            }
        });
        $( "#editcustdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Customer",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        POS.customers.saveCustomer(false);
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "400px");
            }
        });
        $( "#custcontactdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Contact",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        POS.customers.saveContactItem();
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "400px");
            }
        });
    }

}