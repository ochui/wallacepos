<h3>System Requirements Check</h3>
<p>Please ensure your system meets all the requirements before proceeding with the installation.</p>

<div id="requirements-loading">
    <div class="progress">
        <div class="progress-bar progress-bar-striped active" style="width: 100%"></div>
    </div>
    <p>Checking system requirements...</p>
</div>

<div id="requirements-results" style="display: none;">
    <div id="requirements-list"></div>
    
    <div class="btn-navigation">
        <button type="button" class="btn btn-default" onclick="checkRequirements()">
            <i class="icon-refresh"></i> Refresh Check
        </button>
        <button id="btn-next-step" type="button" class="btn btn-primary pull-right" disabled onclick="proceedToNextStep()">
            Next Step <i class="icon-arrow-right"></i>
        </button>
        <div id="ignore-requirements" style="display: none; margin-top: 10px;">
            <label>
                <input type="checkbox" id="ignore-check" onchange="toggleIgnoreRequirements()">
                Ignore requirements check (not recommended)
            </label>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    checkRequirements();
});

function checkRequirements() {
    $('#requirements-loading').show();
    $('#requirements-results').hide();
    
    $.ajax({
        url: '../api/install/requirements',
        type: 'POST',
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