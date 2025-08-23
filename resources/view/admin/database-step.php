<?php
/**
 * Database configuration step template
 */
?>
<div class="step-content">
    <h3>Database Configuration</h3>
    <p>Configure your database connection settings. The installer will test the connection and create the .env configuration file.</p>
    
    <form id="database-form" class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label">Database Host:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="host" id="db-host" value="localhost" required>
                <span class="help-block">Usually localhost for local installations</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">Database Port:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" name="port" id="db-port" value="3306" required>
                <span class="help-block">Default MySQL port is 3306</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">Database Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="database" id="db-name" required>
                <span class="help-block">The database must already exist</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">Username:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="username" id="db-username" required>
                <span class="help-block">Database user with read/write permissions</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-sm-3 control-label">Password:</label>
            <div class="col-sm-9">
                <input type="password" class="form-control" name="password" id="db-password">
                <span class="help-block">Leave blank if no password is required</span>
            </div>
        </div>
    </form>
    
    <div id="database-status" style="margin-top: 20px;"></div>

    <div class="step-buttons">
        <button type="button" class="btn btn-default" id="prev-step-btn">
            <i class="fa fa-arrow-left"></i> Back: Requirements
        </button>
        <button type="button" class="btn btn-info" id="test-connection-btn">
            <i class="fa fa-plug"></i> Test Connection
        </button>
        <button type="button" class="btn btn-success" id="save-config-btn" style="display: none;">
            Save & Continue <i class="fa fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function initStep2() {
    $('#prev-step-btn').click(function() {
        Installer.prevStep();
    });
    
    $('#test-connection-btn').click(function() {
        testDatabaseConnection();
    });
    
    $('#save-config-btn').click(function() {
        saveDatabaseConfig();
    });
    
    // Auto-test on form change
    $('#database-form input').on('blur', function() {
        if ($(this).val() && $('#db-name').val() && $('#db-username').val()) {
            testDatabaseConnection();
        }
    });
}

function testDatabaseConnection() {
    var formData = {
        host: $('#db-host').val(),
        port: $('#db-port').val(),
        database: $('#db-name').val(),
        username: $('#db-username').val(),
        password: $('#db-password').val()
    };
    
    // Validate required fields
    if (!formData.database || !formData.username) {
        $('#database-status').html(
            '<div class="alert alert-warning">' +
            '<strong>Validation Error:</strong> Database name and username are required.' +
            '</div>'
        );
        return;
    }
    
    $('#database-status').html(
        '<div class="alert alert-info">' +
        '<i class="fa fa-spinner fa-spin"></i> Testing database connection...' +
        '</div>'
    );
    
    Installer.apiCall('test-database', formData, function(data) {
        $('#database-status').html(
            '<div class="alert alert-success">' +
            '<strong>Success!</strong> ' + data.message +
            '</div>'
        );
        $('#save-config-btn').show();
    }, function(error) {
        $('#database-status').html(
            '<div class="alert alert-danger">' +
            '<strong>Connection Failed:</strong> ' + error +
            '</div>'
        );
        $('#save-config-btn').hide();
    });
}

function saveDatabaseConfig() {
    var formData = {
        host: $('#db-host').val(),
        port: $('#db-port').val(),
        database: $('#db-name').val(),
        username: $('#db-username').val(),
        password: $('#db-password').val()
    };
    
    $('#database-status').html(
        '<div class="alert alert-info">' +
        '<i class="fa fa-spinner fa-spin"></i> Saving configuration...' +
        '</div>'
    );
    
    Installer.apiCall('save-database', formData, function(data) {
        $('#database-status').html(
            '<div class="alert alert-success">' +
            '<strong>Configuration Saved!</strong> ' + data.message +
            '</div>'
        );
        
        // Store database config for later steps
        Installer.stepData.database = formData;
        
        // Automatically proceed to next step after a short delay
        setTimeout(function() {
            Installer.nextStep();
        }, 1500);
    }, function(error) {
        $('#database-status').html(
            '<div class="alert alert-danger">' +
            '<strong>Save Failed:</strong> ' + error +
            '</div>'
        );
    });
}
</script>