<?php

namespace Tests\Feature\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Application;

/**
 * Integration tests for core FreePOS functionality
 * Tests the complete request-response cycle without complex mocking
 */
class CoreFunctionalityTest extends TestCase
{
    protected $app;
    protected $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        // Save original $_SERVER state
        $this->originalServer = $_SERVER;
        $this->app = new Application();
    }

    protected function tearDown(): void
    {
        // Restore original $_SERVER state
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    public function testApplicationCanBeInstantiated()
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testRouteDispatchingWorks()
    {
        // Test that the route dispatcher is properly configured
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        $this->assertInstanceOf(\FastRoute\Dispatcher::class, $dispatcher);
    }

    public function testAuthenticationRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test critical auth routes
        $authRoutes = [
            '/api/auth' => 'authenticate',
            '/api/authrenew' => 'renewToken',
            '/api/logout' => 'logout',
            '/api/hello' => 'hello'
        ];

        foreach ($authRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Auth route $route should exist");
            $this->assertEquals(\App\Controllers\Api\AuthController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testPosRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test core POS routes
        $posRoutes = [
            '/api/config/get' => 'getConfig',
            '/api/items/get' => 'getItems',
            '/api/sales/get' => 'getSales',
            '/api/sales/add' => 'addSale',
            '/api/sales/void' => 'voidSale',
            '/api/customers/get' => 'getCustomers',
            '/api/tax/get' => 'getTaxes',
            '/api/devices/get' => 'getDevices',
            '/api/locations/get' => 'getLocations',
            '/api/orders/set' => 'setOrder',
            '/api/orders/remove' => 'removeOrder',
            '/api/transactions/get' => 'getTransaction'
        ];

        foreach ($posRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "POS route $route should exist");
            $this->assertEquals(\App\Controllers\Api\PosController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testAdminRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test critical admin routes
        $adminRoutes = [
            '/api/items/add' => 'addItem',
            '/api/items/edit' => 'editItem',
            '/api/items/delete' => 'deleteItem',
            '/api/categories/get' => 'getCategories',
            '/api/categories/add' => 'addCategory',
            '/api/categories/edit' => 'editCategory',
            '/api/categories/delete' => 'deleteCategory',
            '/api/suppliers/get' => 'getSuppliers',
            '/api/suppliers/add' => 'addSupplier',
            '/api/stock/get' => 'getStock',
            '/api/stock/add' => 'addStock',
            '/api/customers/add' => 'addCustomer',
            '/api/customers/edit' => 'editCustomer',
            '/api/users/get' => 'getUsers',
            '/api/users/add' => 'addUser',
            '/api/devices/add' => 'addDevice',
            '/api/locations/add' => 'addLocation',
            '/api/settings/get' => 'getSettings',
            '/api/settings/set' => 'saveSettings'
        ];

        foreach ($adminRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Admin route $route should exist");
            $this->assertEquals(\App\Controllers\Api\AdminController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testInstallationRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test installation routes
        $installRoutes = [
            '/api/install/status' => 'status',
            '/api/install/requirements' => 'requirements',
            '/api/install/test-database' => 'testDatabase',
            '/api/install/save-database' => 'saveDatabaseConfig',
            '/api/install/configure-admin' => 'configureAdmin',
            '/api/install/install-with-config' => 'installWithConfig',
            '/api/install' => 'install',
            '/api/install/upgrade' => 'upgrade'
        ];

        foreach ($installRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Install route $route should exist");
            $this->assertEquals(\App\Controllers\Api\InstallController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testCustomerRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test customer routes
        $customerRoutes = [
            '/api/customer/auth' => 'customerAuth',
            '/api/customer/register' => 'register',
            '/api/customer/resetpasswordemail' => 'sendPasswordResetEmail',
            '/api/customer/resetpassword' => 'resetPassword',
            '/api/customer/config' => 'getConfig',
            '/api/customer/mydetails/get' => 'getMyDetails',
            '/api/customer/mydetails/save' => 'saveMyDetails',
            '/api/customer/transactions/get' => 'getTransactions'
        ];

        foreach ($customerRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Customer route $route should exist");
            $this->assertEquals(\App\Controllers\Api\CustomerController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testViewRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test view routes with parameters
        $viewRoutes = [
            '/api/admin/content/dashboard' => 'adminContent',
            '/api/customer/content/profile' => 'customerContent',
            '/api/installer/content/setup' => 'installerView'
        ];

        foreach ($viewRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('GET', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "View route $route should exist");
            $this->assertEquals(\App\Controllers\ViewController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testInvoiceManagementRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test invoice management routes
        $invoiceRoutes = [
            '/api/invoices/get' => 'getInvoices',
            '/api/invoices/add' => 'addInvoice',
            '/api/invoices/edit' => 'editInvoice',
            '/api/invoices/delete' => 'deleteInvoice',
            '/api/invoices/generate' => 'generateInvoice',
            '/api/invoices/email' => 'emailInvoice'
        ];

        foreach ($invoiceRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Invoice route $route should exist");
            $this->assertEquals(\App\Controllers\Api\AdminController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testStatisticsRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test statistics and reporting routes
        $statsRoutes = [
            '/api/stats/general' => 'getOverviewStats',
            '/api/stats/itemselling' => 'getItemSellingStats',
            '/api/stats/takings' => 'getTakingsStats',
            '/api/stats/locations' => 'getLocationStats',
            '/api/stats/devices' => 'getDeviceStats',
            '/api/stats/users' => 'getUserStats',
            '/api/graph/general' => 'getGeneralGraph',
            '/api/graph/takings' => 'getTakingsGraph'
        ];

        foreach ($statsRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Stats route $route should exist");
            $this->assertEquals(\App\Controllers\Api\AdminController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testTaxManagementRoutesExist()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test tax management routes
        $taxRoutes = [
            '/api/tax/rules/add' => 'addTaxRule',
            '/api/tax/rules/edit' => 'editTaxRule',
            '/api/tax/rules/delete' => 'deleteTaxRule',
            '/api/tax/items/add' => 'addTaxItem',
            '/api/tax/items/edit' => 'editTaxItem',
            '/api/tax/items/delete' => 'deleteTaxItem'
        ];

        foreach ($taxRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Tax route $route should exist");
            $this->assertEquals(\App\Controllers\Api\AdminController::class, $routeInfo[1][0]);
            $this->assertEquals($method, $routeInfo[1][1]);
        }
    }

    public function testHttpMethodsAreCorrectlyConfigured()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test that critical routes accept both GET and POST
        $dualMethodRoutes = [
            '/api/auth',
            '/api/hello',
            '/api/items/get',
            '/api/sales/add',
            '/api/install/status'
        ];

        foreach ($dualMethodRoutes as $route) {
            // Test GET
            $getRouteInfo = $dispatcher->dispatch('GET', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $getRouteInfo[0], "Route $route should accept GET");

            // Test POST
            $postRouteInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $postRouteInfo[0], "Route $route should accept POST");
        }
    }

    public function testRouteAliasesWork()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test singular/plural aliases
        $aliasTests = [
            ['/api/users/disable', '/api/user/disable'],
            ['/api/devices/disable', '/api/device/disable'],
            ['/api/locations/add', '/api/location/add'],
            ['/api/locations/edit', '/api/location/edit'],
            ['/api/locations/delete', '/api/location/delete'],
            ['/api/locations/disable', '/api/location/disable']
        ];

        foreach ($aliasTests as [$pluralRoute, $singularRoute]) {
            $pluralInfo = $dispatcher->dispatch('POST', $pluralRoute);
            $singularInfo = $dispatcher->dispatch('POST', $singularRoute);

            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $pluralInfo[0], "Plural route $pluralRoute should exist");
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $singularInfo[0], "Singular route $singularRoute should exist");
            $this->assertEquals($pluralInfo[1][1], $singularInfo[1][1], "Both routes should call the same method");
        }
    }

    public function testInvalidRoutesReturn404()
    {
        $_SERVER['REQUEST_URI'] = '/api/invalid/route';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('API endpoint not found', $response['error']);
        $this->assertEquals('/api/invalid/route', $response['requested_uri']);
    }

    public function testInvalidMethodsReturn405()
    {
        $_SERVER['REQUEST_URI'] = '/api/auth';
        $_SERVER['REQUEST_METHOD'] = 'PUT'; // Not allowed

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('Method not allowed', $response['error']);
    }

    public function testQueryStringsAreRemovedFromRouting()
    {
        $_SERVER['REQUEST_URI'] = '/api/invalid/route?param=value&other=test';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('API endpoint not found', $response['error']);
        $this->assertEquals('/api/invalid/route', $response['requested_uri']);
    }
}