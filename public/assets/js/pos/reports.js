/**
 *
 * reports.js Provides functions for calculating till reports and reconciliation items.
 *
 */

function POSReports() {
  // Overview
  this.populateOverview = function () {
    var stats = getOverviewStats();
    // Fill UI
    $("#rsalesnum").text(stats.salesnum);
    $("#rsalestotal").text(POS.util.currencyFormat(stats.salestotal.toFixed(2)));
    $("#rrefundsnum").text(stats.refundnum);
    $("#rrefundstotal").text(POS.util.currencyFormat(stats.refundtotal.toFixed(2)));
    $("#rvoidsnum").text(stats.voidnum);
    $("#rvoidstotal").text(POS.util.currencyFormat(stats.voidtotal.toFixed(2)));
    $("#rtotaltakings").text(POS.util.currencyFormat(stats.totaltakings.toFixed(2)));

    showAdditionalReports();
    // generate takings report
    this.generateTakingsReport();
    // populate reconciliation table with configured denominations
    this.populateReconciliationTable();
  };

  function showAdditionalReports() {
    // show eftpos reports if available
    if (POS.hasOwnProperty("eftpos") && POS.eftpos.isEnabledAndReady() && POS.eftpos.getType() == "tyro") {
      $("#tyroreports").removeClass("hide");
    } else {
      $("#tyroreports").addClass("hide");
    }
  }

  this.populateReconciliationTable = function () {
    var config = POS.getConfigTable();
    var denominations;
    
    // Get denominations from config, fall back to defaults
    if (config.pos && config.pos.cash_denominations) {
      try {
        denominations = JSON.parse(config.pos.cash_denominations);
      } catch(e) {
        denominations = getDefaultDenominations();
      }
    } else {
      denominations = getDefaultDenominations();
    }
    
    var tbody = $("#cash-reconciliation-tbody");
    tbody.empty();
    
    // Add denomination rows (2 per table row)
    for (var i = 0; i < denominations.length; i += 2) {
      var row = $('<tr></tr>');
      
      // First denomination
      var denom1 = denominations[i];
      var id1 = 'recdenom' + denom1.label.replace(/[^a-zA-Z0-9]/g, '');
      row.append('<td style="text-align: right;">' + denom1.symbol + denom1.label + ':</td>');
      row.append('<td><input onchange="POS.reports.calcReconcil();" type="text" size="4" id="' + id1 + '" value="0" data-value="' + denom1.value + '"/></td>');
      
      // Second denomination (if exists)
      if (i + 1 < denominations.length) {
        var denom2 = denominations[i + 1];
        var id2 = 'recdenom' + denom2.label.replace(/[^a-zA-Z0-9]/g, '');
        row.append('<td style="text-align: right;">' + denom2.symbol + denom2.label + ':</td>');
        row.append('<td><input onchange="POS.reports.calcReconcil();" type="text" size="4" id="' + id2 + '" value="0" data-value="' + denom2.value + '"/></td>');
      } else {
        row.append('<td></td><td></td>');
      }
      
      tbody.append(row);
    }
    
    // Add float row
    var floatRow = $('<tr></tr>');
    floatRow.append('<td style="text-align: right;">Float:</td>');
    floatRow.append('<td><input onchange="POS.reports.calcReconcil();" type="text" size="4" id="recfloat" value="0"/></td>');
    floatRow.append('<td></td><td></td>');
    tbody.append(floatRow);
    
    // Add totals rows
    var takingsRow = $('<tr></tr>');
    takingsRow.append('<td colspan="2" style="text-align: right;">Takings - Float:</td>');
    takingsRow.append('<td colspan="2"><span id="rectakings"></span></td>');
    tbody.append(takingsRow);
    
    var balanceRow = $('<tr></tr>');
    balanceRow.append('<td colspan="2" style="text-align: right;">Balance:</td>');
    balanceRow.append('<td colspan="2"><span id="recbalance"></span></td>');
    tbody.append(balanceRow);
  };

  function getDefaultDenominations() {
    return [
      {label: "100", value: 100, symbol: "$"},
      {label: "50", value: 50, symbol: "$"},
      {label: "20", value: 20, symbol: "$"},
      {label: "10", value: 10, symbol: "$"},
      {label: "5", value: 5, symbol: "$"},
      {label: "2", value: 2, symbol: "$"},
      {label: "1", value: 1, symbol: "$"},
      {label: "50c", value: 0.5, symbol: ""},
      {label: "20c", value: 0.2, symbol: ""},
      {label: "10c", value: 0.1, symbol: ""},
      {label: "5c", value: 0.05, symbol: ""}
    ];
  }

  this.calcReconcil = function () {
    var calcedtakings = 0;
    var balance;
    var recfloat = parseFloat($("#recfloat").val()) || 0;
    
    // Sum all denomination inputs
    $("#cash-reconciliation-tbody input[data-value]").each(function() {
      var count = parseFloat($(this).val()) || 0;
      var value = parseFloat($(this).attr('data-value'));
      calcedtakings += count * value;
    });
    
    var rectakings = $("#rectakings");
    var recbalance = $("#recbalance");

    calcedtakings = calcedtakings - recfloat;
    calcedtakings = calcedtakings.toFixed(2);
    balance = (calcedtakings - curcashtakings).toFixed(2);
    $(rectakings).text(POS.util.currencyFormat(calcedtakings));
    $(recbalance).text(POS.util.currencyFormat(balance));
    if (balance === -0.0) {
      balance = 0.0;
    }
    // set status
    if (balance < 0.0) {
      $(recbalance).attr("class", "red");
      $(rectakings).attr("class", "red");
    } else {
      $(recbalance).attr("class", "text-success");
      $(rectakings).attr("class", "text-success");
    }
  };

  var curstats;
  var curcashtakings;

  function getTodaysRecords(includerefunds) {
    var sales = POS.getSalesTable();
    var todaysales = {};
    var stime = new Date();
    var etime = new Date();
    stime.setHours(0);
    stime.setMinutes(0);
    stime.setSeconds(0);
    stime = stime.getTime();
    etime.setHours(23);
    etime.setMinutes(59);
    etime.setSeconds(59);
    etime = etime.getTime();
    for (var key in sales) {
      // ignore if an order
      if (sales[key].hasOwnProperty("isorder") == false) {
        // ignore if the sale was not made today or refunded today
        if (sales[key].processdt > stime && sales[key].processdt < etime) {
          // ignore if not made by this device
          if (sales[key].devid == POS.getConfigTable().deviceid) {
            todaysales[key] = sales[key];
          }
        } else {
          if (includerefunds)
            if (sales[key].hasOwnProperty("refunddata")) {
              // check for refund made today
              var refdata = sales[key].refunddata;
              for (var record in refdata) {
                if (refdata[record].processdt > stime && refdata[record].processdt < etime) {
                  // ignore if not made by this device
                  if (refdata[record].deviceid == POS.getConfigTable().deviceid) {
                    todaysales[key] = sales[key];
                  }
                }
              }
            }
        }
      }
    }
    return todaysales;
  }

  function getOverviewStats() {
    var sales = getTodaysRecords(true);
    var sale;
    var emptfloat = parseFloat("0.00");
    var stime = new Date();
    var etime = new Date();
    stime.setHours(0);
    stime.setMinutes(0);
    stime.setSeconds(0);
    stime = stime.getTime();
    etime.setHours(23);
    etime.setMinutes(59);
    etime.setSeconds(59);
    etime = etime.getTime();
    var data = { salesnum: 0, salestotal: emptfloat, voidnum: 0, voidtotal: emptfloat, refundnum: 0, refundtotal: emptfloat, totaltakings: emptfloat, methodtotals: {} };
    var salestat;
    for (var key in sales) {
      sale = sales[key];
      salestat = getTransactionStatus(sale);
      var amount;
      var method;
      switch (salestat) {
        case 2:
          data.voidnum++;
          data.voidtotal += parseFloat(sale.total);
          break;
        case 3:
          // cycle though all refunds and add to total
          for (var i in sale.refunddata) {
            amount = parseFloat(sale.refunddata[i].amount);
            method = sale.refunddata[i].method;
            data.refundnum++;
            data.refundtotal += amount;
            // add payment type totals
            if (data.methodtotals.hasOwnProperty(method)) {
              // check if payment method field is alredy set
              data.methodtotals[method].refamount += amount;
              data.methodtotals[method].refqty++;
            } else {
              data.methodtotals[method] = {};
              data.methodtotals[method].refamount = amount;
              data.methodtotals[method].refqty = 1;
              data.methodtotals[method].amount = parseFloat(0);
              data.methodtotals[method].qty = 0;
            }
          }
          // count refund as a sale, but only if it was sold today
          if (sale.processdt < stime || sale.processdt > etime) {
            break; // the sale was not made today
          }
        case 1:
          data.salesnum++;
          data.salestotal += parseFloat(sale.total);
          // calc payment methods
          for (var p in sale.payments) {
            amount = parseFloat(sale.payments[p].amount);
            method = sale.payments[p].method;
            if (data.methodtotals.hasOwnProperty(method)) {
              // check if payment method field is alredy set
              data.methodtotals[method].amount += amount;
              data.methodtotals[method].qty++;
            } else {
              data.methodtotals[method] = {};
              data.methodtotals[method].amount = amount;
              data.methodtotals[method].qty = 1;
              data.methodtotals[method].refamount = parseFloat(0);
              data.methodtotals[method].refqty = 0;
            }
          }
      }
    }
    for (var x in data.methodtotals) {
      data.methodtotals[x].amount = parseFloat(data.methodtotals[x].amount).toFixed(2);
      data.methodtotals[x].refamount = parseFloat(data.methodtotals[x].refamount).toFixed(2);
    }
    // calculate takings
    data.totaltakings = data.salestotal.toFixed(2) - data.refundtotal.toFixed(2);
    if (data.methodtotals.hasOwnProperty("cash")) {
      curcashtakings =
        (data.methodtotals.cash.hasOwnProperty("amount") ? data.methodtotals["cash"].amount : 0) -
        (data.methodtotals.cash.hasOwnProperty("refamount") ? data.methodtotals["cash"].refamount : 0);
    } else {
      curcashtakings = parseFloat(0).toFixed(2);
    }
    curstats = data;
    //alert(JSON.stringify(data));
    return data;
  }

  function getTransactionStatus(saleobj) {
    if (saleobj.hasOwnProperty("voiddata")) {
      return 2;
    } else if (saleobj.hasOwnProperty("refunddata")) {
      return 3;
    }
    return 1;
  }

  var config;
  var reportheader = function (name) {
    if (config == null) {
      config = POS.getConfigTable();
    }
    return (
      '<div style="text-align: center; margin-bottom: 5px;"><h3>' + name + "</h3><h5>" + POS.util.getShortDate(null) + " - " + config.devicename + " - " + config.locationname + "</h5></div>"
    );
  };

  function getSellerStats() {
    var sales = getTodaysRecords(true);
    var sale;
    var emptfloat = parseFloat("0.00");
    var stime = new Date();
    var etime = new Date();
    stime.setHours(0);
    stime.setMinutes(0);
    stime.setSeconds(0);
    stime = stime.getTime();
    etime.setHours(23);
    etime.setMinutes(59);
    etime.setSeconds(59);
    etime = etime.getTime();

    var data = {};
    var salestat;
    for (var key in sales) {
      sale = sales[key];
      salestat = getTransactionStatus(sale);
      var userid;
      switch (salestat) {
        case 2:
          userid = sale.voiddata.userid;
          if (data.hasOwnProperty(userid)) {
            data[userid].voidrefs.push(sale.ref);
            data[userid].voidnum++;
            data[userid].voidtotal += parseFloat(sale.total);
          } else {
            data[userid] = {};
            data[userid].salerefs = [];
            data[userid].salenum = 0;
            data[userid].saleamount = 0;
            data[userid].refrefs = [];
            data[userid].refnum = 0;
            data[userid].refamount = 0;
            data[userid].voidrefs = [sale.ref];
            data[userid].voidnum = 1;
            data[userid].voidamount = parseFloat(sale.total);
          }
          break;
        case 3:
          // cycle though all refunds and add to total
          for (var i in sale.refunddata) {
            var amount = parseFloat(sale.refunddata[i].amount);
            userid = sale.refunddata[i].userid;
            if (data.hasOwnProperty(userid)) {
              data[userid].refrefs.push(sale.ref);
              data[userid].refnum++;
              data[userid].reftotal += parseFloat(amount);
            } else {
              data[userid] = {};
              data[userid].salerefs = [];
              data[userid].salenum = 0;
              data[userid].saletotal = 0;
              data[userid].refrefs = [sale.ref];
              data[userid].refnum = 1;
              data[userid].reftotal = parseFloat(amount);
              data[userid].voidrefs = [];
              data[userid].voidnum = 0;
              data[userid].voidtotal = 0;
            }
          }
          // count refund as a sale, but only if it was sold today
          if (sale.processdt < stime || sale.processdt > etime) {
            break; // the sale was not made today
          }
        case 1:
          if (data.hasOwnProperty(sale.userid)) {
            data[sale.userid].salerefs.push(sale.ref);
            data[sale.userid].salenum++;
            data[sale.userid].saletotal += parseFloat(sale.total);
          } else {
            data[sale.userid] = {};
            data[sale.userid].salerefs = [sale.ref];
            data[sale.userid].salenum = 1;
            data[sale.userid].saletotal = parseFloat(sale.total);
            data[sale.userid].refrefs = [];
            data[sale.userid].refnum = 0;
            data[sale.userid].reftotal = 0;
            data[sale.userid].voidrefs = [];
            data[sale.userid].voidnum = 0;
            data[sale.userid].voidtotal = 0;
          }
      }
    }
    for (var x in data) {
      data[x].balance = (data[x].saletotal - data[x].reftotal).toFixed(2);
      data[x].saletotal = data[x].saletotal.toFixed(2);
      data[x].reftotal = data[x].reftotal.toFixed(2);
    }

    return data;
  }

  function getWhatsSellingStats() {
    var itemstats = { items: [], totalsold: 0 };
    var records = getTodaysRecords(false);
    var items = [];
    var item = {};
    var discount = 0;
    var discprice = 0;
    for (var ref in records) {
      if (!records[ref].hasOwnProperty("voiddata")) {
        // do not count voided sales
        discount = parseFloat(records[ref].discount);
        items = records[ref].items;
        for (var index in items) {
          item = items[index];
          discprice = parseFloat(item.price) - item.price * (discount / 100);
          // check if record exists
          if (itemstats.items.hasOwnProperty(item.sitemid)) {
            // sum values
            itemstats.items[item.sitemid].qty += parseInt(item.qty);
            itemstats.items[item.sitemid].total += discprice;
          } else {
            // create new record
            var itemname = item.sitemid == "0" ? "Miscellaneous" : item.name;
            itemstats.items[item.sitemid] = { qty: parseInt(item.qty), total: discprice, name: itemname };
          }
          itemstats.totalsold += item.qty;
        }
      }
    }

    return itemstats;
  }

  this.generateTakingsReport = function () {
    var html =
      reportheader("Takings Count Report") +
      '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Method</th><th># Payments</th><th>Takings</th><th># Refunds</th><th>Refunds</th><th>Balance</th></tr></thead><tbody>';
    var methdenoms = curstats.methodtotals;
    for (var method in methdenoms) {
      html +=
        "<tr><td>" +
        POS.util.capFirstLetter(method) +
        "</td><td>" +
        methdenoms[method].qty +
        "</td><td>" +
        POS.util.currencyFormat(methdenoms[method].amount) +
        "</td><td>" +
        methdenoms[method].refqty +
        "</td><td>" +
        POS.util.currencyFormat(methdenoms[method].refamount) +
        "</td><td>" +
        POS.util.currencyFormat((parseFloat(methdenoms[method].amount) - parseFloat(methdenoms[method].refamount)).toFixed(2)) +
        "</td></tr>";
    }
    html += "</tbody></table>";
    // put into report window
    $("#reportcontain").html(html);
  };

  this.generateWhatsSellingReport = function () {
    var html = reportheader("What's Selling Report") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Item</th><th># Sold</th><th>Total</th></tr></thead><tbody>';
    var stats = getWhatsSellingStats();
    var item;
    for (var id in stats.items) {
      item = stats.items[id];
      html += "<tr><td>" + item.name + "</td><td>" + item.qty + "</td><td>" + POS.util.currencyFormat(item.total) + "</td></tr>";
    }

    html += "</tbody></table>";
    // put into report window
    $("#reportcontain").html(html);
  };

  this.generateSellerReport = function () {
    var html =
      reportheader("Seller Takings") +
      '<table style="width:100%;" class="table table-stripped"><thead><tr><th>User</th><th>Sales</th><th>Voids</th><th>Refunds</th><th>Balance</th></tr></thead><tbody>';
    var stats = getSellerStats();
    var item;
    var users = POS.getConfigTable().users;
    for (var id in stats) {
      item = stats[id];
      var user = users.hasOwnProperty(id) ? users[id].username : "Unknown";
      html +=
        "<tr><td>" +
        user +
        "</td><td>" +
        POS.util.currencyFormat(item.saletotal) +
        " (" +
        item.salenum +
        ")" +
        "</td><td>" +
        POS.util.currencyFormat(item.voidtotal) +
        " (" +
        item.voidnum +
        ")" +
        "</td><td>" +
        POS.util.currencyFormat(item.reftotal) +
        " (" +
        item.refnum +
        ")" +
        "</td><td>" +
        POS.util.currencyFormat(item.balance) +
        "</td></tr>";
    }

    html += "</tbody></table>";
    // put into report window
    $("#reportcontain").html(html);
  };

  this.generateTyroReport = function () {
    var type = $("#tyroreptype").val();
    POS.eftpos.getTyroReport(type, type == "detail" ? POS.reports.populateTyroDetailed : POS.reports.populateTyroSummary);
  };

  this.populateTyroSummary = function (xml) {
    xml = parseXML(xml);
    var html =
      reportheader("Tyro Eftpos Summary Report") +
      '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Card Type</th><th style="text-align: right;">Purchase</th><th style="text-align: right;">Cash-Out</th><th style="text-align: right;">Refunds</th><th style="text-align: right;">Total</th></tr></thead><tbody>';
    var line = xml.find("card");
    //console.log(xml);
    //console.log(recon);
    $.each(line, function (i) {
      //console.log($(this));
      html +=
        '<tr><td style="text-align: left;">' +
        POS.util.capFirstLetter($(this).attr("type")) +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("purchases")) +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("cash-out") ? $(this).attr("cash-out") : "0.00") +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("refunds")) +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("total")) +
        "</td></tr>";
    });
    html +=
      '<tr><td colspan="4" style="text-align: left;"><strong>Total:</strong></td><td style="text-align: right;">' +
      POS.util.currencyFormat(xml.find("reconciliation-summary").attr("total")) +
      "</td></tr>";
    html += "</tbody></table>";
    // put into report window
    $("#reportcontain").html(html);
  };

  this.populateTyroDetailed = function (xml) {
    xml = parseXML(xml);
    var html =
      reportheader("Tyro Eftpos Detail Report") +
      '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Time</th><th>Type</th><th>Card Type</th><th style="text-align: right;">Cash Out</th><th style="text-align: right;">Total</th></tr></thead><tbody>';
    var line = xml.find("transaction");
    $.each(line, function (i) {
      html +=
        '<tr><td  style="text-align: left;">' +
        $(this).attr("transaction-local-date-time") +
        '</td><td style="text-align: left;">' +
        POS.util.capFirstLetter($(this).attr("type")) +
        '</td><td style="text-align: left;">' +
        POS.util.capFirstLetter($(this).attr("card-type")) +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("cash-out") ? $(this).attr("cash-out") : "0.00") +
        '</td><td style="text-align: right;">' +
        POS.util.currencyFormat($(this).attr("amount")) +
        "</td></tr>";
    });
    html +=
      '<tr><td colspan="3" style="text-align: left;"><strong>Total:</strong></td><td style="text-align: right;">' +
      POS.util.currencyFormat(xml.find("reconciliation-detail").attr("total")) +
      "</td></tr>";
    html += "</tbody></table>";
    // put into report window
    $("#reportcontain").html(html);
  };

  function parseXML(xml) {
    var xmlobj = $.parseXML(xml);
    return $(xmlobj);
  }
}
