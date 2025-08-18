<?php

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Auth;
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
            
            // Multi API endpoint
            $r->addRoute(['GET', 'POST'], '/api/multi', [AuthController::class, 'multiApi']);
            
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
            $r->addRoute(['GET', 'POST'], '/api/customer/auth', [AuthController::class, 'customerAuth']);
            $r->addRoute(['GET', 'POST'], '/api/customer/logout', [AuthController::class, 'logout']);
            $r->addRoute(['GET', 'POST'], '/api/customer/hello', [AuthController::class, 'customerHello']);
            $r->addRoute(['GET', 'POST'], '/api/customer/register', [CustomerController::class, 'register']);
            $r->addRoute(['GET', 'POST'], '/api/customer/resetpasswordemail', [CustomerController::class, 'sendPasswordResetEmail']);
            $r->addRoute(['GET', 'POST'], '/api/customer/resetpassword', [CustomerController::class, 'resetPassword']);
            $r->addRoute(['GET', 'POST'], '/api/customer/config', [CustomerController::class, 'getConfig']);
            $r->addRoute(['GET', 'POST'], '/api/customer/mydetails/get', [CustomerController::class, 'getMyDetails']);
            $r->addRoute(['GET', 'POST'], '/api/customer/mydetails/save', [CustomerController::class, 'saveMyDetails']);
            $r->addRoute(['GET', 'POST'], '/api/customer/transactions/get', [CustomerController::class, 'getTransactions']);
            $r->addRoute(['GET', 'POST'], '/api/customer/invoice/generate', [CustomerController::class, 'generateInvoice']);
        });
    }

    /**
     * Handle incoming HTTP requests
     */
    public function handleRequest()
    {
        // Get request URI and method
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        // Remove query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Dispatch request through FastRoute
        $routeInfo = $this->dispatcher->dispatch($requestMethod, $requestUri);
        
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                echo json_encode([
                    "error" => "API endpoint not found",
                    "requested_uri" => $requestUri
                ]);
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