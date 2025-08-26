<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for input validation and data integrity
 * Tests validation rules used throughout the FreePOS system
 */
class ValidationRulesTest extends TestCase
{
    public function testItemValidation()
    {
        // Test valid item data
        $validItem = [
            'code' => 'ITEM001',
            'name' => 'Valid Item Name',
            'price' => '29.99',
            'cost' => '15.00',
            'categoryid' => 1,
            'supplierid' => 1,
            'description' => 'A valid item description'
        ];
        
        $this->assertTrue($this->validateItem($validItem));
        
        // Test invalid item data
        $invalidItems = [
            ['code' => '', 'name' => 'No Code', 'price' => '10.00'], // Missing code
            ['code' => 'ITEM002', 'name' => '', 'price' => '10.00'], // Missing name
            ['code' => 'ITEM003', 'name' => 'Negative Price', 'price' => '-5.00'], // Negative price
            ['code' => 'ITEM004', 'name' => 'Invalid Price', 'price' => 'abc'], // Non-numeric price
        ];
        
        foreach ($invalidItems as $item) {
            $this->assertFalse($this->validateItem($item));
        }
    }

    public function testCustomerValidation()
    {
        // Test valid customer data
        $validCustomer = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main Street'
        ];
        
        $this->assertTrue($this->validateCustomer($validCustomer));
        
