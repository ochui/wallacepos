<?php

namespace Tests\Feature\Authentication;

use Tests\Support\BaseTestCase;
use App\Controllers\Api\AuthController;
use App\Auth;

/**
 * Integration test to verify authentication functionality with database connection
 */
class AuthenticationIntegrationTest extends BaseTestCase
{
    public function testUserCanLoginWithValidCredentials()
    {
        // Set up login request
        $this->createPostData([
            'data' => json_encode([
                'username' => 'admin',
                'password' => 'admin'
            ])
        ]);

        // Create auth controller 
        $authController = new AuthController();
        
        // Capture the output since controller echoes JSON
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->authenticate();
        });

        // Should return success response
        $this->assertStringContainsString('OK', $output);
        $this->assertStringContainsString('admin', $output);
        
        // Verify session was created (this would normally be handled by Auth class)
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
    }

    public function testUserCannotLoginWithInvalidCredentials()
    {
        // Set up login request with wrong password
        $this->createPostData([
            'data' => json_encode([
                'username' => 'admin',
                'password' => 'wrongpassword'
            ])
        ]);

        $authController = new AuthController();
        
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->authenticate();
        });

        // Should return error response
        $this->assertStringNotContainsString('"errorCode":"OK"', $output);
        
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertNotEquals('OK', $response['errorCode']);
    }

    public function testSessionAuthenticationWithAdmin()
    {
        // Use helper to set up admin session
        $this->actingAsAdmin();
        
        // Create Auth instance and test isLoggedIn
        $auth = new Auth();
        $isLoggedIn = $auth->isLoggedIn();
        
        $this->assertTrue($isLoggedIn, 'Admin user should be logged in');
        
        // Test admin privileges
        $isAdmin = $auth->isAdmin();
        $this->assertTrue($isAdmin, 'Admin user should have admin privileges');
    }

    public function testSessionAuthenticationWithStaff()
    {
        // Use helper to set up staff session
        $this->actingAsStaff();
        
        $auth = new Auth();
        $isLoggedIn = $auth->isLoggedIn();
        
        $this->assertTrue($isLoggedIn, 'Staff user should be logged in');
        
        // Test staff does not have admin privileges
        $isAdmin = $auth->isAdmin();
        $this->assertFalse($isAdmin, 'Staff user should not have admin privileges');
    }

    public function testGuestUserNotAuthenticated()
    {
        // Clear session
        $this->actingAsGuest();
        
        $auth = new Auth();
        $isLoggedIn = $auth->isLoggedIn();
        
        $this->assertFalse($isLoggedIn, 'Guest user should not be logged in');
        
        $isAdmin = $auth->isAdmin();
        $this->assertFalse($isAdmin, 'Guest user should not have admin privileges');
    }

    public function testLogoutFunctionality()
    {
        // First login as admin
        $this->actingAsAdmin();
        
        $auth = new Auth();
        $this->assertTrue($auth->isLoggedIn(), 'Should be logged in initially');
        
        // Create logout request
        $this->createPostData([]);
        
        $authController = new AuthController();
        
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->logout();
        });

        // Should return success
        $this->assertStringContainsString('OK', $output);
        
        // Session should be cleared
        $this->assertEmpty($_SESSION['username'] ?? '');
    }

    public function testTokenRenewal()
    {
        // Set up existing session
        $this->actingAsAdmin();
        
        $authController = new AuthController();
        
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->renewToken();
        });

        // Should return success with renewed token
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testWebsocketAuthorization()
    {
        // Set up admin session
        $this->actingAsAdmin();
        
        $authController = new AuthController();
        
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->authorizeWebsocket();
        });

        // Should return authorization data
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
    }

    public function testHelloEndpoint()
    {
        $authController = new AuthController();
        
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->hello();
        });

        // Should return simple OK response
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
    }

    public function testAuthenticationWithDatabaseUsers()
    {
        // Verify our test users exist in database
        $users = $this->getRecords("SELECT * FROM auth WHERE username IN ('admin', 'staff')");
        $this->assertCount(2, $users, 'Should have both admin and staff test users');
        
        // Test that passwords are properly hashed
        foreach ($users as $user) {
            $this->assertNotEmpty($user['password'], 'User should have password hash');
            $this->assertNotEquals($user['username'], $user['password'], 'Password should be hashed, not plain text');
        }
    }
}