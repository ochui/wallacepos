<div style="text-align: center;">
    <ul class="breadcrumb">
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('requirements')">1. Check Requirements</a></li>
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('database')">2. Configure Database</a></li>
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('setup')">3. Admin Setup</a></li>
        <li class="active"><strong>4. Install System</strong></li>
    </ul>
</div>

<div>
    <div id="install-view">
        <h4>System Installation</h4>
        <p>The system is now ready to be installed. Click the button below to start the installation process.</p>
        <div class="space-4"></div>
        
        <div id="install-progress" class="text-center" style="padding: 20px 0; min-height: 120px;">
            <div id="install-status">
                Ready to install FreePOS system...
            </div>
            <div>
                <button id="install-button" class="btn btn-success btn-lg" onclick="startInstallation()" style="padding: 12px 24px; font-size: 16px; display: inline-block !important; visibility: visible !important;">
                    <i class="icon-download"></i> Install FreePOS
                </button>
            </div>
        </div>
    </div>
    
    <div id="complete-view" class="hide">
        <h4><i class="icon-check green"></i> Installation Complete!</h4>
        <div class="space-4"></div>
        <div class="alert alert-success">
            <h5>FreePOS has been successfully installed!</h5>
            <p>Your Point of Sale system is now ready to use.</p>
        </div>
        
        <div class="space-4"></div>
        <div class="text-center">
            <a href="/admin" class="btn btn-primary btn-lg">
                <i class="icon-lock"></i> Go to Admin Panel
            </a>
            <span class="space-2"></span>
            <a href="/" class="btn btn-success btn-lg">
                <i class="icon-shopping-cart"></i> Go to POS Terminal
            </a>
        </div>
        
        <div class="space-6"></div>
        <div class="well">
            <h5>Next Steps:</h5>
            <ul class="list-unstyled">
                <li><i class="icon-check"></i> Login to the admin panel with the password you configured</li>
                <li><i class="icon-cog"></i> Configure your store settings and locations</li>
                <li><i class="icon-plus"></i> Add your products and categories</li>
                <li><i class="icon-shopping-cart"></i> Start selling!</li>
            </ul>
        </div>
    </div>
</div>

<script>
function startInstallation() {
    $('#install-button').prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Installing...');
    $('#install-status').html('<div class="alert alert-info"><i class="icon-spinner icon-spin"></i> Installing FreePOS database and system files...</div>');
    
    POS.sendJsonDataAsync('install/install-with-config', {}, function(result) {
        if (result) {
            $('#install-status').html('<div class="alert alert-success"><i class="icon-check"></i> Installation completed successfully!</div>');
            setTimeout(function() {
                $('#install-view').hide();
                $('#complete-view').removeClass('hide').show();
            }, 2000);
        } else {
            $('#install-status').html('<div class="alert alert-danger"><i class="icon-remove"></i> Installation failed. Please check the logs and try again.</div>');
            $('#install-button').prop('disabled', false).html('<i class="icon-download"></i> Retry Installation');
        }
    }, function(error) {
        $('#install-status').html('<div class="alert alert-danger"><i class="icon-remove"></i> Installation error: ' + error + '</div>');
        $('#install-button').prop('disabled', false).html('<i class="icon-download"></i> Retry Installation');
    });
}

// Auto-start installation if coming from previous step
$(document).ready(function() {
    // Optional: Auto-start installation after a short delay
    // setTimeout(function() {
    //     startInstallation();
    // }, 2000);
});
</script>