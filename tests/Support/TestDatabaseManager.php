<?php

namespace Tests\Support;

use App\Database\DbConfig;
use App\Utility\TestData;
use PDO;
use PDOException;

class TestDatabaseManager
{
    private static $db = null;
    private static $initialized = false;

    /**
     * Set up a SQLite in-memory database for testing
     */
    public static function setupTestDatabase()
    {
        if (self::$initialized) {
            return self::$db;
        }

        try {
            // Create in-memory SQLite database
            self::$db = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Set up database structure
            self::createTables();
            
            // Seed with test data
            self::seedDatabase();
            
            self::$initialized = true;
            
            return self::$db;
        } catch (PDOException $e) {
            throw new \Exception('Failed to set up test database: ' . $e->getMessage());
        }
    }

    /**
     * Create database tables using schema adapted for SQLite
     */
    private static function createTables()
    {
        $schema = self::getSqliteSchema();
        
        foreach ($schema as $table) {
            self::$db->exec($table);
        }
    }

    /**
     * Seed database with test data using TestData utility
     */
    private static function seedDatabase()
    {
        // Mock the DbConfig to use our test database
        self::mockDbConfig();
        
        try {
            $testData = new TestData();
            
            // Insert basic demo records without purging (since we just created tables)
            self::insertBasicTestData();
            
        } catch (\Exception $e) {
            error_log('Failed to seed test database: ' . $e->getMessage());
            // Continue with empty database for now
        }
    }

    /**
     * Mock DbConfig to use our test database
     */
    private static function mockDbConfig()
    {
        // Create a reflection to override the private database connection
        $reflection = new \ReflectionClass(DbConfig::class);
        $dbProperty = $reflection->getProperty('_db');
        $dbProperty->setAccessible(true);
        
        // For now, we'll work without mocking DbConfig directly
        // Tests will need to be adjusted to work with SQLite
    }

