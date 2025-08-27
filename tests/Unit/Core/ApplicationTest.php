<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Application;
use Mockery;

/**
 * Unit tests for Application class
 * Tests request routing, route handling, and error responses
 */
class ApplicationTest extends TestCase
{
    protected $app;
    protected $serverBackup;

    protected function setUp(): void
    {
        parent::setUp();
        // Save original $_SERVER state
        $this->serverBackup = $_SERVER;
        $this->app = new Application();
    }

    protected function tearDown(): void
    {
        // Restore original $_SERVER state
        $_SERVER = $this->serverBackup;
        Mockery::close();
        parent::tearDown();
    }

    public function testApplicationInstantiation()
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testRouteDispatcherCreation()
    {
        // Test that the dispatcher is properly created
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        $this->assertNotNull($dispatcher);
        $this->assertInstanceOf(\FastRoute\Dispatcher::class, $dispatcher);
    }

    public function testAuthInstantiation()
    {
        // Test that Auth is properly instantiated
        $reflection = new \ReflectionClass($this->app);
        $authProperty = $reflection->getProperty('auth');
        $authProperty->setAccessible(true);
        $auth = $authProperty->getValue($this->app);

        $this->assertNotNull($auth);
        $this->assertInstanceOf(\App\Auth::class, $auth);
    }

    public function testResultDefaultStructure()
    {
        // Test the default result structure
        $reflection = new \ReflectionClass($this->app);
        $resultProperty = $reflection->getProperty('result');
        $resultProperty->setAccessible(true);
        $result = $resultProperty->getValue($this->app);

        $expectedResult = ["errorCode" => "OK", "error" => "OK", "data" => ""];
        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleRequestWith404()
    {
        // Mock a non-existent route
        $_SERVER['REQUEST_URI'] = '/api/nonexistent';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals('API endpoint not found', $response['error']);
        $this->assertEquals('/api/nonexistent', $response['requested_uri']);
    }

    public function testHandleRequestWithQueryString()
    {
        // Test that query strings are properly removed from URI
        $_SERVER['REQUEST_URI'] = '/api/nonexistent?param=value&other=data';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals('API endpoint not found', $response['error']);
        $this->assertEquals('/api/nonexistent', $response['requested_uri']);
    }

    public function testHandleRequestWithMethodNotAllowed()
    {
        // Test a route that exists but doesn't allow the HTTP method
        // Most routes in the application allow both GET and POST, so this tests edge cases
        $_SERVER['REQUEST_URI'] = '/api/auth';
        $_SERVER['REQUEST_METHOD'] = 'DELETE'; // Not allowed method

        ob_start();
        $this->app->handleRequest();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals('Method not allowed', $response['error']);
    }

    public function testValidRouteExists()
    {
        // Test that some known routes are properly registered
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test auth route
        $routeInfo = $dispatcher->dispatch('POST', '/api/auth');
        $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0]);
        $this->assertEquals(\App\Controllers\Api\AuthController::class, $routeInfo[1][0]);
        $this->assertEquals('authenticate', $routeInfo[1][1]);

