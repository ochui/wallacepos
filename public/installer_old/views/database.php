<h3>Database Configuration</h3>
<p>Please provide your database connection details. FreePOS will test the connection and create the .env configuration file.</p>

<form id="database-form">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="db_host">Database Host</label>
                <input type="text" class="form-control" id="db_host" name="host" value="localhost" required>
                <small class="help-block">Usually 'localhost' or '127.0.0.1'</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="db_port">Database Port</label>
                <input type="number" class="form-control" id="db_port" name="port" value="3306" required>
                <small class="help-block">Default MySQL port is 3306</small>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="db_name">Database Name</label>
        <input type="text" class="form-control" id="db_name" name="database" placeholder="freepos" required>
        <small class="help-block">The database must already exist</small>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="db_username">Database Username</label>
                <input type="text" class="form-control" id="db_username" name="username" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="db_password">Database Password</label>
                <input type="password" class="form-control" id="db_password" name="password">
                <small class="help-block">Leave empty if no password</small>
            </div>
        </div>
    </div>
    
    <div class="btn-navigation">
        <button type="button" class="btn btn-default" onclick="window.location.href='index.php?step=1'">
            <i class="icon-arrow-left"></i> Back
        </button>
        
        <button type="button" class="btn btn-info" onclick="testDatabaseConnection()">
            <i class="icon-check"></i> Test Connection
        </button>
        
        <button type="button" class="btn btn-primary pull-right" id="btn-save-database" disabled onclick="saveDatabaseConfig()">
            Save & Continue <i class="icon-arrow-right"></i>
        </button>
    </div>
</form>

<script>
var connectionTested = false;

function testDatabaseConnection() {
    removeAlerts();
    
    var formData = {
        host: $('#db_host').val(),
        port: $('#db_port').val(),
        database: $('#db_name').val(),
        username: $('#db_username').val(),
        password: $('#db_password').val()
    };
    
    // Basic validation
    if (!formData.database || !formData.username) {
        showAlert('error', 'Database name and username are required.');
        return;
    }
    
    // Show loading state
    var testBtn = $('button:contains("Test Connection")');
    var originalText = testBtn.html();
    testBtn.html('<i class="icon-spinner icon-spin"></i> Testing...').prop('disabled', true);
    
    $.ajax({
        url: '../api/install/test-database',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            testBtn.html(originalText).prop('disabled', false);
            
            if (response.errorCode === 'OK') {
                showAlert('success', '<strong>Database connection successful!</strong> You can now save the configuration.');
                connectionTested = true;
                $('#btn-save-database').prop('disabled', false);
            } else {
                showAlert('error', '<strong>Database connection failed:</strong> ' + response.error);
                connectionTested = false;
                $('#btn-save-database').prop('disabled', true);
            }
        },
        error: function() {
            testBtn.html(originalText).prop('disabled', false);
            showAlert('error', 'Unable to test database connection. Please check your configuration.');
            connectionTested = false;
            $('#btn-save-database').prop('disabled', true);
        }
    });
}

function saveDatabaseConfig() {
    if (!connectionTested) {
        showAlert('error', 'Please test the database connection first.');
        return;
    }
    
    removeAlerts();
    
    var formData = {
        host: $('#db_host').val(),
        port: $('#db_port').val(),
        database: $('#db_name').val(),
        username: $('#db_username').val(),
        password: $('#db_password').val()
    };
    
    // Show loading state
    var saveBtn = $('#btn-save-database');
    var originalText = saveBtn.html();
    saveBtn.html('<i class="icon-spinner icon-spin"></i> Saving...').prop('disabled', true);
    
    $.ajax({
        url: '../api/install/save-database',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.errorCode === 'OK') {
                showAlert('success', '<strong>Database configuration saved!</strong> Proceeding to admin setup...');
                setTimeout(function() {
                    window.location.href = 'index.php?step=3';
                }, 2000);
            } else {
                saveBtn.html(originalText).prop('disabled', false);
                showAlert('error', '<strong>Failed to save configuration:</strong> ' + response.error);
            }
        },
        error: function() {
            saveBtn.html(originalText).prop('disabled', false);
            showAlert('error', 'Unable to save database configuration. Please check file permissions.');
        }
    });
}

// Enable save button when form changes (in case connection was already tested)
$('#database-form input').on('input', function() {
    connectionTested = false;
    $('#btn-save-database').prop('disabled', true);
});
</script>