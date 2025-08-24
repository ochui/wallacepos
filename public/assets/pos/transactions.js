/**
 *
 * transactions.js Provides functions loading and viewing transaction data/UI across all admin dash pages.
 *
 */

function POSTransactions() {
    var transactions = {};
    var items = null;
    // functions for opening info dialogs and populating data
    var curref;
    var curid;
    // TRANSACTION DETAILS DIALOG FUNCTIONS
    this.openTransactionList = function(refs){
        // check input
        if (!refs) return;
        // Initiate UI
        if (!uiinit) initUI();
        var translist = $("#translist"), transdialog = $("#translistdialog");
        $("#translistheader").html('<th>Ref</th><th>Details</th>');
        $("#translistdetailsbtn").show();
        translist.html('');
        translist.data('refs', refs);
        refs = refs.split(',');
        for (var i in refs){
            translist.append('<tr><td>'+refs[i]+'</td><td><button class="btn btn-sm btn-primary" onclick="POS.transactions.openTransactionDialog(\''+refs[i]+'\');">Details</button></td></tr>');
        }
        transdialog.dialog('open');
        transdialog.dialog("option", "position", {my: "center", at: "center", of: window});
        transdialog.trigger("resize");
    };
    this.loadTransactionListDetails = function(){

        var translist = $("#translist");
        var refs = translist.data('refs').split(',');
        var trans = loadTransactions(refs);
        translist.html('');
        $("#translistheader").html('<th>Ref</th><th>Time</th><th>User</th><th>Total</th><th>Details</th>');
        for (var ref in trans){
            if (!trans.hasOwnProperty(ref)) continue;
            var tran = trans[ref];
            translist.append('<tr><td>'+ref+'</td>' +
                '<td>'+POS.util.getDateFromTimestamp(tran.processdt)+'</td>' +
                '<td>'+(POS.users.hasOwnProperty(tran.userid) ? POS.users[tran.userid].username : tran.userid)+'</td>' +
                '<td>'+POS.util.currencyFormat(tran.total)+'</td>' +
                '<td><button class="btn btn-sm btn-primary" onclick="POS.transactions.openTransactionDialog(\''+ref+'\');">Details</button></td></tr>');
        }
        $("#translistdetailsbtn").hide();
        $("#translistdialog").dialog("option", "position", {my: "center", at: "center", of: window});
    };
    this.openTransactionDialog = function(ref){
        // Initiate UI
        if (!uiinit) initUI();
        // Check for transaction record
        if (transactions.hasOwnProperty(ref)==false){
            // try to load the customer record
            POS.util.showLoader();
            if (loadTransaction(ref)==false){
                POS.util.hideLoader();
                return;
            }
        }
        var record = transactions[ref];
        curref = ref;
        curid = record.id;
        // Populate general transaction info
        $("#transref").text(record.ref);
        $("#transid").text(record.id);
        $("#transtime").text(POS.util.getDateFromTimestamp(record.processdt));
        $("#transptime").text(record.dt);
        $("#transuser").text((POS.users.hasOwnProperty(record.userid) ? POS.users[record.userid].username : 'NA'));
        var device = (POS.devices.hasOwnProperty(record.devid) ? POS.devices[record.devid].name : 'NA');
        var location = (POS.locations.hasOwnProperty(record.locid) ? POS.locations[record.locid].name : 'NA');
        $("#transdev").text(device);
        $("#transloc").text(location);
        $("#transnotes").val(record.notes);
        $("#transsubtotal").text(POS.util.currencyFormat(record.subtotal));

        if (record.discount > 0) {
            $("#transdiscount").text(record.discount + "% (" + POS.util.currencyFormat(record.discountval) + ')');
            $("#transdisdiv").show();
        } else {
            $("#transdisdiv").hide();
        }

        $("#transtotal").text(POS.util.currencyFormat(record.total));

        populateItemsTable(record.items);
        populatePaymentsTable(record.payments);
        populateInvoiceInfo(record);
        populateTaxinfo(record);
        populateVoidInfo(record);
        // Populate customer information
        var customer = (record.custid==0?false:POS.customers.getCustomer(record.custid));
        if (customer!==false) {
            $("#tcustid").val(customer.id);
            $("#tcustname").text(customer.name);
            $("#tcustaddress").text(customer.address);
            $("#tcustsuburb").text(customer.suburb);
            $("#tcustpostcode").text(customer.postcode);
            $("#tcustcountry").text(customer.country);
            $("#tcustphone").text(customer.phone);
            $("#tcustmobile").text(customer.mobile);
            $("#tcustemail").text(customer.email);
            $("#tcustdetails").show();
        } else {
            $("#tcustdetails").hide();
        }

        POS.util.hideLoader();
        $("#edittransdialog").dialog("open");
    };

    this.setTransactions = function(transdata){
        transactions = transdata;
    };

    this.setTransaction = function(trans){
        if (trans.hasOwnProperty('ref'))
        transactions[trans.ref] = trans;
    };

    function loadTransactions(refs){
        var trans = POS.sendJsonData("transactions/get", JSON.stringify({refs: refs}));
        for (var ref in trans){
            transactions[ref] = trans[ref];
        }
        return trans;
    }

    function loadTransaction(ref){
        var trans = POS.sendJsonData("transactions/get", JSON.stringify({ref: ref}));
        if (!trans.hasOwnProperty(ref)){
            POS.notifications.error("Could not load the selected transaction.", "Transaction Load Error", {delay: 0});
            return false;
        }
        transactions[ref] = trans[ref];
        return true;
    }

    this.getTransactions = function(){
        return transactions;
    };

    function populateInvoiceInfo(record){
        if (!record.hasOwnProperty("duedt")){
            $(".transinvoptions").hide();
            $(".itembuttons").hide();
            $(".paybuttons").hide();
            $(".transsaleoptions").show();
            return;
        }
        $(".transsaleoptions").hide();
        $(".transinvoptions").show();
        var invprocessdt = $("#invprocessdt");
        var invduedt = $("#invduedt");
        invprocessdt.datepicker('setDate', new Date(record.processdt));
        invduedt.datepicker('setDate', new Date(record.duedt));
        var invclosedt = $("#invclosedt");
        if (record.balance == 0) {
            invclosedt.prop("disabled", false);
            invclosedt.datepicker('setDate', new Date(record.closedt));
        } else {
            invclosedt.prop("disabled", true);
            invclosedt.val('');
        }
        $("#invdiscountval").val(record.discount);
    }

    function populateTaxinfo(record) {
        var transtax = $('#transtax');
        transtax.html('');
        if (record.hasOwnProperty('taxdata')) {
            for (var i in record.taxdata) {
                transtax.append('<label class="fixedlabel">' + POS.getTaxTable().items[i].name + ' (' + POS.getTaxTable().items[i].value + '%):</label><span>' + POS.util.currencyFormat(record.taxdata[i]) + '</span><br/>');
            }
        }
    }

    function populateVoidInfo(record) {
        var status = getTransactionStatus(record);
        $("#transstat").html(getStatusHtml(status));
        if (status > 1) {
            $("#voidinfo").show();
            if (status == 2) {
                // hide buttons if void
                $(".voidbuttons").hide();
            } else {
                $(".voidbuttons").show();
            }
            // populate void/refund list
            populateRefundTable(record);
        } else {
            $(".voidbuttons").show();
            $("#voidinfo").hide();
        }
    }

    function populateItemsTable(items) {
        var itemtable = $("#transitemtable");
        $(itemtable).html('');
        var taxitems = POS.getTaxTable().items;
        for (var i = 0; i < items.length; i++) {
            // tax data
            var taxStr = "";
            for (var x in items[i].tax.values){
                taxStr += POS.util.currencyFormat(items[i].tax.values[x]) + " (" + taxitems[x].name + " " + taxitems[x].value + "%) <br/>";
            }
            if (taxStr == "")
                taxStr = POS.util.currencyFormat(0.00);
            // item mod details
            var modStr = "";
            if (items[i].hasOwnProperty('mod')){
                for (x=0; x<items[i].mod.items.length; x++){
                    var mod = items[i].mod.items[x];
                    modStr+= '<br/>'+(mod.hasOwnProperty('qty')?(mod.qty>0?'+ ':'')+mod.qty:'')+' '+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+POS.util.currencyFormat(mod.price)+')';
                }
            }
            $(itemtable).append('<tr><td>' + items[i].qty + '</td><td>' + items[i].name + modStr + '</td><td>' + POS.util.currencyFormat(items[i].unit) + '</td><td>' + taxStr + '</td><td>' + POS.util.currencyFormat(items[i].price) + '</td>' +
                '<td><div class="action-buttons itembuttons" style="text-align: right;"><a onclick="POS.transactions.openInvoiceItemDialog(' + i + ');" class="green"><i class="icon-pencil bigger-130"></i></a><a onclick="POS.transactions.deleteInvoiceItem(' + items[i].id + ')" class="red"><i class="icon-trash bigger-130"></i></a></div></td></tr>');
        }
    }

    function populatePaymentsTable(payments) {
        var paytable = $("#transpaymenttable");
        $(paytable).html('');
        var method, amount;
        for (var i = 0; i < payments.length; i++) {
            // catch extras
            method = payments[i].method;
            amount = payments[i].amount;
            var paydetailsbtn = '';
            if (payments[i].hasOwnProperty('paydata')){
                // check for integrated payment details
                if (payments[i].paydata.hasOwnProperty('transRef')){
                    paydetailsbtn = "<button onclick='POS.transactions.showPaymentInfo(this);' class='btn btn-xs btn-primary' data-paydata='"+JSON.stringify(payments[i].paydata)+"'>Details</button>";
                }
                // catch cash-outs
                if (payments[i].paydata.hasOwnProperty('cashOut')){
                    method = "cashout ("+POS.util.currencyFormat((-amount).toFixed(2))+")";
                }
            }
            $(paytable).append('<tr><td>' + POS.util.capFirstLetter(method) + '</td><td>' + POS.util.currencyFormat(amount) + '</td><td>' + POS.util.getShortDate(payments[i].processdt) + '</td><td>' + paydetailsbtn +
                '<div class="action-buttons paybuttons" style="text-align: right;"><a onclick="openInvoicePaymentDialog(' + i + ')" class="green"><i class="icon-pencil bigger-130"></i></a><a onclick="POS.transactions.deleteInvoicePayment(' + payments[i].id + ')" class="red"><i class="icon-trash bigger-130"></i></a></div></td></tr>');
        }
    }

    function populateRefundTable(record) {
        var refundtable = $("#transvoidtable");
        $(refundtable).html("");
        if (record.refunddata !== undefined) {
            var tempdata;
            for (var i = 0; i < record.refunddata.length; i++) {
                tempdata = record.refunddata[i];
                $(refundtable).append('<tr><td><span class="label label-warning arrowed">Refund</span></td><td>' + POS.util.getDateFromTimestamp(tempdata.processdt) + '</td><td><button class="btn btn-sm btn-primary" onclick="POS.transactions.showRefundDialog(' + i + ');">View</button></td><td><button onclick="POS.transactions.removeVoid(' + curid + ', ' + tempdata.processdt + ');" class="btn btn-sm btn-danger">X</button></td></tr>');
            }
        }
        if (record.voiddata !== undefined && record.voiddata !== null) {
            $(refundtable).append('<tr>><td><span class="label label-danger arrowed">Void</span></td><td>' + POS.util.getDateFromTimestamp(record.voiddata.processdt) + '</td><td><button class="btn btn-sm btn-primary" onclick="POS.transactions.showVoidDialog();">View</button></td><td><button onclick="POS.transactions.removeVoid(' + curid + ', ' + record.voiddata.processdt + ');" class="btn btn-sm btn-danger">X</button></td></tr>');
        }
    }

    function populateSharedVoidData(record) {
        $("#reftime").text(POS.util.getDateFromTimestamp(record.processdt));
        $("#refuser").text((POS.users.hasOwnProperty(record.userid) ? POS.users[record.userid].username : 'NA'));
        $("#refdev").text((POS.devices.hasOwnProperty(record.devid) ? POS.devices[record.devid].name : 'NA'));
        $("#refloc").text((POS.locations.hasOwnProperty(record.locid) ? POS.locations[record.locid].name : 'NA'));
        $("#refreason").text(record.reason);
    }

    this.showVoidDialog = function() {
        var record = getVoidData(curref, false);
        populateSharedVoidData(record);
        $("#refunddetails").hide(); // the dialog section is used for refunds too, hide that view
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#voiddetails").show();
        mdialog.dialog('option', 'title', "Void Details");
        mdialog.dialog('open');
    };

    this.showRefundDialog = function(refundindex){
        var record = getVoidData(curref, true);
        record = record[refundindex]; // get the right refund record from the array
        populateSharedVoidData(record);
        $("#refmethod").text(record.method);
        $("#refamount").text(POS.util.currencyFormat(record.amount));
        // show payment details button if available
        var dtlbtn = $("#refpaydtlbtn");
        if (record.hasOwnProperty('paydata')){
            dtlbtn.removeClass('hide');
            console.log(record.paydata);
            dtlbtn.data('paydata', record.paydata);
        } else {
            dtlbtn.addClass('hide');
        }
        // populate refunded items
        var refitemtbl = $("#refitemtable");
        refitemtbl.html("");
        for (var i = 0; i < record.items.length; i++) {
            refitemtbl.append("<tr><td>" + getItemData(curref, record.items[i].ref, record.items[i].id).name + "</td><td>" + record.items[i].numreturned + "</td></tr>");
        }
        $("#refunddetails").show(); // show the refund only view.
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#voiddetails").show();
        mdialog.dialog('option', 'title', "Refund Details");
        mdialog.dialog('open');
    };

    this.showPaymentInfo = function(btn){
        // the data is already stored in a HTML5 data element
        console.log($(btn).data('paydata'));
        showEftPaymentDialog($(btn).data('paydata'));
    };

    var paydialoginit = false;
    function showEftPaymentDialog(object){
        var paydialog = $("#eftdetailsdialog");
        if (!paydialoginit){
            paydialoginit = true;
            paydialog.removeClass('hide').dialog({
                maxWidth : 200,
                width : 'auto',
                modal   : true,
                autoOpen: false,
                buttons: [
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            paydialog.dialog('close');
                        }
                    }
                ],
                create: function( event, ui ) {
                }
            });
        }
        $("#efttransref").text(object.transRef);
        $("#efttranscard").text(object.cardType);
        $("#eftcustrec").text(object.customerReceipt);
        $("#eftmerchrec").text(object.merchantReceipt);
        paydialog.dialog('open');
    }

    // EDIT DIALOG FUNCTIONS
    this.showVoidForm = function() {
        $("#voidform").dialog('open');
    };

    this.openInvoiceItemDialog = function(index){
        var itemdialog = $('#transitemdialog');
        if (index !== false) {
            var item = transactions[curref].items[index];
            $('#transitemid').val(item.id);
            $('#transitemsitemid').val(item.sitemid);
            $('#transitemname').val(item.name);
            $('#transitemaltname').val(item.alt_name);
            $('#transitemdesc').val(item.desc);
            $('#transitemqty').val(item.qty);
            $('#transitemcost').val(item.cost);
            $('#transitemunit').val(item.unit);
            $('#transitemtaxid').val(item.taxid);
            $('#transitemprice').text(POS.util.currencyFormat(item.price));
            itemdialog.dialog('option', 'title', 'Edit Item');
        } else {
            $('#transitemform')[0].reset();
            $('#transitemid').val(0);
            $('#transitemsitemid').val(0);
            $('#transitemcost').val("0.00");
            $('#transitemunit').data("unit_original", 0.00);
            $('#transitemprice').text(POS.util.currencyFormat(0.00));
            itemdialog.dialog('option', 'title', 'Add Item');
        }
        calculateItemTotals();
        setDisabledItemFields();
        itemdialog.dialog('open');
    };

    function setDisabledItemFields() {
        if ($('#transitemsitemid').val() != 0) {
            $('#transitemname').prop("disabled", true);
            $('#transitemtaxid').prop("disabled", (!POS.getConfigTable().pos.hasOwnProperty('taxedit') || POS.getConfigTable().pos.taxedit=='no'));
            var unitfield = $('#transitemunit');
            var disableedit = (unitfield.val() !== "" && (!POS.getConfigTable().pos.hasOwnProperty('priceedit') || POS.getConfigTable().pos.priceedit=='blank'));
            unitfield.prop("disabled", disableedit);
            unitfield = $('#transitemcost');
            disableedit = (unitfield.val() !== "" && (!POS.getConfigTable().pos.hasOwnProperty('priceedit') || POS.getConfigTable().pos.priceedit=='blank'));
            unitfield.prop("disabled", disableedit);
        } else {
            $('#transitemname').prop("disabled", false);
            $('#transitemtaxid').prop("disabled", false);
            $('#transitemunit').prop("disabled", false);
        }
    }

    this.calculateItemTotals = function(){
        calculateItemTotals();
    };

    function calculateItemTotals() {
        var itemprice = Number( (parseFloat($('#transitemunit').val()) * parseFloat($('#transitemqty').val())));
        var itemcost = Number( (parseFloat($('#transitemcost').val()) * parseFloat($('#transitemqty').val())));
        itemprice = isNaN(itemprice) ? 0 : itemprice;
        // calculate item tax
        var taxdata = POS.util.calcTax($('#transitemtaxid').val(), itemprice, itemcost);
        console.log(taxdata);
        if (!taxdata.inclusive){
            itemprice = (itemprice + taxdata.total).toFixed(2);
        }
        $('#transitemprice').text(POS.util.currencyFormat(itemprice));
        $('#transitempriceval').val(itemprice);
        var taxElem = $('#transitemtax');
        taxElem.html('');
        for (var i in taxdata.values){
            taxElem.append('<label class="fixedlabel">' + POS.getTaxTable().items[i].name + " (" + POS.getTaxTable().items[i].value + '%):</label><span>'+ POS.util.currencyFormat(taxdata.values[i]) +'</span><br/>');
        }
        $('#transitemtaxval').val(JSON.stringify(taxdata));
    }

    this.openInvoicePaymentDialog = function(index){
        var paydialog = $('#transpaydialog');
        if (index !== false) {
            var payment = transactions[curref].payments[index];
            $('#transpayid').val(payment.id);
            $('#transpaymethod').val(payment.method);
            $('#transpayamount').val(payment.amount);
            $('#transpaydt').datepicker('setDate', new Date(payment.processdt));
            paydialog.dialog('option', 'title', 'Edit Payment');
        } else {
            $('#transpayform')[0].reset();
            $('#transpayid').val(0);
            paydialog.dialog('option', 'title', 'Add Payment');
        }
        paydialog.dialog('open');
    };

    this.showHistoryDialog = function(){
        // Get the data
        POS.util.showLoader();
        var data = POS.sendJsonData("invoices/history/get", JSON.stringify({id: curid}));
        if (data === false) {
            return;
        }
        // Poputlate the data
        var histtable = $("#transhisttable");
        histtable.html('');
        for (var i in data) {
            histtable.append('<tr><td>' + data[i].dt + '</td><td>' + POS.users[data[i].userid].username + '</td><td>' + data[i].type + '</td><td>' + data[i].description + '</td></tr>');
        }
        // Open the dialog
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#transhist").show();
        mdialog.dialog('option', 'title', "Invoice History");
        mdialog.dialog('open');
        POS.util.hideLoader();
    };

    this.showGenerateDialog = function(){
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#geninvoiceform").show();

        populateTemplateSelect($("#invoicetemplate"));

        mdialog.dialog('option', 'title', "Generate Invoice");
        mdialog.dialog('open');
    };

    function populateTemplateSelect(element){
        var templates = POS.getConfigTable()['templates'];
        element.html('');
        element.append('<option value="" selected="selected">Use Default</option>');
        for (var i in templates){
            if (templates[i].type=="invoice")
                element.append('<option value="'+i+'">'+templates[i].name+'</option>');
        }
    }

    this.showEmailDialog = function(){
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#sendinvoiceform").show();

        populateTemplateSelect($("#emlinvoicetemplate"));

        mdialog.dialog('option', 'title', "Email Invoice");
        mdialog.dialog('open');
        $("#emailsubject").val('Invoice #' + curref + " Attached");
        var emlto=$("#emailto"), emlcc=$("#emailcc"), emlbcc=$("#emailbcc");
        emlto.data('tag').removeAll();
        emlcc.data('tag').removeAll();
        emlbcc.data('tag').removeAll();
        emlto.val('');
        emlcc.val('');
        emlbcc.val('');
        $("#emailmessage").html('');
        // get customer
        var customer = POS.customers.getCustomers()[transactions[curref].custid];

        var recipient = false;
        for (var i in customer.contacts) {
            if (customer.contacts[i].receivesinv == 1 && customer.contacts[i].email.indexOf("@") !== -1) {
                recipient = customer.contacts[i];
            }
        }
        if (customer.email != "" && recipient == false) {
            recipient = customer;
        }
        if (recipient !== false) {
            var $tag_obj = $('#emailto').data('tag');
            //programmatically add default to address
            $tag_obj.add(recipient.email);
            var message = POS.getConfigTable().invoice.emailmsg;
            $("#emailmessage").html(message.replace("%name%", recipient.name));
        }
    };

    // DATA FUNCTIONS
    this.updateInvoice = function() {
        var self = this;
        POS.util.confirm("Save invoice details?", function() {
            // show loader
            POS.util.showLoader();
            var result = POS.sendJsonData("invoices/edit", JSON.stringify({id: curid, processdt: $("#invprocessdt").datepicker("getDate").getTime(), duedt: $("#invduedt").datepicker("getDate").getTime(), closedt: ($("#invclosedt").val() == "" ? "" : $("#invclosedt").datepicker("getDate").getTime()), discount: $("#invdiscountval").val(), notes: $('#transnotes').val()}));
            if (result !== false) {
                transactions[curref] = result;
                self.openTransactionDialog(curref);
                reloadTransactionTables();
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    this.saveInvoiceItem = function() {
        POS.util.showLoader();
        var action, itemid = $("#transitemid").val();
        var data = {id: curid, sitemid: $("#transitemsitemid").val(), qty: $("#transitemqty").val(), name: $('#transitemname').val(), alt_name: $('#transitemaltname').val(), desc: $('#transitemdesc').val(), cost: $('#transitemcost').val(), unit: $('#transitemunit').val(), taxid: $('#transitemtaxid').val(), tax: JSON.parse($('#transitemtaxval').val()), price: $('#transitempriceval').val()};
        if (itemid == 0) {
            action = "invoices/items/add";
            data.unit_original = $('#transitemunit').data("unit_original");
        } else {
            action = "invoices/items/edit";
            data.itemid = itemid;
        }
        var result = POS.sendJsonData(action, JSON.stringify(data));
        if (result !== false) {
            transactions[curref] = result;
            $("#transitemdialog").dialog('close');
            this.openTransactionDialog(curref);
            reloadTransactionTables();
        }
        // hide loader
        POS.util.hideLoader();
    };

    this.deleteInvoiceItem = function(id) {
        var self = this;
        POS.util.confirm("Are you sure you want to delete this invoice item?", function() {
            POS.util.showLoader();
            var result = POS.sendJsonData("invoices/items/delete", JSON.stringify({id: curid, itemid: id}));
            if (result !== false) {
                transactions[curref] = result;
                self.openTransactionDialog(curref);
                reloadTransactionTables();
            }
            POS.util.hideLoader();
        });
    };

    this.saveInvoicePayment = function(){
        // show loader
        POS.util.showLoader();
        var action, paymentid = $("#transpayid").val();
        var data = {id: curid, processdt: $("#transpaydt").datepicker("getDate").getTime(), method: $("#transpaymethod").val(), amount: $("#transpayamount").val()};
        if (paymentid == 0) {
            action = "invoices/payments/add";
        } else {
            action = "invoices/payments/edit";
            data.paymentid = paymentid;
        }
        var result = POS.sendJsonData(action, JSON.stringify(data));
        if (result !== false) {
            transactions[curref] = result;
            $("#transpaydialog").dialog('close');
            this.openTransactionDialog(curref);
            reloadTransactionTables();
        }
        // hide loader
        POS.util.hideLoader();
    };

    this.deleteInvoicePayment = function(id){
        var self = this;
        POS.util.confirm("Are you sure you want to delete this invoice payment?", function() {
            POS.util.showLoader();
            var result = POS.sendJsonData("invoices/payments/delete", JSON.stringify({id: curid, paymentid: id}));
            if (result !== false) {
                transactions[curref] = result;
                self.openTransactionDialog(curref);
                reloadTransactionTables();
            }
            POS.util.hideLoader();
        });
    };

    this.addVoid = function() {
        POS.util.confirm("Are you sure you want to void this transaction?", function() {
            // show loader
            POS.util.showLoader();
            var reason = $("#voidreason").val();
            var result = POS.sendJsonData("sales/adminvoid", JSON.stringify({"id": curid, "reason": reason}));
            if (result !== false) {
                transactions[curref] = result;
                reloadTransactionTables();
                populateVoidInfo(transactions[curref]);
                $("#voidform").dialog('close');
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    this.updateNotes = function(){
        POS.util.confirm("Save sale notes?", function() {
            // show loader
            POS.util.showLoader();
            var notes = $('#transnotes').val();
            var result = POS.sendJsonData("sales/updatenotes", JSON.stringify({ref: curref, notes: notes}));
            if (result !== false) {
                transactions[curref].notes = notes;
                //this.openTransactionDialog(curref); not nessesary
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    // Delete all data associated with a invoice (except customer acount)
    this.deleteTransaction = function(ref){
        POS.util.confirm("Are you sure you want to delete this transaction? It is recommended to backup your database first as this action is irreversible!", function() {
            if (!transactions.hasOwnProperty(ref)) {
                return;
            }
            // show loader
            POS.util.showLoader();
            var record = transactions[ref];
            var a = (record.hasOwnProperty('duedt')?"invoices/delete":"sales/delete");
            if (POS.sendJsonData(a, JSON.stringify({"id": transactions[ref].id})) !== false) {
                // remove invoice from the data
                delete transactions[ref];
                reloadTransactionTables();
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    // remove a void or refund record associated with a invoice
    this.removeVoid = function(id, processdt) {
        POS.util.confirm("Are you sure you want to delete this void/refund? It is recommended to backup your database first as this action is irreversible!", function() {
            // show loader
            POS.util.showLoader();
            var result = POS.sendJsonData("sales/deletevoid", JSON.stringify({"id": id, "processdt": processdt}));
            if (result !== false) {
                transactions[curref] = result;
                reloadTransactionTables();
                populateVoidInfo(transactions[curref]);
            }
            // hide loader
            POS.util.hideLoader();
        });
    };

    this.generateInvoice = function(type, download, template) {
        var link = "/api/invoices/generate?id=" + curid;
        if (type == "html") {
            link += "&type=html";
        } else {
            link += "&type=pdf";
        }
        if (download == 1) {
            link += "&download=1";
        } else {
            link += "&download=0";
        }
        link += "&template="+template;

        window.open(link, '_blank');
    };

    this.emailInvoice = function(){
        var to = $("#emailto").val();
        var cc = $("#emailcc").val();
        var bcc = $("#emailbcc").val();
        var subject = $("#emailsubject").val();
        var message = $("#emailmessage").html();
        var template = $("#emlinvoicetemplate").val();
        var result = POS.sendJsonData("invoices/email", JSON.stringify({id: curid, to: to, cc: cc, bcc: bcc, subject: subject, message: message, template:template}));
        if (result !== false) {
            $("#miscdialog").dialog('close');
        }
    };

    // functions for processing json data
    function getStatusHtml(status) {
        var stathtml;
        switch (status) {
            case -2:
                stathtml = '<span class="label label-danger arrowed">Overdue</span>';
                break;
            case -1:
                stathtml = '<span class="label label-primary arrowed">Open</span>';
                break;
            case 0:
                stathtml = '<span class="label label-success arrowed">Order</span>';
                break;
            case 1:
                stathtml = '<span class="label label-success arrowed">Closed</span>';
                break;
            case 2:
                stathtml = '<span class="label arrowed">Void</span>';
                break;
            case 3:
                stathtml = '<span class="label label-warning arrowed">Refunded</span>';
                break;
            default:
                stathtml = '<span class="label arrowed">Unknown</span>';
                break
        }
        return stathtml;
    }

    function getTransactionStatus(record) {
        if (record.hasOwnProperty('voiddata')) {
            return 2;
        } else if (record.hasOwnProperty("refunddata")) {
            // refund
            return 3;
        } else if (record.balance == 0 && record.total != 0) {
            // closed
            return 1;
        } else if ((record.duedt < (new Date).getTime()) && record.balace != 0) {
            // overdue
            return -2
        }
        return -1;
    }

    function getVoidData(ref, isrefund) {
        var record;
        record = transactions[ref];
        if (isrefund) {
            return record.refunddata;
        } else {
            return record.voiddata;
        }
    }

    function getItemData(ref, itemref, itemid) {
        var items = transactions[ref].items;
        for (var key in items) {
            if (items[key].ref == itemref) {
                return items[key];
            } else if (items[key].id == itemid){
                return items[key];
            }
        }
        return false;
    }

    function reloadTransactionTables(){
        if (typeof(reloadInvoicesTable)=="function") reloadInvoicesTable();
        if (typeof(reloadSalesTable)=="function") reloadSalesTable();
    }

    this.searchItems = function(query) {
        var results = [];
        query.trim();
        if (query !== '') {
            var upquery = query.toUpperCase();
            // search items for the text.
            if (items === null) {
                items = POS.getJsonData("items/get");
            }
            for (var key in items) {
                if (!items.hasOwnProperty(key)) {
                    continue;
                }
                if (items[key].name.toUpperCase().indexOf(upquery) != -1) {
                    results.push(items[key]);
                } else if (items[key].code.toUpperCase().indexOf(upquery) != -1) {
                    results.push(items[key]);
                }
            }
        }
        return results;
    };

    var uiinit = false;
    function initUI(){
        uiinit = true;
        $(function(){
            // dialogs
            $("#edittransdialog").removeClass('hide').dialog({
                resizable: false,
                maxWidth: 620,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Transaction Details",
                title_html: true,

                buttons: [
                    {
                        html: "<i class='icon-ban-circle bigger-110'></i>&nbsp; Void",
                        "class": "btn btn-danger btn-xs voidbuttons",
                        click: function () {
                            POS.transactions.showVoidForm();
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "620px");
                }
            });
            $("#transitemdialog").removeClass('hide').dialog({
                width: 'auto',
                maxWidth: 400,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "Add Item",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-edit bigger-110'></i>&nbsp; Save",
                        "class": "btn btn-success btn-xs",
                        click: function () {
                            POS.transactions.saveInvoiceItem();
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "400px");
                }
            });
            $("#transpaydialog").removeClass('hide').dialog({
                width: 'auto',
                maxWidth: 400,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "Add Payment",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-edit bigger-110'></i>&nbsp; Save",
                        "class": "btn btn-success btn-xs",
                        click: function () {
                            POS.transactions.saveInvoicePayment();
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "400px");
                }
            });
            $("#miscdialog").removeClass('hide').dialog({
                width: 'auto',
                maxWidth: 600,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "",
                title_html: true,
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "600px");
                }
            });
            $("#voidform").removeClass('hide').dialog({
                height: 280,
                width: 300,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                open: function (event, ui) {
                },
                close: function (event, ui) {
                },
                buttons: [
                    {
                        html: "<i class='icon-ban-circle bigger-110'></i>&nbsp; Process",
                        "class": "btn btn-danger btn-xs",
                        click: function () {
                            POS.transactions.addVoid();
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ]
            });
            // transaction listing dialog
            $("#translistdialog").removeClass('hide').css('padding', '0').dialog({
                width: 'auto',
                maxWidth: 600,
                minWidth: 275,
                maxHeight: 520,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "Report Transactions",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-refresh bigger-110'></i>&nbsp; Details",
                        "class": "btn btn-success btn-xs",
                        "id": "translistdetailsbtn",
                        click: function () {
                            POS.transactions.loadTransactionListDetails();
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("max-width", "600px");
                    $(this).css("min-width", "275px");
                    $(this).css("max-height", "520px");
                }
            });

            // Edit invoice datepickers
            var invpaydt = $("#transpaydt");
            invpaydt.datepicker({dateFormat: "dd/mm/yy"});
            invpaydt.datepicker('setDate', new Date().getTime());
            $("#invprocessdt").datepicker({dateFormat: "dd/mm/yy"});
            $("#invduedt").datepicker({dateFormat: "dd/mm/yy"});
            $("#invclosedt").datepicker({dateFormat: "dd/mm/yy"});


            // customer email search
            var customers = POS.customers.getCustomers();
            var emlarr = [];
            for (var i in customers) {
                for (var a in customers[i].contacts) {
                    if (customers[i].contacts[a].email.indexOf('@') !== -1)
                        emlarr.push(customers[i].contacts[a].email);
                }
                if (customers[i].email.indexOf('@') !== -1)
                    emlarr.push(customers[i].email)
            }
            $('.email-input').each(function (index, element) {
                try {
                    $(element).tag({
                        placeholder: $(element).attr('placeholder'),
                        //enable typeahead by specifying the source array
                        source: emlarr //defined in ace.js >> ace.enable_search_ahead
                    });
                }
                catch (e) {
                    console.log(e);
                    //display a textarea for old IE, because it doesn't support this plugin or another one I tried!
                    $(element).after('<textarea id="' + $(element).attr('id') + '" name="' + $(element).attr('name') + '" rows="3">' + $(element).val() + '</textarea>').remove();
                }
            });

            // email wysiwyg
            $('.wysiwyg-toolbar').remove();
            $('#emailmessage').ace_wysiwyg();
            $(".wysiwyg-toolbar").addClass('wysiwyg-style2');

            // item autocomplete
            $.ui.autocomplete.prototype._renderItem = function (ul, item) {
                return $("<li>").data("ui-autocomplete-item", item).append("<a>" + (item.email != undefined ? item.email : item.name) + "</a>").appendTo(ul);
            };
            $("#stitemsearch").autocomplete({
                source: function (request, response) {
                    response(POS.transactions.searchItems(request.term));
                },
                search: function () {
                    // custom minLength
                    var term = this.value;
                    return term.length >= 2;
                },
                focus: function () {
                    // prevent value inserted on focus
                    return false;
                },
                select: function (event, ui) {
                    $('#transitemsitemid').val(ui.item.id);
                    $('#transitemname').val(ui.item.name);
                    $('#transitemaltname').val(ui.item.alt_name);
                    $('#transitemdesc').val(ui.item.description);
                    $('#transitemqty').val(ui.item.qty);
                    $('#transitemcost').val(ui.item.cost);
                    $('#transitemunit').val(ui.item.price).data("unit_original", ui.item.price);
                    $('#transitemtaxid').val(ui.item.taxid);
                    // lock fields
                    setDisabledItemFields();
                    calculateItemTotals();
                    this.value = "";
                    return false;
                }
            });

            // populate tax select boxes
            refreshTaxSelects();
        });
    }

    this.refreshTaxSelects = function(){
        refreshTaxSelects();
    };
    function refreshTaxSelects(){
        var taxsel = $(".taxselect");
        taxsel.html('');
        for (var key in POS.getTaxTable().rules) {
            taxsel.append('<option class="taxid-' + POS.getTaxTable().rules[key].id + '" value="' + POS.getTaxTable().rules[key].id + '">' + POS.getTaxTable().rules[key].name + '</option>');
        }
    }

}