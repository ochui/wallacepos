<?php

namespace Tests\Feature\Database;

use Tests\Support\BaseTestCase;
use Tests\Support\TestDatabaseManager;
use App\Utility\TestData;

/**
 * Integration test for TestData utility functionality
 */
class TestDataSeedingTest extends BaseTestCase
{
    public function testTestDataClassCanBeInstantiated()
    {
        $testData = new TestData();
        $this->assertInstanceOf(TestData::class, $testData);
    }

    public function testBasicDemoDataStructure()
    {
        // Verify the basic structure is in place for TestData to work
        $requiredTables = [
            'auth', 'stored_categories', 'stored_suppliers', 'stored_items',
            'locations', 'devices', 'customers', 'tax_rules', 'config'
        ];

        foreach ($requiredTables as $table) {
            $exists = $this->tableExists($table);
            $this->assertTrue($exists, "Table $table should exist for TestData to work");
        }
    }

    public function testSeededDataIntegrity()
    {
        // Test that seeded data follows expected patterns
        
        // Auth users should have proper structure
        $users = $this->getRecords("SELECT * FROM auth");
        foreach ($users as $user) {
            $this->assertNotEmpty($user['username'], 'Username should not be empty');
            $this->assertNotEmpty($user['password'], 'Password should not be empty');
            $this->assertIsInt($user['admin'], 'Admin flag should be integer');
            $this->assertIsInt($user['disabled'], 'Disabled flag should be integer');
        }

        // Items should have valid prices and costs
        $items = $this->getRecords("SELECT * FROM stored_items");
        foreach ($items as $item) {
            $this->assertNotEmpty($item['name'], 'Item name should not be empty');
            $this->assertNotEmpty($item['code'], 'Item code should not be empty');
            $this->assertIsNumeric($item['price'], 'Item price should be numeric');
            $this->assertIsNumeric($item['cost'], 'Item cost should be numeric');
            $this->assertGreaterThanOrEqual(0, $item['price'], 'Item price should be non-negative');
            $this->assertGreaterThanOrEqual(0, $item['cost'], 'Item cost should be non-negative');
        }

        // Categories should have names
        $categories = $this->getRecords("SELECT * FROM stored_categories");
        foreach ($categories as $category) {
            $this->assertNotEmpty($category['name'], 'Category name should not be empty');
        }

        // Tax rules should have valid structures
        $taxRules = $this->getRecords("SELECT * FROM tax_rules");
        foreach ($taxRules as $rule) {
            $this->assertNotEmpty($rule['name'], 'Tax rule name should not be empty');
            $this->assertContains($rule['type'], ['percentage', 'fixed'], 'Tax rule type should be valid');
            $this->assertIsNumeric($rule['value'], 'Tax rule value should be numeric');
        }
    }

    public function testDatabaseRelationalConstraints()
    {
        // Test that foreign key relationships are properly maintained
        
        // Items should reference valid categories
        $invalidItems = $this->getRecords("
            SELECT i.* 
            FROM stored_items i 
            LEFT JOIN stored_categories c ON i.categoryid = c.id 
            WHERE c.id IS NULL AND i.categoryid != 0
        ");
        $this->assertCount(0, $invalidItems, 'All items should reference valid categories (or 0 for no category)');

        // Items should reference valid suppliers
        $invalidSupplierItems = $this->getRecords("
            SELECT i.* 
            FROM stored_items i 
            LEFT JOIN stored_suppliers s ON i.supplierid = s.id 
            WHERE s.id IS NULL AND i.supplierid != 0
        ");
        $this->assertCount(0, $invalidSupplierItems, 'All items should reference valid suppliers (or 0 for no supplier)');

        // Devices should reference valid locations
        $invalidDevices = $this->getRecords("
            SELECT d.* 
            FROM devices d 
            LEFT JOIN locations l ON d.locationid = l.id 
            WHERE l.id IS NULL
        ");
        $this->assertCount(0, $invalidDevices, 'All devices should reference valid locations');
    }

    public function testConfigurationExists()
    {
        // Test that basic configuration is available
        $configs = $this->getRecords("SELECT * FROM config");
        $this->assertGreaterThan(0, count($configs), 'Should have configuration data');

        $configNames = array_column($configs, 'name');
        $this->assertContains('pos_config', $configNames, 'Should have POS configuration');
        $this->assertContains('general_config', $configNames, 'Should have general configuration');
    }

    public function testAuthenticationUsers()
    {
        // Test that we have the required authentication users
        $adminUsers = $this->getRecords("SELECT * FROM auth WHERE admin = 1");
        $this->assertGreaterThan(0, count($adminUsers), 'Should have at least one admin user');

        $staffUsers = $this->getRecords("SELECT * FROM auth WHERE admin = 0");
        $this->assertGreaterThan(0, count($staffUsers), 'Should have at least one staff user');

        // Test specific test users
        $admin = $this->getRecords("SELECT * FROM auth WHERE username = 'admin'");
        $this->assertCount(1, $admin, 'Should have admin test user');
        $this->assertEquals(1, $admin[0]['admin'], 'Admin user should have admin privileges');

        $staff = $this->getRecords("SELECT * FROM auth WHERE username = 'staff'");
        $this->assertCount(1, $staff, 'Should have staff test user');
        $this->assertEquals(0, $staff[0]['admin'], 'Staff user should not have admin privileges');
    }

    public function testPOSDataStructure()
    {
        // Test that POS-specific data is properly structured

        // Locations for multi-store support
        $locations = $this->getRecords("SELECT * FROM locations");
        $this->assertGreaterThan(0, count($locations), 'Should have test locations');

        // Devices/registers for each location
        $devices = $this->getRecords("SELECT * FROM devices");
        $this->assertGreaterThan(0, count($devices), 'Should have test devices/registers');

        // Tax rules for price calculations
        $taxRules = $this->getRecords("SELECT * FROM tax_rules");
        $this->assertGreaterThan(0, count($taxRules), 'Should have tax rules');

        // Items for sales
        $items = $this->getRecords("SELECT * FROM stored_items");
        $this->assertGreaterThan(0, count($items), 'Should have test items for sales');

        // Customers for invoicing
        $customers = $this->getRecords("SELECT * FROM customers");
        $this->assertGreaterThan(0, count($customers), 'Should have test customers');
    }

    public function testDatabaseCanHandleSalesData()
    {
        // Test that we can create sales data (structure is ready)
        
        // Insert a test sale
        $saleData = [
            'ref' => 'TEST-' . time(),
            'processdt' => time() * 1000,
            'userid' => 1, // admin user
            'devid' => 1, // first device
            'locid' => 1, // first location
            'custid' => 1, // first customer
            'discount' => 0.00,
            'total' => 10.00,
            'dt' => date('Y-m-d H:i:s')
        ];

        $this->insertTestRecord('sales', $saleData);

        // Verify sale was inserted
        $sales = $this->getRecords("SELECT * FROM sales WHERE ref = ?", [$saleData['ref']]);
        $this->assertCount(1, $sales, 'Test sale should be inserted');
        $this->assertEquals($saleData['total'], $sales[0]['total'], 'Sale total should match');
    }

    /**
     * Check if a table exists in the database
     */
    private function tableExists($tableName)
    {
        try {
            $db = $this->getTestDatabase();
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}