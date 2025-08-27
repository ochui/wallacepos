<?php

namespace Tests\Feature\Database;

use Tests\Support\BaseTestCase;
use Tests\Support\TestDatabaseManager;
use App\Utility\TestData;

/**
 * Integration test to verify database connection and seeding functionality
 */
class DatabaseConnectionTest extends BaseTestCase
{
    public function testDatabaseConnectionEstablished()
    {
        $db = $this->getTestDatabase();
        $this->assertNotNull($db, 'Test database connection should be established');
        
        // Test that we can execute a simple query
        $result = $db->query("SELECT 1 as test")->fetch();
        $this->assertEquals(1, $result['test']);
    }

    public function testBasicTestDataSeeded()
    {
        $db = $this->getTestDatabase();
        
        // Check that auth users exist
        $users = $this->getRecords("SELECT * FROM auth");
        $this->assertGreaterThan(0, count($users), 'Should have seeded auth users');
        
        // Check admin user exists
        $admin = $this->getRecords("SELECT * FROM auth WHERE username = 'admin'");
        $this->assertCount(1, $admin, 'Should have admin user');
        $this->assertEquals(1, $admin[0]['admin'], 'Admin user should have admin flag');
        
        // Check categories exist
        $categories = $this->getRecords("SELECT * FROM stored_categories");
        $this->assertGreaterThan(0, count($categories), 'Should have seeded categories');
        
        // Check items exist
        $items = $this->getRecords("SELECT * FROM stored_items");
        $this->assertGreaterThan(0, count($items), 'Should have seeded items');
        
        // Check locations exist
        $locations = $this->getRecords("SELECT * FROM locations");
        $this->assertGreaterThan(0, count($locations), 'Should have seeded locations');
        
        // Check devices exist
        $devices = $this->getRecords("SELECT * FROM devices");
        $this->assertGreaterThan(0, count($devices), 'Should have seeded devices');
    }

    public function testDatabaseReset()
    {
        $db = $this->getTestDatabase();
        
        // Insert a test record
        $this->insertTestRecord('stored_categories', [
            'name' => 'Temporary Category',
            'dt' => '2024-01-01 00:00:00'
        ]);
        
        // Verify it exists
        $tempCategories = $this->getRecords("SELECT * FROM stored_categories WHERE name = 'Temporary Category'");
        $this->assertCount(1, $tempCategories);
        
        // Reset database
        TestDatabaseManager::resetDatabase();
        
        // Verify temp record is gone but basic data remains
        $tempCategories = $this->getRecords("SELECT * FROM stored_categories WHERE name = 'Temporary Category'");
        $this->assertCount(0, $tempCategories, 'Temporary record should be removed');
        
        $basicCategories = $this->getRecords("SELECT * FROM stored_categories");
        $this->assertGreaterThan(0, count($basicCategories), 'Basic seeded data should remain');
    }

    public function testAuthenticationData()
    {
        // Test that we can authenticate with seeded users
        $db = $this->getTestDatabase();
        
        // Test admin login data
        $admin = $this->getRecords("SELECT * FROM auth WHERE username = 'admin'")[0];
        $this->assertEquals('admin', $admin['username']);
        $this->assertEquals('Test Admin', $admin['name']);
        // Password should be hashed 'admin' -> sha256
        $this->assertEquals('8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', $admin['password']);
        
        // Test staff login data  
        $staff = $this->getRecords("SELECT * FROM auth WHERE username = 'staff'")[0];
        $this->assertEquals('staff', $staff['username']);
        $this->assertEquals('Test Staff', $staff['name']);
        // Password should be hashed 'staff' -> sha256
        $this->assertEquals('1562206543da764123c21bd524674f0a8aaf49c8a89744c97352fe677f7e4006', $staff['password']);
    }

    public function testConfigurationData()
    {
        $db = $this->getTestDatabase();
        
        // Check that basic config exists
        $config = $this->getRecords("SELECT * FROM config");
        $this->assertGreaterThan(0, count($config), 'Should have configuration data');
        
        // Check specific config entries
        $posConfig = $this->getRecords("SELECT * FROM config WHERE name = 'pos_config'");
        $this->assertCount(1, $posConfig, 'Should have POS configuration');
        
        $generalConfig = $this->getRecords("SELECT * FROM config WHERE name = 'general_config'");
        $this->assertCount(1, $generalConfig, 'Should have general configuration');
    }

    public function testRelationalDataIntegrity()
    {
        $db = $this->getTestDatabase();
        
        // Test that items reference valid categories and suppliers
        $items = $this->getRecords("
            SELECT i.*, c.name as category_name, s.name as supplier_name 
            FROM stored_items i 
            LEFT JOIN stored_categories c ON i.categoryid = c.id 
            LEFT JOIN stored_suppliers s ON i.supplierid = s.id
        ");
        
        $this->assertGreaterThan(0, count($items));
        
        foreach ($items as $item) {
            $this->assertNotNull($item['category_name'], "Item {$item['name']} should have valid category");
            $this->assertNotNull($item['supplier_name'], "Item {$item['name']} should have valid supplier");
        }
        
        // Test that devices reference valid locations
        $devices = $this->getRecords("
            SELECT d.*, l.name as location_name 
            FROM devices d 
            LEFT JOIN locations l ON d.locationid = l.id
        ");
        
        $this->assertGreaterThan(0, count($devices));
        
        foreach ($devices as $device) {
            $this->assertNotNull($device['location_name'], "Device {$device['name']} should have valid location");
        }
    }
}