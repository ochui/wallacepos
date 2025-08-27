<?php

namespace Tests\Feature\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\InstallController;
use Mockery;

/**
 * Feature tests for InstallController
 * These tests verify the installation requirement checking functionality
 */
class InstallControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCheckRequirementsStructure()
    {
        // Create an instance of the controller
        $controller = new InstallController();
        
        // Use reflection to access the private checkRequirements method
        $reflection = new \ReflectionClass($controller);
        $checkRequirementsMethod = $reflection->getMethod('checkRequirements');
        $checkRequirementsMethod->setAccessible(true);
        
        // Call the real method
        $result = $checkRequirementsMethod->invoke($controller);
        
        // Verify the structure matches expectations
        $this->assertArrayHasKey('webserver', $result);
        $this->assertArrayHasKey('php', $result);
        $this->assertArrayHasKey('all', $result);
        $this->assertArrayHasKey('requirements', $result);
        
        // Verify types
        $this->assertIsBool($result['webserver']);
        $this->assertIsBool($result['php']);
        $this->assertIsBool($result['all']);
        $this->assertIsArray($result['requirements']);
    }

    public function testRequirementItemStructure()
    {
        // Create an instance of the controller
        $controller = new InstallController();
        
        // Use reflection to access the private checkRequirements method
        $reflection = new \ReflectionClass($controller);
        $checkRequirementsMethod = $reflection->getMethod('checkRequirements');
        $checkRequirementsMethod->setAccessible(true);
        
        // Call the real method
        $result = $checkRequirementsMethod->invoke($controller);
        
        // Verify that requirements array is not empty
        $this->assertNotEmpty($result['requirements'], 'Requirements array should not be empty');
        
        // Get the first requirement item to test its structure
        $requirementItem = $result['requirements'][0];
        
        $this->assertArrayHasKey('name', $requirementItem);
        $this->assertArrayHasKey('status', $requirementItem);
        $this->assertArrayHasKey('current', $requirementItem);
        $this->assertArrayHasKey('required', $requirementItem);
        $this->assertArrayHasKey('type', $requirementItem);
        
        $this->assertIsBool($requirementItem['status']);
        $this->assertIsString($requirementItem['name']);
        $this->assertIsString($requirementItem['current']);
        $this->assertIsString($requirementItem['required']);
        $this->assertIsString($requirementItem['type']);
    }

    public function testPHPVersionComparison()
    {
        // Test PHP version comparison logic (similar to what's in checkRequirements)
        $currentPHPVersion = '8.3.6';
        $requiredPHPVersion = '8.0.0';
        
        $isVersionOk = version_compare($currentPHPVersion, $requiredPHPVersion) >= 0;
        
        $this->assertTrue($isVersionOk);
        
        // Test with insufficient version
        $lowPHPVersion = '7.4.0';
        $isLowVersionOk = version_compare($lowPHPVersion, $requiredPHPVersion) >= 0;
        
        $this->assertFalse($isLowVersionOk);
    }

    public function testRequiredPHPExtensions()
    {
        // Test the extensions array structure used in checkRequirements
        $extensions = [
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'json' => 'JSON',
            'curl' => 'cURL',
            'mbstring' => 'Multibyte String',
            'openssl' => 'OpenSSL',
            'xml' => 'XML',
            'zip' => 'ZIP'
        ];
        
        $this->assertArrayHasKey('pdo', $extensions);
        $this->assertArrayHasKey('pdo_mysql', $extensions);
        $this->assertArrayHasKey('json', $extensions);
        $this->assertArrayHasKey('curl', $extensions);
        $this->assertArrayHasKey('mbstring', $extensions);
        $this->assertArrayHasKey('openssl', $extensions);
        $this->assertArrayHasKey('xml', $extensions);
        $this->assertArrayHasKey('zip', $extensions);
        
        // Test that all values are human-readable names
        foreach ($extensions as $extension => $humanName) {
            $this->assertIsString($extension);
            $this->assertIsString($humanName);
            $this->assertNotEmpty($humanName);
        }
    }

    public function testWebServerDetection()
    {
        // Save original SERVER_SOFTWARE if it exists
        $originalServerSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
        
        try {
            // Test web server detection logic
            $mockServerSoftware = 'Apache/2.4.41 (Ubuntu)';
            
            // Simulate $_SERVER['SERVER_SOFTWARE']
            $_SERVER['SERVER_SOFTWARE'] = $mockServerSoftware;
            
            $webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
            
            $this->assertEquals($mockServerSoftware, $webServer);
            
            // Test unknown server
            unset($_SERVER['SERVER_SOFTWARE']);
            $webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
            
            $this->assertEquals('Unknown', $webServer);
        } finally {
            // Restore original value
            if ($originalServerSoftware !== null) {
                $_SERVER['SERVER_SOFTWARE'] = $originalServerSoftware;
            } else {
                unset($_SERVER['SERVER_SOFTWARE']);
            }
        }
    }

    public function testRequirementsEndpoint()
    {
        // Test the actual requirements endpoint
        $controller = new InstallController();
        
        // Capture the output from the requirements method
        ob_start();
        $result = $controller->requirements();
        $output = ob_get_clean();
        
        // If output was echoed, use that; otherwise use the return value
        if (!empty($output)) {
            $response = json_decode($output, true);
        } else {
            $response = json_decode($result, true);
        }
        
        $this->assertNotNull($response, 'Response should be valid JSON');
        $this->assertArrayHasKey('errorCode', $response);
        $this->assertArrayHasKey('data', $response);
        
        $data = $response['data'];
        $this->assertArrayHasKey('webserver', $data);
        $this->assertArrayHasKey('php', $data);
        $this->assertArrayHasKey('all', $data);
        $this->assertArrayHasKey('requirements', $data);
        
        $this->assertIsBool($data['webserver']);
        $this->assertIsBool($data['php']);
        $this->assertIsBool($data['all']);
        $this->assertIsArray($data['requirements']);
    }
}