        // Test invalid customer data
        $invalidCustomers = [
            ['name' => '', 'email' => 'test@example.com'], // Missing name
            ['name' => 'Jane Doe', 'email' => 'invalid-email'], // Invalid email
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'phone' => '123'], // Invalid phone
        ];
        
        foreach ($invalidCustomers as $customer) {
            $this->assertFalse($this->validateCustomer($customer));
        }
    }

    public function testSaleValidation()
    {
        // Test valid sale data
        $validSale = [
            'items' => [
                ['id' => 1, 'qty' => 2, 'price' => '10.99'],
                ['id' => 2, 'qty' => 1, 'price' => '15.50']
            ],
            'total' => '37.48',
            'payment_method' => 'cash',
            'customer_id' => 1
        ];
        
        $this->assertTrue($this->validateSale($validSale));
        
        // Test invalid sale data
        $invalidSales = [
            ['items' => [], 'total' => '0.00'], // No items
            ['items' => [['id' => 1, 'qty' => 0, 'price' => '10.00']], 'total' => '0.00'], // Zero quantity
            ['items' => [['id' => 1, 'qty' => 1, 'price' => '-5.00']], 'total' => '-5.00'], // Negative price
            ['items' => [['id' => 1, 'qty' => 1, 'price' => '10.00']], 'total' => ''], // Missing total
        ];
        
        foreach ($invalidSales as $sale) {
            $this->assertFalse($this->validateSale($sale));
        }
    }

    public function testUserValidation()
    {
        // Test valid user data
        $validUser = [
            'username' => 'testuser',
            'password' => 'SecurePassword123!',
            'email' => 'user@example.com',
            'role' => 'cashier',
            'displayname' => 'Test User'
        ];
        
        $this->assertTrue($this->validateUser($validUser));
        
        // Test invalid user data
        $invalidUsers = [
            ['username' => '', 'password' => 'password', 'email' => 'test@example.com'], // Missing username
            ['username' => 'user', 'password' => '123', 'email' => 'test@example.com'], // Weak password
            ['username' => 'user', 'password' => 'password', 'email' => 'invalid'], // Invalid email
            ['username' => 'user', 'password' => 'password', 'email' => 'test@example.com', 'role' => 'invalid'], // Invalid role
        ];
        
        foreach ($invalidUsers as $user) {
            $this->assertFalse($this->validateUser($user));
        }
    }

    public function testPasswordStrength()
    {
        $strongPasswords = [
            'SecurePass123!',
            'MyP@ssw0rd2024',
            'ComplexP@ss#123'
        ];
        
        foreach ($strongPasswords as $password) {
            $this->assertTrue($this->isStrongPassword($password));
        }
        
        $weakPasswords = [
            'password',
            '123456',
            'abc',
            'PASSWORD',
            'password123'
        ];
        
        foreach ($weakPasswords as $password) {
            $this->assertFalse($this->isStrongPassword($password));
        }
    }

    public function testNumericValidation()
    {
        $validNumbers = ['123', '123.45', '0', '0.01', '999999.99'];
        $invalidNumbers = ['abc', '12.34.56', '', 'NaN', 'Infinity'];
        
        foreach ($validNumbers as $number) {
            $this->assertTrue(is_numeric($number));
        }
        
        foreach ($invalidNumbers as $number) {
            $this->assertFalse(is_numeric($number));
        }
    }

    public function testCodeFormatValidation()
    {
        // Test item code format (e.g., ITEM001, PROD123)
        $validCodes = ['ITEM001', 'PROD123', 'ABC999', 'TEST001'];
        $invalidCodes = ['', 'item001', '123ITEM', 'ITEM-001', 'IT EM001'];
        
        foreach ($validCodes as $code) {
            $this->assertTrue($this->isValidItemCode($code));
        }
        
        foreach ($invalidCodes as $code) {
            $this->assertFalse($this->isValidItemCode($code));
        }
    }

    public function testDateRangeValidation()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Test valid date ranges
        $this->assertTrue($this->isValidDateRange($yesterday, $today));
        $this->assertTrue($this->isValidDateRange($today, $tomorrow));
        
        // Test invalid date ranges (end before start)
        $this->assertFalse($this->isValidDateRange($today, $yesterday));
        $this->assertFalse($this->isValidDateRange($tomorrow, $today));
    }

    public function testPermissionStringValidation()
    {
        $validPermissions = ['users/add', 'items/edit', 'sales/void', 'reports/view'];
        $invalidPermissions = ['users', 'users/', '/add', 'users/add/extra', ''];
        
        foreach ($validPermissions as $permission) {
            $this->assertTrue($this->isValidPermission($permission));
        }
        
        foreach ($invalidPermissions as $permission) {
            $this->assertFalse($this->isValidPermission($permission));
        }
    }

    public function testJSONValidation()
    {
        $validJSON = ['{"name":"test"}', '[]', '{"items":[{"id":1,"qty":2}]}'];
        $invalidJSON = ['{name:"test"}', '{"name":}', '{]', 'not json'];
        
        foreach ($validJSON as $json) {
            $this->assertTrue($this->isValidJSON($json));
        }
        
        foreach ($invalidJSON as $json) {
            $this->assertFalse($this->isValidJSON($json));
        }
    }

    // Helper validation methods

    private function validateItem($item)
    {
        return !empty($item['code']) && 
               !empty($item['name']) && 
               isset($item['price']) && 
               is_numeric($item['price']) && 
               (float)$item['price'] >= 0;
    }

    private function validateCustomer($customer)
    {
        return !empty($customer['name']) &&
               (!isset($customer['email']) || filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) &&
               (!isset($customer['phone']) || strlen($customer['phone']) >= 10);
    }

    private function validateSale($sale)
    {
        if (empty($sale['items']) || !is_array($sale['items'])) {
            return false;
        }
        
        foreach ($sale['items'] as $item) {
            if (!isset($item['qty']) || $item['qty'] <= 0 ||
                !isset($item['price']) || !is_numeric($item['price']) || (float)$item['price'] < 0) {
                return false;
            }
        }
        
        return isset($sale['total']) && is_numeric($sale['total']) && (float)$sale['total'] >= 0;
    }

    private function validateUser($user)
    {
        $validRoles = ['admin', 'manager', 'cashier', 'customer'];
        
        return !empty($user['username']) &&
               !empty($user['password']) && $this->isStrongPassword($user['password']) &&
               (!isset($user['email']) || filter_var($user['email'], FILTER_VALIDATE_EMAIL)) &&
               (!isset($user['role']) || in_array($user['role'], $validRoles));
    }

    private function isStrongPassword($password)
    {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }

    private function isValidItemCode($code)
    {
        return preg_match('/^[A-Z]{2,10}[0-9]{3,6}$/', $code);
    }

    private function isValidDateRange($startDate, $endDate)
    {
        return strtotime($startDate) <= strtotime($endDate);
    }

    private function isValidPermission($permission)
    {
        return preg_match('/^[a-z]+\/[a-z]+$/', $permission);
    }

    private function isValidJSON($json)
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
}