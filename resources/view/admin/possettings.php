<div class="page-header">
    <h1>
        POS Settings
        <small>
            <i class="icon-double-angle-right"></i>
            Manage global POS settings
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Receipt</h4>
            </div>

            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Default Template:</label></div>
                        <div class="col-sm-5">
                            <select id="rectemplate"></select><br/>
                            <small>not used for ESCP text-mode receipts</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Header Line 2:</label></div>
                        <div class="col-sm-5"><input type="text" id="recline2" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Header Line 3:</label></div>
                        <div class="col-sm-5"><input type="text" id="recline3" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Print Sale ID:</label>
                        <div class="col-sm-5">
                            <input type="checkbox" id="recprintid" /><br/>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Print Item Description:</label>
                        <div class="col-sm-5">
                            <input type="checkbox" id="recprintdesc" /><br/>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Receipt Printer Logo:</label>
                        <div class="col-sm-5">
                            <input type="text" id="reclogo" /><br/>
                            <img id="reclogoprev" width="128" height="64" src="" />
                            <input type="file" id="reclogofile" name="file" />
                            <small>Must be a monochromatic 1-bit png (256*128)</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Print Receipt Logo:</label>
                        <div class="col-sm-5">
                            <input type="checkbox" id="recprintlogo" value="true" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Receipt Currency Characters:</label>
                        <div class="col-sm-5">
                            <input type="text" id="reccurrency" /><br/>
                            <small>Used for ESC/P text-mode printing.</small>
                            <small>Supply alternate decimal character codes separated by a comma or leave blank to disable.</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Receipt Currency Codepage:</label>
                        <div class="col-sm-5">
                            <input type="number" id="reccurrency_codepage" /><br/>
                            <small>Alternate codepage used to print the currency characters above.</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Browser/Email Logo:</label>
                        <div class="col-sm-5">
                            <input type="text" id="recemaillogo" /><br/>
                            <img id="emaillogoprev" width="128" height="64" src="" />
                            <input type="file" id="emaillogofile" name="file" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Receipt Footer Text:</label>
                        <div class="col-sm-5"><input type="text" id="recfooter" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-5">Promo QR code:</label>
                        <div class="col-sm-5"><input type="text" id="recqrcode" /><br/><small>Leave blank to disable</small>
                            <br/><img id="qrpreview" width="150" src="">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">POS Records: Load sale records...</h4>
            </div>

            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>for the last:</label></div>
                        <div class="col-sm-5">
                            <select id="salerange">
                                <option value="week">1 week</option>
                                <option value="day">1 day</option>
                                <option value="month">1 month</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Include:</label></div>
                        <div class="col-sm-5">
                            <select id="saledevice">
                                <option value="device">Devices sales</option>
                                <option value="location">Locations sales</option>
                                <option value="all">All sales</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Sale Options</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div>
                        <div class="form-group">
                            <div class="col-sm-5"><label>Allow Changing Stored Item Prices:</label></div>
                            <div class="col-sm-5">
                                <select id="priceedit">
                                    <option value="blank">When Price is Blank</option>
                                    <option value="always">Always</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-4"></div>
                        <div class="form-group">
                            <div class="col-sm-5"><label>Allow Changing Stored Item Tax:</label></div>
                            <div class="col-sm-5">
                                <select id="taxedit">
                                    <option value="no">No</option>
                                    <option value="always">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-4"></div>
                        <div class="form-group">
                            <div class="col-sm-5"><label>Cash rounding:</label></div>
                            <div class="col-sm-5">
                                <select id="cashrounding">
                                    <option value="0">None</option>
                                    <option value="5">5¢</option>
                                    <option value="10">10¢</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-4"></div>
                        <div class="form-group">
                            <div class="col-sm-5"><label>Allow negative item prices:</label></div>
                            <div class="col-sm-5">
                                <input id="negative_items" type="checkbox" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Cash Reconciliation Denominations</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <div class="table-header">
                    Configure cash denominations for reconciliation calculations
                </div>
                <div class="table-responsive">
                    <table id="cash-denominations-table" class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>Label</th>
                            <th>Value</th>
                            <th>Currency Symbol</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="denominations-tbody">
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-sm btn-primary" type="button" onclick="addDenomination();"><i class="icon-plus"></i> Add Denomination</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveSettings();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script type="text/javascript">
        var options;

        function saveSettings(){
            // show loader
            POS.util.showLoader();
            var data = {};
            $("#maincontent").find("form :input").each(function(){
                if ($(this).is(':checkbox')) {
                    data[$(this).prop('id')] = $(this).is(":checked") ? true : false;
                } else {
                    data[$(this).prop('id')] = $(this).val();
                }
            });
            
            // Collect cash denominations
            var denominations = [];
            $("#denominations-tbody tr").each(function(){
                var row = $(this);
                var label = row.find(".denom-label").val();
                var value = parseFloat(row.find(".denom-value").val());
                var symbol = row.find(".denom-symbol").val();
                if (label && !isNaN(value)) {
                    denominations.push({
                        label: label,
                        value: value,
                        symbol: symbol || "$"
                    });
                }
            });
            data['cash_denominations'] = JSON.stringify(denominations);
            
            var result = POS.sendJsonData("settings/pos/set", JSON.stringify(data));
            if (result !== false){
                POS.setConfigSet('pos', result);
            }
            refreshPreviewImages();
            // hide loader
            POS.util.hideLoader();
        }

        function loadSettings(){
            options = POS.getJsonData("settings/pos/get");
            // load option values into the form
            for (var i in options){
                var input = $("#"+i);
                if (input.is(':checkbox')) {
                    input.prop('checked', options[i]);
                } else {
                    input.val(options[i]);
                }
            }
            // unfortunately the above doesn't work for checkboxes :( so a fix is below :)
            /*if (options.recprintlogo==true){
                $("#recprintlogo").prop("checked", "checked");
            }*/
            refreshTemplateList(options['rectemplate']);
            refreshPreviewImages();
            loadDenominations();
        }

        function refreshPreviewImages(){
            // set logo images
            $("#reclogoprev").attr("src", options.reclogo + "?t=" + new Date().getTime());
            $("#emaillogoprev").attr("src", options.recemaillogo + "?t=" + new Date().getTime());
            $("#qrpreview").attr("src", (options.recqrcode!=="" ? "/assets/qrcode.png?t=" + new Date().getTime() : ""));
        }

        function refreshTemplateList(selectedid){
            var templates = POS.getConfigTable()['templates'];
            var list = $("#rectemplate");
            list.html('');
            for (var i in templates){
                if (templates[i].type=="receipt")
                    list.append('<option value="'+i+'" '+(i==selectedid?'selected="selected"':'')+'>'+templates[i].name+'</option>');
            }
        }

        $('#reclogofile').on('change',uploadRecLogo);
        $('#reclogo').on('change',function(e){
            $("#reclogoprev").prop("src", $(e.target).val());
        });

        $('#emaillogofile').on('change',uploadEmailLogo);
        $('#recemaillogo').on('change',function(e){
            $("#emaillogoprev").prop("src", $(e.target).val());
        });

        function uploadRecLogo(event){
            POS.uploadFile(event, function(data){
                $("#reclogo").val(data.path);
                $("#reclogoprev").prop("src", data.path);
                saveSettings();
            }); // Start file upload, passing a callback to fire if it completes successfully
        }

        function uploadEmailLogo(event){
            POS.uploadFile(event, function(data){
                $("#recemaillogo").val(data.path);
                $("#emaillogoprev").prop("src", data.path);
                saveSettings();
            }); // Start file upload, passing a callback to fire if it completes successfully
        }

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

        function loadDenominations() {
            var denominations;
            if (options.cash_denominations) {
                try {
                    denominations = JSON.parse(options.cash_denominations);
                } catch(e) {
                    denominations = getDefaultDenominations();
                }
            } else {
                denominations = getDefaultDenominations();
            }
            
            var tbody = $("#denominations-tbody");
            tbody.empty();
            
            for (var i = 0; i < denominations.length; i++) {
                addDenominationRow(denominations[i]);
            }
        }

        function addDenomination() {
            addDenominationRow({label: "", value: 0, symbol: "$"});
        }

        function addDenominationRow(denom) {
            var tbody = $("#denominations-tbody");
            var row = $('<tr></tr>');
            
            row.html(
                '<td><input type="text" class="denom-label form-control" value="' + denom.label + '" placeholder="e.g. 20 or 50c"></td>' +
                '<td><input type="number" step="0.01" class="denom-value form-control" value="' + denom.value + '" placeholder="0.00"></td>' +
                '<td><input type="text" class="denom-symbol form-control" value="' + denom.symbol + '" placeholder="$"></td>' +
                '<td><button type="button" class="btn btn-xs btn-danger" onclick="removeDenomination(this)"><i class="icon-trash"></i></button></td>'
            );
            
            tbody.append(row);
        }

        function removeDenomination(button) {
            $(button).closest('tr').remove();
        }

        $(function(){
            loadSettings();

            // hide loader
            POS.util.hideLoader();
        })
</script>