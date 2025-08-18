<?php

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Auth;
use App\Controllers\Admin\WposAdminItems;
use App\Controllers\Admin\WposAdminGraph;
use App\Controllers\Admin\WposAdminSettings;
use App\Controllers\Admin\WposAdminStats;
use App\Controllers\Admin\WposAdminUtilities;
use App\Controllers\Admin\WposAdminCustomers;
use App\Controllers\Admin\WposAdminStock;
use App\Controllers\Pos\WposPosSetup;
use App\Controllers\Pos\WposPosData;
use App\Controllers\Pos\WposPosSale;
use App\Controllers\ViewController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\PosController;
use App\Controllers\Api\AdminController;
use App\Controllers\Api\CustomerController;

/**
 * Main Application class that handles request routing and processing
 */
class Application
{
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];
    private $auth;
    private $dispatcher;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->dispatcher = $this->createDispatcher();
    }

    /**
     * Create FastRoute dispatcher with route definitions
     */
    private function createDispatcher()
    {
        return simpleDispatcher(function(RouteCollector $r) {
            // Template content routes
            $r->get('/admin/content/{template}', [ViewController::class, 'adminContent']);
            $r->get('/customer/content/{template}', [ViewController::class, 'customerContent']);
            
            // Authentication routes
            $r->addRoute(['GET', 'POST'], '/api/auth', [AuthController::class, 'authenticate']);
            $r->addRoute(['GET', 'POST'], '/api/authrenew', [AuthController::class, 'renewToken']);
            $r->addRoute(['GET', 'POST'], '/api/logout', [AuthController::class, 'logout']);
            $r->addRoute(['GET', 'POST'], '/api/hello', [AuthController::class, 'hello']);
            $r->addRoute(['GET', 'POST'], '/api/auth/websocket', [AuthController::class, 'authorizeWebsocket']);
            
            // POS routes
            $r->addRoute(['GET', 'POST'], '/api/config/get', [PosController::class, 'getConfig']);
            $r->addRoute(['GET', 'POST'], '/api/items/get', [PosController::class, 'getItems']);
            $r->addRoute(['GET', 'POST'], '/api/sales/get', [PosController::class, 'getSales']);
            $r->addRoute(['GET', 'POST'], '/api/tax/get', [PosController::class, 'getTaxes']);
            $r->addRoute(['GET', 'POST'], '/api/customers/get', [PosController::class, 'getCustomers']);
            $r->addRoute(['GET', 'POST'], '/api/devices/get', [PosController::class, 'getDevices']);
            $r->addRoute(['GET', 'POST'], '/api/locations/get', [PosController::class, 'getLocations']);
            $r->addRoute(['GET', 'POST'], '/api/orders/set', [PosController::class, 'setOrder']);
            $r->addRoute(['GET', 'POST'], '/api/orders/remove', [PosController::class, 'removeOrder']);
            $r->addRoute(['GET', 'POST'], '/api/sales/add', [PosController::class, 'addSale']);
            $r->addRoute(['GET', 'POST'], '/api/sales/void', [PosController::class, 'voidSale']);
            $r->addRoute(['GET', 'POST'], '/api/sales/search', [PosController::class, 'searchSales']);
            $r->addRoute(['GET', 'POST'], '/api/sales/updatenotes', [PosController::class, 'updateSaleNotes']);
            $r->addRoute(['GET', 'POST'], '/api/transactions/get', [PosController::class, 'getTransaction']);
            
            // Admin routes
            $r->addRoute(['GET', 'POST'], '/api/devices/setup', [AdminController::class, 'setupDevice']);
            $r->addRoute(['GET', 'POST'], '/api/adminconfig/get', [AdminController::class, 'getAdminConfig']);
            
            // Items management
            $r->addRoute(['GET', 'POST'], '/api/items/add', [AdminController::class, 'addItem']);
            $r->addRoute(['GET', 'POST'], '/api/items/edit', [AdminController::class, 'editItem']);
            $r->addRoute(['GET', 'POST'], '/api/items/delete', [AdminController::class, 'deleteItem']);
            
            // Suppliers management
            $r->addRoute(['GET', 'POST'], '/api/suppliers/get', [AdminController::class, 'getSuppliers']);
            $r->addRoute(['GET', 'POST'], '/api/suppliers/add', [AdminController::class, 'addSupplier']);
            $r->addRoute(['GET', 'POST'], '/api/suppliers/edit', [AdminController::class, 'editSupplier']);
            $r->addRoute(['GET', 'POST'], '/api/suppliers/delete', [AdminController::class, 'deleteSupplier']);
            
            // Categories management
            $r->addRoute(['GET', 'POST'], '/api/categories/get', [AdminController::class, 'getCategories']);
            $r->addRoute(['GET', 'POST'], '/api/categories/add', [AdminController::class, 'addCategory']);
            $r->addRoute(['GET', 'POST'], '/api/categories/edit', [AdminController::class, 'editCategory']);
            $r->addRoute(['GET', 'POST'], '/api/categories/delete', [AdminController::class, 'deleteCategory']);
            
            // Stock management
            $r->addRoute(['GET', 'POST'], '/api/stock/get', [AdminController::class, 'getStock']);
            $r->addRoute(['GET', 'POST'], '/api/stock/add', [AdminController::class, 'addStock']);
            $r->addRoute(['GET', 'POST'], '/api/stock/set', [AdminController::class, 'setStock']);
            $r->addRoute(['GET', 'POST'], '/api/stock/transfer', [AdminController::class, 'transferStock']);
            $r->addRoute(['GET', 'POST'], '/api/stock/history', [AdminController::class, 'getStockHistory']);
            
            // Customer management
            $r->addRoute(['GET', 'POST'], '/api/customers/add', [AdminController::class, 'addCustomer']);
            $r->addRoute(['GET', 'POST'], '/api/customers/edit', [AdminController::class, 'editCustomer']);
            $r->addRoute(['GET', 'POST'], '/api/customers/delete', [AdminController::class, 'deleteCustomer']);
            
            // User management
            $r->addRoute(['GET', 'POST'], '/api/users/get', [AdminController::class, 'getUsers']);
            $r->addRoute(['GET', 'POST'], '/api/users/add', [AdminController::class, 'addUser']);
            $r->addRoute(['GET', 'POST'], '/api/users/edit', [AdminController::class, 'editUser']);
            $r->addRoute(['GET', 'POST'], '/api/users/delete', [AdminController::class, 'deleteUser']);
            
            // Device management
            $r->addRoute(['GET', 'POST'], '/api/devices/add', [AdminController::class, 'addDevice']);
            $r->addRoute(['GET', 'POST'], '/api/devices/edit', [AdminController::class, 'editDevice']);
            $r->addRoute(['GET', 'POST'], '/api/devices/delete', [AdminController::class, 'deleteDevice']);
            
            // Location management
            $r->addRoute(['GET', 'POST'], '/api/locations/add', [AdminController::class, 'addLocation']);
            $r->addRoute(['GET', 'POST'], '/api/locations/edit', [AdminController::class, 'editLocation']);
            $r->addRoute(['GET', 'POST'], '/api/locations/delete', [AdminController::class, 'deleteLocation']);
            
            // Invoice management
            $r->addRoute(['GET', 'POST'], '/api/invoices/get', [AdminController::class, 'getInvoices']);
            $r->addRoute(['GET', 'POST'], '/api/invoices/search', [AdminController::class, 'searchInvoices']);
            $r->addRoute(['GET', 'POST'], '/api/invoices/add', [AdminController::class, 'addInvoice']);
            $r->addRoute(['GET', 'POST'], '/api/invoices/edit', [AdminController::class, 'editInvoice']);
            $r->addRoute(['GET', 'POST'], '/api/invoices/delete', [AdminController::class, 'deleteInvoice']);
            
            // Tax management
            $r->addRoute(['GET', 'POST'], '/api/tax/rules/add', [AdminController::class, 'addTaxRule']);
            $r->addRoute(['GET', 'POST'], '/api/tax/rules/edit', [AdminController::class, 'editTaxRule']);
            $r->addRoute(['GET', 'POST'], '/api/tax/rules/delete', [AdminController::class, 'deleteTaxRule']);
            
            // Node/Socket management
            $r->addRoute(['GET', 'POST'], '/api/node/status', [AdminController::class, 'getNodeStatus']);
            $r->addRoute(['GET', 'POST'], '/api/node/start', [AdminController::class, 'startNode']);
            $r->addRoute(['GET', 'POST'], '/api/node/stop', [AdminController::class, 'stopNode']);
            $r->addRoute(['GET', 'POST'], '/api/node/restart', [AdminController::class, 'restartNode']);
            
            // Logging
            $r->addRoute(['GET', 'POST'], '/api/logs/list', [AdminController::class, 'listLogs']);
            $r->addRoute(['GET', 'POST'], '/api/logs/read', [AdminController::class, 'readLog']);
            
            // Database and utilities
            $r->addRoute(['GET', 'POST'], '/api/db/backup', [AdminController::class, 'backupDatabase']);
            $r->addRoute(['GET', 'POST'], '/api/message/send', [AdminController::class, 'sendMessage']);
            $r->addRoute(['GET', 'POST'], '/api/device/reset', [AdminController::class, 'resetDevice']);
            
            // Settings management
            $r->addRoute(['GET', 'POST'], '/api/settings/get', [AdminController::class, 'getSettings']);
            $r->addRoute(['GET', 'POST'], '/api/settings/set', [AdminController::class, 'saveSettings']);
            $r->addRoute(['GET', 'POST'], '/api/stats/general', [AdminController::class, 'getOverviewStats']);
            $r->addRoute(['GET', 'POST'], '/api/file/upload', [AdminController::class, 'uploadFile']);
            
            // Customer API routes
            $r->addRoute(['GET', 'POST'], '/customerapi/auth', [AuthController::class, 'customerAuth']);
            $r->addRoute(['GET', 'POST'], '/customerapi/logout', [AuthController::class, 'logout']);
            $r->addRoute(['GET', 'POST'], '/customerapi/hello', [AuthController::class, 'customerHello']);
            $r->addRoute(['GET', 'POST'], '/customerapi/register', [CustomerController::class, 'register']);
            $r->addRoute(['GET', 'POST'], '/customerapi/resetpasswordemail', [CustomerController::class, 'sendPasswordResetEmail']);
            $r->addRoute(['GET', 'POST'], '/customerapi/resetpassword', [CustomerController::class, 'resetPassword']);
            $r->addRoute(['GET', 'POST'], '/customerapi/config', [CustomerController::class, 'getConfig']);
            $r->addRoute(['GET', 'POST'], '/customerapi/mydetails/get', [CustomerController::class, 'getMyDetails']);
            $r->addRoute(['GET', 'POST'], '/customerapi/mydetails/save', [CustomerController::class, 'saveMyDetails']);
            $r->addRoute(['GET', 'POST'], '/customerapi/transactions/get', [CustomerController::class, 'getTransactions']);
            $r->addRoute(['GET', 'POST'], '/customerapi/invoice/generate', [CustomerController::class, 'generateInvoice']);
        });
    }

    /**
     * Handle incoming HTTP requests
     */
    public function handleRequest()
    {
        // Check if this is a content request (set by .htaccess)
        if (isset($_SERVER['CONTENT_REQUEST'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // Remove query string
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }
            
            // Try FastRoute for content routes
            $routeInfo = $this->dispatcher->dispatch('GET', $requestUri);
            
            if ($routeInfo[0] === Dispatcher::FOUND) {
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                
                if ($handler[0] === ViewController::class) {
                    $controller = new ViewController();
                    call_user_func_array([$controller, $handler[1]], $vars);
                    return;
                }
            }
            
            // If route not found, return 404
            http_response_code(404);
            echo "Template not found";
            return;
        }
        
        // Get request URI and method
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        // Remove query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Try FastRoute for all requests
        $routeInfo = $this->dispatcher->dispatch($requestMethod, $requestUri);
        
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // Fall back to legacy routing for unmapped routes
                $this->handleLegacyRequest($requestUri);
                break;
                
            case Dispatcher::METHOD_NOT_ALLOWED:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
                
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                
                // Instantiate the controller and call the method
                $controller = new $handler[0]();
                call_user_func_array([$controller, $handler[1]], $vars);
                break;
        }
    }

    /**
     * Handle legacy requests that haven't been migrated to FastRoute yet
     */
    private function handleLegacyRequest($requestUri)
    {
        // Route based on the URI pattern (legacy API routing)
        if (strpos($requestUri, '/api/') !== false) {
            $this->handleApiRequest();
        } elseif (strpos($requestUri, '/customerapi/') !== false) {
            $this->handleCustomerApiRequest();
        } else {
            // Default routing for other requests
            $this->result['error'] = "Invalid API endpoint";
            $this->returnResult();
        }
    }

    /**
     * Handle main API requests (wpos.php equivalent)
     */
    private function handleApiRequest()
    {
        if (!isset($_REQUEST['a'])) {
            exit;
        }

        // Check for auth request
        if ($_REQUEST['a'] == "auth" || $_REQUEST['a'] == "authrenew") {
            $data = json_decode($_REQUEST['data']);
            if ($_REQUEST['a'] == "auth"){
                $authres = $this->auth->login($data->username, $data->password, isset($data->getsessiontokens));
            } else {
                $authres = $this->auth->renewTokenSession($data->username, $data->auth_hash);
            }
            if ($data !== false) {
                switch ($authres){
                    case true:
                        $this->result['data'] = $this->auth->getUser();
                        if ($this->result['data']==null){
                            $this->result['error'] = "Could not retrieve user data from php session.";
                        }
                        break;

                    case -1:
                        $this->result['errorCode'] = "authdenied";
                        $this->result['error'] = "Your account has been disabled, please contact your system administrator!";
                        break;

                    case false:
                    default:
                        $this->result['errorCode'] = "authdenied";
                        $this->result['error'] = "Access Denied!";
                }
            } else {
                $this->result['errorCode'] = "jsondec";
                $this->result['error'] = "Error decoding the json request!";
            }
            $this->returnResult();
        } else if ($_REQUEST['a'] == "logout") {
            $this->auth->logout();
            $this->returnResult();
        }

        // the hello request checks server connectivity aswell as providing the status of the logged in user
        if ($_REQUEST['a'] == "hello") {
            if ($this->auth->isLoggedIn()) {
                $this->result['data'] = $this->auth->getUser();
            } else {
                $this->result['data'] = false;
            }
            $this->returnResult();
        }

        // check login status and exit if not logged in
        if (!$this->auth->isLoggedIn()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "Access Denied!";
            $this->returnResult();
        }

        if ($_SERVER['HTTP_ANTI_CSRF_TOKEN'] != $this->auth->getCsrfToken()) {
            $this->result['errorCode'] = "auth";
            $this->result['error'] = "CSRF token invalid. Please try reloading the page.";
            $this->returnResult();
        }

        // Decode JSON data if provided
        if (isset($_REQUEST['data']) && $_REQUEST['data']!=""){
            // Easier to sanitize in JSON format all at once.
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $cleanData = $purifier->purify($_REQUEST['data']);

            if (($requests=json_decode($cleanData))==false){
                $this->result['errorCode'] = "request";
                $this->result['error'] = "Could not parse the provided json request";
                $this->returnResult();
            }
        } else {
            $requests = new \stdClass();
        }

        // Route the provided requests
        if ($_REQUEST['a']!=="multi"){
            // route a single api call
            $this->result = $this->routeApiCall($_REQUEST['a'], $requests, $this->result);
        } else {
            // run a multi api call
            if (empty($requests)){
                $this->result['error'] = "No API request data provided";
                $this->returnResult();
            }
            $this->result['data']=array();
            // loop through each request, stop & return the first error if encountered
            foreach ($requests as $action=>$data){
                if ($data==null) {
                    $data = new \stdClass();
                }
                $tempresult = $this->routeApiCall($action, $data, $this->result);
                if ($tempresult['error']=="OK"){
                    // set data and move to the next request
                    $this->result['data'][$action] = $tempresult['data'];
                } else {
                    $this->result['error'] = $tempresult['error'];
                    break;
                }
            }
        }
        $this->returnResult();
    }

    /**
     * Handle customer API requests (customerapi.php equivalent)
     */
    private function handleCustomerApiRequest()
    {
        // enable cross origin requests
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

        if (!isset($_REQUEST['a'])) {
            exit;
        }

        // Check for auth request
        if ($_REQUEST['a'] == "auth") {
            $data = json_decode($_REQUEST['data']);
            if ($data !== false) {
                if (($authres = $this->auth->customerLogin($data->username, $data->password)) === true) {
                    $this->result['data'] = $this->auth->getCustomer();
                } else if ($authres == -1) {
                    $this->result['errorCode'] = "authdenied";
                    $this->result['error'] = "Your account has been disabled, please contact your system administrator!";
                } else if ($authres == -2) {
                    $this->result['errorCode'] = "authdenied";
                    $this->result['error'] = "Your account has not yet been activated, please activate your account or reset your password.";
                } else {
                    $this->result['errorCode'] = "authdenied";
                    $this->result['error'] = "Access Denied!";
                }
            } else {
                $this->result['errorCode'] = "jsondec";
                $this->result['error'] = "Error decoding the json request!";
            }
            $this->returnResult();
        } else if ($_REQUEST['a'] == "logout") {
            $this->auth->logout();
            $this->returnResult();
        }

        // the hello request checks server connectivity aswell as providing the status of the logged in user
        if ($_REQUEST['a'] == "hello") {
            $this->result['data'] = new \stdClass();
            if ($this->auth->isCustomerLoggedIn()) {
                $this->result['data']->user = $this->auth->getCustomer();
            } else {
                $this->result['data']->user = false;
            }
            // unlike other hello requests, this also provide some current business info.
            $conf = WposAdminSettings::getSettingsObject('general');
            $this->result['data']->bizname = $conf->bizname;
            $this->result['data']->bizlogo = $conf->bizlogo;

            $this->returnResult();
        }

        // Decode JSON data if provided
        if ($_REQUEST['data']!=""){
            if (($requests=json_decode($_REQUEST['data']))==false){
                $this->result['error'] = "Could not parse the provided json request";
                $this->returnResult();
            }
        } else {
            $requests = new \stdClass();
        }

        // Route the provided requests
        if ($_REQUEST['a']!=="multi"){
            // route a single api call
            $this->result = $this->routeCustomerApiCall($_REQUEST['a'], $requests, $this->result);
        } else {
            // run a multi api call
            if (empty($requests)){
                $this->result['error'] = "No API request data provided";
                $this->returnResult();
            }
            // loop through each request, stop & return the first error if encountered
            foreach ($requests as $action=>$data){
                if ($data==null) {
                    $data = new \stdClass();
                }
                $tempresult = $this->routeCustomerApiCall($action, $data, $this->result);
                if ($tempresult['error']=="OK"){
                    // set data and move to the next request
                    $this->result['data'][$action] = $tempresult['data'];
                } else {
                    $this->result['error'] = $tempresult['error'];
                    break;
                }
            }
        }
        $this->returnResult();
    }

    /**
     * Route API calls (original wpos.php routing logic)
     */
    private function routeApiCall($action, $data, $result) 
    {
        $notinprev = false;
        // Check for action in unprotected area (does not require permission)
        switch ($action) {
            case "auth/websocket":
                $result['data'] = $this->auth->authoriseWebsocket();
                break;
            // POS Specific
            case "config/get":
                $setup = new WposPosSetup($data);
                $result = $setup->getDeviceRecord($result);
                break;

            case "items/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getItems($result);
                break;

            case "sales/get":
                $jsondata = new WposPosData($data);
                $result = $jsondata->getSales($result);
                break;

            case "tax/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getTaxes($result);
                break;

            case "customers/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getCustomers($result);
                break;

            case "devices/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getPosDevices($result);
                break;

            case "locations/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getPosLocations($result);
                break;

            case "orders/set":
                $sale = new WposPosSale($data);
                $result = $sale->setOrder($result);
                break;

            case "orders/remove":
                $sale = new WposPosSale($data);
                $result = $sale->removeOrder($result);
                break;

            case "sales/add":
                $sale = new WposPosSale($data);
                $result = $sale->insertTransaction($result);
                break;

            case "sales/void": // also used for sale refunds
                $sale = new WposPosSale($data, false);
                $result = $sale->insertVoid($result);
                break;

            case "sales/search":
                $sale = new WposPosData();
                if (isset($data)) {
                    $result = $sale->searchSales($data, $result);
                }
                break;

            case "sales/updatenotes":
                $sale = new WposPosSale($data, false);
                $result = $sale->updateTransationNotes($result);
                break;

            case "transactions/get":
                $trans = new WposTransactions($data);
                $result = $trans->getTransaction($result);
                break;

            default:
                $notinprev = true;
        }
        if ($notinprev == false) { // an action has been executed: return the data
            return $result;
        }
        $notinprev = false;
        // Check if user is allowed to use this API request
        if ($this->auth->isUserAllowed($action) === false) {
            $result['errorCode'] = "priv";
            $result['error'] = "You do not have permission to perform this action.";
            return $result;
        }
        // Check in permission protected API calls
        switch ($action) {
        // admin only
            // device setup
            case "devices/setup":
                $setup = new WposPosSetup($data);
                $result = $setup->setupDevice($result);
                break;

            // stored items
            case "adminconfig/get":
                $setupMdl = new WposPosSetup();
                $result = $setupMdl->getAdminConfig($result);
                break;

            case "items/add":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->addStoredItem($result);
                break;

            case "items/edit":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->updateStoredItem($result);
                break;

            case "items/delete":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->deleteStoredItem($result);
                break;

            case "items/import/set":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->importItemsSet($result);
                break;

            case "items/import/start":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->importItemsStart($result);
                break;

            // suppliers
            case "suppliers/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getSuppliers($result);
                break;

            case "suppliers/add":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->addSupplier($result);
                break;

            case "suppliers/edit":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->updateSupplier($result);
                break;

            case "suppliers/delete":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->deleteSupplier($result);
                break;
            // categories
            case "categories/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getCategories($result);
                break;

            case "categories/add":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->addCategory($result);
                break;

            case "categories/edit":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->updateCategory($result);
                break;

            case "categories/delete":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->deleteCategory($result);
                break;
            // suppliers
            case "stock/get":
                $jsondata = new WposPosData();
                $result = $jsondata->getStock($result);
                break;
            case "stock/add":
                $stockMdl = new WposAdminStock($data);
                $result = $stockMdl->addStock($result);
                break;
            case "stock/set":
                $stockMdl = new WposAdminStock($data);
                $result = $stockMdl->setStockLevel($result);
                break;
            case "stock/transfer":
                $stockMdl = new WposAdminStock($data);
                $result = $stockMdl->transferStock($result);
                break;
            case "stock/history":
                $stockMdl = new WposAdminStock($data);
                $result = $stockMdl->getStockHistory($result);
                break;

            // customers
            case "customers/add":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->addCustomer($result);
                break;
            case "customers/edit":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->updateCustomer($result);
                break;
            case "customers/delete":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->deleteCustomer($result);
                break;
            case "customers/contacts/add":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->addContact($result);
                break;
            case "customers/contacts/edit":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->updateContact($result);
                break;
            case "customers/contacts/delete":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->deleteContact($result);
                break;
            case "customers/setaccess":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->setAccess($result);
                break;
            case "customers/setpassword":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->setPassword($result);
                break;
            case "customers/sendreset":
                $custMdl = new WposAdminCustomers($data);
                $result = $custMdl->sendResetEmail($result);
                break;
            // USERS
            case "users/get":
                $data = new WposPosData();
                $result = $data->getUsers($result);
                break;
            case "users/add":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->addUser($result);
                break;
            case "users/edit":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->updateUser($result);
                break;
            case "users/delete":
                $adminMdl = new WposAdminItems($data);
                $result = $adminMdl->deleteUser($result);
                break;
            case "users/disable":
                $setup = new WposAdminItems($data);
                $result = $setup->setUserDisabled($result);
                break;

            // DEVICES
            case "devices/add":
                $setup = new WposPosSetup($data);
                $result = $setup->addDevice($result);
                break;
            case "devices/edit":
                $setup = new WposPosSetup($data);
                $result = $setup->updateDevice($result);
                break;
            case "devices/delete":
                $setup = new WposPosSetup($data);
                $result = $setup->deleteDevice($result);
                break;
            case "devices/disable":
                $setup = new WposPosSetup($data);
                $result = $setup->setDeviceDisabled($result);
                break;

            // LOCATIONS
            case "locations/add":
                $setup = new WposPosSetup($data);
                $result = $setup->addLocation($result);
                break;
            case "locations/edit":
                $setup = new WposPosSetup($data);
                $result = $setup->updateLocationName($result);
                break;
            case "locations/delete":
                $setup = new WposPosSetup($data);
                $result = $setup->deleteLocation($result);
                break;
            case "locations/disable":
                $setup = new WposPosSetup($data);
                $result = $setup->setLocationDisabled($result);
                break;

            // tax
            case "tax/rules/add":
                $tax = new WposAdminItems($data);
                $result = $tax->addTaxRule($result);
                break;
            case "tax/rules/edit":
                $tax = new WposAdminItems($data);
                $result = $tax->updateTaxRule($result);
                break;
            case "tax/rules/delete":
                $tax = new WposAdminItems($data);
                $result = $tax->deleteTaxRule($result);
                break;
            case "tax/items/add":
                $tax = new WposAdminItems($data);
                $result = $tax->addTaxItem($result);
                break;
            case "tax/items/edit":
                $tax = new WposAdminItems($data);
                $result = $tax->updateTaxItem($result);
                break;
            case "tax/items/delete":
                $tax = new WposAdminItems($data);
                $result = $tax->deleteTaxItem($result);
                break;

            // SALES (All transactions)
            case "sales/delete":
                $aSaleMdl = new WposTransactions($data);
                $result = $aSaleMdl->deleteSale($result);
                break;
            case "sales/deletevoid":
                $aSaleMdl = new WposTransactions($data);
                $result = $aSaleMdl->removeVoidRecord($result);
                break;
            case "sales/adminvoid": // the admin add void method, only requires sale id and reason
                $aSaleMdl = new WposTransactions($data);
                $result = $aSaleMdl->voidSale($result);
                break;

            // INVOICES
            case "invoices/get":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->getInvoices($result);
                break;

            case "invoices/search":
                $invMdl = new WposInvoices();
                if (isset($data)) {
                    $result = $invMdl->searchInvoices($data, $result);
                }
                break;

            case "invoices/add":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->createInvoice($result);
                break;

            case "invoices/edit":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->updateInvoice($result);
                break;

            case "invoices/delete":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->removeInvoice($result);
                break;

            case "invoices/items/add":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->addItem($result);
                break;

            case "invoices/items/edit":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->updateItem($result);
                break;

            case "invoices/items/delete":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->removeItem($result);
                break;

            case "invoices/payments/add":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->addPayment($result);
                break;

            case "invoices/payments/edit":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->updatePayment($result);
                break;

            case "invoices/payments/delete":
                $invMdl = new WposInvoices($data);
                $result = $invMdl->removePayment($result);
                break;

            case "invoices/history/get":
                $invMdl = new WposTransactions($data);
                $result = $invMdl->getTransactionHistory($result);
                break;
            case "invoices/generate":
                $invMdl = new WposTransactions(null, $_REQUEST['id'], false);
                $invMdl->generateInvoice();
                break;
            case "invoices/email":
                $invMdl = new WposTransactions($data);
                $result = $invMdl->emailInvoice($result);
                break;

            // STATS
            case "stats/general": // general overview stats
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getOverviewStats($result);
                break;
            case "stats/takings": // account takings stats, categorized by payment method
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getCountTakingsStats($result);
                break;
            case "stats/itemselling": // whats selling, grouped by stored items
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getWhatsSellingStats($result);
                break;
            case "stats/categoryselling": // whats selling, grouped by categories
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getWhatsSellingStats($result, 1);
                break;
            case "stats/supplyselling": // whats selling, grouped by suppliers
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getWhatsSellingStats($result, 2);
                break;
            case "stats/stock": // current stock levels
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getStockLevels($result);
                break;
            case "stats/devices": // whats selling, grouped by stored items
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getDeviceBreakdownStats($result);
                break;
            case "stats/locations": // whats selling, grouped by stored items
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getDeviceBreakdownStats($result, 'location');
                break;
            case "stats/users": // whats selling, grouped by stored items
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getDeviceBreakdownStats($result, 'user');
                break;
            case "stats/tax": // whats selling, grouped by stored items
                $statsMdl = new WposAdminStats($data);
                $result = $statsMdl->getTaxStats($result);
                break;

            // GRAPH
            case "graph/general": // like the general stats, but in graph form/time.
                $graphMdl = new WposAdminGraph($data);
                $result = $graphMdl->getOverviewGraph($result);
                break;
            case "graph/takings": // like the general stats, but in graph form/time.
                $graphMdl = new WposAdminGraph($data);
                $result = $graphMdl->getMethodGraph($result);
                break;
            case "graph/devices": // like the general stats, but in graph form/time.
                $graphMdl = new WposAdminGraph($data);
                $result = $graphMdl->getDeviceGraph($result);
                break;
            case "graph/locations": // like the general stats, but in graph form/time.
                $graphMdl = new WposAdminGraph($data);
                $result = $graphMdl->getLocationGraph($result);
                break;

            // Admin/Global Config
            case "settings/get":
                $configMdl = new WposAdminSettings();
                $configMdl->setName($data->name);
                $result = $configMdl->getSettings($result);
                break;
            case "settings/general/get":
                $configMdl = new WposAdminSettings();
                $configMdl->setName("general");
                $result = $configMdl->getSettings($result);
                break;
            case "settings/pos/get":
                $configMdl = new WposAdminSettings();
                $configMdl->setName("pos");
                $result = $configMdl->getSettings($result);
                break;
            case "settings/invoice/get":
                $configMdl = new WposAdminSettings();
                $configMdl->setName("invoice");
                $result = $configMdl->getSettings($result);
                break;

            case "settings/set":
                $configMdl = new WposAdminSettings($data);
                $result = $configMdl->saveSettings($result);
                break;
            case "settings/general/set":
                $configMdl = new WposAdminSettings($data);
                $configMdl->setName("general");
                $result = $configMdl->saveSettings($result);
                break;
            case "settings/pos/set":
                $configMdl = new WposAdminSettings($data);
                $configMdl->setName("pos");
                $result = $configMdl->saveSettings($result);
                break;
            case "settings/invoice/set":
                $configMdl = new WposAdminSettings($data);
                $configMdl->setName("invoice");
                $result = $configMdl->saveSettings($result);
                break;
            case "settings/google/authinit":
                GoogleIntegration::initGoogleAuth();
                break;
            case "settings/google/authremove":
                GoogleIntegration::removeGoogleAuth();
                break;
            case "settings/xero/oauthinit":
                XeroIntegration::initXeroAuth();
                break;
            case "settings/xero/oauthcallback":
                XeroIntegration::processCallbackAuthCode();
                break;
            case "settings/xero/oauthremove":
                XeroIntegration::removeXeroAuth();
                break;
            case "settings/xero/configvalues":
                $result = XeroIntegration::getXeroConfigValues($result);
                break;
            case "settings/xero/export":
                $result = XeroIntegration::exportXeroSales($data->stime, $data->etime);
                break;

            case "node/status":
                $Sserver = new WposSocketControl();
                $result = $Sserver->isServerRunning($result);
                break;

            case "node/start":
                $Sserver = new WposSocketControl();
                $result = $Sserver->startSocketServer($result);
                break;

            case "node/stop":
                $Sserver = new WposSocketControl();
                $result = $Sserver->stopSocketServer($result);
                break;

            case "node/restart":
                $Sserver = new WposSocketControl();
                $result = $Sserver->restartSocketServer($result);
                break;

            case "db/backup":
                $util = new WposAdminUtilities();
                $util->backUpDatabase();
                break;

            case "logs/list":
                $result['data'] = Logger::ls();
                break;

            case "logs/read":
                $result['data'] = Logger::read($data->filename);
                break;

            case "file/upload":
                if (isset($_FILES['file'])) {

                    $uploaddir = 'storage';

                    $file_type = $_FILES['foreign_character_upload']['type']; //returns the mimetype

                    $allowed = array("image/jpeg", "image/gif", "image/png", "application/pdf");
                    if(!in_array($file_type, $allowed)) {
                        $result['error'] = 'Only jpg, gif, and pdf files are allowed.';
                        break;
                    }

                    $newpath = $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);

                    if (move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/../../../' . $newpath) !== false) {
                        $result['data'] = ["path" => "/" . $newpath];
                    } else {
                        $result['error'] = "There was an error uploading the file " . $newpath;
                    }
                } else {
                    $result['error'] = "No file selected";
                }
                break;

            // device message
            case "message/send":
                $socket = new WposSocketIO();
                if ($data->device === null) {
                    if (($error = $socket->sendMessageToDevices(null, $data->message)) !== true) {
                        $result['error'] = $error;
                    }
                } else {
                    $devid = intval($data->device);
                    $devices = new \stdClass();
                    $devices->{$devid} = $devid;
                    if (($error = $socket->sendMessageToDevices($devices, $data->message)) !== true) {
                        $result['error'] = $error;
                    }
                }
                break;
            // device reset
            case "device/reset":
                $socket = new WposSocketIO();
                if ($data->device === null) {
                    if (($error = $socket->sendResetCommand()) !== true) {
                        $result['error'] = $error;
                    }
                } else {
                    $devid = intval($data->device);
                    $devices = new \stdClass();
                    $devices->{$devid} = $devid;
                    if (($error = $socket->sendResetCommand($devices)) !== true) {
                        $result['error'] = $error;
                    }
                }
                break;

            default:
                $notinprev = true;
                break;
        }
        if ($notinprev == false) { // an action has been executed: return the data
            return $result;
        }

        // Check if user is allowed admin only API calls
        if (!$this->auth->isAdmin()) {
            $result['errorCode'] = "priv";
            $result['error'] = "You do not have permission to perform this action.";
            return $result;
        }
        // Check in permission protected API calls
        switch ($action) {
            case "devices/registrations":
                $setup = new WposPosSetup($data);
                $result = $setup->getDeviceRegistrations($result);
                break;
            case "devices/registrations/delete":
                $setup = new WposPosSetup($data);
                $result = $setup->deleteDeviceRegistration($result);
                break;
            case "templates/get":
                $result = WposTemplates::getTemplates($result);
                break;
            case "templates/edit":
                $tempMdl = new WposTemplates($data);
                $result = $tempMdl->editTemplate($result);
                break;
            case "templates/restore":
                WposTemplates::restoreDefaults((isset($data->filename)?$data->filename:null));
                break;

            default:
            $result["error"] = "Action not defined: ".$action;
            break;
        }

        return $result;
    }

    /**
     * Route customer API calls (original customerapi.php routing logic)
     */
    private function routeCustomerApiCall($action, $data, $result) 
    {
        $notinprev = false;
        switch ($action){
            case 'register':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->register($result);
                break;
            case 'resetpasswordemail':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->sendResetPasswordEmail($result);
                break;
            case 'resetpassword':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->doPasswordReset($result);
                break;
            case 'config':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->getSettings($result);
                break;
            default:
                $notinprev = true;
        }
        if ($notinprev == false) { // an action has been executed: return the data
            return $result;
        }
        // check login status and exit if not logged in
        if (!$this->auth->isCustomerLoggedIn()) {
            $result['errorCode'] = "auth";
            $result['error'] = "Access Denied!";
            return $result;
        }
        // Check for action in unprotected area (does not use permission system)
        switch ($action) {
            case 'mydetails/get':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->getCurrentCustomerDetails($result);
                break;
            case 'mydetails/save':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->saveCustomerDetails($result);
                break;
            case 'transactions/get':
                $wCust = new WposCustomerAccess($data);
                $result = $wCust->getCustomerTransactions($result);
                break;
            case 'invoice/generate':
                $wCust = new WposCustomerAccess();
                $wCust->generateCustomerInvoice($_REQUEST['id']);
                break;
            default:
                $result["error"] = "Action not defined: ".$action;
                break;
        }
        return $result;
    }

    /**
     * Encodes and returns the json result object
     */
    private function returnResult()
    {
        if (($resstr = json_encode($this->result)) === false) {
            echo(json_encode(["error" => "Failed to encode the response data into json"]));
        } else {
            echo($resstr);
        }
        die();
    }
}