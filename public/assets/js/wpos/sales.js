/**
 * sales.js is part of Wallace Point of Sale system (WPOS)
 *
 * sales.js Provides functions for processing sales, including the storage and upload of offline sales,
 * It also includes WPOSItems object for adding items to the sale.
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
 * @since      Class created 15/1/14 12:01 PM
 */

function WPOSItems() {
    // public members for modifying the current sales items
    /**
     * Adds a blank item row for the user to fill in
     */
    this.addManualItemRow = function () {
        // add the row
        addItemRow(1, "", "0.00", 1, 0, {desc:"", cost:0.00, unit_original:0.00});
        // focus on qty
        $("#itemtable")
            .children('tr :last')
            .find(".itemqty")
            .focus();
        // mark invalid records
        POSsales.updateSalesTotal();
    };
    /**
     * Removes an item from the sale
     * @param {string} sitem
     */
    this.removeItem = function (sitem) {
        $(sitem).parent('td').parent('tr').remove();
        POSsales.updateSalesTotal();
    };

    /**
     * Adds an item from a stock code
     * @param {string} code
     */
    this.addItemFromStockCode = function (code) {
        // find the item id from the stock code index and use it to retrieve the record.
        var item = POSgetItemsTable()[POSgetStockIndex()[code.toUpperCase()]];
        if (item === null || item === undefined || item === "") {//ADAM: Should use triple equals
            POSnotifications.error("Item not found", "Item Error");
            $("#codeinput").val('');
        } else {
            // add the item
            addItem(item);
        }
    };

    /**
     *
     * @param {Number} id
     */
    this.addItemFromId = function (id) {
        var item = POSgetItemsTable()[id];
        if (item === null) {
            POSnotifications.error("Item not found", "Item Error");
        } else {
            // add the item
            addItem(item);
        }
    };

    /**
     *
     * @param {String} query
     * @returns {Array}
     */
    this.searchItems = function (query) {
        var results = [];
        query.trim();
        if (query !== '') {
            var upquery = query.toUpperCase();
            // search items for the text.
            var itemtable = POSgetItemsTable();
            for (var key in itemtable) {
                if (!itemtable.hasOwnProperty(key)) {
                    continue;
                }
                if (itemtable[key].name.toUpperCase().indexOf(upquery) != -1) {
                    results.push(itemtable[key]);
                } else if (itemtable[key].code.toUpperCase().indexOf(upquery) != -1) {
                    results.push(itemtable[key]);
                }
            }
        }
        //alert(JSON.stringify(results));
        return results;
    };

    this.generateItemGrid = function(categoryId){
        var iboxitems = $("#iboxitems");
        iboxitems.html('<div style="padding: 5px;"><button class="btn btn-sm btn-primary" onclick="POSitems.generateItemGridCategories();"><i class="icon-backward">&nbsp;</i>Categories</button></div>');
        var price;
        var items = [];
        if (categoryId>-1){
            if (POSgetCategoryIndex().hasOwnProperty(categoryId)) {
                var index = POSgetCategoryIndex()[categoryId];
                var tempitems = POSgetItemsTable();
                for (var x = 0; x < index.length; x++) {
                    items[index[x]] = tempitems[index[x]];
                }
            }
        } else {
            items = POSgetItemsTable();
        }

        for (var i in items){
            price = (items[i].price==""?"??.??":parseFloat(items[i].price).toFixed(2));
            iboxitems.append('<div class="iboxitem" onclick="POSitems.addItemFromId('+items[i].id+'); toggleItemBox(false);">' +
                                '<h6>'+items[i].name+'</h6>'+
                                '<h5>'+POSutil.currencyFormat(price)+'</h5>'+
                            '</div>');
        }
    };

    this.generateItemGridCategories = function(){
        var iboxitems = $("#iboxitems");
        iboxitems.html('<div class="iboxitem" onclick="POSitems.generateItemGrid(-1);"><h5>All Categories</h5><h6>('+Object.keys(POSgetItemsTable()).length+' items)</h6></div>');
        var catindex = POSgetCategoryIndex();
        var categories = POSgetConfigTable().item_categories;
        //console.log(catindex);
        for (var i in categories){
            iboxitems.append('<div class="iboxitem" onclick="POSitems.generateItemGrid('+i+');">' +
                '<h5>'+categories[i].name+'</h5>'+
                '<h6>('+(catindex.hasOwnProperty(i)?catindex[i].length:0)+' items)</h6>'+
                '</div>');
        }
        var misctotal = catindex.hasOwnProperty(0)?catindex[0].length:0;
        iboxitems.append('<div class="iboxitem" onclick="POSitems.generateItemGrid(0);"><h5>Miscellaneous</h5><h6>('+misctotal+' items)</h6></div>');
    };

    /**
     * Adds a html row into the sales table, if sitem id is greater than 0, all fields that are filled are disabled to prevent modification.
     * @param {Number} qty
     * @param {String} name
     * @param {String} unit
     * @param {Number} taxid
     * @param {Number} sitemid ; the stored item id to keep track of inventory sales
     * @param data
     */
    function addItemRow(qty, name, unit, taxid, sitemid, data) {
        sitemid = (sitemid>0?sitemid:0);
        var disable = (sitemid>0); // disable fields that are filled by the stored item
        var disableprice = (sitemid>0 && POSgetConfigTable().pos.priceedit!="always");
        var disabletax = (!POSgetConfigTable().pos.hasOwnProperty('taxedit') || POSgetConfigTable().pos.taxedit=='no');
        var row = $('<tr class="item_row">' +
            '<td><input class="itemid" type="hidden" value="' + sitemid + '" data-options=\''+JSON.stringify(data)+'\' /><input onChange="POSsales.updateSalesTotal();" style="width:50px;" type="text" class="itemqty numpad" value="' + qty + '" /></td>' +
            '<td><input '+((disable==true && name!="")?"disabled":"")+' style="width: 100%; min-width: 100px;" type="text" class="itemname" value="' + name + '" onChange="POSsales.updateSalesTotal();" /><div class="itemmodtxt"></div></td>' +
            '<td><input '+((disableprice==true && unit!="")?"disabled":"")+' onChange="POSsales.updateSalesTotal();" style="max-width:50px;" type="text" class="itemunit numpad" value="' + unit + '" /></td>' +
            '<td><button onclick="POSitems.openItemModDialog(this);" class="btn btn-primary btn-xs"><i class="icon-list-ul"></i></button><div class="itemmodtext"></div></td>' +
            '<td><select '+((disabletax==true && taxid!=null)?"disabled":"")+' onChange="POSsales.updateSalesTotal();" style="max-width:110px;" class="itemtax">' +getTaxSelectHTML(taxid)+ '</select><input class="itemtaxval" type="hidden" value="0.00" /></td>' +
            '<td><input style="max-width:50px;" type="text" class="itemprice" value="0.00" disabled /></td>' +
            '<td style="text-align: center;"><button class="btn btn-sm btn-danger" onclick="POSitems.removeItem($(this));">X</button></td>' +
            '</tr>');
        if (data.orderid) {
            row.insertAfter("#order_row_"+data.orderid);
        } else {
            $("#itemtable").append(row);
        }
        $('#items_contain').scrollTop(1E10);
        // reinitialize keypad & field listeners
        POSinitKeypad();
    }
    this.addItemRow = function(qty, name, unit, taxid, sitemid, data){
        addItemRow(qty, name, unit, taxid, sitemid, data)
    };

    /**
     * Gets or generates the taxid select HTML depending on input
     * @param {Number} taxid
     * @returns {String}
     */
    function getTaxSelectHTML(taxid) {
        var taxselecthtml = "";
        var taxrules = POSgetTaxTable().rules;
            for (var key in taxrules) {
                if (taxrules.hasOwnProperty(key)) {
                    taxselecthtml += "<option id='taxrule-" + key + "' value='" + key + "' "+(taxid==key?"selected='selected'":"")+">" + taxrules[key].name + "</option>";
                }
            }
        return taxselecthtml;
    }

    /**
     *
     * @param {Object} item
     */
    function addItem(item) {
        // Item cost may be null if we're adding stored items that were created in a previous version, explicitly set the cost in this case.
        if (!item.hasOwnProperty('cost')) item.cost = 0.00;
        // TODO: remove last row from table if its invalid?
        // check if a priced item is already present in the sale and if so increment it's qty
        if (item.price==""){
            // insert item into table
            addItemRow(1, item.name, item.price, item.taxid, item.id, {desc:item.description, cost:item.cost, unit_original:item.price, alt_name:item.alt_name});
        } else {
            if (!isItemAdded(item.id, true)){
                // insert item into table
                addItemRow(1, item.name, item.price, item.taxid, item.id, {desc:item.description, cost:item.cost, unit_original:item.price, alt_name:item.alt_name});
            }
        }
        $("#codeinput").val('');
        POSsales.updateSalesTotal();
    }

    function isItemAdded(itemid, addqty){
        var found = false;
        $("#itemtable").children(".valid").each(function(index, element) {
            var itemfield = $(element).find(".itemid");
            if (itemfield.val()==itemid){
                // check for item modifiers, a new line item must be added if a modifier is used
                if (itemfield.data('options').hasOwnProperty('mod') && itemfield.data('options').mod.items.length>0){
                    return true;
                }
                if (addqty) $(element).find(".itemqty").val(parseInt($(element).find(".itemqty").val())+1);
                found = true;
                return false;
            }
            return true;
        });
        return found;
    }

    var itemrow;
    this.openItemModDialog = function(elem){
        itemrow = $(elem).parent().parent();
        var data = itemrow.find('.itemid').data('options');
        //console.log(data);
        $("#itemdesc").val(data.desc);
        var disableprice = (itemrow.find('.itemid').val()>0 && POSgetConfigTable().pos.priceedit!="always");
        $("#itemcost").val(data.cost).prop('disabled', (disableprice && data.cost!=""));
        $("#itemaltname").val(data.alt_name);
        // get stored item mods
        var itemid = itemrow.find('.itemid').val();
        modtable.html('');
        if (itemid>0){
            var itemmods = POSgetItemsTable()[itemid].modifiers;
            if (itemmods!=null && itemmods.length>0){
                for (var i=0; i<itemmods.length; i++){
                    zerostr = POSutil.currencyFormat(0);
                    if (itemmods[i].type=="select"){
                        insertSelectModRow(itemmods[i]);
                    } else {
                        insertSimpleModRow(itemmods[i]);
                    }
                }
            }
            // get mods for current sale item, update the item mods
            if (data.hasOwnProperty('mod')){
                var mods = data.mod.items;
                for (i=0; i<mods.length; i++){
                    var mod = mods[i];
                    var row = $("#mod-"+mod.name.replace(/\s/g, ""));
                    if (row){
                        var costelem = row.find('.modcost');
                        if (mod.hasOwnProperty('qty')){
                            var qtyelem = row.find('.modqty');
                            qtyelem.text(qtyelem.data('default') + mod.qty);
                            costelem.data('modqty', mod.qty);
                            costelem.data('modprice', mod.price);
                            costelem.text(POSutil.currencyFormat(mod.price));
                        } else {
                            row.find('.modselect').val(mod.value);
                            costelem.data('modprice', mod.price);
                            costelem.text(POSutil.currencyFormat(mod.price));
                        }
                    } else {
                        // TODO: Handle mods deleted from stored items that are already in the order
                    }
                }
                calculateTotalMods();
            }
        }
        $("#itemoptionsdialog").dialog("open");
    };
    var modtable = $("#itemmods");
    var zerostr = "";
    function insertSimpleModRow(mod){
        modtable.append('<tr id="mod-'+mod.name.replace(/\s/g, "")+'">' +
            '<td><span class="modname">'+mod.name+'</span></td>' +
            '<td><button onclick="POSitems.incrementModQty(this, true);" class="btn btn-primary btn-xs" style="margin-right: 5px;"><i class="icon-arrow-up"></i></button><button onclick="POSitems.incrementModQty(this, false);" class="btn btn-primary btn-xs" style="margin-right: 4px;"><i class="icon-arrow-down"></i></button>' +
            '<span data-min="'+mod.minqty+'" data-max="'+mod.maxqty+'" data-default="'+mod.qty+'" data-price="'+mod.price+'" class="modqty">'+mod.qty+'</span></td>' +
            '<td><span data-moddefault="'+mod.qty+'" data-modqty="0" data-modprice="0" class="modcost">'+zerostr+'</span></td></tr>');
    }
    this.incrementModQty = function(elem, positive){
        var row = $(elem).parent().parent();
        var qtyelem = row.find('.modqty');
        var defaultqty = qtyelem.data('default');
        var minqty = qtyelem.data('min');
        var maxqty = qtyelem.data('max');
        var price = parseFloat(qtyelem.data('price'));
        var newqty = parseInt(qtyelem.text()) + (positive?1:-1);
        if (newqty<minqty || newqty>maxqty){
            var ismax = newqty>maxqty;
            POSnotifications.warning("Cannot have "+(ismax?"more":"less")+" than "+(ismax?maxqty:minqty)+" "+row.find('.modname').text(), "Quantity Limit");
            return;
        }
        var modqty = newqty-defaultqty;
        var newprice = (modqty*price).toFixed(2);
        qtyelem.text(newqty);
        var costelem = row.find('.modcost');
        costelem.data('modqty', modqty);
        costelem.data('modprice', newprice);
        costelem.text(POSutil.currencyFormat(newprice));
        calculateTotalMods();
    };
    function insertSelectModRow(mod){
        var selecthtml = '';
        var selectdefault;
        for (var i=0; i<mod.options.length; i++){
            if (mod.options[i].default) selectdefault = mod.options[i];
            selecthtml += '<option data-default="'+mod.options[i].default+'" data-price="'+mod.options[i].price+'" value="'+mod.options[i].name+'" '+(mod.options[i].default?'selected="selected"':'')+'>'+mod.options[i].name+'</option>';
        }
        modtable.append('<tr id="mod-'+mod.name.replace(/\s/g, "")+'">' +
            '<td><span class="modname">'+mod.name+'</span></td>' +
            '<td><select onchange="POSitems.modSelectValue(this);" class="modselect">'+selecthtml+'</select></td>' +
            '<td><span data-moddefault="'+selectdefault.name+'" data-modprice="0" data-defaultprice="'+selectdefault.price+'" class="modcost">'+zerostr+'</span></td></tr>');
    }
    this.modSelectValue = function(selectelem){
        var costfield = $(selectelem).parent().parent().find('.modcost');
        var newprice = $(selectelem).children('option:selected').data('price');
        var defaultprice = costfield.data('defaultprice');
        var moddiffprice = (newprice - defaultprice).toFixed(2);
        costfield.data('modprice', moddiffprice);
        costfield.text(POSutil.currencyFormat(moddiffprice));
        calculateTotalMods();
    };
    function calculateTotalMods(){
        var total = 0;
        modtable.children('tr').each(function(){
            total += parseFloat($(this).find('.modcost').data('modprice'));
        });
        var totalfield = $("#itemmodtotal");
        totalfield.data('modtotal', total);
        totalfield.text(POSutil.currencyFormat(total));
        var newunit = parseFloat(itemrow.find('.itemunit').val()) + total;
        $("#itemmodunit").text(POSutil.currencyFormat(newunit));
    }

    this.saveItemMods = function(){
        var moddata = {total:$("#itemmodtotal").data('modtotal'), items:[]};
        var modtxt = "";
        modtable.children('tr').each(function(){
            var dataelem = $(this).find('.modcost');
            var modqty = dataelem.data('modqty');
            var moddefault = dataelem.data('moddefault');
            var modval = $(this).find('.modselect').val();
            if ((!isNaN(modqty) && modqty!=0) || (!dataelem.attr('data-modqty') && moddefault!=modval)){
                var mod = {};
                mod.name = $(this).find('.modname').text();
                mod.price = dataelem.data('modprice');
                if (dataelem.attr('data-modqty')){
                    mod.qty = modqty;
                } else {
                    mod.value = modval;
                }
                moddata.items.push(mod);
                modtxt+= (mod.hasOwnProperty('qty')?(mod.qty>0?'+ ':'')+mod.qty:'')+' '+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+POSutil.currencyFormat(mod.price)+')<br/>';
            }
        });
        itemrow.find('.itemmodtxt').html(modtxt);
        var data = itemrow.find('.itemid').data('options');
        data.desc = $("#itemdesc").val();
        data.cost = $("#itemcost").val();
        if (moddata.items.length>0){
            data.mod = moddata;
        } else {
            delete data.mod;
        }
        itemrow.find('.itemid').data('options', data);
        POSsales.updateSalesTotal();
    };

    this.searchCustomers = function(query){
        var results = [];
        var customers = POSgetCustTable();
        query.trim();
        query = query.toLowerCase();
        for (var key in customers){
            if (customers[key].email.toLowerCase().indexOf(query) != -1){
                results.push(customers[key]);
            }
        }
        //this.processNewEmail();
        return results;
    };

    this.loadCustomerDetails = function(custid, email){ // triggered on autocomplete click
        validateEmail(email);
        loadCustomerIfExists(custid);
    };

    function loadCustomerIfExists(custid){
        var custdata = POSgetCustTable()[custid];
        if (custdata!=null){
            $("#custid").val(custdata.id);
            $("#custname").val(custdata.name);
            //$("#custemail").val(custdata.email);
            $("#custphone").val(custdata.phone);
            $("#custmobile").val(custdata.mobile);
            $("#custaddress").val(custdata.address);
            $("#custsuburb").val(custdata.suburb);
            $("#custpostcode").val(custdata.postcode);
            $("#custcountry").val(custdata.country);
            return true;
        } else {
            return false;
        }
    }

    this.openCustomerDialog = function(){
        $("#custdiv").dialog("open");
    };

    this.processNewEmail = function(){ // triggered while the user is typing
        var email = $('#custemail').val();
        if (validateEmail(email)==true){
            if (loadCustomerIfExists(POSgetCustId(email))){
                return;
            }
        }
        // clear old forms/flags if no account loaded
        $('#custid').val(0);
        POSsales.clearCustUpdate();
        $('#custform').trigger('reset');
    };

    function validateEmail(email){
        var emailreccb = $("#emailreceipt");
        if (email!=="" && email.indexOf("@")!==-1){
            emailreccb.prop('checked', true); // make this a local setting
            emailreccb.prop('disabled', false);
            return true;
        } else {
            emailreccb.prop('checked', false);
            emailreccb.prop('disabled', true);
            return false;
        }
    }
}
// Item UI stuff
$(function () {
    $.ui.autocomplete.prototype._renderItem = function (ul, item) {
        return $("<li>").data("ui-autocomplete-item", item).append("<a>" + (item.email!=undefined?item.email:item.name) + "</a>").appendTo(ul);
    };

    $("#itemsearch").autocomplete({
        source: function (request, response) {
            response(POSitems.searchItems(request.term));
        },
        search: function () {
            // custom minLength
            var term = this.value;
            return term.length >= 2;
        },
        focus : function () {
            // prevent value inserted on focus
            return false;
        },
        select: function (event, ui) {
            POSitems.addItemFromId(ui.item.id);
            this.value = "";
            return false;
        }
    });

    $("#custemail").autocomplete({
        source: function (request, response) {
            response(POSitems.searchCustomers(request.term));
        },
        search: function () {
            // custom minLength
            var term = this.value;
            return term.length >= 2;

        },
        select: function (event, ui) {
            this.value = ui.item.email;
            POSitems.loadCustomerDetails(ui.item.id, ui.item.email);
            return false;
        }
    });

    $("#itemoptionsdialog").removeClass('hide').dialog({
        width : 'auto',
        maxWidth        : 475,
        modal        : true,
        closeOnEscape: true,
        autoOpen     : false,
        open         : function (event, ui) {
        },
        close        : function (event, ui) {
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "475px");
        },
        buttons:[
            {
                html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                "class" : "btn btn-success btn-xs",
                click: function() {
                    POSitems.saveItemMods();
                    $(this).dialog( "close" );
                }
            }
            ,
            {
                html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                "class" : "btn btn-xs",
                click: function() {
                    $(this).dialog( "close" );
                }
            }
        ]
    });

    $("#subtotal").text(POSutil.currencyFormat(0));
    $("#totaltax").text(POSutil.currencyFormat(0));
    $("#discounttxt").text("("+POSutil.currencyFormat(0)+")");
    $("#total").text(POSutil.currencyFormat(0));
});

