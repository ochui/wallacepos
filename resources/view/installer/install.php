<div style="text-align: center;">
<?php
    if ($_REQUEST['doupgrade']) {
        ?>
        <ul class="breadcrumb">
            <li>Check Requirements</li>
            <li><strong>Upgrade</strong></li>
        </ul>
<?php
    } else {
?>
        <ul class="breadcrumb">
            <li>Check Requirements</li>
            <li>Configure Database</li>
            <li>Initial Setup</li>
            <li><strong>Install System</strong></li>
        </ul>
<?php
    }
?>
</div>
<div style="text-align: center;">
    <div id="install_view">
        <h4>Installing System</h4>
        <h5>Do not leave the page until the process is complete</h5>
    </div>
    <div id="complete_view" class="hide">
        <h4>Installation Complete</h4>
        <h5>Check the below frame for errors. If successful <a href="/admin">click here</a> to login.</h5>
    </div>
    
</div>