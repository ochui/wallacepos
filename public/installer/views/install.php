<h3>System Installation</h3>

<div class="install-waiting">
    <p>All configuration steps completed successfully. Click the button below to begin the installation process.</p>
    
    <div class="alert alert-info">
        <i class="icon-info-circle"></i>
        <strong>What happens during installation:</strong>
        <ul style="margin-top: 10px; margin-bottom: 0;">
            <li>Database tables will be created</li>
            <li>Default data will be inserted</li>
            <li>Admin user will be configured</li>
            <li>System settings will be initialized</li>
        </ul>
    </div>
    
    <div class="text-center" style="margin: 30px 0;">
        <button type="button" class="btn btn-success btn-lg" onclick="startInstallation()">
            <i class="icon-play"></i> Start Installation
        </button>
    </div>
</div>

<div class="install-progress" id="install-progress">
    <h4>Installing FreePOS...</h4>
    <div class="progress progress-striped active">
        <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
    </div>
    <div id="install-status">Preparing installation...</div>
    
    <div class="alert alert-warning">
        <i class="icon-warning"></i>
        <strong>Important:</strong> Do not close this window or navigate away until the installation is complete.
    </div>
</div>

<div class="install-complete" id="install-complete">
    <div class="alert alert-success">
        <i class="icon-check-circle"></i>
        <strong>Installation Complete!</strong> FreePOS has been successfully installed and configured.
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h4 class="panel-title">Login Credentials</h4>
                </div>
                <div class="panel-body">
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> <em>(the password you configured)</em>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h4 class="panel-title">Security Notice</h4>
                </div>
                <div class="panel-body">
                    Please delete or secure the <code>/installer</code> directory after installation for security.
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center" style="margin-top: 30px;">
        <a href="../admin/" class="btn btn-primary btn-lg">
            <i class="icon-dashboard"></i> Go to Admin Dashboard
        </a>
        <a href="../" class="btn btn-info btn-lg">
            <i class="icon-shopping-cart"></i> Go to POS System
        </a>
    </div>
</div>

<div class="btn-navigation">
    <button type="button" class="btn btn-default" onclick="window.location.href='index.php?step=3'" id="back-button">
        <i class="icon-arrow-left"></i> Back
    </button>
</div>

<script>
function startInstallation() {
    $('.install-waiting').hide();
    $('#install-progress').show();
    $('#back-button').hide();
    
    updateProgress(10, 'Initializing installation...');
    
    setTimeout(function() {
        updateProgress(25, 'Creating database tables...');
        performInstallation();
    }, 1000);
}

function performInstallation() {
    $.ajax({
        url: '../api/install/install-with-config',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.errorCode === 'OK') {
                updateProgress(75, 'Configuring system settings...');
                
                setTimeout(function() {
                    updateProgress(90, 'Finalizing installation...');
                    
                    setTimeout(function() {
                        updateProgress(100, 'Installation completed successfully!');
                        
                        setTimeout(function() {
                            $('#install-progress').hide();
                            $('#install-complete').show();
                        }, 1500);
                    }, 1000);
                }, 1000);
            } else {
                updateProgress(0, 'Installation failed: ' + response.error);
                $('.progress-bar').removeClass('progress-bar-success').addClass('progress-bar-danger');
                $('.progress').removeClass('active');
                
                showAlert('error', '<strong>Installation Failed:</strong> ' + response.error);
                $('#back-button').show();
            }
        },
        error: function(xhr, status, error) {
            updateProgress(0, 'Installation failed: Connection error');
            $('.progress-bar').removeClass('progress-bar-success').addClass('progress-bar-danger');
            $('.progress').removeClass('active');
            
            showAlert('error', '<strong>Installation Failed:</strong> Unable to complete installation. Please check your configuration and try again.');
            $('#back-button').show();
        }
    });
}

function updateProgress(percent, message) {
    $('#progress-bar').css('width', percent + '%');
    $('#install-status').text(message);
    
    if (percent === 100) {
        $('.progress-bar').addClass('progress-bar-success');
        $('.progress').removeClass('active');
    }
}
</script>