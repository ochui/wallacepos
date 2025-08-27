<?php

namespace Tests\Unit\Controllers\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\AuthController;
use App\Auth;
use Mockery;

/**
 * Unit tests for AuthController
 * Tests authentication functionality, token management, and security features
 */
class AuthControllerTest extends TestCase
{
    protected $authController;
    protected $mockAuth;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Auth class
        $this->mockAuth = Mockery::mock(Auth::class);
        
        // Create a partial mock of AuthController
        $this->authController = Mockery::mock(AuthController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Create reflection to access private properties
        $reflection = new \ReflectionClass(AuthController::class);
        if ($reflection->hasProperty('auth')) {
            $authProperty = $reflection->getProperty('auth');
            $authProperty->setAccessible(true);
            $authProperty->setValue($this->authController, $this->mockAuth);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAuthenticateWithValidCredentials()
    {
        // Setup request data
        $_REQUEST['data'] = json_encode([
            'username' => 'testuser',
            'password' => 'testpass',
            'getsessiontokens' => true
        ]);

        $mockUserData = [
            'id' => 1,
            'username' => 'testuser',
            'displayname' => 'Test User',
            'role' => 'admin'
        ];

        // Mock successful login
        $this->mockAuth->shouldReceive('login')
            ->with('testuser', 'testpass', true)
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getUser')
            ->once()
            ->andReturn($mockUserData);

        // Mock the returnResult method
        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockUserData) {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockUserData, $result['data']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testAuthenticateWithInvalidCredentials()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'baduser',
            'password' => 'badpass'
        ]);

        // Mock failed login
        $this->mockAuth->shouldReceive('login')
            ->with('baduser', 'badpass', false)
            ->once()
            ->andReturn(false);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('authdenied', $result['errorCode']);
                $this->assertEquals('Access Denied!', $result['error']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testAuthenticateWithDisabledAccount()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'disableduser',
            'password' => 'password'
        ]);

        // Mock disabled account (-1 return)
        $this->mockAuth->shouldReceive('login')
            ->with('disableduser', 'password', false)
            ->once()
            ->andReturn(-1);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('authdenied', $result['errorCode']);
                $this->assertEquals('Your account has been disabled, please contact your system administrator!', $result['error']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testAuthenticateWithMissingData()
    {
        unset($_REQUEST['data']);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('request', $result['errorCode']);
                $this->assertEquals('No authentication data provided', $result['error']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testAuthenticateWithInvalidJSON()
    {
        $_REQUEST['data'] = 'invalid-json-data';

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('jsondec', $result['errorCode']);
                $this->assertEquals('Error decoding the json request!', $result['error']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testRenewTokenWithValidData()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'testuser',
            'auth_hash' => 'valid-hash-123'
        ]);

        $mockUserData = [
            'id' => 1,
            'username' => 'testuser',
            'displayname' => 'Test User'
        ];

        $this->mockAuth->shouldReceive('renewTokenSession')
            ->with('testuser', 'valid-hash-123')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getUser')
            ->once()
            ->andReturn($mockUserData);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockUserData) {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockUserData, $result['data']);
                return json_encode($result);
            });

        $this->authController->renewToken();
    }

    public function testRenewTokenWithInvalidHash()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'testuser',
            'auth_hash' => 'invalid-hash'
        ]);

        $this->mockAuth->shouldReceive('renewTokenSession')
            ->with('testuser', 'invalid-hash')
            ->once()
            ->andReturn(false);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('authdenied', $result['errorCode']);
                $this->assertEquals('Access Denied!', $result['error']);
                return json_encode($result);
            });

        $this->authController->renewToken();
    }

    public function testAuthenticateWithNullUserData()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        // Mock successful login but null user data
        $this->mockAuth->shouldReceive('login')
            ->with('testuser', 'testpass', false)
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getUser')
            ->once()
            ->andReturn(null);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals('Could not retrieve user data from php session.', $result['error']);
                return json_encode($result);
            });

        $this->authController->authenticate();
    }

    public function testLogoutFunctionality()
    {
        $this->mockAuth->shouldReceive('logout')
            ->once()
            ->andReturn(true);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                return json_encode($result);
            });

        $this->authController->logout();
    }

    public function testHelloEndpointLoggedIn()
    {
        $mockUserData = ['id' => 1, 'username' => 'testuser'];
        
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getUser')
            ->once()
            ->andReturn($mockUserData);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockUserData) {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockUserData, $result['data']);
                return json_encode($result);
            });

        $this->authController->hello();
    }

    public function testHelloEndpointNotLoggedIn()
    {
        $this->mockAuth->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(false);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals(false, $result['data']);
                return json_encode($result);
            });

        $this->authController->hello();
    }

    public function testCustomerAuthWithValidCredentials()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'customer@test.com',
            'password' => 'customerpass'
        ]);

        $mockCustomerData = [
            'id' => 1,
            'email' => 'customer@test.com',
            'name' => 'Test Customer'
        ];

        $this->mockAuth->shouldReceive('customerLogin')
            ->with('customer@test.com', 'customerpass')
            ->once()
            ->andReturn(true);

        $this->mockAuth->shouldReceive('getCustomer')
            ->once()
            ->andReturn($mockCustomerData);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockCustomerData) {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockCustomerData, $result['data']);
                return json_encode($result);
            });

        $this->authController->customerAuth();
    }

    public function testCustomerAuthWithDisabledAccount()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'disabled@test.com',
            'password' => 'password'
        ]);

        $this->mockAuth->shouldReceive('customerLogin')
            ->with('disabled@test.com', 'password')
            ->once()
            ->andReturn(-1);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('authdenied', $result['errorCode']);
                $this->assertEquals('Your account has been disabled, please contact your system administrator!', $result['error']);
                return json_encode($result);
            });

        $this->authController->customerAuth();
    }

    public function testCustomerAuthWithUnactivatedAccount()
    {
        $_REQUEST['data'] = json_encode([
            'username' => 'unactivated@test.com',
            'password' => 'password'
        ]);

        $this->mockAuth->shouldReceive('customerLogin')
            ->with('unactivated@test.com', 'password')
            ->once()
            ->andReturn(-2);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('authdenied', $result['errorCode']);
                $this->assertEquals('Your account has not yet been activated, please activate your account or reset your password.', $result['error']);
                return json_encode($result);
            });

        $this->authController->customerAuth();
    }

    public function testAuthorizeWebsocket()
    {
        $mockWebsocketAuth = ['token' => 'ws-token-123', 'expires' => time() + 3600];

        $this->mockAuth->shouldReceive('authoriseWebsocket')
            ->once()
            ->andReturn($mockWebsocketAuth);

        $this->authController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockWebsocketAuth) {
                $reflection = new \ReflectionClass($this->authController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->authController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockWebsocketAuth, $result['data']);
                return json_encode($result);
            });

        $this->authController->authorizeWebsocket();
    }
}