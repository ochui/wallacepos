<?php

namespace App\Controllers\Api;

use App\Auth;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Controllers\Api\PosController;
use App\Controllers\Api\AdminController;

/**
 * API Authentication Controller
 * Handles authentication-related API endpoints
 */
class AuthController
{
    private $auth;
    private $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Handle user authentication
     */
    public function authenticate()
    {
        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->login($data->username, $data->password, isset($data->getsessiontokens));
        
        switch ($authres) {
            case true:
                $this->result['data'] = $this->auth->getUser();
                if ($this->result['data'] == null) {
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

        return $this->returnResult();
    }

    /**
     * Handle token session renewal
     */
    public function renewToken()
    {
        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->renewTokenSession($data->username, $data->auth_hash);
        
        switch ($authres) {
            case true:
                $this->result['data'] = $this->auth->getUser();
                if ($this->result['data'] == null) {
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

        return $this->returnResult();
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        $this->auth->logout();
        return $this->returnResult();
    }

    /**
     * Handle hello/connectivity check
     */
    public function hello()
    {
        if ($this->auth->isLoggedIn()) {
            $this->result['data'] = $this->auth->getUser();
        } else {
            $this->result['data'] = false;
        }
        return $this->returnResult();
    }

    /**
     * Handle customer authentication
     */
    public function customerAuth()
    {
        // Enable cross origin requests for customer API
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

        if (!isset($_REQUEST['data'])) {
            $this->result['errorCode'] = "request";
            $this->result['error'] = "No authentication data provided";
            return $this->returnResult();
        }

        $data = json_decode($_REQUEST['data']);
        if ($data === false) {
            $this->result['errorCode'] = "jsondec";
            $this->result['error'] = "Error decoding the json request!";
            return $this->returnResult();
        }

        $authres = $this->auth->customerLogin($data->username, $data->password);
        
        if ($authres === true) {
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

        return $this->returnResult();
    }

    /**
     * Handle customer hello/connectivity check
     */
    public function customerHello()
    {
        // Enable cross origin requests for customer API
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, POST, GET");

        $this->result['data'] = new \stdClass();
        if ($this->auth->isCustomerLoggedIn()) {
            $this->result['data']->user = $this->auth->getCustomer();
        } else {
            $this->result['data']->user = false;
        }
        
        // Provide business info for customer interface
        $conf = \App\Controllers\Admin\WposAdminSettings::getSettingsObject('general');
        $this->result['data']->bizname = $conf->bizname;
        $this->result['data']->bizlogo = $conf->bizlogo;

        return $this->returnResult();
    }

    /**
     * Authorize websocket connection
     */
    public function authorizeWebsocket()
    {
        $this->result['data'] = $this->auth->authoriseWebsocket();
        return $this->returnResult();
    }

    /**
     * Handle multiple API calls in a single request
     */
    public function multiApi()
    {
        if (!isset($_REQUEST['data'])) {
            $this->result['error'] = "No API request data provided";
            return $this->returnResult();
        }

        $requests = json_decode($_REQUEST['data']);
        if ($requests === false || empty($requests)) {
            $this->result['error'] = "No API request data provided";
            return $this->returnResult();
        }

        $this->result['data'] = array();
        
        // Create a FastRoute dispatcher with the same routes as the main application
        $dispatcher = $this->createMultiApiDispatcher();
        
        // Loop through each request, stop & return the first error if encountered
        foreach ($requests as $action => $data) {
            if ($data == null) {
                $data = new \stdClass();
            }
            
            $tempresult = $this->routeApiCall($dispatcher, $action, $data);
            
            if ($tempresult['error'] == "OK") {
                // Set data and move to the next request
                $this->result['data'][$action] = $tempresult['data'];
            } else {
                $this->result['error'] = $tempresult['error'];
                break;
            }
        }
        
        return $this->returnResult();
    }

    /**
     * Create FastRoute dispatcher for multi API calls
     */
    private function createMultiApiDispatcher()
    {
        return simpleDispatcher(function(RouteCollector $r) {
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
        });
    }

    /**
     * Route a single API call within multi API context
     */
    private function routeApiCall($dispatcher, $action, $data)
    {
        $result = ["errorCode" => "OK", "error" => "OK", "data" => ""];
        
        // Convert action to full API path
        $requestUri = '/api/' . $action;
        
        // Store the original $_REQUEST data
        $originalRequest = $_REQUEST;
        
        // Set up the data for the individual API call
        $_REQUEST['data'] = json_encode($data);
        
        // Dispatch the request
        $routeInfo = $dispatcher->dispatch('POST', $requestUri);
        
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $result['error'] = "API endpoint '$action' not found";
                break;
                
            case Dispatcher::METHOD_NOT_ALLOWED:
                $result['error'] = "Method not allowed for '$action'";
                break;
                
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                
                try {
                    // Create a new instance of the controller for this call
                    $controller = new $handler[0]();
                    
                    // For multi API, we need to call the method and get result without die()
                    // This is a special case handling for known endpoints
                    switch ($action) {
                        case 'hello':
                            $auth = new \App\Auth();
                            if ($auth->isLoggedIn()) {
                                $result['data'] = $auth->getUser();
                            } else {
                                $result['data'] = false;
                            }
                            break;
                            
                        case 'auth/websocket':
                            $auth = new \App\Auth();
                            $result['data'] = $auth->authoriseWebsocket();
                            break;
                            
                        default:
                            // For other endpoints, we'll need to capture output
                            // This is a fallback that should work for most cases
                            ob_start();
                            
                            // Temporarily override the die() function behavior
                            register_shutdown_function(function() {
                                // This won't help with die() calls, but it's worth trying
                            });
                            
                            call_user_func_array([$controller, $handler[1]], $vars);
                            
                            $output = ob_get_clean();
                            if (!empty($output)) {
                                $parsed = json_decode($output, true);
                                if ($parsed !== null) {
                                    $result = $parsed;
                                } else {
                                    $result['error'] = "Invalid JSON response from '$action'";
                                }
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                }
                break;
        }
        
        // Restore original $_REQUEST
        $_REQUEST = $originalRequest;
        
        return $result;
    }

    /**
     * Return JSON result and exit
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