    /**
     * Insert basic test data manually (simplified version of TestData)
     */
    private static function insertBasicTestData()
    {
        // Insert basic auth data
        self::$db->exec("
            INSERT INTO auth (id, username, name, password, token, uuid, admin, disabled, permissions) VALUES
            (1, 'admin', 'Test Admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', '', 'test-uuid-admin', 1, 0, ''),
            (2, 'staff', 'Test Staff', '1562206543da764123c21bd524674f0a8aaf49c8a89744c97352fe677f7e4006', '', 'test-uuid-staff', 0, 0, '{\"sections\":{\"access\":\"yes\"}}')
        ");

        // Insert basic categories
        self::$db->exec("
            INSERT INTO stored_categories (id, name, dt) VALUES
            (1, 'Food', '2024-01-01 00:00:00'),
            (2, 'Electronics', '2024-01-01 00:00:00')
        ");

        // Insert basic suppliers
        self::$db->exec("
            INSERT INTO stored_suppliers (id, name, dt) VALUES
            (1, 'Test Supplier', '2024-01-01 00:00:00')
        ");

        // Insert basic items
        self::$db->exec("
            INSERT INTO stored_items (id, categoryid, supplierid, code, qty, name, description, taxid, cost, price, type, modifiers) VALUES
            (1, 1, 1, 'TEST001', 10, 'Test Item', 'Test Description', '1', '5.00', '10.00', 'general', '[]')
        ");

        // Insert basic locations
        self::$db->exec("
            INSERT INTO locations (id, name, dt) VALUES
            (1, 'Test Location', '2024-01-01 00:00:00')
        ");

        // Insert basic devices
        self::$db->exec("
            INSERT INTO devices (id, name, locationid, type, ordertype, orderdisplay, dt) VALUES
            (1, 'Test Device', 1, 'general_register', '', '', '2024-01-01 00:00:00')
        ");

        // Insert basic customers
        self::$db->exec("
            INSERT INTO customers (id, name, email, address, phone, mobile, suburb, state, postcode, country, notes, dt) VALUES
            (1, 'Test Customer', 'test@example.com', '123 Test St', '1234567890', '0987654321', 'Testville', 'NSW', '2000', 'Australia', '', '2024-01-01 00:00:00')
        ");

        // Insert basic tax rules
        self::$db->exec("
            INSERT INTO tax_rules (id, name, type, value, location, dt) VALUES
            (1, 'GST', 'percentage', '10.00', 0, '2024-01-01 00:00:00'),
            (2, 'No Tax', 'percentage', '0.00', 0, '2024-01-01 00:00:00')
        ");

        // Insert basic config
        self::$db->exec("
            INSERT INTO config (name, data) VALUES
            ('pos_config', '{\"test\": true}'),
            ('general_config', '{\"test\": true}')
        ");
    }

    /**
     * Get SQLite-compatible schema (converted from MySQL)
     */
    private static function getSqliteSchema()
    {
        return [
            "CREATE TABLE IF NOT EXISTS auth (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(256) NOT NULL UNIQUE,
                name VARCHAR(66) NOT NULL DEFAULT '',
                password VARCHAR(256) NOT NULL,
                token VARCHAR(64) NOT NULL DEFAULT '',
                uuid CHAR(16) NOT NULL UNIQUE,
                admin INTEGER(1) NOT NULL,
                disabled INTEGER(1) NOT NULL DEFAULT 0,
                permissions VARCHAR(2048) NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS stored_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS stored_suppliers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS stored_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoryid INTEGER NOT NULL,
                supplierid INTEGER NOT NULL,
                code VARCHAR(256) NOT NULL,
                qty INTEGER NOT NULL,
                name VARCHAR(256) NOT NULL,
                description TEXT,
                taxid VARCHAR(32) NOT NULL,
                cost DECIMAL(10,2) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                type VARCHAR(32) NOT NULL,
                modifiers TEXT
            )",
            
            "CREATE TABLE IF NOT EXISTS locations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS devices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                locationid INTEGER NOT NULL,
                type VARCHAR(32) NOT NULL,
                ordertype VARCHAR(32),
                orderdisplay VARCHAR(32),
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                email VARCHAR(256),
                address VARCHAR(512),
                phone VARCHAR(32),
                mobile VARCHAR(32),
                suburb VARCHAR(256),
                state VARCHAR(256),
                postcode VARCHAR(32),
                country VARCHAR(256),
                notes TEXT,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS tax_rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(256) NOT NULL,
                type VARCHAR(32) NOT NULL,
                value DECIMAL(10,2) NOT NULL,
                location INTEGER NOT NULL,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS sales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ref VARCHAR(256) NOT NULL UNIQUE,
                processdt BIGINT NOT NULL,
                userid INTEGER NOT NULL,
                devid INTEGER NOT NULL,
                locid INTEGER NOT NULL,
                custid INTEGER,
                discount DECIMAL(10,2) NOT NULL DEFAULT 0,
                total DECIMAL(10,2) NOT NULL,
                dt DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS sale_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                saleid INTEGER NOT NULL,
                ref INTEGER NOT NULL,
                sitemid INTEGER NOT NULL,
                qty INTEGER NOT NULL,
                name VARCHAR(256) NOT NULL,
                description TEXT,
                cost DECIMAL(10,2) NOT NULL,
                unit DECIMAL(10,2) NOT NULL,
                taxid VARCHAR(32) NOT NULL,
                price DECIMAL(10,2) NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS sale_payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                saleid INTEGER NOT NULL,
                method VARCHAR(32) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                tender DECIMAL(10,2),
                change_amt DECIMAL(10,2)
            )",
            
            "CREATE TABLE IF NOT EXISTS config (
                name VARCHAR(256) PRIMARY KEY,
                data TEXT
            )",
            
            "CREATE TABLE IF NOT EXISTS sale_voids (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                saleid INTEGER NOT NULL,
                userid INTEGER NOT NULL,
                deviceid INTEGER NOT NULL,
                locationid INTEGER NOT NULL,
                reason VARCHAR(256),
                processdt BIGINT NOT NULL,
                method VARCHAR(32),
                amount DECIMAL(10,2)
            )"
        ];
    }

    /**
     * Reset database for clean state between tests
     */
    public static function resetDatabase()
    {
        if (self::$db) {
            // Clear all tables
            $tables = ['sales', 'sale_items', 'sale_payments', 'sale_voids', 'stored_items', 'stored_categories', 'stored_suppliers', 'customers', 'devices', 'locations', 'auth', 'config', 'tax_rules'];
            
            foreach ($tables as $table) {
                try {
                    self::$db->exec("DELETE FROM $table");
                } catch (PDOException $e) {
                    // Table might not exist, continue
                }
            }
            
            // Re-seed basic data
            self::insertBasicTestData();
        }
    }

    /**
     * Get test database connection
     */
    public static function getConnection()
    {
        if (!self::$initialized) {
            self::setupTestDatabase();
        }
        return self::$db;
    }

    /**
     * Clean up resources
     */
    public static function tearDown()
    {
        self::$db = null;
        self::$initialized = false;
    }
}