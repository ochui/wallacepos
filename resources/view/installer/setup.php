<div style="text-align: center;">
    <ul class="breadcrumb">
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('requirements')">1. Check Requirements</a></li>
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('database')">2. Configure Database</a></li>
        <li class="active"><strong>3. Admin Setup</strong></li>
        <li>4. Install System</li>
    </ul>
</div>

<div>
    <h4>Admin User Configuration</h4>
    <p>Create a secure password for the administrator account. This account will have full access to the system.</p>
    <div class="space-4"></div>
    
    <form id="admin-form">
        <div class="row">
            <div class="col-xs-12 col-sm-8 col-sm-offset-2">
                <div class="form-group">
                    <label for="admin-password">Administrator Password</label>
                    <input type="password" class="form-control" id="admin-password" name="password" placeholder="Enter a secure password (minimum 8 characters)" required>
                    <small class="help-block">Password should be at least 8 characters long and contain a mix of letters, numbers, and special characters.</small>
                </div>
                
                <div class="form-group">
                    <label for="admin-confirm-password">Confirm Password</label>
                    <input type="password" class="form-control" id="admin-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <div id="password-strength" class="space-4"></div>
                <div id="admin-result" class="space-4"></div>
            </div>
        </div>
    </form>
    
    <hr/>
    <div style="height: 40px;">
        <button class="pull-left btn btn-info" onclick="POS.loadInstallerStep('database')">
            <i class="icon-arrow-left"></i> Back
        </button>
        <button id="next-button" type="button" class="pull-right btn btn-primary" disabled onclick="configureAdmin()">
            Next <i class="icon-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function validatePassword() {
    var password = $('#admin-password').val();
    var confirmPassword = $('#admin-confirm-password').val();
    var strength = 0;
    var messages = [];
    
    // Check password length
    if (password.length >= 8) {
        strength++;
    } else {
        messages.push('At least 8 characters');
    }
    
    // Check for numbers
    if (/\d/.test(password)) {
        strength++;
    } else {
        messages.push('Include numbers');
    }
    
    // Check for letters
    if (/[a-zA-Z]/.test(password)) {
        strength++;
    } else {
        messages.push('Include letters');
    }
    
    // Check for special characters
    if (/[^a-zA-Z0-9]/.test(password)) {
        strength++;
    } else {
        messages.push('Include special characters');
    }
    
    // Update strength indicator
    var strengthText = '';
    var strengthClass = '';
    
    if (password.length === 0) {
        strengthText = '';
    } else if (strength < 2) {
        strengthText = 'Weak';
        strengthClass = 'text-danger';
    } else if (strength < 3) {
        strengthText = 'Medium';
        strengthClass = 'text-warning';
    } else {
        strengthText = 'Strong';
        strengthClass = 'text-success';
    }
    
    var html = '';
    if (strengthText) {
        html += '<small class="' + strengthClass + '">Password strength: ' + strengthText + '</small>';
        if (messages.length > 0) {
            html += '<br><small class="text-muted">Suggestions: ' + messages.join(', ') + '</small>';
        }
    }
    
    $('#password-strength').html(html);
    
    // Check if passwords match and enable next button
    var isValid = password.length >= 8 && password === confirmPassword;
    $('#next-button').prop('disabled', !isValid);
    
    return isValid;
}

function configureAdmin() {
    if (!validatePassword()) {
        $('#admin-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> Please fix the password issues above.</div>');
        return;
    }
    
    var formData = $('#admin-form').serialize();
    
    $('#admin-result').html('<div class="alert alert-info"><i class="icon-spinner icon-spin"></i> Configuring admin user...</div>');
    $('#next-button').prop('disabled', true);
    
    POS.sendFormDataAsync('install/configure-admin', formData, function(result) {
        if (result) {
            $('#admin-result').html('<div class="alert alert-success"><i class="icon-check"></i> Admin user configured successfully!</div>');
            setTimeout(function() {
                POS.loadInstallerStep('install');
            }, 1000);
        } else {
            $('#admin-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> Failed to configure admin user.</div>');
            $('#next-button').prop('disabled', false);
        }
    }, function(error) {
        $('#admin-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> ' + error + '</div>');
        $('#next-button').prop('disabled', false);
    });
}

// Real-time password validation
$('#admin-password, #admin-confirm-password').on('keyup', function() {
    validatePassword();
});
</script>
