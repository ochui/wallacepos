<?php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabaseManager;
use App\Auth;
use Mockery;

abstract class BaseTestCase extends TestCase
{
    protected $originalServer;
    protected $testDb;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Back up global state
        $this->originalServer = $_SERVER;
        
        // Set up clean testing environment
        $this->setupTestEnvironment();
        
        // Initialize test database
        $this->testDb = TestDatabaseManager::setupTestDatabase();
        
        // Mock authentication for tests that need it
        $this->setupTestAuthentication();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Restore global state
        $_SERVER = $this->originalServer;
        
        // Reset database for next test
        TestDatabaseManager::resetDatabase();
        
        // Clean up Mockery
        Mockery::close();
        
        parent::tearDown();
    }

    /**
     * Set up the testing environment
     */
    protected function setupTestEnvironment()
    {
        // Set up $_SERVER for testing
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SCRIPT_NAME' => '/index.php',
            'QUERY_STRING' => '',
            'DOCUMENT_ROOT' => base_path('public'),
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        // Set testing flag
        $_ENV['TESTING'] = true;
        
        // Ensure constants are defined
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    /**
     * Set up test authentication
     */
    protected function setupTestAuthentication()
    {
        // This will be used by tests that need authenticated users
        // Individual tests can override this as needed
    }

    /**
     * Create an authenticated user session for testing
     */
    protected function actingAsAdmin()
    {
        $_SESSION['username'] = 'admin';
        $_SESSION['uuid'] = 'test-uuid-admin';
        $_SESSION['admin'] = true;
        $_SESSION['permissions'] = '';
        return $this;
    }

    /**
     * Create a staff user session for testing
     */
    protected function actingAsStaff()
    {
        $_SESSION['username'] = 'staff';
        $_SESSION['uuid'] = 'test-uuid-staff';
        $_SESSION['admin'] = false;
        $_SESSION['permissions'] = '{"sections":{"access":"yes"}}';
        return $this;
    }

    /**
     * Clear authentication session
     */
    protected function actingAsGuest()
    {
        session_unset();
        return $this;
    }

    /**
     * Mock a controller's database connection to use test database
     */
    protected function mockControllerDatabase($controller)
    {
        if (method_exists($controller, '_db') || property_exists($controller, '_db')) {
            $reflection = new \ReflectionClass($controller);
            if ($reflection->hasProperty('_db')) {
                $dbProperty = $reflection->getProperty('_db');
                $dbProperty->setAccessible(true);
                $dbProperty->setValue($controller, $this->testDb);
            }
        }
        
        return $controller;
    }

    /**
     * Assert that a JSON response contains expected data
     */
    protected function assertJsonResponse($expectedData, $actualJson)
    {
        $decoded = json_decode($actualJson, true);
        $this->assertNotNull($decoded, 'Response should be valid JSON');
        
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $decoded);
            if (!is_null($value)) {
                $this->assertEquals($value, $decoded[$key]);
            }
        }
    }

    /**
     * Assert that a response indicates success
     */
    protected function assertSuccessResponse($response)
    {
        $this->assertJsonResponse(['errorCode' => 'OK'], $response);
    }

    /**
     * Assert that a response indicates an error
     */
    protected function assertErrorResponse($expectedError, $response)
    {
        $decoded = json_decode($response, true);
        $this->assertNotNull($decoded);
        $this->assertNotEquals('OK', $decoded['errorCode'] ?? '');
        
        if ($expectedError) {
            $this->assertStringContainsString($expectedError, $decoded['error'] ?? '');
        }
    }

    /**
     * Create a mock POST request data
     */
    protected function createPostData($data = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        return $data;
    }

    /**
     * Create a mock GET request data
     */
    protected function createGetData($data = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $data;
        $_SERVER['QUERY_STRING'] = http_build_query($data);
        return $data;
    }

    /**
     * Capture output from a method that echoes
     */
    protected function captureOutput(callable $callback)
    {
        ob_start();
        try {
            $result = $callback();
            $output = ob_get_contents();
            return [$result, $output];
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Get test database connection
     */
    protected function getTestDatabase()
    {
        return $this->testDb;
    }

    /**
     * Execute SQL on test database
     */
    protected function executeSql($sql, $params = [])
    {
        $stmt = $this->testDb->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get records from test database
     */
    protected function getRecords($sql, $params = [])
    {
        $stmt = $this->testDb->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Insert test record
     */
    protected function insertTestRecord($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ":$col"; }, $columns);
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        $stmt = $this->testDb->prepare($sql);
        return $stmt->execute($data);
    }
}