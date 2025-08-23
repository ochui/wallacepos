<?php
/**
 * Requirements step template
 */
?>
<div class="step-content">
    <h3>System Requirements Check</h3>
    <p>Please ensure your system meets all the requirements before proceeding with the installation.</p>
    
    <div id="requirements-list">
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Checking system requirements...</p>
        </div>
    </div>

    <div class="step-buttons">
        <button type="button" class="btn btn-primary" id="check-requirements-btn">
            <i class="fa fa-refresh"></i> Recheck Requirements
        </button>
        <button type="button" class="btn btn-success" id="next-step-btn" style="display: none;">
            Next: Configure Database <i class="fa fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function initStep1() {
    checkRequirements();
    
    $('#check-requirements-btn').click(function() {
        checkRequirements();
    });
    
    $('#next-step-btn').click(function() {
        Installer.nextStep();
    });
}

function checkRequirements() {
    $('#requirements-list').html(
        '<div class="text-center">' +
        '<i class="fa fa-spinner fa-spin fa-2x"></i>' +
        '<p>Checking system requirements...</p>' +
        '</div>'
    );
    
    Installer.apiCall('requirements', {}, function(data) {
        displayRequirements(data);
    });
}

function displayRequirements(data) {
    var html = '';
    var allPassed = data.all;
    
    for (var i = 0; i < data.requirements.length; i++) {
        var req = data.requirements[i];
        var statusClass = req.status ? 'requirement-pass' : 'requirement-fail';
        var statusIcon = req.status ? 'fa-check' : 'fa-times';
        
        html += '<div class="requirement-item ' + statusClass + '">';
        html += '<i class="fa ' + statusIcon + '"></i> ';
        html += '<strong>' + req.name + '</strong><br>';
        html += 'Current: ' + req.current + '<br>';
        html += 'Required: ' + req.required;
        html += '</div>';
    }
    
    $('#requirements-list').html(html);
    
    if (allPassed) {
        $('#next-step-btn').show();
        $('#requirements-list').append(
            '<div class="alert alert-success" style="margin-top: 20px;">' +
            '<strong>Great!</strong> All system requirements are met. You can proceed to the next step.' +
            '</div>'
        );
    } else {
        $('#next-step-btn').hide();
        $('#requirements-list').append(
            '<div class="alert alert-danger" style="margin-top: 20px;">' +
            '<strong>Requirements Not Met!</strong> Please resolve the issues above before proceeding.' +
            '</div>'
        );
    }
}
</script>