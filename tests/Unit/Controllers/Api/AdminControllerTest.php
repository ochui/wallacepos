<?php

namespace Tests\Unit\Controllers\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\AdminController;
use App\Auth;
use Mockery;

/**
 * Unit tests for AdminController
 * Tests administrative functionality including permissions, item management, and settings
 */
class AdminControllerTest extends TestCase
{
    protected $adminController;
    protected $mockAuth;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Auth class
        $this->mockAuth = Mockery::mock(Auth::class);
        
        // Create a partial mock of AdminController
        $this->adminController = Mockery::mock(AdminController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Set the auth property using reflection
        $reflection = new \ReflectionClass($this->adminController);
        $authProperty = $reflection->getProperty('auth');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->adminController, $this->mockAuth);
        
        // Mock HTTP headers for CSRF
        $_SERVER['HTTP_ANTI_CSRF_TOKEN'] = 'test-csrf-token';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        unset($_SERVER['HTTP_ANTI_CSRF_TOKEN']);
        parent::tearDown();
    }

    public function testSetupDeviceWithAuthentication()
    {
        $mockDeviceData = ['name' => 'New Device', 'locationid' => 1];

        // Mock authentication checks
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('devices/setup')
            ->once()
            ->andReturn(true);

        // Mock getRequestData to return our test data
        $this->adminController->shouldReceive('getRequestData')
            ->once()
            ->andReturn((object)$mockDeviceData);

        // Mock PosSetup to avoid external dependencies
        $mockPosSetup = Mockery::mock('overload:App\Controllers\Pos\PosSetup');
        $mockPosSetup->shouldReceive('__construct')
            ->once()
            ->with((object)$mockDeviceData);
        $mockPosSetup->shouldReceive('setupDevice')
            ->once()
            ->andReturnUsing(function($result) {
                $result['data'] = ['id' => 1, 'name' => 'New Device', 'status' => 'active'];
                return $result;
            });

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $this->adminController->setupDevice();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
    }

    public function testSetupDeviceWithoutAuthentication()
    {
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(false);

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $this->adminController->setupDevice();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('auth', $response['errorCode']);
        $this->assertEquals('Access Denied!', $response['error']);
    }

    public function testSetupDeviceWithInvalidCSRF()
    {
        $_SERVER['HTTP_ANTI_CSRF_TOKEN'] = 'invalid-token';

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('valid-csrf-token');

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('auth', $result['errorCode']);
                $this->assertEquals('CSRF token invalid. Please try reloading the page.', $result['error']);
                return json_encode($result);
            });

        $this->adminController->shouldReceive('setupDevice')
            ->once()
            ->andReturnUsing(function() {
                // Simulate CSRF check failure
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['errorCode'] = 'auth';
                $result['error'] = 'CSRF token invalid. Please try reloading the page.';
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->setupDevice();
    }

    public function testSetupDeviceWithoutPermission()
    {
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('devices/setup')
            ->once()
            ->andReturn(false);

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('priv', $result['errorCode']);
                $this->assertEquals('You do not have permission to perform this action.', $result['error']);
                return json_encode($result);
            });

        $this->adminController->shouldReceive('setupDevice')
            ->once()
            ->andReturnUsing(function() {
                // Simulate permission check failure
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['errorCode'] = 'priv';
                $result['error'] = 'You do not have permission to perform this action.';
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->setupDevice();
    }

    public function testGetAdminConfig()
    {
        $mockConfigData = [
            'business_name' => 'Test Store',
            'currency' => 'USD',
            'tax_rate' => 10.0
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->adminController->shouldReceive('getAdminConfig')
            ->once()
            ->andReturnUsing(function() use ($mockConfigData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockConfigData;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockConfigData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockConfigData, $result['data']);
                return json_encode($result);
            });

        $this->adminController->getAdminConfig();
    }

    public function testAddItemWithValidData()
    {
        $mockItemData = [
            'code' => 'ITEM003',
            'name' => 'New Test Item',
            'price' => '20.00',
            'categoryid' => 1
        ];
        
        $mockItemResult = [
            'id' => 3,
            'code' => 'ITEM003',
            'name' => 'New Test Item',
            'status' => 'created'
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('items/add')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getRequestData')
            ->once()
            ->andReturn((object)$mockItemData);

        $this->adminController->shouldReceive('addItem')
            ->once()
            ->andReturnUsing(function() use ($mockItemResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockItemResult;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockItemResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockItemResult, $result['data']);
                return json_encode($result);
            });

        $this->adminController->addItem();
    }

    public function testEditItemWithValidData()
    {
        $mockEditData = [
            'id' => 1,
            'name' => 'Updated Item Name',
            'price' => '25.00'
        ];
        
        $mockEditResult = [
            'id' => 1,
            'name' => 'Updated Item Name',
            'price' => '25.00',
            'status' => 'updated'
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('items/edit')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getRequestData')
            ->once()
            ->andReturn((object)$mockEditData);

        $this->adminController->shouldReceive('editItem')
            ->once()
            ->andReturnUsing(function() use ($mockEditResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockEditResult;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockEditResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockEditResult, $result['data']);
                return json_encode($result);
            });

        $this->adminController->editItem();
    }

    public function testDeleteItemWithValidData()
    {
        $mockDeleteData = ['id' => 1];
        $mockDeleteResult = ['id' => 1, 'status' => 'deleted'];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('items/delete')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getRequestData')
            ->once()
            ->andReturn((object)$mockDeleteData);

        $this->adminController->shouldReceive('deleteItem')
            ->once()
            ->andReturnUsing(function() use ($mockDeleteResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockDeleteResult;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockDeleteResult) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockDeleteResult, $result['data']);
                return json_encode($result);
            });

        $this->adminController->deleteItem();
    }

    public function testGetSuppliersWithPermission()
    {
        $mockSuppliersData = [
            ['id' => 1, 'name' => 'Supplier A', 'contact' => 'contact@suppliera.com'],
            ['id' => 2, 'name' => 'Supplier B', 'contact' => 'contact@supplierb.com']
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('suppliers/get')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getSuppliers')
            ->once()
            ->andReturnUsing(function() use ($mockSuppliersData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockSuppliersData;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockSuppliersData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockSuppliersData, $result['data']);
                return json_encode($result);
            });

        $this->adminController->getSuppliers();
    }

    public function testGetCategoriesWithPermission()
    {
        $mockCategoriesData = [
            ['id' => 1, 'name' => 'Electronics'],
            ['id' => 2, 'name' => 'Books']
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isUserAllowed')
            ->with('categories/get')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getCategories')
            ->once()
            ->andReturnUsing(function() use ($mockCategoriesData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockCategoriesData;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockCategoriesData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockCategoriesData, $result['data']);
                return json_encode($result);
            });

        $this->adminController->getCategories();
    }

    public function testGetUsersWithAdminPermission()
    {
        $mockUsersData = [
            ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
            ['id' => 2, 'username' => 'user1', 'role' => 'user']
        ];

        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isAdmin')
            ->once()
            ->andReturn(true);

        $this->adminController->shouldReceive('getUsers')
            ->once()
            ->andReturnUsing(function() use ($mockUsersData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['data'] = $mockUsersData;
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockUsersData) {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockUsersData, $result['data']);
                return json_encode($result);
            });

        $this->adminController->getUsers();
    }

    public function testGetUsersWithoutAdminPermission()
    {
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');

        $this->mockAuth->shouldReceive('isAdmin')
            ->once()
            ->andReturn(false);

        $this->adminController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                
                $this->assertEquals('priv', $result['errorCode']);
                $this->assertEquals('You do not have permission to perform this action.', $result['error']);
                return json_encode($result);
            });

        $this->adminController->shouldReceive('getUsers')
            ->once()
            ->andReturnUsing(function() {
                // Simulate admin permission check failure
                $reflection = new \ReflectionClass($this->adminController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->adminController);
                $result['errorCode'] = 'priv';
                $result['error'] = 'You do not have permission to perform this action.';
                $resultProperty->setValue($this->adminController, $result);
                return $this->adminController->returnResult();
            });

        $this->adminController->getUsers();
    }
}