function WPOSSales() {
    var paymentMethods = ['eftpos', 'credit', 'cash', 'cheque', 'deposit'];
    var cursubtotal = 0.00;
    var curtaxtotal = 0.00;
    var curtotal = 0.00;
    var lasttransref = null;
    var curref = null; // reference for the order/sale currently processing, set when a prev order is loaded, cleared when the form is cleared

    this.getLastRef = function(){
        return lasttransref;
    };

    this.getCurrentRef = function(){
        return curref;
    };

    /**
     *
     */
    this.updateSalesTotal = function () {
        var total = 0.00;
        var tempprice = 0.00;
        curtaxtotal = 0.00; // clear last tax
        // validate records, marks valid records to be used in sale and informs user of invalid records
        // It also calculates item total and checks that its a correct result
        validateSalesItems();
        // cycle through valid records and add item total to the sales total
        var temptax;
        $("#itemtable").children('.valid').each(function (index, element) {
                // get item total
                tempprice = parseFloat($(element).find(".itemprice").val());
                // add to total
                total += tempprice;
                // get tax amount included with each item
                var taxtotals = $(element).find(".itemtaxval").data('taxdata');
                curtaxtotal += taxtotals.total;
        });
        // remove cur tax from the total and we have our subtotal
        curtotal = total;
        cursubtotal = (total - curtaxtotal);
        $("#subtotal").text(POSutil.currencyFormat(cursubtotal.toFixed(2)));
        $("#totaltax").text(POSutil.currencyFormat(curtaxtotal.toFixed(2)));
        this.updateDiscount();
    };

    /**
     *
     */
    this.updateDiscount = function () {
        if (cursubtotal === null) {
            this.updateSalesTotal();
            return;
        }
        var discountobj = $("#salediscount");
        var distxtobj = $("#discounttxt");
        var discount = discountobj.val();
        curtotal = (parseFloat(cursubtotal) + parseFloat(curtaxtotal)); // !important reset total
        if (discount === "" || discount == "0" || discount === null) {
            discountobj.val("0");
            distxtobj.text("("+POSutil.currencyFormat("0.00")+")");
        } else {
            var discountsum = ((discount / 100) * curtotal).toFixed(2);
            distxtobj.text("(" + POSutil.currencyFormat(discountsum) + ")");
            curtotal = (curtotal - discountsum);
        }
        $("#total").text(POSutil.currencyFormat(curtotal.toFixed(2)));
    };

    /**
     *
     */
    var curgrandtotal = 0;
    var curround = 0;
    var curbalance = 0;
    this.updatePaymentSums = function () {
        var roundcents = POSgetConfigTable().pos.cashrounding;
        var totalpaid = 0;
        var temppay = 0;
        var temptend = 0;
        var totalchange = 0;
        var allcash = true;
        var paymentstable = $("#paymentstable");
        // loop through payments, if cash apply rounding to payment amount and find change amount
        paymentstable.children('tr').each(function (index, element) {
            var paystr = $(element).find(".payamount").val();
            temppay = parseFloat(paystr);
            if ($(element).find(".paymethod").val()=='cash'){
                // apply cash rounding
                temppay = roundcents>0?POSutil.roundToNearestCents(roundcents, temppay):temppay;
                // get tender amount
                var change, tendfield = $(element).find(".paytender");
                temptend = parseFloat(tendfield.val()).toFixed(2);
                // apply cash rounding
                temptend = roundcents>0?POSutil.roundToNearestCents(roundcents, temptend):temptend;
                $(element).find(".paytender").val(temptend);
                //tendfield.val(temptend); causes problems with keypad
                if (temptend>temppay){
                    change = temptend - temppay;
                    totalchange+=change;
                } else {
                    change = 0.00;
                }
                $(element).find(".paychange").val(change.toFixed(2));
            } else {
                allcash = false;
            }

            if (paystr.match(new RegExp("^[0-9]+\.[0-9][0-9]$"))){
                $(element).find(".payamount").val(parseFloat(temppay).toFixed(2));
            }

            if (!isNaN(temppay)) {
                totalpaid += parseFloat(temppay);
            }
        });
        totalpaid = totalpaid.toFixed(2);
        // if all payments are cash, apply cash rounding to total else reverse any currently applied rounding
        if (roundcents==0 || allcash==false || paymentstable.children('tr').length==0){
            curgrandtotal = curtotal;
            curround = 0;
        } else {
            curgrandtotal = roundcents>0?POSutil.roundToNearestCents(roundcents, curtotal):curtotal;
            curround = curgrandtotal - curtotal;
        }
        // update payment sums
        var balance = -(curgrandtotal - totalpaid).toFixed(2);
        $("#salestotal").text(POSutil.currencyFormat(parseFloat(curgrandtotal).toFixed(2)));
        $("#paymentstotal").text(POSutil.currencyFormat(parseFloat(totalpaid).toFixed(2)));
        $("#salesbalance").text(POSutil.currencyFormat(balance));
        $("#saleschange").text(POSutil.currencyFormat((balance>=0?totalchange.toFixed(2):0.00)));
        salebalanced = balance == 0;
        curbalance = balance;
        //console.log("GT:"+ curgrandtotal + "\nPAID:" + totalpaid+ "\nBALANCE:"+ balance + "\nROUNDCENTS:"+ roundcents + "\nROUNDCENTS:"+ curround);
    };

    this.updatePaymentChange = function(element){
        var tender = parseFloat($(element).find(".paytender").val());
        var amount = parseFloat($(element).parent().parent().find(".payamount").val());
        var change = 0.00;
        if (tender>amount){
            change = tender - amount;
        } /*else {
            // update payment amount field to match tender
            $(element).parent().parent().find(".payamount").val(tender);
            POSsales.updatePaymentSums();
        }*/
        $(element).find(".paychange").val(change.toFixed(2));
    };

    /**
     *
     */
    this.userAbortSale = function () {
        POSutil.confirm("Are you sure you want to abort this order?", function() {
            clearSalesForm();
        });
    };

    this.resetSalesForm = function(){
        clearSalesForm();
    };

    /**
     *
     */
    function clearSalesForm() {
        $('#paymentsdiv').dialog('close');
        // clear sales form
        $("#itemtable").html('');
        // add a new order row
        if (POSisOrderTerminal()) {
            $('<tr class="order_row"><td style="background-color:#438EB9; color:#FFF;" colspan="7"><h4 style="text-align: center; margin: 0;">New Order</h4></td></tr>').appendTo("#itemtable");
            $("#tablenumber").val(0).prop('readonly', true);
            $("#radio_takeaway").prop('checked', true);
        }
        $("#paymentstable").html('');
        $(".payamount").val(POSutil.currencyFormat(0));
        $("#salenotes").text("").val('');
        $("#salediscount").val(0);
        $("#discounttxt").text("("+POSutil.currencyFormat(0)+")");
        $("#totaltax").text(POSutil.currencyFormat(0));
        $("#subtotal").text(POSutil.currencyFormat(0));
        $("#total").text(POSutil.currencyFormat(0));
        $("#custemail").val("");
        $("#custid").val(0);
        var emailreccb = $("#emailreceipt");
        emailreccb.prop("checked", false);
        emailreccb.prop("disabled", true);
        $("#custform").trigger('reset');
        POSsales.clearCustUpdate();
        // zero current totals
        cursubtotal = 0.00;
        curtotal = 0.00;
        curround = 0.00;
        curgrandtotal = 0.00;
        curref = null;
        // remove error notice
        $("#invaliditemnotice").hide();
    }

    function getNumSalesItems(){
        return $("#itemtable").children(".valid").length;
    }

    function validateSalesItems(){
        var qty,name, unit, mod, tempprice, tempcost;
        var numinvalid = 0;
        var allow_negative = POSgetConfigTable().pos.negative_items;
        $("#itemtable").children(".item_row").each(function (index, element) {
                qty = parseFloat($(element).find(".itemqty").val());
                name = $(element).find(".itemname").val();
                unit = parseFloat($(element).find(".itemunit").val());
                var itemdata = $(element).find(".itemid").data('options');
                mod = itemdata.hasOwnProperty('mod') ? itemdata.mod.total : 0;
                tempprice = parseFloat("0.00");
                if (qty > 0 && name != "" && (unit>0 || allow_negative)) {
                    // add item modification total to unit price & calculate item total
                    tempprice = qty * (unit + mod);
                    tempcost = qty * itemdata.cost;
                    // calculate item tax
                    var taxruleid = $(element).find(".itemtax").val();
                    var taxdata = POSutil.calcTax(taxruleid, tempprice, tempcost);
                    if (!taxdata.inclusive) {
                        tempprice += taxdata.total;
                    }
                    $(element).find(".itemtaxval").data('taxdata', taxdata);
                    $(element).find(".itemprice").val(tempprice.toFixed(2));
                    // valid item; mark as valid, remove ui indicator class
                    $(element).addClass("valid");
                    $(element).removeClass("danger");
                } else {
                    // not a valid record
                    $(element).removeClass("valid");
                    $(element).addClass("danger");
                    // something is null, set price to 0
                    $(element).find(".itemprice").val("0.00");
                    // increment number invalid
                    numinvalid++;
                }
        });
        // show warning if items invalid
        if (numinvalid>0){
            $("#invaliditemnotice").show();
        } else {
            $("#invaliditemnotice").hide();
        }
    }

    this.showPaymentDialog = function () {
        POSsales.updatePaymentSums();
        if (getNumSalesItems() && curgrandtotal>0){
            // Show integrated eftpos button if enabled
            var inteftbtn = $("#eftpospaybtn");
            if (POShasOwnProperty('eftpos') && POSeftpos.isEnabledAndReady()){
                inteftbtn.show();
                inteftbtn.text(POSutil.capFirstLetter(POSeftpos.getType())+' Eftpos');
            } else {
                inteftbtn.hide();
            }
            $("#paymentsdiv").dialog('open');
            $("#endsalebtn").prop("disabled", false); // make sure the damn button is active, dunno why but when the page reloads it seems to keep its state.
        } else {
            POSnotifications.warning("Please add some valid items to the sale before proceeding!", "No Items");
        }
    };

    this.addAdditionalPayment = function(){
        addPaymentRow('cash', 0, 0, 0);
    };

    this.removePayment = function(pitem){
        $(pitem).parent('td').parent('tr').remove();
        POSsales.updatePaymentSums();
    };

    this.addPayment = function(method){
        if ($("#paymentstable").children('tr').length>0){
            addPaymentRow(method, (-curbalance+(method=="cash"?0:-curround)).toFixed(2), null, null); // don't add sale total
        } else {
            addPaymentRow(method, curgrandtotal.toFixed(2), curgrandtotal.toFixed(2), null);
        }
        POSsales.updatePaymentSums();
    };

    this.addPaymentRowWithData = function(method, value, extraData){
        addPaymentRow(method, value, value, 0, extraData);
        POSsales.updatePaymentSums();
    };

    this.startEftposPayment = function(){
        // find out if cashout supported by provider
        if (POSeftpos.isCashoutSupported){
            showCashoutDialog();
        } else {
            initEftposPayment(0);
        }
    };

    var codialoginit = false;
    function showCashoutDialog(){
        var codialog = $("#codialog");
        if (!codialoginit){
            codialoginit = true;
            codialog.removeClass('hide').dialog({
                maxWidth : 200,
                width : 'auto',
                modal   : true,
                autoOpen: false,
                appendTo: "#paymentsdiv",
                buttons: [
                    {
                        html: "<i class='icon-check bigger-110'></i>&nbsp; Ok",
                        "class": "btn btn-success btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            var cashout =  parseFloat($("#cashoutamount").val()).toFixed(2);
                            if (cashout<0){
                                POSnotifications.warning("Cashout value must be positive or 0", "Invalid Amount");
                                return;
                            }
                            codialog.dialog('close');
                            initEftposPayment(cashout);
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class": "btn btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            codialog.dialog('close');
                        }
                    }
                ],
                create: function( event, ui ) {
                }
            });
        }
        $("#cashoutamount").val(0);
        codialog.dialog('open');
    }

    function initEftposPayment(cashout){
        if ($("#paymentstable").children('tr').length>0){
            POSeftpos.startEftposPayment((-curbalance+-curround).toFixed(2), cashout); // use current balance, remove rounding
        } else {
            POSeftpos.startEftposPayment(curgrandtotal.toFixed(2), cashout);
        }
    }

    this.onPaymentMethodChange = function(elem){
        if($(elem).find(':selected').val()=='cash'){
            $(elem).parent().find('.cashvals').show();
        }else{
            $(elem).parent().find('.cashvals').hide();
        }
        POSsales.updatePaymentSums();
    };

    function addPaymentRow(method, value, tender, change, extraData){
        var exmethod = '';
        if ($.inArray(method, paymentMethods)==-1)
            exmethod = '<option value="'+method+'" selected>'+method+'</option>';
        var data = '';
        if (extraData)
            data = "data-paydata='"+JSON.stringify(extraData)+"'";

        var curBefore = "", curAfter = "";
        if (POSutil.getCurrencyPlacedAfter()){
            curAfter = POSutil.getCurrencySymbol();
        } else {
            curBefore = POSutil.getCurrencySymbol();
        }

        var payrow =  '<tr '+data+'><td>' +
            '<select class="paymethod" onchange="POSsales.onPaymentMethodChange(this);">' +
            '<option value="eftpos" '+(method=='eftpos'?'selected':'')+'>Eftpos</option>' +
            '<option value="credit" '+(method=='credit'?'selected':'')+'>Credit</option>' +
            '<option value="cash" '+(method=='cash'?'selected':'')+'>Cash</option>' +
            '<option value="cheque" '+(method=='cheque'?'selected':'')+'>Cheque</option>' +
            '<option value="deposit" '+(method=='deposit'?'selected':'')+'>Deposit</option>' +
            exmethod+ '</select>' +
            '<div class="cashvals" '+(method!='cash'?'style="display: none"':'width:150px;')+'>' +
            '<div style="width: 100px; display: inline-block;">Tendered:</div><input onChange="POSsales.updatePaymentChange($(this).parent());" class="paytender numpad" style="width:50px;" type="text" value="'+(method!='cash'?0.00:(tender!=null?tender:value))+'" />' +
            '<div style="width: 100px; display: inline-block;">Change:</div><input class="paychange" style="width:50px;" type="text" value="'+(method!='cash'?0.00:(change!=null?change:0.00))+'" readonly />' +
            '</div></td>' +
            '<td>'+curBefore+'<input onChange="POSsales.updatePaymentSums();" class="payamount numpad" style="width:50px;" type="text" value="'+value+'" autocomplete="off"/>'+curAfter+'</td>' +
            '<td><button class="btn btn-xs btn-danger" onclick="POSsales.removePayment($(this));">X</button></td></tr>';

        $("#paymentstable").append(payrow);

        // reinitialize keypad & field listeners
        POSinitKeypad();
    }

    // FUNCTIONS BEFORE ARE FOR PROCESSING ORDERS
    this.saveOrder = function(){
        processOrder();
    };

    function processOrder(){
        var salesobj = getSaleObject();
        var sales_json = JSON.stringify(salesobj);
        if (sales_json.length > 16384) return POSnotifications.error('Too Many Items', 'Transaction Size Limit'); // depends on database field size for sales.data
        if (curref!=null){
            salesobj.ref = curref;
            var cursale = POStrans.getTransactionRecord(curref);
            if (cursale.hasOwnProperty('id')){
                salesobj.id = cursale.id; // make sure we add the id
            }
            removeSalesRecord(curref);
        }
        lasttransref = salesobj.ref;
        salesobj.isorder = true;
        // add to offline table temporarily
        addOfflineSale(salesobj, "orders/set");
        if (POSisOnline()) {
            POSsetStatusBar(2, "Uploading Record...");
            POSsendJsonDataAsync("orders/set", JSON.stringify(salesobj), function(data){ POSsales.postSaleUpload(data, salesobj.ref); });
        } else {
            // update status
            POSsetStatusBar(3, "POSis offline ("+POSsales.getOfflineSalesNum()+" offline records)");
        }
        // close the payment dialog and clear form
        clearSalesForm();
        $("#paymentsdiv").dialog("close");
        // process the orders
        POSorders.processOrder(salesobj, cursale);
    }

    this.loadOrder = function(ref){
        loadOrder(ref);
    };

    this.removeOrder = function(ref){
        POSutil.confirm("Are you sure you want to delete this order?", function() {
            POSutil.showLoader();
            POSsendJsonDataAsync("orders/remove", JSON.stringify({ref: ref}), function(result){

                if (result){
                    var cursale = POStrans.getTransactionRecord(ref);
                    removeSalesRecord(ref);
                    // if the order is loaded we need to clear the sales form
                    if (ref==curref)
                        clearSalesForm();
                    // process the orders
                    POSorders.processOrder(ref, cursale);
                } else {
                    POSnotifications.error("Could not delete the order!", "Order Deletion Failed");
                }
                POSutil.hideLoader();
                POStrans.showTransactionView();
            });
        });
    };

    function loadOrder(ref){
        // get the existing sales object.
        var salesobj = POStrans.getTransactionRecord(ref);
        if (salesobj!=false){
            clearSalesForm();
            $("#itemtable").html('');
            // if order data exists,
            for (var id in salesobj.orderdata){
                var orderdata = salesobj.orderdata[id];
                $('<tr id="order_row_'+id+'" class="order_row" data-data=\''+JSON.stringify(orderdata)+'\'><td style="background-color:#438EB9; color:#FFF;" colspan="7"><h4 style="text-align: center; margin: 0;">Order #'+orderdata.id+'</h4></td></tr>')
                    .appendTo("#itemtable");
            }
            // load items into the table
            var item;
            for (var i in salesobj.items){
                item = salesobj.items[i];
                var data = {
                    desc:   item.desc,
                    ref:    item.ref,
                    orderid:item.orderid
                };
                if (item.hasOwnProperty('mod')) data.mod = item.mod;
                POSitems.addItemRow(item.qty, item.name, item.unit, item.taxid, item.sitemid, data);
            }
            // add a new order row
            if (POSisOrderTerminal())
                $('<tr class="order_row"><td style="background-color:#438EB9; color:#FFF;" colspan="7"><h4 style="text-align: center; margin: 0;">New Order</h4></td></tr>').appendTo("#itemtable");
            // load sale data
            $("#salediscount").val(salesobj.discount);
            $("#salenotes").val(salesobj.salenotes);
            $("#custemail").val(salesobj.custemail);
            $("#custid").val(salesobj.custid);
            if (salesobj.hasOwnProperty("custdata") && typeof salesobj.custdata =="object"){
                var custdata = salesobj.custdata;
                $("#custname").val(custdata.name);
                $("#custphone").val(custdata.phone);
                $("#custmobile").val(custdata.mobile);
                $("#custaddress").val(custdata.address);
                $("#custsuburb").val(custdata.suburb);
                $("#custpostcode").val(custdata.postcode);
                $("#custcountry").val(custdata.country);
                POSsales.setUpdateCust();
            }
            var payment;
            for (i in salesobj.payments){
                payment = salesobj.payments[i];
                var tender, change;
                if (payment.method == "cash"){
                    tender = payment.tender;
                    change = payment.change;
                } else {
                    tender = 0;
                    change = 0
                }
                addPaymentRow(payment.method, payment.amount, tender, change);
            }
            // set the current transaction reference and close dialog
            curref = salesobj.ref;
            POSsales.updateSalesTotal();
            POSsales.updatePaymentSums();
            $("#transactiondiv").dialog('close');
            $("#wrapper").tabs("option", "active", 0);
        } else {
            POSnotifications.error("Could not find the current record.", "Record Not Found");
        }
    }

    // FUNCTIONS BELOW ARE FOR PROCESSING THE SALE
    this.processSale = function () {
        var salebtn = $("#endsalebtn");
        salebtn.prop("disabled", true);
        if (!isSaleBalanced()){
            POSnotifications.warning("Please balance the sale before continuing", "Sale Not Balanced");
            salebtn.prop("disabled", false);
            return;
        }
        if (!validatePayments()){
            POSnotifications.warning("Only cash-out payments may have a negative amount", "Invalid Payment Amount");
            salebtn.prop("disabled", false);
            return;
        }
        ProcessSaleTransaction();
        salebtn.prop("disabled", false);
    };

    var salebalanced = false;

    function isSaleBalanced(){
        return salebalanced;
    }

    function validatePayments(){
        var valid = true;
        $("#paymentstable").children("tr").each(function (index, element) {
            // Make sure payments are positive amounts, except cashout
            if (parseFloat($(element).find(".payamount").val())<0){
                if ($(element).find(".payamount").val()=='cash' && !$(element).data('paydata').hasOwnProperty('cashOut'))
                    valid = false;
            }
        });
        return valid;
    }

    function ProcessSaleTransaction(){
        var salesobj = getSaleObject();
        var sales_json = JSON.stringify(salesobj);
        if (sales_json.length > 16384) return POSnotifications.error('Too Many Items', 'Transaction Size Limit'); // depends on database field size for sales.data

        // check for sale reference, indicating an exiting order and set it's reference onto the new data
        var cursale = null;
        if (curref!==null){
            //alert("Processing using orders existing reference: "+curref);
            cursale = POStrans.getTransactionRecord(curref);
            salesobj.ref = curref;
            delete salesobj.isorder;
            removeSalesRecord(curref);
        }
        // add to offline table temporarily
        addOfflineSale(salesobj, "sales/add");
        lasttransref = salesobj.ref; // set for recall function use
        if (POSisOnline()) {
            POSsetStatusBar(2, "Uploading Record...");
            POSsendJsonDataAsync("sales/add", JSON.stringify(salesobj), function(data){ POSsales.postSaleUpload(data, salesobj.ref); });
        } else {
            // update status
            POSsetStatusBar(3, "POSis offline ("+POSsales.getOfflineSalesNum()+" offline records)");
        }
        var recemailed = $("#emailreceipt").is(":checked");
        // close the payment dialog and clear form (clears current ref aswell)
        clearSalesForm();
        $("#paymentsdiv").dialog("close");
        // open the draw if a cash payment
        for (var i in salesobj.payments){
            if (salesobj.payments[i].method == "cash"){
                POSprint.openCashDraw(true); // opens cash draw if configured
                break;
            }
        }
        // process the orders
        POSorders.processOrder(salesobj, cursale);
        // print receipt or prompt
        var psetting = POSprint.getGlobalPrintSetting('recask');
        if (psetting == "print"){
            POSprint.printReceipt(salesobj.ref);
        } else {
            if (psetting == "email" && recemailed){
                return; // receipt has been emailed
            }
            POSutil.confirm("Would you like to print a receipt?", function() {
                POSprint.printReceipt(salesobj.ref);
            });

        }
    }

    this.postSaleUpload = function(jsonresponse, callbackref){
        if (jsonresponse !== false) {
            // SUCCESS
            // add json response to sales records
            addSalesRecord(jsonresponse);
            // remove from offline temp
            removeOfflineSale(callbackref);
            // check if customer data available for processing
            if (jsonresponse.custdata != undefined){
                jsonresponse.custdata.id = jsonresponse.custid;
                POSupdateCustTable(jsonresponse.custdata);
            }
            // reset status Icon
            POSsetStatusBar(1, "POSis Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        } else {
            // ERROR
            if (POSswitchToOffline()) { // do not store record if offline mode is not supported.
                // update status
                var statusmsg = "The POS is offine and will store sale data locally until a connection becomes available.";
                POSsetStatusBar(3, "POSis offline ("+POSsales.getOfflineSalesNum()+" offline records)", statusmsg, 0);
            } else {
                // remove from offline temp
                removeOfflineSale(callbackref);
            }
        }
    };

    function getSaleObject(){
        // get sales items
        var itemtable = $("#itemtable");
        var date = new Date().getTime();
        var items = [];
        var taxtotals = {};
        var taxdata, itemdata, taxruleid, tempqty, numitems = 0, totalcost = 0;
        var orders = {};
        var oldorders = {};
        var neworderid = null;
        // get orders & load their data
        itemtable.children(".order_row").each(function (index, element) {
            var curorder;
            if ($(element).attr('data-data')) {
                curorder = $(element).data('data');
                oldorders[curorder.id] = $.extend({}, curorder); // save the current order to work out if it's been modified
                curorder.items = {};
            } else {
                curorder = getNewOrderObject(date);
                if (orders.hasOwnProperty(curorder.id)){
                    curorder.id = POSutil.getSequencialOrderNumber(); // avoiding a duplicate order numbers in the same transaction
                }
                neworderid = curorder.id;
            }
            orders[curorder.id] = curorder;
        });
        itemtable.children(".item_row, .valid").each(function (index, element) {
                // add tax information into the tax totals array
                taxdata = $(element).find(".itemtaxval").data('taxdata');
                taxruleid = $(element).find(".itemtax").val();
                for (var i in taxdata.values) {
                    if (!taxtotals.hasOwnProperty(i)) {
                        taxtotals[i] = 0;
                    }
                    taxtotals[i] += taxdata.values[i];
                }
                // add # items to total
                tempqty = parseFloat($(element).find(".itemqty").val());
                numitems += tempqty;
                // add item to the array
                var data = {
                    "ref": POSutil.getRandomId(), // use index as reference for this sale item,
                    "sitemid": $(element).find(".itemid").val(),
                    "qty": tempqty,
                    "name": $(element).find(".itemname").val(),
                    "unit": parseFloat($(element).find(".itemunit").val()).toFixed(2),
                    "taxid": taxruleid,
                    "tax": taxdata,
                    "price": parseFloat($(element).find(".itemprice").val()).toFixed(2)
                };
                itemdata = $(element).find(".itemid").data('options');
                for (var x in itemdata) {
                    data[x] = itemdata[x];
                }
                if (data.cost>0)
                    totalcost += (data.cost*data.qty);
                items.push(data);

                if (POSisOrderTerminal()){
                    // if order id is undefined, add to the new order
                    if (!data.hasOwnProperty('orderid')) {
                        data.orderid = neworderid;
                    }
                    // add referece to current order item; store the index for quick access to it's data, the index may change but the id will remain the same.
                    orders[data.orderid].items[data.ref] = index;
                }
        });

        // cycle through orders & match the old order items to the new, if they don't match, update the moddt
        if (POSisOrderTerminal())
            for (var o in orders){
                // check number of items & remove if 0
                if (Object.keys(orders[o].items).length==0) {
                    delete orders[o];
                } else {
                    console.log(oldorders);
                    // We can determine if the order has changed if the ids have changed, if so we set the order modified flag.
                    if (oldorders.hasOwnProperty(o)) {
                        if (Object.keys(orders[o].items).sort().join(',')!=Object.keys(oldorders[o].items).sort().join(',')){
                            orders[o].moddt = date;
                        } else {
                            // otherwise we need to check against each value
                            var olditems = POStrans.getTransactionRecord(curref).items;
                            for (var index in orders[o].items) {
                                if (!POSutil.areObjectsEquivalent(items[orders[o].items[index]], olditems[orders[o].items[index]])) {
                                    console.log("order not equivalent, updating moddt");
                                    orders[o].moddt = date;
                                }
                            }
                        }
                    }
                }
            }

        // gather payments
        var payments = [];
        $("#paymentstable").children("tr").each(function (index, element) {
            var payment = {"method": $(element).find(".paymethod option:selected").val(), "amount": parseFloat($(element).find(".payamount").val()).toFixed(2) };
            if (payment.method == 'cash'){
                payment.tender = parseFloat($(element).find(".paytender").val()).toFixed(2);
                payment.change = parseFloat($(element).find(".paychange").val()).toFixed(2);

            }
            if ($(element).data('paydata'))
                payment.paydata = $(element).data('paydata');
            payments.push(payment);
        });

        // create object
        var salesobj = {};
        var config = POSgetConfigTable();
        salesobj.ref = date + "-" + config.deviceid + "-" + Math.floor((Math.random() * 10000) + 1);
        salesobj.userid = POSgetCurrentUserId();
        salesobj.devid = config.deviceid;
        salesobj.locid = config.locationid;
        salesobj.custid = $("#custid").val();
        salesobj.custemail = $("#custemail").val();
        salesobj.notes = $("#salenotes").val();
        salesobj.discount = $("#salediscount").val();
        salesobj.rounding = curround.toFixed(2);
        salesobj.cost = parseFloat(totalcost).toFixed(2);
        salesobj.subtotal = cursubtotal.toFixed(2);
        salesobj.total = parseFloat(curgrandtotal).toFixed(2);
        salesobj.numitems = numitems;
        salesobj.processdt = date;
        salesobj.items = items;
        salesobj.payments = payments;
        // add tax information
        salesobj.tax = curtaxtotal.toFixed(2);
        for (var i in taxtotals){
            taxtotals[i] = taxtotals[i].toFixed(2);
        }
        salesobj.taxdata = taxtotals;

        // is customer data needed
        if (updatecust){
            salesobj.custdata = getCustomerObject();
            updatecust = false; // reset flag
        }

        // if customer wants the receipt send, set the flag
        if ($("#emailreceipt").is(":checked")){
            salesobj.emailrec = true;
        }

        // add order data to the record
        if (POSisOrderTerminal())
            salesobj.orderdata = orders;

        return salesobj;
    }

    function getNewOrderObject(date){
        return {
            id:         POSutil.getSequencialOrderNumber(),
            items:      {},
            processdt:  date,
            received:   false,
            tablenum:   $("#tablenumber").val()
        };
    }

    function getCustomerObject(){
        var custdata = {};

        custdata.id = $('#custid').val();
        custdata.name = $('#custname').val();
        custdata.email = $('#custemail').val();
        custdata.phone = $('#custphone').val();
        custdata.mobile = $('#custmobile').val();
        custdata.address = $('#custaddress').val();
        custdata.suburb = $('#custsuburb').val();
        custdata.postcode = $('#custpostcode').val();
        custdata.country = $('#custcountry').val();

        return custdata;
    }

    var updatecust = false; // flag indicating customer details need updating/adding
    this.setUpdateCust = function(){ // used to indicate new customer data
        updatecust = true;
    };
    this.clearCustUpdate = function(){ // used when the customer email field is changed
        updatecust = false;
    };

    this.openRefundDialog = function(ref){
        $("#voidform").hide();
        $("#refundform").show();
        $("#refundref").val(ref);
        var refamtinput = $("#refundamount");
        refamtinput.val(0);
        refamtinput.removeData('paydata'); // remove extra payment data
        var formdiv = $("#formdiv");
        formdiv.dialog("option", "title", "Refund transaction");
        var voidbtn = $("#procvoidbtn");
        voidbtn.attr("onclick", 'POSsales.processRefund();');
        voidbtn.prop('disabled', false);
        // populate items
        var sale = POStrans.getTransactionRecord(ref);
        var items = sale.items;
        var refitems = $("#refunditems");
        refitems.html('');
        var itemid;
        for (var i = 0; i<items.length; i++){
            // calc how many current items have already been refunded
            itemid = items[i].id;
            var refnum = 0;
            // loop though each refund
            for (var key in sale.refunddata){
                var ritems = sale.refunddata[key].items;
                // loop through items of the refund and add to total if the id equals
                for (var key1 in ritems){
                    if(!ritems[key1].id && ritems[key1].ref){
                        refnum += (ritems[key1].ref == items[i].ref?(parseInt(ritems[key1].numreturned) || 1):0);
                        continue;
                    }
                    refnum += (ritems[key1].id == itemid?(parseInt(ritems[key1].numreturned) || 1):0);
                }
            }
            refitems.append('<tr>' +
                '<td><input size="4" class="refundqty" type="number" value="0" onchange="POSsales.validateRefund();" autocomplete="off"/>' +
                '<input class="refunditemref" type="hidden" value="'+(items[i].hasOwnProperty('ref')?items[i].ref:0)+'"/>' +
                '<input class="refunditemid" type="hidden" value="'+(items[i].hasOwnProperty('id')?items[i].id:0)+'"/>' + // temp fix for old db records not containing item ref
                '<input class="refundsqty" type="hidden" value="'+(parseInt(items[i].qty)-refnum)+'"/>' +
                '<input class="refundsunit" type="hidden" value="'+(items[i].price/items[i].qty)+'"/></td>' +
                '<td>'+items[i].qty+' x '+items[i].name+' ($'+items[i].price+')</td>' +
            '</tr>');
        }
        var eftbtn = $("#eftposrefundbtn");
        if (POShasOwnProperty('eftpos') && POSeftpos.isEnabledAndReady()){
            eftbtn.show();
        } else {
            eftbtn.hide();
        }
        formdiv.dialog('open');
    };

    this.addRefundPaymentData = function(method, amount, data){
        var refamount = $("#refundamount");
        refamount.data('paydata', data);
        refamount.val(amount);
        var refmethodsel = $("#refundmethod");
        refmethodsel.append('<option value="'+method+'">'+method+'</option>');
        refmethodsel.val(method);
    };

    this.validateRefund = function(){
        var refundamt = 0;
        $("#refunditems").children("tr").each(function(index, item){
            var refundqty = parseInt($(item).find('.refundqty').val());
            var netqty = parseInt($(item).find('.refundsqty').val());
            // check if the amount is larger than bought qty
            if (refundqty>netqty){
                POSnotifications.warning("Cannot return more items than sold + returned!", "Invalid Return Quantity");
                $(item).find('.refundqty').val(netqty);
                return false;
            }
            if (refundqty>0){
                var boughtunit = $(item).find('.refundsunit').val();
                // calculate refund amount and add to field
                refundamt += parseFloat(boughtunit) * parseFloat(refundqty);
            }
            return true;
        });
        $("#refundamount").val(refundamt.toFixed(2));
        return true;
    };

    this.openVoidDialog = function(ref){
        $("#refundform").hide();
        $("#voidform").show();
        $("#voidref").val(ref);
        var formdiv = $("#formdiv");
        var voidbtn = $("#procvoidbtn");
        formdiv.dialog("option", "title", "Void transaction");
        voidbtn.attr("onclick", 'POSsales.processVoid();');
        voidbtn.prop('disabled', false);
        formdiv.dialog('open');
    };

    this.processVoid = function(){
        // do not allow for sales with integrated eftpos transactions
        var ref = $("#voidref").val();
        var trans = POStrans.getTransactionRecord(ref);
        for (var i in trans.payments){
            if (trans.payments[i].method=="tyro"){
                POSnotifications.warning("Sales with Eftpos transactions cannot be voided. Refund this transaction instead.", "Void Not Allowed");
                return;
            }
        }

        if ($("#voidreason").val()==""){
            POSnotifications.warning("Reason must not be blank.", "Missing Information");
            return;
        }
        POSutil.confirm("Are you sure you want to void this transaction?", function() {
            $("#procvoidbtn").prop('disabled', true);
            processVoidTransaction(ref, false);
            $("#formdiv").dialog('close');
            lasttransref = ref;
            // update transaction info
            POStrans.populateTransactionInfo(ref);
        });
    };

    this.processRefund = function(){
        if ($("#refundreason").val()==""){
            POSnotifications.warning("Reason must not be blank.", "Missing Information");
            return;
        }
        if ($("#refundamount").val()<=0){
            POSnotifications.warning("Amount must be larger than 0.", "Invalid Amount");
            return;
        }
        var ref = $("#refundref").val();
        POSutil.confirm("Are you sure you want to refund this transaction?", function() {
            $("#procvoidbtn").prop('disabled', true);
            processVoidTransaction(ref, true);
            $("#formdiv").dialog('close');
            lasttransref = ref;
            // update transaction info
            POStrans.populateTransactionInfo(ref);
        });
    };

    this.eftposRefund = function(){
        var ref = $("#refundref").val();
        $("#formdiv").dialog('close');
        processVoidTransaction(ref, true);
        lasttransref = ref;
        // update transaction info
        POStrans.populateTransactionInfo(ref);
    };

    function processVoidTransaction(ref, isrefund){
        var refundobj;
        // get current sale object
        if (isSaleOffline(ref)){
            refundobj = getVoidObject(ref, isrefund);
            // update the record in the offline table
            addUpdatedOfflineRecord(false, refundobj, "sales/void");
            return true;
        } else {
            refundobj =  getVoidObject(ref, isrefund);
        }
        if (POSisOnline()){
            POSsetStatusBar(2, "Uploading Record...");
            POSsendJsonDataAsync("sales/void", JSON.stringify(refundobj), function(data){ POSsales.postVoidUpload(data); });
        } else {
            addUpdatedOfflineRecord(true, refundobj, "sales/void"); // update records
            // update status
            POSsetStatusBar(3, "POSis offline ("+POSsales.getOfflineSalesNum()+" offline records)");
        }
        if (isrefund){
            // open the draw if a cash payment
            if (refundobj.refunddata[refundobj.refunddata.length-1].method == "cash"){
                POSprint.openCashDraw(true); // opens cash draw if configured
            }
            // print receipt or prompt
            if (POSgetLocalConfig().recask == "print"){
                POSprint.printReceipt(refundobj.ref);
            } else {
                POSutil.confirm("Would you like to print a receipt?", function() {
                    POSprint.printReceipt(refundobj.ref);
                });
            }
        }
        return true;
    }

    this.postVoidUpload = function(jsonresponse, refundobject){
        if (jsonresponse !== false) {
            // SUCCESS
            // add json response to sales records
            addSalesRecord(jsonresponse);
            // reset status Icon
            POSsetStatusBar(1, "POSis Online");
        } else {
            // ERROR
            if (POSswitchToOffline()) { // do not store record if offline mode is not supported.
                addUpdatedOfflineRecord(true, refundobject, "sales/void"); // update records
                // update status
                POSsetStatusBar(3, "POSis offline ("+POSsales.getOfflineSalesNum()+" offline records)");
            }
        }
    };

    function getVoidObject(ref, refund){
        var refundobj;
        var date = new Date().getTime();
        var config = POSgetConfigTable();
        // get data from the trans object, it holds offline + remotely loaded transactions
        refundobj = POStrans.getTransactionRecord(ref);
        // add refund/void shared data
        var shareddata = {"userid": POSgetCurrentUserId(), "deviceid": config.deviceid, "locationid": config.locationid, "processdt": date};
        // add specific data
        if (refund){
            // if refund data is not defined, create an array
            if (refundobj.refunddata == null){
                refundobj.refunddata = [];
            }
            // add refund specific values to shared data
            shareddata.reason = $("#refundreason").val();
            var items = [];
            // get returned items
            var numreturned;
            $("#refunditems").children("tr").each(function(index, item){
                numreturned = $(item).find('.refundqty').val();
                if (numreturned>0){
                    var ref = $(item).find('.refunditemref').val();
                    if (ref!=0){
                        items.push({"ref": ref, "numreturned": numreturned});
                    } else {
                        items.push({"ref": $(item).find('.refunditemid').val(), "numreturned": numreturned});
                    }
                }
            });
            var refamtinput = $("#refundamount");
            shareddata.items = items;
            shareddata.method = $("#refundmethod").val();
            shareddata.amount = parseFloat(refamtinput.val()).toFixed(2);
            if (refamtinput.data('paydata'))
                shareddata.paydata = refamtinput.data('paydata');
            // add to refund array
            refundobj.refunddata.push(shareddata);
        } else {
            refundobj.voiddata = shareddata;
            refundobj.voiddata.reason = $("#voidreason").val();
        }

        return refundobj;
    }

    function addUpdatedOfflineRecord(removefromsales, jsondata, action){
        // add record to offline table and remove from the sales table
        if (addOfflineSale(jsondata, action)){
            if (removefromsales) removeSalesRecord(jsondata.ref);
        } else {
            POSnotifications.error("Failed to update the record in offline storage, the sale has not been updated.", "Offline Storage Error");
        }
    }

    // THE FOLLOWING FUNCTIONS ARE ALL RELATED TO SALES TABLES IN LOCAL STORAGE.
    /**
     *
     * @param {Object} jsonobj
     * @returns {number|*}
     */
    function addSalesRecord(jsonobj) {
        // add to java object
        POSupdateSalesTable(jsonobj.ref, jsonobj);
        // save to local storage
        var jsonsales;
        if (localStorage.getItem("wpos_csales") !== null) {
            jsonsales = $.parseJSON(localStorage.getItem("wpos_csales"));
            jsonsales[jsonobj.ref] = jsonobj;
        } else {
            jsonsales = {};
            jsonsales[jsonobj.ref] = jsonobj;
        }
        localStorage.setItem("wpos_csales", JSON.stringify(jsonsales));
        return true;
    }

    function removeSalesRecord(ref){
        // remove from local storage
        var jsonsales;
        if (localStorage.getItem("wpos_csales") !== null) {
            jsonsales = $.parseJSON(localStorage.getItem("wpos_csales"));
            delete jsonsales[ref];
        }
        localStorage.setItem("wpos_csales", JSON.stringify(jsonsales));
        return true;
    }

    /**
     *
     * @returns {*}
     */
    this.getOfflineSalesNum = function () {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            return Object.keys(jsonosales).length;
        } else {
            return 0;
        }
    };
    this.getOfflineSales = function (){
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            // strip the action variable so the data is returned in the same format as normal sales table'
            for (var i in jsonosales){
                jsonosales[i] = jsonosales[i].data;
            }
            return jsonosales;
        } else {
            return {};
        }
    };
    /**
     * @param {Object} jsonobj
     * @param {String} action
     * @returns {number|*}
     */
    this.updateOfflineSale = function(jsonobj, action){
        return addOfflineSale(jsonobj, action);
    };
    function addOfflineSale(jsonobj, action) {
        var jsonosales;
        if (localStorage.getItem("wpos_osales") !== null) {
            jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
        } else {
            jsonosales = {};
        }
        // If we wanted to allow multiple actions per record, we can change action to an array.
        jsonosales[jsonobj.ref] = {};
        jsonosales[jsonobj.ref].a = action;
        jsonosales[jsonobj.ref].data = jsonobj;
        localStorage.setItem("wpos_osales", JSON.stringify(jsonosales));
        return true;
    }

    this.isSaleOffline = function(ref){
        return isSaleOffline(ref);
    };

    function isSaleOffline(ref){
        var jsonosales;
        if (localStorage.getItem("wpos_osales") !== null) {
            jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            if (jsonosales.hasOwnProperty(ref)){
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param {String} ref
     * @returns {boolean}
     */
    function removeOfflineSale(ref) {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            delete jsonosales[ref];
            localStorage.setItem("wpos_osales", JSON.stringify(jsonosales));
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    this.uploadOfflineRecords = function(){
        return uploadOfflineRecords();
    };
    function uploadOfflineRecords() {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            var jsonresponse;
            var uploadcount = 1;
            var totalcount = Object.keys(jsonosales).length;
            for (var key in jsonosales){
                // update status
                POSsetStatusBar(2, "Uploading record "+uploadcount+" of "+totalcount);
                // what action to perform to the offline record is kept in the a var
                var action = jsonosales[key].a;
                if (action=="sales/void"){
                    action = (jsonosales[key].data.hasOwnProperty("id")?"sales/void":"sales/add"); // if sale is completely offline, use addsale method.
                }
                jsonresponse = POSsendJsonData(action, JSON.stringify(jsonosales[key].data));
                if (jsonresponse !== false && jsonresponse !== null) {
                    // remove from offline temp
                    removeOfflineSale(jsonosales[key].data.ref);
                    // add json response to todays records
                    addSalesRecord(jsonresponse);
                }
                uploadcount++;
            }
            var count = POSsales.getOfflineSalesNum();
            if (count > 0){
                // damn so close, go back into offline mode
                if (POSswitchToOffline()) {
                    // update status
                    POSsetStatusBar(3, "POSis offline ("+count+" offline records)");
                    return false;
                }
            } else {
                $("#backup_btn").hide();
                return true;
            }
        }
        return true;
    }
}
