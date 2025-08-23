<?php
/**
 * FreePOS Modern Multi-Step Installer
 * 
 * Uses FastRoute routing system and API endpoints for installation
 */

// Start session for step tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize installation step
if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>FreePOS - Installation Wizard</title>
    <meta name="description" content="FreePOS Installation Wizard" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <link rel="shortcut icon" href="/assets/images/favicon.ico">
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/font-awesome.min.css" />
    <link rel="stylesheet" href="/assets/css/ace-fonts.css" />
    <link rel="stylesheet" href="/assets/css/ace.min.css" />
    
    <style>
        .installer-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .breadcrumb {
            background: #f5f5f5;
            padding: 10px 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .breadcrumb li.active {
            font-weight: bold;
            color: #337ab7;
        }
        .step-content {
            min-height: 400px;
            padding: 20px 0;
        }
        .btn-navigation {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .requirement-item {
            margin: 10px 0;
            padding: 10px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .requirement-item.success {
            border-left-color: #5cb85c;
            background: #f5f5f5;
        }
        .requirement-item.error {
            border-left-color: #d9534f;
            background: #fdf7f7;
        }
        .form-group label {
            font-weight: bold;
        }
        .alert {
            margin-bottom: 20px;
        }
        .install-progress {
            display: none;
        }
        .install-complete {
            display: none;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="login-layout">
    <div class="main-container">
        <div class="installer-container">
            <div class="logo">
                <h1><i class="icon-shopping-cart"></i> FreePOS</h1>
                <h4>Installation Wizard</h4>
            </div>

            <!-- Breadcrumb Navigation -->
            <ul class="breadcrumb" id="breadcrumb">
                <li id="step-1"><strong>Check Requirements</strong></li>
                <li id="step-2">Configure Database</li>
                <li id="step-3">Admin Setup</li>
                <li id="step-4">Install System</li>
            </ul>

            <!-- Step Content -->
            <div class="step-content" id="step-content">
                <!-- Loading spinner -->
                <div class="loading-spinner" id="loading">
                    <i class="icon-spinner icon-spin icon-2x"></i>
                    <p>Loading...</p>
                </div>

                <!-- Step 1: Requirements Check -->
                <div id="step-1-content">
                    <h3>System Requirements Check</h3>
                    <p>Please ensure your system meets all the requirements before proceeding with the installation.</p>
                    
                    <div id="requirements-list">
                        <!-- Requirements will be loaded here -->
                    </div>
                    
                    <div class="btn-navigation">
                        <button class="btn btn-primary" id="check-requirements" onclick="checkRequirements()">
                            <i class="icon-refresh"></i> Check Requirements
                        </button>
                        <button class="btn btn-success hidden" id="next-to-database" onclick="goToStep(2)">
                            <i class="icon-arrow-right"></i> Next: Configure Database
                        </button>
                    </div>
                </div>

                <!-- Step 2: Database Configuration -->
                <div id="step-2-content" class="hidden">
                    <h3>Database Configuration</h3>
                    <p>Configure your database connection settings.</p>
                    
                    <form id="database-form">
                        <div class="form-group">
                            <label for="db_host">Database Host:</label>
                            <input type="text" class="form-control" id="db_host" name="host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="db_port">Database Port:</label>
                            <input type="number" class="form-control" id="db_port" name="port" value="3306" required>
                        </div>
                        <div class="form-group">
                            <label for="db_name">Database Name:</label>
                            <input type="text" class="form-control" id="db_name" name="database" required>
                        </div>
                        <div class="form-group">
                            <label for="db_username">Database Username:</label>
                            <input type="text" class="form-control" id="db_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="db_password">Database Password:</label>
                            <input type="password" class="form-control" id="db_password" name="password">
                        </div>
                    </form>
                    
                    <div class="btn-navigation">
                        <button class="btn btn-default" onclick="goToStep(1)">
                            <i class="icon-arrow-left"></i> Back
                        </button>
                        <button class="btn btn-info" onclick="testDatabase()">
                            <i class="icon-cogs"></i> Test Connection
                        </button>
                        <button class="btn btn-success hidden" id="save-database" onclick="saveDatabase()">
                            <i class="icon-arrow-right"></i> Next: Admin Setup
                        </button>
                    </div>
                </div>

                <!-- Step 3: Admin Setup -->
                <div id="step-3-content" class="hidden">
                    <h3>Admin User Setup</h3>
                    <p>Create the administrator account for your FreePOS installation.</p>
                    
                    <form id="admin-form">
                        <div class="form-group">
                            <label for="admin_password">Admin Password:</label>
                            <input type="password" class="form-control" id="admin_password" name="password" required minlength="8">
                            <small class="text-muted">Password must be at least 8 characters long.</small>
                        </div>
                        <div class="form-group">
                            <label for="admin_confirm_password">Confirm Password:</label>
                            <input type="password" class="form-control" id="admin_confirm_password" name="confirm_password" required>
                        </div>
                    </form>
                    
                    <div class="btn-navigation">
                        <button class="btn btn-default" onclick="goToStep(2)">
                            <i class="icon-arrow-left"></i> Back
                        </button>
                        <button class="btn btn-success" onclick="configureAdmin()">
                            <i class="icon-arrow-right"></i> Next: Install System
                        </button>
                    </div>
                </div>

                <!-- Step 4: Installation -->
                <div id="step-4-content" class="hidden">
                    <h3>Install System</h3>
                    <p>Ready to install FreePOS. Click the button below to begin the installation process.</p>
                    
                    <div class="install-progress" id="install-progress">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped active" style="width: 0%"></div>
                        </div>
                        <p id="install-status">Installing...</p>
                    </div>
                    
                    <div class="install-complete" id="install-complete">
                        <div class="alert alert-success">
                            <h4><i class="icon-ok"></i> Installation Complete!</h4>
                            <p>FreePOS has been successfully installed. You can now access your system.</p>
                        </div>
                        <div class="text-center">
                            <a href="/admin" class="btn btn-primary btn-lg">
                                <i class="icon-dashboard"></i> Go to Admin Panel
                            </a>
                        </div>
                    </div>
                    
                    <div class="btn-navigation" id="install-buttons">
                        <button class="btn btn-default" onclick="goToStep(3)">
                            <i class="icon-arrow-left"></i> Back
                        </button>
                        <button class="btn btn-success" onclick="installSystem()">
                            <i class="icon-download"></i> Install FreePOS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/jquery-2.0.3.min.js"></script>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script>
        // Global installer state
        let currentStep = 1;
        let requirementsPassed = false;
        let databaseConfigured = false;
        let adminConfigured = false;

        // Initialize installer
        $(document).ready(function() {
            goToStep(1);
            checkRequirements();
        });

        // Navigation functions
        function goToStep(step) {
            // Hide all step content
            $('[id^="step-"][id$="-content"]').addClass('hidden');
            
            // Show current step content
            $('#step-' + step + '-content').removeClass('hidden');
            
            // Update breadcrumb
            $('#breadcrumb li').removeClass('active').each(function() {
                const stepNum = parseInt($(this).attr('id').split('-')[1]);
                if (stepNum === step) {
                    $(this).addClass('active').html('<strong>' + $(this).text() + '</strong>');
                } else {
                    $(this).html($(this).text().replace(/<\/?strong>/g, ''));
                }
            });
            
            currentStep = step;
        }

        // Utility functions
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
            const alert = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                         '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                         message + '</div>';
            
            $('#step-content').prepend(alert);
            
            if (type === 'success') {
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 3000);
            }
        }

        function removeAlerts() {
            $('.alert').remove();
        }

        function showLoading() {
            $('#loading').show();
        }

        function hideLoading() {
            $('#loading').hide();
        }

        // Step 1: Requirements Check
        function checkRequirements() {
            showLoading();
            removeAlerts();
            
            $.ajax({
                url: '/api/install/requirements',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.errorCode === 'OK') {
                        displayRequirements(response.data);
                        requirementsPassed = response.data.all;
                        
                        if (requirementsPassed) {
                            $('#next-to-database').removeClass('hidden');
                            showAlert('success', 'All requirements are met! You can proceed to the next step.');
                        } else {
                            $('#next-to-database').addClass('hidden');
                            showAlert('error', 'Some requirements are not met. Please resolve the issues above before proceeding.');
                        }
                    } else {
                        showAlert('error', 'Error checking requirements: ' + response.error);
                    }
                },
                error: function() {
                    hideLoading();
                    showAlert('error', 'Failed to check requirements. Please ensure the API is accessible.');
                }
            });
        }

        function displayRequirements(data) {
            let html = '';
            
            data.requirements.forEach(function(req) {
                const statusClass = req.status ? 'success' : 'error';
                const icon = req.status ? 'icon-ok' : 'icon-remove';
                
                html += '<div class="requirement-item ' + statusClass + '">' +
                       '<i class="' + icon + '"></i> ' +
                       '<strong>' + req.name + '</strong><br>' +
                       '<small>Current: ' + req.current + ' | Required: ' + req.required + '</small>' +
                       '</div>';
            });
            
            $('#requirements-list').html(html);
        }

        // Step 2: Database Configuration
        function testDatabase() {
            removeAlerts();
            const formData = $('#database-form').serialize();
            
            $.ajax({
                url: '/api/install/test-database',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.errorCode === 'OK') {
                        showAlert('success', 'Database connection successful!');
                        $('#save-database').removeClass('hidden');
                    } else {
                        showAlert('error', 'Database connection failed: ' + response.error);
                        $('#save-database').addClass('hidden');
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to test database connection.');
                }
            });
        }

        function saveDatabase() {
            removeAlerts();
            const formData = $('#database-form').serialize();
            
            $.ajax({
                url: '/api/install/save-database',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.errorCode === 'OK') {
                        databaseConfigured = true;
                        showAlert('success', 'Database configuration saved!');
                        setTimeout(function() {
                            goToStep(3);
                        }, 1000);
                    } else {
                        showAlert('error', 'Failed to save database configuration: ' + response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to save database configuration.');
                }
            });
        }

        // Step 3: Admin Setup
        function configureAdmin() {
            removeAlerts();
            
            const password = $('#admin_password').val();
            const confirmPassword = $('#admin_confirm_password').val();
            
            if (password !== confirmPassword) {
                showAlert('error', 'Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                showAlert('error', 'Password must be at least 8 characters long!');
                return;
            }
            
            const formData = $('#admin-form').serialize();
            
            $.ajax({
                url: '/api/install/configure-admin',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.errorCode === 'OK') {
                        adminConfigured = true;
                        showAlert('success', 'Admin configuration saved!');
                        setTimeout(function() {
                            goToStep(4);
                        }, 1000);
                    } else {
                        showAlert('error', 'Failed to configure admin: ' + response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to configure admin.');
                }
            });
        }

        // Step 4: Installation
        function installSystem() {
            removeAlerts();
            $('#install-buttons').hide();
            $('#install-progress').show();
            
            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                $('.progress-bar').css('width', progress + '%');
            }, 500);
            
            $.ajax({
                url: '/api/install/install-with-config',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    clearInterval(progressInterval);
                    $('.progress-bar').css('width', '100%');
                    
                    if (response.errorCode === 'OK') {
                        setTimeout(function() {
                            $('#install-progress').hide();
                            $('#install-complete').show();
                        }, 1000);
                    } else {
                        $('#install-progress').hide();
                        $('#install-buttons').show();
                        showAlert('error', 'Installation failed: ' + response.error);
                    }
                },
                error: function() {
                    clearInterval(progressInterval);
                    $('#install-progress').hide();
                    $('#install-buttons').show();
                    showAlert('error', 'Installation failed. Please try again.');
                }
            });
        }
    </script>
</body>
</html>