        // Test install status route
        $routeInfo = $dispatcher->dispatch('GET', '/api/install/status');
        $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0]);
        $this->assertEquals(\App\Controllers\Api\InstallController::class, $routeInfo[1][0]);
        $this->assertEquals('status', $routeInfo[1][1]);
    }

    public function testParameterizedRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test admin content route with template parameter
        $routeInfo = $dispatcher->dispatch('GET', '/api/admin/content/dashboard');
        $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0]);
        $this->assertEquals(\App\Controllers\ViewController::class, $routeInfo[1][0]);
        $this->assertEquals('adminContent', $routeInfo[1][1]);
        $this->assertEquals(['template' => 'dashboard'], $routeInfo[2]);

        // Test customer content route with template parameter
        $routeInfo = $dispatcher->dispatch('GET', '/api/customer/content/profile');
        $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0]);
        $this->assertEquals(\App\Controllers\ViewController::class, $routeInfo[1][0]);
        $this->assertEquals('customerContent', $routeInfo[1][1]);
        $this->assertEquals(['template' => 'profile'], $routeInfo[2]);
    }

    public function testPosRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test POS controller routes
        $posRoutes = [
            '/api/config/get' => 'getConfig',
            '/api/items/get' => 'getItems',
            '/api/sales/get' => 'getSales',
            '/api/sales/add' => 'addSale',
            '/api/sales/void' => 'voidSale',
            '/api/customers/get' => 'getCustomers',
            '/api/devices/get' => 'getDevices',
            '/api/locations/get' => 'getLocations'
        ];

        foreach ($posRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should be found");
            $this->assertEquals(\App\Controllers\Api\PosController::class, $routeInfo[1][0], "Route $route should use PosController");
            $this->assertEquals($method, $routeInfo[1][1], "Route $route should call method $method");
        }
    }

    public function testAdminRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test Admin controller routes
        $adminRoutes = [
            '/api/items/add' => 'addItem',
            '/api/items/edit' => 'editItem',
            '/api/items/delete' => 'deleteItem',
            '/api/categories/get' => 'getCategories',
            '/api/categories/add' => 'addCategory',
            '/api/suppliers/get' => 'getSuppliers',
            '/api/users/get' => 'getUsers',
            '/api/settings/get' => 'getSettings'
        ];

        foreach ($adminRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should be found");
            $this->assertEquals(\App\Controllers\Api\AdminController::class, $routeInfo[1][0], "Route $route should use AdminController");
            $this->assertEquals($method, $routeInfo[1][1], "Route $route should call method $method");
        }
    }

    public function testCustomerRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test Customer controller routes
        $customerRoutes = [
            '/api/customer/register' => 'register',
            '/api/customer/resetpasswordemail' => 'sendPasswordResetEmail',
            '/api/customer/resetpassword' => 'resetPassword',
            '/api/customer/config' => 'getConfig',
            '/api/customer/mydetails/get' => 'getMyDetails',
            '/api/customer/transactions/get' => 'getTransactions'
        ];

        foreach ($customerRoutes as $route => $method) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should be found");
            $this->assertEquals(\App\Controllers\Api\CustomerController::class, $routeInfo[1][0], "Route $route should use CustomerController");
            $this->assertEquals($method, $routeInfo[1][1], "Route $route should call method $method");
        }
    }

    public function testInstallRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test Install controller routes
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
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should be found");
            $this->assertEquals(\App\Controllers\Api\InstallController::class, $routeInfo[1][0], "Route $route should use InstallController");
            $this->assertEquals($method, $routeInfo[1][1], "Route $route should call method $method");
        }
    }

    public function testBothGetAndPostAllowed()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test that most API routes allow both GET and POST
        $testRoutes = [
            '/api/auth',
            '/api/items/get',
            '/api/sales/add',
            '/api/install/status'
        ];

        foreach ($testRoutes as $route) {
            // Test GET
            $routeInfo = $dispatcher->dispatch('GET', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should allow GET");

            // Test POST
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Route $route should allow POST");
        }
    }

    public function testAliasRoutes()
    {
        $reflection = new \ReflectionClass($this->app);
        $dispatcherProperty = $reflection->getProperty('dispatcher');
        $dispatcherProperty->setAccessible(true);
        $dispatcher = $dispatcherProperty->getValue($this->app);

        // Test alias routes (singular vs plural forms)
        $aliasTests = [
            ['/api/users/disable', '/api/user/disable', 'disableUser'],
            ['/api/devices/disable', '/api/device/disable', 'disableDevice'],
            ['/api/locations/add', '/api/location/add', 'addLocation'],
            ['/api/locations/edit', '/api/location/edit', 'editLocation']
        ];

        foreach ($aliasTests as [$pluralRoute, $singularRoute, $method]) {
            // Test plural form
            $routeInfo = $dispatcher->dispatch('POST', $pluralRoute);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Plural route $pluralRoute should be found");
            $this->assertEquals($method, $routeInfo[1][1], "Plural route $pluralRoute should call method $method");

            // Test singular form (alias)
            $routeInfo = $dispatcher->dispatch('POST', $singularRoute);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Singular route $singularRoute should be found");
            $this->assertEquals($method, $routeInfo[1][1], "Singular route $singularRoute should call method $method");
        }
    }

    public function testRouteCount()
    {
        // Test that we have a reasonable number of routes defined
        // This helps ensure we haven't missed major functionality
        $reflection = new \ReflectionClass($this->app);
        $method = $reflection->getMethod('createDispatcher');
        $method->setAccessible(true);
        
        // We can't easily count routes directly, but we can test some key ones exist
        $dispatcher = $method->invoke($this->app);
        
        $keyRoutes = [
            '/api/auth', '/api/logout', '/api/hello',
            '/api/install/status', '/api/install/requirements',
            '/api/config/get', '/api/items/get', '/api/sales/add',
            '/api/customers/get', '/api/users/get', '/api/settings/get',
            '/api/customer/register', '/api/customer/auth'
        ];
        
        foreach ($keyRoutes as $route) {
            $routeInfo = $dispatcher->dispatch('POST', $route);
            $this->assertEquals(\FastRoute\Dispatcher::FOUND, $routeInfo[0], "Key route $route should exist");
        }
    }
}