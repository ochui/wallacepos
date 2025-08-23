<?php
/**
 * Installer Entry Point
 * 
 * This file serves as the installer entry point and routes to the appropriate handler
 */

// Check if composer dependencies are installed
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
$composerInstalled = file_exists($vendorAutoload);

if (!$composerInstalled) {
    // Serve basic installer page with composer installation instructions
    serveBasicInstaller();
    exit;
}

// Register the Composer autoloader...
require $vendorAutoload;

// Bootstrap and handle the installer request...
/** @var \App\Core\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Manually set REQUEST_URI for installer
$_SERVER['REQUEST_URI'] = '/installer';
$_SERVER['REQUEST_METHOD'] = 'GET';

$app->handleRequest();

/**
 * Serve basic installer when composer is not installed
 */
function serveBasicInstaller() {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>FreePOS - Setup Required</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .logo { text-align: center; margin-bottom: 30px; color: #337ab7; }
            .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
            .alert-warning { background-color: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
            .alert-info { background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; }
            pre { background: #f8f8f8; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .btn { display: inline-block; padding: 10px 20px; background: #337ab7; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
            .btn:hover { background: #286090; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">
                <h1>🛒 FreePOS</h1>
                <h3>Setup Required</h3>
            </div>
            
            <div class="alert alert-warning">
                <strong>Composer Dependencies Missing!</strong><br>
                FreePOS requires Composer dependencies to be installed before you can use the installer.
            </div>
            
            <h4>Installation Steps:</h4>
            <ol>
                <li><strong>Install Composer</strong> (if not already installed):
                    <pre>curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer</pre>
                </li>
                <li><strong>Install FreePOS dependencies</strong>:
                    <pre>cd <?php echo htmlspecialchars(dirname(__DIR__)); ?>
composer install --no-dev</pre>
                </li>
                <li><strong>Refresh this page</strong> to continue with the installation.</li>
            </ol>
            
            <div class="alert alert-info">
                <strong>Alternative:</strong> If you have shell access, you can run the installation commands above, then refresh this page to access the full installer.
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="installer.php" class="btn">Refresh Installer</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}