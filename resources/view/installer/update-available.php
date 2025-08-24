<div style="text-align: center;">
    <ul class="breadcrumb">
        <li class="active"><strong>System Update Available</strong></li>
    </ul>
</div>

<div>
    <h4>FreePOS Update Available</h4>
    <div class="space-4"></div>
    
    <div class="alert alert-warning">
        <i class="icon-exclamation-triangle"></i>
        <strong>A new version of FreePOS is available!</strong>
    </div>
    
    <div class="well">
        <h5><i class="icon-info-circle"></i> Version Information</h5>
        <ul class="list-unstyled">
            <li><strong>Current Version:</strong> <span id="current-version">Loading...</span></li>
            <li><strong>Latest Version:</strong> <span id="latest-version">Loading...</span></li>
            <li><strong>Status:</strong> <span class="label label-warning">Update Available</span></li>
        </ul>
    </div>
    
    <div class="space-4"></div>
    
    <div class="alert alert-info">
        <i class="icon-info-circle"></i>
        <strong>Before updating:</strong>
        <ul class="list-unstyled" style="margin-top: 10px;">
            <li>• Ensure you have a recent backup of your database</li>
            <li>• Close all POS terminals and admin sessions</li>
            <li>• The update process may take a few minutes</li>
            <li>• Do not close this page during the update process</li>
        </ul>
    </div>
    
    <div id="update-progress" style="display: none;">
        <div class="alert alert-info">
            <div class="text-center">
                <i class="icon-spinner icon-spin icon-2x"></i><br/>
                <strong>Updating FreePOS...</strong><br/>
                <small>Please wait while the update is being applied.</small>
            </div>
        </div>
        <div class="progress progress-striped active">
            <div class="progress-bar" role="progressbar" style="width: 100%"></div>
        </div>
    </div>
    
    <div id="update-result" style="display: none;"></div>
    
    <hr/>
    <div style="height: 40px;">
        <button class="pull-left btn btn-default" onclick="continueWithoutUpdate()">
            <i class="icon-arrow-left"></i> Continue Without Update
        </button>
        <button id="update-button" class="pull-right btn btn-warning" onclick="startUpdate()">
            <i class="icon-download"></i> Start Update
        </button>
        <a id="admin-link" href="/admin" class="pull-right btn btn-success" target="_blank" style="display: none;">
            <i class="icon-external-link"></i> Go to Admin
        </a>
    </div>
</div>

<script>
function startUpdate() {
    // Show progress and disable button
    $('#update-progress').show();
    $('#update-button').prop('disabled', true).text('Updating...');
    
    // Call the upgrade API
    POS.getJsonDataAsync('install/upgrade', function(data) {
        $('#update-progress').hide();
        
        if (data) {
            $('#update-result').html(
                '<div class="alert alert-success">' +
                '<i class="icon-check-circle"></i> ' +
                '<strong>Update completed successfully!</strong><br/>' +
                '<small>' + data + '</small>' +
                '</div>'
            ).show();
            
            // Show admin link and hide update button
            $('#update-button').hide();
            $('#admin-link').show();
            
        } else {
            $('#update-result').html(
                '<div class="alert alert-danger">' +
                '<i class="icon-exclamation-triangle"></i> ' +
                '<strong>Update failed!</strong><br/>' +
                '<small>Please check the logs and try again, or contact support.</small>' +
                '</div>'
            ).show();
            
            // Re-enable update button
            $('#update-button').prop('disabled', false).text('Retry Update');
        }
    });
}

function continueWithoutUpdate() {
    // Redirect to admin or show already installed screen
    window.location.href = '/admin';
}

// Load version information from URL parameters
$(document).ready(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var currentVersion = urlParams.get('current_version') || 'Unknown';
    var latestVersion = urlParams.get('latest_version') || 'Unknown';
    
    $('#current-version').text(currentVersion);
    $('#latest-version').text(latestVersion);
});
</script>