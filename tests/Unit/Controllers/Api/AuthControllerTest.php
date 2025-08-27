<?php

namespace Tests\Unit\Controllers\Api;

use Tests\Support\BaseTestCase;
use App\Controllers\Api\AuthController;

class AuthControllerTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure clean session state
        $_SESSION = [];
        $_POST = [];
        $_REQUEST = [];
    }

    public function testAuthenticateWithValidCredentials()
    {
        // Create POST data for authentication
        $_REQUEST['data'] = json_encode([
            'username' => 'admin',
            'password' => 'admin',
            'getsessiontokens' => true
        ]);

        $controller = new AuthController();
        
        // Capture output since the controller echoes JSON and calls die()
        ob_start();
        try {
            $controller->authenticate();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be successful authentication
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
        $this->assertNotEmpty($response['data']);
        $this->assertEquals('admin', $response['data']['username']);
    }

    public function testAuthenticateWithInvalidCredentials()
    {
        // Create POST data with invalid credentials
        $_REQUEST['data'] = json_encode([
            'username' => 'admin',
            'password' => 'wrongpassword',
            'getsessiontokens' => false
        ]);

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->authenticate();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be authentication failure
        $this->assertNotEquals('OK', $response['errorCode']);
        $this->assertStringContainsString('Invalid', $response['error']);
    }

    public function testAuthenticateWithMissingData()
    {
        // No data provided
        $_REQUEST = [];

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->authenticate();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be request error
        $this->assertEquals('request', $response['errorCode']);
        $this->assertStringContainsString('No authentication data provided', $response['error']);
    }

    public function testAuthenticateWithInvalidJSON()
    {
        // Provide invalid JSON
        $_REQUEST['data'] = 'invalid-json';

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->authenticate();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be JSON decode error
        $this->assertEquals('jsondec', $response['errorCode']);
        $this->assertStringContainsString('Error decoding the json request', $response['error']);
    }

    public function testAuthenticateWithDisabledAccount()
    {
        // First, we need to disable an account in our test database
        $db = $this->getTestDatabase();
        $db->prepare("UPDATE auth SET disabled = 1 WHERE username = 'staff'")->execute();
        
        // Try to authenticate with disabled staff account
        $_REQUEST['data'] = json_encode([
            'username' => 'staff',
            'password' => 'staff',
            'getsessiontokens' => false
        ]);

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->authenticate();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be authentication failure due to disabled account
        $this->assertNotEquals('OK', $response['errorCode']);
        $this->assertStringContainsString('disabled', $response['error']);
    }

    public function testRenewTokenFunctionality()
    {
        // First authenticate to get a session
        $this->actingAsAdmin();

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->renewToken();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be successful for authenticated user
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
    }

    public function testLogoutFunctionality()
    {
        // First authenticate
        $this->actingAsAdmin();
        
        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->logout();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be successful logout
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
    }

    public function testHelloEndpointLoggedIn()
    {
        // First authenticate
        $this->actingAsAdmin();
        
        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->hello();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should return user data
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
        $this->assertNotEmpty($response['data']);
        $this->assertNotFalse($response['data']['user']);
    }

    public function testHelloEndpointNotLoggedIn()
    {
        // Ensure no authentication
        $this->actingAsGuest();
        
        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->hello();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should return false for user when not logged in
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
        $this->assertFalse($response['data']['user']);
    }

    public function testCustomerAuthWithValidCredentials()
    {
        // Add a test customer with credentials to database
        $db = $this->getTestDatabase();
        $stmt = $db->prepare("INSERT INTO customers (name, email, password, activated) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test Customer', 'customer@test.com', hash('sha256', 'password'), 1]);
        
        $_REQUEST['data'] = json_encode([
            'username' => 'customer@test.com',
            'password' => 'password'
        ]);

        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->customerAuth();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should be successful for valid customer
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
    }

    public function testAuthorizeWebsocket()
    {
        // Authenticate first
        $this->actingAsAdmin();
        
        $controller = new AuthController();
        
        // Capture output
        ob_start();
        try {
            $controller->authorizeWebsocket();
        } catch (\Throwable $e) {
            // Controller calls die(), which is expected
        }
        $output = ob_get_clean();

        // Verify JSON response
        $this->assertJson($output);
        $response = json_decode($output, true);
        
        // Should return websocket authorization data
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals('OK', $response['error']);
        $this->assertNotEmpty($response['data']);
    }
}