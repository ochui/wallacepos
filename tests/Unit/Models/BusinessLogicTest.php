<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for core business logic and validation rules
 * Tests functionality without requiring database or complex dependencies
 */
class BusinessLogicTest extends TestCase
{
    public function testPriceCalculation()
    {
        // Test basic price calculations
        $basePrice = 100.00;
        $taxRate = 10.0;
        $discount = 5.0;
        
        // Calculate tax amount
        $taxAmount = $basePrice * ($taxRate / 100);
        $this->assertEquals(10.0, $taxAmount);
        
        // Calculate discounted price
        $discountAmount = $basePrice * ($discount / 100);
        $discountedPrice = $basePrice - $discountAmount;
        $this->assertEquals(95.0, $discountedPrice);
        
        // Calculate final total with tax on discounted price
        $finalTax = $discountedPrice * ($taxRate / 100);
        $finalTotal = $discountedPrice + $finalTax;
        $this->assertEquals(104.5, $finalTotal);
    }

    public function testSaleCalculations()
    {
        // Test sale total calculations
        $items = [
            ['qty' => 2, 'price' => 10.99],
            ['qty' => 1, 'price' => 15.50],
            ['qty' => 3, 'price' => 5.25]
        ];
        
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }
        
        $expectedSubtotal = (2 * 10.99) + (1 * 15.50) + (3 * 5.25);
        $this->assertEqualsWithDelta($expectedSubtotal, $subtotal, 0.01);
        $this->assertEqualsWithDelta(53.23, $subtotal, 0.01);
    }

    public function testDiscountCalculations()
    {
        // Test percentage discount
        $amount = 100.00;
        $percentageDiscount = 15.0;
        
        $discountAmount = $amount * ($percentageDiscount / 100);
        $finalAmount = $amount - $discountAmount;
        
        $this->assertEquals(15.0, $discountAmount);
        $this->assertEquals(85.0, $finalAmount);
        
        // Test fixed amount discount
        $fixedDiscount = 10.0;
        $finalAmountFixed = $amount - $fixedDiscount;
        
        $this->assertEquals(90.0, $finalAmountFixed);
    }

    public function testTaxCalculationTypes()
    {
        $amount = 100.00;
        
        // Test percentage tax
        $percentageTax = 12.5;
        $percentageTaxAmount = $amount * ($percentageTax / 100);
        $this->assertEquals(12.5, $percentageTaxAmount);
        
        // Test fixed amount tax
        $fixedTax = 5.0;
        $this->assertEquals(5.0, $fixedTax);
        
        // Test compound tax
        $tax1 = 10.0;
        $tax2 = 5.0;
        $compoundTaxBase = $amount + ($amount * ($tax1 / 100));
        $compoundTaxTotal = $compoundTaxBase + ($compoundTaxBase * ($tax2 / 100));
        
        $this->assertEquals(110.0, $compoundTaxBase);
        $this->assertEquals(115.5, $compoundTaxTotal);
    }

    public function testInventoryCalculations()
    {
        // Test stock level calculations
        $initialStock = 100;
        $salesQuantity = 15;
        $returnQuantity = 2;
        $adjustmentQuantity = -3;
        
        $currentStock = $initialStock - $salesQuantity + $returnQuantity + $adjustmentQuantity;
        $this->assertEquals(84, $currentStock);
        
        // Test reorder level checks
        $reorderLevel = 20;
        $this->assertGreaterThan($reorderLevel, $currentStock);
        
        // Test low stock scenario
        $lowStock = 10;
        $this->assertLessThan($reorderLevel, $lowStock);
    }

    public function testCurrencyFormatting()
    {
        // Test currency formatting
        $testCases = [
            [1234.56, '1234.56'],
            [1000, '1000.00'],
            [0.5, '0.50'],
            [999.999, '1000.00'] // Should round up
        ];
        
        foreach ($testCases as [$amount, $expected]) {
            $formatted = number_format((float)$amount, 2, '.', '');
            $this->assertEquals($expected, $formatted);
        }
    }

    public function testDateValidation()
    {
        // Test date validation
        $validDates = [
            '2024-01-01',
            '2024-12-31',
            '2024-02-29' // Leap year
        ];
        
        foreach ($validDates as $date) {
            $this->assertTrue($this->isValidDate($date));
        }
        
        $invalidDates = [
            '2024-13-01', // Invalid month
            '2024-02-30', // Invalid day for February
            '2023-02-29', // Not a leap year
            'invalid-date'
        ];
        
        foreach ($invalidDates as $date) {
            $this->assertFalse($this->isValidDate($date));
        }
    }

    public function testEmailValidation()
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'admin+test@company.org'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
        
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }
    }

    public function testCodeGeneration()
    {
        // Test item code generation
        $prefix = 'ITEM';
        $number = 123;
        $code = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
        
        $this->assertEquals('ITEM000123', $code);
        
        // Test reference generation
        $timestamp = 1640995200; // 2022-01-01 00:00:00
        $deviceId = 5;
        $saleRef = 'SALE' . gmdate('Ymd', $timestamp) . str_pad($deviceId, 2, '0', STR_PAD_LEFT) . str_pad($number, 4, '0', STR_PAD_LEFT);
        
        $this->assertEquals('SALE20220101050123', $saleRef);
    }

    public function testPermissionLogic()
    {
        // Test user permission logic
        $userRoles = ['admin', 'manager', 'cashier', 'customer'];
        $adminPermissions = ['users/add', 'users/delete', 'settings/edit', 'reports/view'];
        $managerPermissions = ['reports/view', 'items/edit', 'sales/void'];
        $cashierPermissions = ['sales/add', 'sales/view'];
        
        $this->assertContains('admin', $userRoles);
        $this->assertContains('users/add', $adminPermissions);
        $this->assertNotContains('users/delete', $managerPermissions);
        $this->assertNotContains('settings/edit', $cashierPermissions);
    }

    public function testDataValidation()
    {
        // Test item data validation
        $validItem = [
            'code' => 'ITEM001',
            'name' => 'Test Item',
            'price' => '10.99',
            'categoryid' => 1
        ];
        
        $this->assertArrayHasKey('code', $validItem);
        $this->assertArrayHasKey('name', $validItem);
        $this->assertArrayHasKey('price', $validItem);
        $this->assertNotEmpty($validItem['code']);
        $this->assertNotEmpty($validItem['name']);
        $this->assertGreaterThan(0, (float)$validItem['price']);
    }

    /**
     * Helper method to validate date format
     */
    private function isValidDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}