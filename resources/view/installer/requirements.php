<div style="text-align: center;">
    <ul class="breadcrumb">
        <li class="active"><strong>1. Check Requirements</strong></li>
        <li>2. Configure Database</li>
        <li>3. Admin Setup</li>
        <li>4. Install System</li>
    </ul>
</div>

<div>
    <h4>System Requirements Check</h4>
    <p>Please ensure your system meets all the requirements before proceeding with the installation.</p>
    <div class="space-4"></div>
    
    <div id="requirements-results">
        <div class="text-center">
            <i class="icon-spinner icon-spin icon-2x"></i><br/>
            Checking system requirements...
        </div>
    </div>
    
    <hr/>
    <div style="height: 40px;">
        <button class="pull-left btn btn-info" onclick="checkRequirements()">
            <i class="icon-refresh"></i> Refresh
        </button>
        <button id="next-button" type="button" class="pull-right btn btn-primary" disabled onclick="nextStep()">
            Next <i class="icon-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function checkRequirements() {
    $('#requirements-results').html('<div class="text-center"><i class="icon-spinner icon-spin icon-2x"></i><br/>Checking system requirements...</div>');
    $('#next-button').prop('disabled', true);
    
    var result = POS.getJsonData('install/requirements');
    if (result) {
        displayRequirements(result);
    } else {
        $('#requirements-results').html('<div class="alert alert-danger">Failed to check requirements. Please try again.</div>');
    }
}

function displayRequirements(data) {
    var html = '<ul class="list-unstyled spaced">';
    var allMet = data.all;
    
    for (var i = 0; i < data.requirements.length; i++) {
        var req = data.requirements[i];
        var iconClass = req.status ? 'icon-check green' : 'icon-remove red';
        
        html += '<li>';
        html += '<i class="icon icon-large ' + iconClass + '"></i> ';
        html += '<strong>' + req.name + '</strong><br/>';
        html += '<small>Current: ' + req.current + ' | Required: ' + req.required + '</small>';
        html += '</li>';
    }
    
    if (!allMet) {
        html += '<li class="space-6">';
        html += '<label><input type="checkbox" id="ignore-requirements" onchange="toggleNext()" /> ';
        html += '&nbsp;I understand the risks and want to proceed anyway</label>';
        html += '</li>';
    }
    
    html += '</ul>';
    
    $('#requirements-results').html(html);
    
    if (allMet) {
        $('#next-button').prop('disabled', false);
    }
}

function toggleNext() {
    var checked = $('#ignore-requirements').is(':checked');
    $('#next-button').prop('disabled', !checked);
}

function nextStep() {
    POS.loadInstallerStep('database');
}

// Auto-check requirements on load
$(document).ready(function() {
    checkRequirements();
});
</script>

