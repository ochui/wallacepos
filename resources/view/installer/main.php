<?php
/**
 * Main installer template - serves the multi-step installer interface
 * This template is loaded via the modern route system at /installer
 */
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="copyright"
        content="Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html>" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FreePOS Installation</title>

    <link rel="shortcut icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png">
    <!-- UI FRAMEWORK STYLES -->
    <link type="text/css" rel="stylesheet" href="/assets/css/wpos.css" />
    <link rel="stylesheet" href="/assets/css/jquery-ui-1.10.3.full.min.css" />
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/font-awesome.min.css" />
    <!--[if IE 7]>
    <link rel="stylesheet" href="/assets/css/font-awesome-ie7.min.css"/>
    <![endif]-->
    <!-- fonts -->
    <link rel="stylesheet" href="/assets/css/ace-fonts.css" />
    <!-- ace styles -->
    <link rel="stylesheet" href="/assets/css/ace.min.css" />
    <link rel="stylesheet" href="/assets/css/ace-rtl.min.css" />
    <!--[if lte IE 8]>
    <link rel="stylesheet" href="/assets/css/ace-ie.min.css"/>
    <![endif]-->
    <style type="text/css">
        .step-nav {
            margin-bottom: 20px;
        }
        .step-nav li {
            display: inline-block;
            margin-right: 20px;
            padding: 8px 16px;
            background: #f5f5f5;
            border-radius: 4px;
            color: #999;
        }
        .step-nav li.active {
            background: #428bca;
            color: white;
        }
        .step-nav li.completed {
            background: #5cb85c;
            color: white;
        }
        .installer-content {
            min-height: 400px;
        }
        .requirement-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .requirement-pass {
            background: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .requirement-fail {
            background: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
        .step-buttons {
            margin-top: 20px;
            text-align: right;
        }
        .step-buttons .btn {
            margin-left: 10px;
        }
    </style>
</head>

<body style="background-color:#000000;">
    <div class="login-layout"
        style="width: 100%; max-width: 800px; min-width: 400px; position: relative; margin: 0 auto;">
        <div class="login-box widget-box visible no-border" style="height: auto;">
            <div class="widget-main" style="min-height: 500px;">
                <h2 class="header blue lighter bigger align-center">
                    <img style="height: 40px; margin-top: -5px;"
                        src="/assets/images/apple-touch-icon-72x72.png">&nbsp;FreePOS Installation
                </h2>
                <div class="space-6"></div>

                <!-- Step Navigation -->
                <div class="step-nav text-center">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li id="step-nav-1">1. Check Requirements</li>
                        <li id="step-nav-2">2. Configure Database</li>
                        <li id="step-nav-3">3. Admin Setup</li>
                        <li id="step-nav-4">4. Install System</li>
                    </ul>
                </div>

                <div class="space-6"></div>

                <!-- Dynamic Content Area -->
                <div id="installer-content" class="installer-content">
                    <!-- Content loaded dynamically via Ajax -->
                </div>

                <!-- Loading indicator -->
                <div id="installer-loading" class="text-center" style="display: none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- UI FRAMEWORK SCRIPTS -->
    <!--[if !IE]> -->
    <script type="text/javascript">
        window.jQuery || document.write("<script src='/assets/js/jquery-2.0.3.min.js'>" + "<" + "/script>");
    </script>
    <!-- <![endif]-->
    <!--[if IE]>
    <script type="text/javascript">
        window.jQuery || document.write("<script src='/assets/js/jquery-1.10.2.min.js'>" + "<" + "/script>");
    </script>
    <![endif]-->
    <script type="text/javascript">
        if ("ontouchend" in document) document.write("<script src='/assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
    </script>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/typeahead-bs2.min.js"></script>
    <script src="/assets/js/jquery-ui-1.10.3.full.min.js"></script>

    <!-- Installer JavaScript -->
    <script>
        var Installer = {
            currentStep: 1,
            stepData: {},

            init: function() {
                this.loadStep(1);
            },

            loadStep: function(step) {
                this.showLoading();
                this.currentStep = step;
                this.updateStepNavigation();

                var stepMapping = {
                    1: 'requirements',
                    2: 'database',
                    3: 'admin',
                    4: 'install'
                };

                var template = stepMapping[step];
                if (!template) {
                    this.showError('Invalid step: ' + step);
                    return;
                }

                $.get('/api/admin/content/' + template + '-step')
                    .done(function(data) {
                        $('#installer-content').html(data);
                        Installer.hideLoading();
                        
                        // Initialize step-specific functionality
                        if (typeof window['initStep' + step] === 'function') {
                            window['initStep' + step]();
                        }
                    })
                    .fail(function() {
                        Installer.showError('Failed to load installer step');
                    });
            },

            updateStepNavigation: function() {
                $('.step-nav li').removeClass('active completed');
                
                for (var i = 1; i <= 4; i++) {
                    var $nav = $('#step-nav-' + i);
                    if (i < this.currentStep) {
                        $nav.addClass('completed');
                    } else if (i === this.currentStep) {
                        $nav.addClass('active');
                    }
                }
            },

            nextStep: function() {
                if (this.currentStep < 4) {
                    this.loadStep(this.currentStep + 1);
                }
            },

            prevStep: function() {
                if (this.currentStep > 1) {
                    this.loadStep(this.currentStep - 1);
                }
            },

            showLoading: function() {
                $('#installer-content').hide();
                $('#installer-loading').show();
            },

            hideLoading: function() {
                $('#installer-loading').hide();
                $('#installer-content').show();
            },

            showError: function(message) {
                this.hideLoading();
                $('#installer-content').html(
                    '<div class="alert alert-danger">' +
                    '<strong>Error:</strong> ' + message +
                    '</div>'
                );
            },

            showSuccess: function(message) {
                $('#installer-content').html(
                    '<div class="alert alert-success">' +
                    '<strong>Success:</strong> ' + message +
                    '</div>'
                );
            },

            apiCall: function(endpoint, data, successCallback, errorCallback) {
                var requestData = data || {};
                
                $.post('/api/install/' + endpoint, requestData)
                    .done(function(response) {
                        if (response.errorCode === 'OK') {
                            if (successCallback) successCallback(response.data);
                        } else {
                            if (errorCallback) errorCallback(response.error);
                            else Installer.showError(response.error);
                        }
                    })
                    .fail(function() {
                        var msg = 'Failed to communicate with installation API';
                        if (errorCallback) errorCallback(msg);
                        else Installer.showError(msg);
                    });
            }
        };

        // Initialize installer when page loads
        $(document).ready(function() {
            Installer.init();
        });
    </script>
</body>

</html>