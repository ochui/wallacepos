<?php

namespace Tests\Unit\Controllers\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\AdminController;
use Mockery;

/**
 * Unit tests for AdminController
 * Tests authentication and permission checks without stubbing the methods under test
 */
class AdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock HTTP headers for CSRF
        $_SERVER['HTTP_ANTI_CSRF_TOKEN'] = 'test-csrf-token';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        unset($_SERVER['HTTP_ANTI_CSRF_TOKEN']);
        parent::tearDown();
    }

    public function testSetupDeviceWithoutAuthentication()
    {
        // Mock Auth class to return false for authentication
        $mockAuth = Mockery::mock('overload:App\Auth');
        $mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(false);

        // Create a real AdminController instance
        $controller = new AdminController();

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $controller->setupDevice();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('auth', $response['errorCode']);
        $this->assertEquals('Access Denied!', $response['error']);
    }

    public function testSetupDeviceWithoutPermission()
    {
        // Mock Auth class to be logged in but without permission
        $mockAuth = Mockery::mock('overload:App\Auth');
        $mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);
        $mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');
        $mockAuth->shouldReceive('isUserAllowed')
            ->with('devices/setup')
            ->once()
            ->andReturn(false);

        // Create a real AdminController instance
        $controller = new AdminController();

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $controller->setupDevice();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('priv', $response['errorCode']);
        $this->assertEquals('You do not have permission to perform this action.', $response['error']);
    }

    public function testAddItemWithoutAuthentication()
    {
        // Mock Auth class to return false for authentication
        $mockAuth = Mockery::mock('overload:App\Auth');
        $mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(false);

        // Create a real AdminController instance
        $controller = new AdminController();

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $controller->addItem();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('auth', $response['errorCode']);
        $this->assertEquals('Access Denied!', $response['error']);
    }

    public function testGetUsersWithoutAdminPermission()
    {
        // Mock Auth class to be logged in but not admin
        $mockAuth = Mockery::mock('overload:App\Auth');
        $mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);
        $mockAuth->shouldReceive('getCsrfToken')
            ->once()
            ->andReturn('test-csrf-token');
        $mockAuth->shouldReceive('isAdmin')
            ->once()
            ->andReturn(false);

        // Create a real AdminController instance
        $controller = new AdminController();

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $controller->getUsers();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('priv', $response['errorCode']);
        $this->assertEquals('You do not have permission to perform this action.', $response['error']);
    }

    public function testControllerInstantiation()
    {
        // Simple test to ensure controller can be instantiated
        $controller = new AdminController();
        $this->assertInstanceOf(AdminController::class, $controller);
    }
}