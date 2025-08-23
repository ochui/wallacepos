/**
 *
 * transactions.js Provides functions to view and manage past transactions, as well as UI functionality for refunds/voids.
 *
 */
var datatable;
function WPOSTransactions() {
  var transdialog = $("#transactiondiv");

  this.showTransactionView = function () {
    $("#wrapper").tabs("option", "active", 1);
    this.setupTransactionView();
  };

  this.setupTransactionView = function () {
    loadLocalTransactions();
    datatable.api().responsive.recalc();
  };

  this.showTransactionInfo = function (ref) {
    populateTransactionInfo(ref);
    transdialog.dialog("open");
    repositionDialog();
  };

  function repositionDialog() {
    transdialog.dialog({
      position: { my: "center", at: "center" },
    });
  }

  this.recallLastTransaction = function () {
    var lastref = POS.sales.getLastRef();
    if (lastref == null) {
      POS.notifications.info("No transactions yet for this session.", "No Transactions");
      return;
    }
    POS.trans.showTransactionInfo(lastref);
    transdialog.dialog("open");
  };

  var tableData = [];

  datatable = $("#transactiontable").dataTable({
    bProcessing: true,
    aaData: tableData,
    aaSorting: [[5, "desc"]],
    aoColumns: [
      {
        sType: "string",
        mData: function (data, type, val) {
          return getOfflineStatusHtml(data.ref) + (data.hasOwnProperty("id") ? "<br/>" + data.id : "");
        },
      },
      {
        sType: "numeric",
        mData: function (data, type, val) {
          return '<a class="reflabel" title="' + data.ref + '" href="">' + data.ref.split("-")[2] + "</a>";
        },
      },
      {
        sType: "string",
        mData: function (data, type, val) {
          return getDeviceLocationText(data.devid, data.locid);
        },
      },
      { sType: "numeric", mData: "numitems" },
      {
        sType: "currency",
        mData: function (data, type, val) {
          return POS.util.currencyFormat(data["total"]);
        },
      },
      {
        sType: "timestamp",
        mData: function (data, type, val) {
          return datatableTimestampRender(type, data.processdt, POS.util.getDateFromTimestamp);
        },
      },
      {
        sType: "html",
        mData: function (data, type, val) {
          return getStatusHtml(getTransactionStatus(data.ref));
        },
      },
      {
        sType: "html",
        mData: function (data, type, val) {
          return "<button class='btn btn-sm btn-primary' onclick='POS.trans.showTransactionInfo(" + '"' + data.ref + '"' + ")'>View</button>";
        },
        bSortable: false,
      },
    ],
    columns: [{}, {}, {}, {}, {}, {}, {}, { width: "52px" }],
  });

  function loadIntoTable(sales) {
    tableData = [];
    for (var key in sales) {
      tableData.push(sales[key]);
    }
    datatable.fnClearTable(false);
    datatable.fnAddData(tableData, false);
    datatable.api().draw(false);
  }

  function getDeviceLocationText(deviceid, locationid) {
    var text = "";
    text += POS.getConfigTable().devices.hasOwnProperty(deviceid) ? POS.getConfigTable().devices[deviceid].name : "Unknown";
    text += " / " + (POS.getConfigTable().locations.hasOwnProperty(locationid) ? POS.getConfigTable().locations[locationid].name : "Unknown");
    return text;
  }

  function getOfflineStatusHtml(ref) {
    var syncstat;
    if (POS.sales.getOfflineSales().hasOwnProperty(ref)) {
      if (POS.sales.getOfflineSales()[ref].hasOwnProperty("id")) {
        syncstat = 2;
      } else {
        syncstat = 1;
      }
    } else {
      syncstat = 3;
    }
    var ostathtml;
    switch (syncstat) {
      case 1:
        ostathtml = '<span class="label label-sm label-warning arrowed">offline</span>';
        break;
      case 2:
        ostathtml = '<span class="label label-sm label-warning arrowed">partial</span>';
        break;
      case 3:
        ostathtml = '<span class="label label-sm label-primary arrowed">synced</span>';
        break;
    }
    return ostathtml;
  }

  function getStatusHtml(status) {
    var stathtml;
    switch (status) {
      case 0:
        stathtml = '<span class="label label-primary arrowed">Order</span>';
        break;
      case 1:
        stathtml = '<span class="label label-success arrowed">Complete</span>';
        break;
      case 2:
        stathtml = '<span class="label label-danger arrowed">Void</span>';
        break;
      case 3:
        stathtml = '<span class="label label-warning arrowed">Refunded</span>';
        break;
      default:
        stathtml = '<span class="label arrowed">Unknown</span>';
        break;
    }
    return stathtml;
  }

  this.getTransactionRecord = function (ref) {
    return getTransactionRecord(ref);
  };

  function getTransactionRecord(ref) {
    if (POS.getSalesTable().hasOwnProperty(ref)) {
      return POS.getSalesTable()[ref];
    } else if (POS.hasOwnProperty("sales") && POS.sales.getOfflineSales().hasOwnProperty(ref)) {
      // check offline sales
      return POS.sales.getOfflineSales()[ref];
    } else if (remtrans.hasOwnProperty(ref)) {
      // check in remote transaction table
      return remtrans[ref];
    } else {
      return false;
    }
  }

  this.populateTransactionInfo = function (ref) {
    populateTransactionInfo(ref);
  };

  function populateTransactionInfo(ref) {
    var record = getTransactionRecord(ref);
    var status = getTransactionStatus(ref);
    if (record === false) {
      POS.notifications.error("Could not find the transaction record!", "Record Not Found");
    }
    // set values in info div
    $("#transstat").html(getStatusHtml(status));
    $("#transref").text(ref);
    $("#transid").text(record.id);
    $("#transtime").text(POS.util.getDateFromTimestamp(record.processdt));
    $("#transptime").text(record.dt);
    var config = POS.getConfigTable();
    $("#transuser").text(config.users.hasOwnProperty(record.userid) ? config.users[record.userid].username : "NA");
    $("#transdev").text(config.devices.hasOwnProperty(record.devid) ? config.devices[record.devid].name : "NA");
    $("#transloc").text(config.locations.hasOwnProperty(record.locid) ? config.locations[record.locid].name : "NA");
    $("#transnotes").val(record.salenotes);

    $("#transsubtotal").text(POS.util.currencyFormat(record.subtotal));
    populateTaxinfo(record);
    if (record.discount > 0) {
      $("#transdiscount").text(
        record.discount + "% (" + POS.util.currencyFormat((parseFloat(record.total) - Math.abs(parseFloat(record.subtotal) + parseFloat(record.tax))).toFixed(2)) + ")"
      );
      $("#transdisdiv").show();
    } else {
      $("#transdisdiv").hide();
    }
    $("#transtotal").text(POS.util.currencyFormat(record.total));

    populateItemsTable(record.items);
    populatePaymentsTable(record.payments);
    if (status > 1) {
      $("#voidinfo").show();
      $("#orderbuttons").hide();
      if (status == 2) {
        // hide buttons if void
        $("#voidbuttons").hide();
      } else {
        $("#voidbuttons").show();
      }
      // populate void/refund list
      populateRefundTable(record);
    } else {
      if (status == 0) {
        $("#voidbuttons").hide();
        $("#orderbuttons").show();
      } else {
        $("#orderbuttons").hide();
        $("#voidbuttons").show();
      }
      $("#voidinfo").hide();
    }
  }

  function populateTaxinfo(record) {
    var transtax = $("#transtax");
    transtax.html("");
    var taxitems = POS.getTaxTable().items;
    if (record.hasOwnProperty("taxdata")) {
      for (var i in record.taxdata) {
        transtax.append('<label class="fixedlabel">' + taxitems[i].name + " (" + taxitems[i].value + "%):</label><span>" + POS.util.currencyFormat(record.taxdata[i]) + "</span><br/>");
      }
    }
  }

  function populateItemsTable(items) {
    var itemtable = $("#transitemtable");
    $(itemtable).html("");
    var taxitems = POS.getTaxTable().items;
    for (var i = 0; i < items.length; i++) {
      // tax details
      var taxStr = "";
      for (var x in items[i].tax.values) {
        taxStr += POS.util.currencyFormat(items[i].tax.values[x]) + " (" + taxitems[x].name + " " + taxitems[x].value + "%) <br/>";
      }
      if (taxStr == "") taxStr = POS.util.currencyFormat(0.0);
      // item mod details
      var modStr = "";
      if (items[i].hasOwnProperty("mod")) {
        for (x = 0; x < items[i].mod.items.length; x++) {
          var mod = items[i].mod.items[x];
          modStr +=
            "<br/>" +
            (mod.hasOwnProperty("qty") ? (mod.qty > 0 ? "+ " : "") + mod.qty : "") +
            " " +
            mod.name +
            (mod.hasOwnProperty("value") ? ": " + mod.value : "") +
            " (" +
            POS.util.currencyFormat(mod.price) +
            ")";
        }
      }
      $(itemtable).append(
        "<tr><td>" +
          items[i].qty +
          "</td><td>" +
          items[i].name +
          modStr +
          "</td><td>" +
          POS.util.currencyFormat(items[i].unit) +
          "</td><td>" +
          taxStr +
          "</td><td>" +
          POS.util.currencyFormat(items[i].price) +
          "</td></tr>"
      );
    }
  }

  function populatePaymentsTable(payments) {
    var paytable = $("#transpaymenttable");
    $(paytable).html("");
    var method, amount;
    for (var i = 0; i < payments.length; i++) {
      // catch extras
      method = payments[i].method;
      amount = payments[i].amount;
      var paydetailsbtn = "";
      if (payments[i].hasOwnProperty("paydata")) {
        // check for integrated payment details
        if (payments[i].paydata.hasOwnProperty("transRef")) {
          console.log(payments[i].paydata);
          paydetailsbtn = "<button onclick='POS.trans.showPaymentInfo(this);' class='btn btn-xs btn-primary' data-paydata='" + JSON.stringify(payments[i].paydata) + "'>Details</button>";
        }
        // catch cash-outs
        if (payments[i].paydata.hasOwnProperty("cashOut")) {
          method = "cashout (" + POS.util.currencyFormat((-amount).toFixed(2)) + ")";
        }
      }
      $(paytable).append("<tr><td>" + POS.util.capFirstLetter(method) + "</td><td>" + POS.util.currencyFormat(amount) + '</td><td style="text-align: right;">' + paydetailsbtn + "</td></tr>");
    }
  }

  var curtransref;

  function populateRefundTable(record) {
    curtransref = record.ref;
    var refundtable = $("#transvoidtable");
    $(refundtable).html("");
    if (record.refunddata !== undefined) {
      var tempdata;
      for (var i = 0; i < record.refunddata.length; i++) {
        tempdata = record.refunddata[i];
        $(refundtable).append(
          '<tr><td><span class="label label-warning arrowed">Refund</span></td><td>' +
            POS.util.getDateFromTimestamp(tempdata.processdt) +
            '</td><td><button class="btn btn-sm btn-primary" onclick="POS.trans.showRefundDialog(' +
            i +
            ');">View</button></td></tr>'
        );
      }
    }
    if (record.voiddata !== undefined && record.voiddata !== null) {
      $(refundtable).append(
        '<tr><td><span class="label label-danger arrowed">Void</span></td><td>' +
          POS.util.getDateFromTimestamp(record.voiddata.processdt) +
          '</td><td><button class="btn btn-sm btn-primary" onclick="POS.trans.showVoidDialog();">View</button></td></tr>'
      );
    }
  }

  function getVoidData(ref, isrefund) {
    var record;
    record = getTransactionRecord(ref);
    if (isrefund) {
      return record.refunddata;
    } else {
      return record.voiddata;
    }
  }

  function populateSharedVoidData(record) {
    $("#transreftime").text(POS.util.getDateFromTimestamp(record.processdt));
    var config = POS.getConfigTable();
    $("#transrefuser").text(config.users.hasOwnProperty(record.userid) ? config.users[record.userid].username : "NA");
    $("#transrefdev").text(config.devices.hasOwnProperty(record.deviceid) ? config.devices[record.deviceid].name : "NA");
    $("#transrefloc").text(config.locations.hasOwnProperty(record.locationid) ? config.locations[record.locationid].name : "NA");
    $("#transrefreason").text(record.reason);
  }

  this.showVoidDialog = function () {
    populateVoidData(curtransref);
    $("#refunddetails").hide(); // the dialog is used for refunds too, hide that view
    var voiddiv = $("#voiddiv");
    voiddiv.dialog("option", "title", "Void Details");
    voiddiv.dialog("open");
  };

  function populateVoidData(ref) {
    var record;
    record = getVoidData(ref, false);
    populateSharedVoidData(record);
  }

  this.showRefundDialog = function (refundindex) {
    populateRefundData(curtransref, refundindex);
    $("#refunddetails").show(); // show the refund only view.
    var voiddiv = $("#voiddiv");
    voiddiv.dialog("option", "title", "Refund Details");
    voiddiv.dialog("open");
  };

  function populateRefundData(ref, refundindex) {
    var record;
    record = getVoidData(ref, true);
    record = record[refundindex]; // get the right refund record from the array
    populateSharedVoidData(record);
    $("#transrefmethod").text(record.method);
    $("#transrefamount").text(POS.util.currencyFormat(record.amount));
    // show payment details button if available
    var dtlbtn = $("#refpaydtlbtn");
    if (record.hasOwnProperty("paydata")) {
      dtlbtn.removeClass("hide");
      //console.log(record.paydata);
      dtlbtn.data("paydata", record.paydata);
    } else {
      dtlbtn.addClass("hide");
    }
    // populate refunded items
    var treftable = $("#transrefitemtable");
    treftable.html("");
    for (var i = 0; i < record.items.length; i++) {
      treftable.append("<tr><td>" + getSaleItemData(ref, record.items[i].ref, record.items[i].id).name + "</td><td>" + record.items[i].numreturned + "</td></tr>");
    }
  }

  this.showPaymentInfo = function (btn) {
    // the data is already stored in a HTML5 data element
    console.log($(btn).data("paydata"));
    showEftPaymentDialog($(btn).data("paydata"));
  };

  var paydialoginit = false;
  function showEftPaymentDialog(object) {
    var paydialog = $("#eftdetailsdialog");
    if (!paydialoginit) {
      paydialoginit = true;
      paydialog.removeClass("hide").dialog({
        maxWidth: 200,
        width: "auto",
        modal: true,
        autoOpen: false,
        buttons: [
          {
            html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
            class: "btn btn-xs",
            click: function () {
              $(".keypad-popup").hide();
              paydialog.dialog("close");
            },
          },
        ],
        create: function (event, ui) {},
      });
    }
    $("#efttransref").text(object.transRef);
    $("#efttranscard").text(object.cardType);
    $("#eftcustrec").text(object.customerReceipt);
    $("#eftmerchrec").text(object.merchantReceipt);
    paydialog.dialog("open");
  }

  function getSaleItemData(ref, itemref, itemid) {
    var items = getTransactionRecord(ref).items;
    for (var key in items) {
      if (items[key].ref == itemref) {
        return items[key];
      } else if (items[key].id == itemid) {
        return items[key];
      }
    }
    return false;
  }

  function clearTransactions() {
    transtable = {};
  }

  var transtable = {};

  function loadLocalTransactions() {
    clearTransactions();
    var salestable = POS.getSalesTable();
    // Populate synced records
    for (var ref in salestable) {
      transtable[ref] = salestable[ref];
    }
    // Populate offline records
    if (POS.hasOwnProperty("sales") && POS.sales.getOfflineSalesNum() > 0) {
      var olsales = POS.sales.getOfflineSales();
      //var syncstat, gid;
      for (ref in olsales) {
        // add to the transaction info table
        delete transtable[ref];
        transtable[ref] = olsales[ref];
      }
    }
    // load into datatables
    loadIntoTable(transtable);
  }

  function getTransactionStatus(ref) {
    var record = getTransactionRecord(ref);
    if (record.hasOwnProperty("voiddata")) {
      return 2;
    } else if (record.hasOwnProperty("refunddata")) {
      // refund
      return 3;
    } else if (record.hasOwnProperty("isorder")) {
      return 0;
    }
    return 1;
  }

  this.searchRemote = function () {
    var searchdata = {};
    var refinput = $("#remsearchref");
    if (refinput.val() != "") {
      searchdata.ref = refinput.val();
    }
    if (Object.keys(searchdata).length > 0) {
      searchRemoteTransactions(searchdata);
    } else {
      POS.notifications.warning("Please select at least one search option.", "Search Options Required");
    }
  };

  var remtrans = {};

  function searchRemoteTransactions(searchdata) {
    POS.sendJsonDataAsync("sales/search", JSON.stringify(searchdata), function (result) {
      if (result !== false) {
        loadIntoTable(remtrans);
        repositionDialog();
      }
    });
  }

  this.clearSearch = function () {
    loadIntoTable(transtable);
    $("#remsearchref").val("");
    repositionDialog();
  };

  this.updateSaleNotes = function () {
    if (POS.isOnline()) {
      updateSaleNotes();
    } else {
      // TODO: update notes and misc info offline
      POS.notifications.warning("Updating notes offline is not supported at this time\nsorry for the inconvenience", "Offline Limitation");
    }
  };

  function updateSaleNotes() {
    POS.util.confirm("Save sale notes?", function () {
      // show loader
      POS.util.showLoader();
      var ref = $("#transref").text();
      var notes = $("#transnotes").val();
      POS.sendJsonDataAsync("sales/updatenotes", JSON.stringify({ ref: ref, notes: notes }), function (result) {
        if (result !== false) {
          // update local copy
          var sale = POS.trans.getTransactionRecord(ref);
          if (sale != false) {
            // set new notes
            sale.salenotes = notes;
            if (POS.sales.isSaleOffline(ref) === true) {
              POS.sales.updateOfflineSale(sale, "sales/updatenotes");
            } else {
              POS.updateSalesTable(ref, sale);
            }
          }
        }
        // hide loader
        POS.util.hideLoader();
      });
    });
  }
}
