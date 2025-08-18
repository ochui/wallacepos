<?php
/**
 * Customer Account Activation
 * 
 * This file implements the modern bootstrap pattern for customer activation.
 */

// Register the Composer autoloader...
require __DIR__ . '/../../vendor/autoload.php';

// Bootstrap the application...
/** @var \App\Core\Application $app */
$app = require_once __DIR__ . '/../../bootstrap/app.php';

$activated = false;
$error = "No token supplied!";

if (isset($_REQUEST['token'])){
    // try to activate account with token
    $custAc = new WposCustomerAccess();
    $error = $custAc->activateAccount($_REQUEST['token']);
    if ($error===true){
        $activated = true;
    }
}
?>
<html>
<head>
    <title>Account Activation</title>
    <link rel="stylesheet" href="/assets/ace.form.css"/>
    <script>
        function redirect(){
            document.location.href = "<?php echo(isset($_REQUEST['redirect'])?htmlspecialchars($_REQUEST['redirect'], ENT_QUOTES, 'UTF-8'):'/'); ?>";
        }
    </script>
</head>
<body style="text-align: center;" onload="<?php if($activated){ echo("setTimeout('redirect();', 2000);"); } ?>">
<img style="width: 200px;" src="/assets/images/receipt-logo.png"/>
<?php
    if ($activated){
        echo("<h3 class='smaller'>Account Successfully Activated!</h3>Redirecting...");
    } else {
        echo("<h3 class='smaller'>Account Activation Failed</h3>".$error);
    }
?>
</body>
</html>