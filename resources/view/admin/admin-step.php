<?php
/**
 * Admin setup step template
 */
?>
<div class="step-content">
    <h3>Administrator Setup</h3>
    <p>Set up the administrator account that will be used to manage FreePOS.</p>
    
    <form id="admin-form" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label">Admin Password:</label>
            <div class="col-sm-9">
                <input type="password" class="form-control" name="password" id="admin-password" required>
                <span class="help-block">Password must be at least 8 characters long</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">Confirm Password:</label>
            <div class="col-sm-9">
                <input type="password" class="form-control" name="confirm_password" id="admin-confirm-password" required>
                <span class="help-block">Re-enter the password to confirm</span>
            </div>
        </div>
        
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <div class="alert alert-info">
                    <strong>Note:</strong> The default administrator username is <code>admin</code>. 
                    You can change this later from the admin panel.
                </div>
            </div>
        </div>
    </form>
    
    <div id="admin-status" style="margin-top: 20px;"></div>

    <div class="step-buttons">
        <button type="button" class="btn btn-default" id="prev-step-btn">
            <i class="fa fa-arrow-left"></i> Back: Database
        </button>
        <button type="button" class="btn btn-success" id="save-admin-btn">
            Save Admin Config <i class="fa fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function initStep3() {
    $('#prev-step-btn').click(function() {
        Installer.prevStep();
    });
    
    $('#save-admin-btn').click(function() {
        saveAdminConfig();
    });
    
    // Real-time password validation
    $('#admin-password, #admin-confirm-password').on('keyup', function() {
        validatePasswords();
    });
}

function validatePasswords() {
    var password = $('#admin-password').val();
    var confirmPassword = $('#admin-confirm-password').val();
    
    var isValid = true;
    var messages = [];
    
    if (password.length > 0 && password.length < 8) {
        isValid = false;
        messages.push('Password must be at least 8 characters long');
    }
    
    if (confirmPassword.length > 0 && password !== confirmPassword) {
        isValid = false;
        messages.push('Passwords do not match');
    }
    
    if (messages.length > 0) {
        $('#admin-status').html(
            '<div class="alert alert-warning">' +
            '<strong>Password Issues:</strong><ul><li>' + messages.join('</li><li>') + '</li></ul>' +
            '</div>'
        );
        $('#save-admin-btn').prop('disabled', true);
    } else if (password.length >= 8 && password === confirmPassword) {
        $('#admin-status').html(
            '<div class="alert alert-success">' +
            '<strong>Password Valid:</strong> Ready to save configuration.' +
            '</div>'
        );
        $('#save-admin-btn').prop('disabled', false);
    } else {
        $('#admin-status').html('');
        $('#save-admin-btn').prop('disabled', false);
    }
}

function saveAdminConfig() {
    var password = $('#admin-password').val();
    var confirmPassword = $('#admin-confirm-password').val();
    
    // Validate inputs
    if (!password) {
        $('#admin-status').html(
            '<div class="alert alert-warning">' +
            '<strong>Validation Error:</strong> Password is required.' +
            '</div>'
        );
        return;
    }
    
    if (password.length < 8) {
        $('#admin-status').html(
            '<div class="alert alert-warning">' +
            '<strong>Validation Error:</strong> Password must be at least 8 characters long.' +
            '</div>'
        );
        return;
    }
    
    if (password !== confirmPassword) {
        $('#admin-status').html(
            '<div class="alert alert-warning">' +
            '<strong>Validation Error:</strong> Passwords do not match.' +
            '</div>'
        );
        return;
    }
    
    var formData = {
        password: password,
        confirm_password: confirmPassword
    };
    
    $('#admin-status').html(
        '<div class="alert alert-info">' +
        '<i class="fa fa-spinner fa-spin"></i> Saving admin configuration...' +
        '</div>'
    );
    
    Installer.apiCall('configure-admin', formData, function(data) {
        $('#admin-status').html(
            '<div class="alert alert-success">' +
            '<strong>Admin Configuration Saved!</strong> ' + data.message +
            '</div>'
        );
        
        // Store admin config for later steps
        Installer.stepData.admin = { configured: true };
        
        // Automatically proceed to next step after a short delay
        setTimeout(function() {
            Installer.nextStep();
        }, 1500);
    }, function(error) {
        $('#admin-status').html(
            '<div class="alert alert-danger">' +
            '<strong>Configuration Failed:</strong> ' + error +
            '</div>'
        );
    });
}
</script>