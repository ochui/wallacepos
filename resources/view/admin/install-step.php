<?php
/**
 * Installation step template
 */
?>
<div class="step-content">
    <h3>Install FreePOS</h3>
    <p>Ready to install FreePOS! This will create the database tables and configure your system.</p>
    
    <div id="installation-status">
        <div class="alert alert-info">
            <strong>Ready to Install:</strong> Click the button below to begin the installation process.
        </div>
    </div>
    
    <div id="installation-progress" style="display: none;">
        <div class="progress">
            <div class="progress-bar progress-bar-info progress-bar-striped active" 
                 role="progressbar" style="width: 100%">
                <span>Installing FreePOS...</span>
            </div>
        </div>
    </div>

    <div class="step-buttons">
        <button type="button" class="btn btn-default" id="prev-step-btn">
            <i class="fa fa-arrow-left"></i> Back: Admin Setup
        </button>
        <button type="button" class="btn btn-success btn-lg" id="install-btn">
            <i class="fa fa-download"></i> Install FreePOS
        </button>
        <button type="button" class="btn btn-primary" id="finish-btn" style="display: none;">
            <i class="fa fa-check"></i> Access FreePOS Admin
        </button>
    </div>
</div>

<script>
function initStep4() {
    $('#prev-step-btn').click(function() {
        Installer.prevStep();
    });
    
    $('#install-btn').click(function() {
        performInstallation();
    });
    
    $('#finish-btn').click(function() {
        window.location.href = '/admin/';
    });
}

function performInstallation() {
    $('#install-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Installing...');
    $('#installation-progress').show();
    
    $('#installation-status').html(
        '<div class="alert alert-info">' +
        '<i class="fa fa-spinner fa-spin"></i> <strong>Installing FreePOS...</strong><br>' +
        'This may take a few moments. Please do not close this window.' +
        '</div>'
    );
    
    Installer.apiCall('install-with-config', {}, function(data) {
        $('#installation-progress').hide();
        
        if (data.indexOf('Installation Completed!') !== -1 || 
            data.indexOf('Database detected, skipping full installation.') !== -1) {
            
            $('#installation-status').html(
                '<div class="alert alert-success">' +
                '<strong><i class="fa fa-check"></i> Installation Completed Successfully!</strong><br>' +
                '<div style="margin-top: 10px; font-family: monospace; background: #f8f8f8; padding: 10px; border-radius: 4px;">' +
                data.replace(/\n/g, '<br>') +
                '</div>' +
                '</div>'
            );
            
            $('#install-btn').hide();
            $('#prev-step-btn').hide();
            $('#finish-btn').show();
            
            // Update step navigation to show completion
            $('.step-nav li').removeClass('active').addClass('completed');
            
        } else {
            $('#installation-status').html(
                '<div class="alert alert-warning">' +
                '<strong>Installation Result:</strong><br>' +
                '<div style="margin-top: 10px; font-family: monospace; background: #f8f8f8; padding: 10px; border-radius: 4px;">' +
                data.replace(/\n/g, '<br>') +
                '</div>' +
                '</div>'
            );
            
            $('#install-btn').prop('disabled', false).html('<i class="fa fa-download"></i> Install FreePOS');
        }
    }, function(error) {
        $('#installation-progress').hide();
        
        $('#installation-status').html(
            '<div class="alert alert-danger">' +
            '<strong>Installation Failed:</strong> ' + error +
            '</div>'
        );
        
        $('#install-btn').prop('disabled', false).html('<i class="fa fa-download"></i> Retry Installation');
    });
}
</script>