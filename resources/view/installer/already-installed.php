<div style="text-align: center;">
    <ul class="breadcrumb">
        <li class="active"><strong>System Status</strong></li>
    </ul>
</div>

<div>
    <h4>FreePOS Already Installed</h4>
    <div class="space-4"></div>
    
    <div class="alert alert-success">
        <i class="icon-check-circle"></i>
        <strong>FreePOS is already installed and running!</strong>
    </div>
    
    <div class="well">
        <h5><i class="icon-info-circle"></i> System Information</h5>
        <ul class="list-unstyled">
            <li><strong>Current Version:</strong> <span id="current-version">Loading...</span></li>
            <li><strong>Latest Version:</strong> <span id="latest-version">Loading...</span></li>
            <li><strong>Status:</strong> <span class="label label-success">Up to Date</span></li>
        </ul>
    </div>
    
    <div class="space-4"></div>
    
    <div class="alert alert-info">
        <i class="icon-lightbulb-o"></i>
        <strong>What can you do now?</strong>
        <ul class="list-unstyled" style="margin-top: 10px;">
            <li>• Access your <a href="/admin" target="_blank">Admin Dashboard</a></li>
            <li>• Access your <a href="/dashboard" target="_blank">POS System</a></li>
            <li>• Check for updates using the button below</li>
        </ul>
    </div>
    
    <hr/>
    <div style="height: 40px;">
        <button class="pull-left btn btn-info" onclick="checkForUpdates()">
            <i class="icon-refresh"></i> Check for Updates
        </button>
        <a href="/admin" class="pull-right btn btn-success" target="_blank">
            <i class="icon-external-link"></i> Go to Admin
        </a>
    </div>
</div>

<script>
function checkForUpdates() {
    // Reload installer to check for updates
    window.location.reload();
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