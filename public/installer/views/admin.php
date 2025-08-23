<h3>Admin User Setup</h3>
<p>Create a password for the default admin user. You can change this later from the admin dashboard.</p>

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <form id="admin-form">
            <div class="form-group">
                <label for="admin_password">Admin Password</label>
                <input type="password" class="form-control" id="admin_password" name="password" required>
                <small class="help-block">Must be at least 8 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="admin_password_confirm">Confirm Password</label>
                <input type="password" class="form-control" id="admin_password_confirm" name="confirm_password" required>
                <small class="help-block">Enter the same password again</small>
            </div>
            
            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                <strong>Default Login Credentials:</strong><br>
                Username: <code>admin</code><br>
                Password: <em>(the password you set above)</em>
            </div>
            
            <div class="alert alert-warning">
                <i class="icon-warning"></i>
                <strong>Security Note:</strong> Make sure to use a strong password and change it regularly for security.
            </div>
        </form>
    </div>
</div>

<div class="btn-navigation">
    <button type="button" class="btn btn-default" onclick="window.location.href='index.php?step=2'">
        <i class="icon-arrow-left"></i> Back
    </button>
    
    <button type="button" class="btn btn-primary pull-right" onclick="configureAdmin()">
        Save & Continue <i class="icon-arrow-right"></i>
    </button>
</div>

<script>
function configureAdmin() {
    removeAlerts();
    
    var password = $('#admin_password').val();
    var confirmPassword = $('#admin_password_confirm').val();
    
    // Validation
    if (!password) {
        showAlert('error', 'Password is required.');
        return;
    }
    
    if (password.length < 8) {
        showAlert('error', 'Password must be at least 8 characters long.');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('error', 'Passwords do not match.');
        return;
    }
    
    // Show loading state
    var saveBtn = $('button:contains("Save & Continue")');
    var originalText = saveBtn.html();
    saveBtn.html('<i class="icon-spinner icon-spin"></i> Saving...').prop('disabled', true);
    
    $.ajax({
        url: '../api/install/configure-admin',
        type: 'POST',
        data: {
            password: password,
            confirm_password: confirmPassword
        },
        dataType: 'json',
        success: function(response) {
            if (response.errorCode === 'OK') {
                showAlert('success', '<strong>Admin configuration saved!</strong> Proceeding to installation...');
                setTimeout(function() {
                    window.location.href = 'index.php?step=4';
                }, 2000);
            } else {
                saveBtn.html(originalText).prop('disabled', false);
                showAlert('error', '<strong>Failed to configure admin:</strong> ' + response.error);
            }
        },
        error: function() {
            saveBtn.html(originalText).prop('disabled', false);
            showAlert('error', 'Unable to save admin configuration. Please try again.');
        }
    });
}

// Real-time password validation
$('#admin_password, #admin_password_confirm').on('input', function() {
    var password = $('#admin_password').val();
    var confirmPassword = $('#admin_password_confirm').val();
    
    // Remove previous validation messages
    $('.form-group').removeClass('has-error has-success');
    $('.password-help').remove();
    
    if (password.length > 0) {
        if (password.length < 8) {
            $('#admin_password').parent().addClass('has-error');
            $('#admin_password').after('<div class="password-help text-danger"><small>Password too short (minimum 8 characters)</small></div>');
        } else {
            $('#admin_password').parent().addClass('has-success');
        }
    }
    
    if (confirmPassword.length > 0) {
        if (password !== confirmPassword) {
            $('#admin_password_confirm').parent().addClass('has-error');
            $('#admin_password_confirm').after('<div class="password-help text-danger"><small>Passwords do not match</small></div>');
        } else if (password.length >= 8) {
            $('#admin_password_confirm').parent().addClass('has-success');
        }
    }
});
</script>