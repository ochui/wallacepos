<?php
/**
 * FreePOS Multi-Step Installer
 * 
 * Provides a web-based installation wizard for FreePOS
 * with support for system requirements checking, database configuration,
 * .env file creation, and admin user setup.
 */

session_start();
ini_set('display_errors', 'On');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

/**
 * Get current installation step
 */
function getCurrentStep() {
    return $_SESSION['install_step'] ?? 1;
}

/**
 * Set current installation step
 */
function setCurrentStep($step) {
    $_SESSION['install_step'] = $step;
}

/**
 * Check if we can proceed to next step
 */
function canProceedToStep($step) {
    switch ($step) {
        case 2: // Database configuration
            return true; // Always allow after requirements
        case 3: // Admin setup
            return isset($_SESSION['database_configured']);
        case 4: // Installation
            return isset($_SESSION['database_configured']) && isset($_SESSION['admin_configured']);
        default:
            return true;
    }
}

/**
 * Process step navigation
 */
if (isset($_GET['step'])) {
    $requestedStep = (int)$_GET['step'];
    if ($requestedStep >= 1 && $requestedStep <= 4 && canProceedToStep($requestedStep)) {
        setCurrentStep($requestedStep);
    }
}

// Handle POST actions for different steps
if ($_POST) {
    $currentStep = getCurrentStep();
    
    switch ($currentStep) {
        case 1: // Requirements check
            if (isset($_POST['next_step'])) {
                setCurrentStep(2);
            }
            break;
            
        case 2: // Database configuration
            if (isset($_POST['test_database'])) {
                // This will be handled by AJAX
            } elseif (isset($_POST['save_database'])) {
                $_SESSION['database_configured'] = true;
                setCurrentStep(3);
            }
            break;
            
        case 3: // Admin setup
            if (isset($_POST['configure_admin'])) {
                $_SESSION['admin_configured'] = true;
                setCurrentStep(4);
            }
            break;
            
        case 4: // Installation
            // Installation is handled by AJAX
            break;
    }
}

$currentStep = getCurrentStep();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>FreePOS - Installation Wizard</title>
    <meta name="description" content="FreePOS Installation Wizard" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <link rel="shortcut icon" href="../assets/images/favicon.ico">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/font-awesome.min.css" />
    <link rel="stylesheet" href="../assets/css/ace-fonts.css" />
    <link rel="stylesheet" href="../assets/css/ace.min.css" />
    
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
            <ul class="breadcrumb">
                <li class="<?php echo $currentStep == 1 ? 'active' : ''; ?>">
                    <?php echo $currentStep == 1 ? '<strong>Check Requirements</strong>' : 'Check Requirements'; ?>
                </li>
                <li class="<?php echo $currentStep == 2 ? 'active' : ''; ?>">
                    <?php echo $currentStep == 2 ? '<strong>Configure Database</strong>' : 'Configure Database'; ?>
                </li>
                <li class="<?php echo $currentStep == 3 ? 'active' : ''; ?>">
                    <?php echo $currentStep == 3 ? '<strong>Admin Setup</strong>' : 'Admin Setup'; ?>
                </li>
                <li class="<?php echo $currentStep == 4 ? 'active' : ''; ?>">
                    <?php echo $currentStep == 4 ? '<strong>Install System</strong>' : 'Install System'; ?>
                </li>
            </ul>

            <!-- Step Content -->
            <div class="step-content">
                <?php
                switch ($currentStep) {
                    case 1:
                        include 'views/requirements.php';
                        break;
                    case 2:
                        include 'views/database.php';
                        break;
                    case 3:
                        include 'views/admin.php';
                        break;
                    case 4:
                        include 'views/install.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-1.10.2.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        // Global installer JavaScript functions
        function showAlert(type, message) {
            var alertClass = 'alert-info';
            if (type === 'success') alertClass = 'alert-success';
            if (type === 'error') alertClass = 'alert-danger';
            
            var alert = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                       '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                       message + '</div>';
            
            $('.step-content').prepend(alert);
            
            // Auto-dismiss success alerts
            if (type === 'success') {
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 3000);
            }
        }
        
        function removeAlerts() {
            $('.alert').remove();
        }

        // Check requirements on page load if we're on step 1
        $(document).ready(function() {
            <?php if ($currentStep == 1): ?>
            checkRequirements();
            <?php endif; ?>
        });

        // Requirements checking function
        function checkRequirements() {
            $('#requirements-loading').show();
            $('#requirements-results').hide();
            
            $.ajax({
                url: '../api/install.php',
                type: 'POST',
                data: { action: 'requirements' },
                dataType: 'json',
                success: function(response) {
                    $('#requirements-loading').hide();
                    $('#requirements-results').show();
                    
                    if (response.errorCode === 'OK') {
                        displayRequirements(response.data);
                    } else {
                        showAlert('error', 'Failed to check requirements: ' + response.error);
                    }
                },
                error: function() {
                    $('#requirements-loading').hide();
                    $('#requirements-results').show();
                    showAlert('error', 'Unable to check system requirements. Please check your server configuration.');
                }
            });
        }

        function displayRequirements(data) {
            var html = '';
            var allMet = data.all;
            
            data.requirements.forEach(function(req) {
                var statusClass = req.status ? 'success' : 'error';
                var iconClass = req.status ? 'icon-check green' : 'icon-remove red';
                
                html += '<div class="requirement-item ' + statusClass + '">';
                html += '<i class="' + iconClass + '"></i> ';
                html += '<strong>' + req.name + '</strong><br>';
                html += '<small>Current: ' + req.current + ' | Required: ' + req.required + '</small>';
                html += '</div>';
            });
            
            $('#requirements-list').html(html);
            
            if (allMet) {
                $('#btn-next-step').prop('disabled', false);
                $('#ignore-requirements').hide();
                showAlert('success', '<strong>All requirements met!</strong> You can proceed with the installation.');
            } else {
                $('#btn-next-step').prop('disabled', true);
                $('#ignore-requirements').show();
                showAlert('error', '<strong>Some requirements are not met.</strong> Please resolve the issues above before proceeding.');
            }
        }

        function toggleIgnoreRequirements() {
            var ignore = $('#ignore-check').is(':checked');
            $('#btn-next-step').prop('disabled', !ignore);
        }

        function proceedToNextStep() {
            window.location.href = 'index.php?step=2';
        }
    </script>
</body>
</html>