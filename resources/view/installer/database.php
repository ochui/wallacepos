<div style="text-align: center;">
    <ul class="breadcrumb">
        <li><a href="javascript:void(0)" onclick="POS.loadInstallerStep('requirements')">1. Check Requirements</a></li>
        <li class="active"><strong>2. Configure Database</strong></li>
        <li>3. Admin Setup</li>
        <li>4. Install System</li>
    </ul>
</div>

<div>
    <h4>Database Configuration</h4>
    <p>Enter your database connection details below. The installer will test the connection and create the .env configuration file.</p>
    <div class="space-4"></div>
    
    <form id="database-form">
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <label for="db-host">Database Host</label>
                    <input type="text" class="form-control" id="db-host" name="host" value="localhost" required>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <label for="db-port">Port</label>
                    <input type="number" class="form-control" id="db-port" name="port" value="3306" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="db-name">Database Name</label>
            <input type="text" class="form-control" id="db-name" name="database" placeholder="freepos" required>
        </div>
        
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <label for="db-username">Username</label>
                    <input type="text" class="form-control" id="db-username" name="username" required>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <label for="db-password">Password</label>
                    <input type="password" class="form-control" id="db-password" name="password">
                </div>
            </div>
        </div>
        
        <div id="database-test-result" class="space-4"></div>
    </form>
    
    <hr/>
    <div style="height: 40px;">
        <button class="pull-left btn btn-info" onclick="POS.loadInstallerStep('requirements')">
            <i class="icon-arrow-left"></i> Back
        </button>
        <button class="btn btn-info" onclick="testDatabaseConnection()" style="margin-left: 10px;">
            <i class="icon-cog"></i> Test Connection
        </button>
        <button id="next-button" type="button" class="pull-right btn btn-primary" disabled onclick="saveDatabaseConfig()">
            Next <i class="icon-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function testDatabaseConnection() {
    var formData = $('#database-form').serialize();
    
    $('#database-test-result').html('<div class="alert alert-info"><i class="icon-spinner icon-spin"></i> Testing database connection...</div>');
    $('#next-button').prop('disabled', true);
    
    POS.sendFormDataAsync('install/test-database', formData, function(result) {
        if (result) {
            $('#database-test-result').html('<div class="alert alert-success"><i class="icon-check"></i> Database connection successful!</div>');
            $('#next-button').prop('disabled', false);
        } else {
            $('#database-test-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> Database connection failed. Please check your settings.</div>');
            $('#next-button').prop('disabled', true);
        }
    }, function(error) {
        $('#database-test-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> ' + error + '</div>');
        $('#next-button').prop('disabled', true);
    });
}

function saveDatabaseConfig() {
    var formData = $('#database-form').serialize();
    
    $('#database-test-result').html('<div class="alert alert-info"><i class="icon-spinner icon-spin"></i> Saving database configuration...</div>');
    $('#next-button').prop('disabled', true);
    
    POS.sendFormDataAsync('install/save-database', formData, function(result) {
        if (result) {
            $('#database-test-result').html('<div class="alert alert-success"><i class="icon-check"></i> Database configuration saved!</div>');
            setTimeout(function() {
                POS.loadInstallerStep('setup');
            }, 1000);
        } else {
            $('#database-test-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> Failed to save database configuration.</div>');
            $('#next-button').prop('disabled', false);
        }
    }, function(error) {
        $('#database-test-result').html('<div class="alert alert-danger"><i class="icon-remove"></i> ' + error + '</div>');
        $('#next-button').prop('disabled', false);
    });
}

// Auto-test connection if all fields are filled
$('#database-form input').on('blur', function() {
    var allFilled = true;
    $('#database-form input[required]').each(function() {
        if ($(this).val() === '') {
            allFilled = false;
            return false;
        }
    });
    
    if (allFilled) {
        testDatabaseConnection();
    }
});
</script>
