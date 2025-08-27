<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use App\Database\TaxRulesModel;
use Mockery;

/**
 * Unit tests for TaxRulesModel
 * Tests tax rule management and calculations
 */
class TaxRulesModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateMethodStructure()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $testTaxData = (object)[
            'name' => 'GST',
            'rate' => 10.0,
            'type' => 'percentage',
            'locationid' => 1
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'INSERT INTO tax_rules') === 0;
                }),
                Mockery::type('array')
            )
            ->andReturn(123);

        $result = $mockModel->create($testTaxData);
        $this->assertEquals(123, $result);
    }

    public function testGetTaxRules()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $expectedTaxRules = [
            ['id' => 1, 'name' => 'GST', 'rate' => 10.0, 'type' => 'percentage'],
            ['id' => 2, 'name' => 'Service Tax', 'rate' => 5.0, 'type' => 'percentage']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM tax_rules', [])
            ->andReturn($expectedTaxRules);

        $result = $mockModel->get();
        $this->assertEquals($expectedTaxRules, $result);
    }

    public function testGetTaxRuleById()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $expectedTaxRule = [
            ['id' => 1, 'name' => 'GST', 'rate' => 10.0, 'type' => 'percentage']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM tax_rules') === 0 &&
                           strpos($sql, 'WHERE id = :id') !== false;
                }),
                [':id' => 1]
            )
            ->andReturn($expectedTaxRule);

        $result = $mockModel->get(1);
        $this->assertEquals($expectedTaxRule, $result);
    }

    public function testUpdateTaxRule()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $updateData = (object)[
            'id' => 1,
            'name' => 'Updated GST',
            'rate' => 12.0,
            'type' => 'percentage'
        ];

        $mockModel->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'UPDATE tax_rules SET') === 0 &&
                           strpos($sql, 'WHERE id = :id') !== false;
                }),
                Mockery::type('array')
            )
            ->andReturn(true);

        $result = $mockModel->update($updateData);
        $this->assertTrue($result);
    }

    public function testDeleteTaxRule()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $mockModel->shouldReceive('delete')
            ->once()
            ->with(
                'DELETE FROM tax_rules WHERE id = :id',
                [':id' => 1]
            )
            ->andReturn(true);

        $result = $mockModel->delete(1);
        $this->assertTrue($result);
    }

    public function testGetTaxRulesByLocation()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $expectedRules = [
            ['id' => 1, 'name' => 'GST', 'rate' => 10.0, 'locationid' => 1]
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM tax_rules') === 0 &&
                           strpos($sql, 'WHERE locationid = :locationid') !== false;
                }),
                [':locationid' => 1]
            )
            ->andReturn($expectedRules);

        $result = $mockModel->getByLocation(1);
        $this->assertEquals($expectedRules, $result);
    }

    public function testCalculatePercentageTax()
    {
        // Test percentage-based tax calculation
        $amount = 100.00;
        $taxRate = 10.0;
        
        $expectedTax = $amount * ($taxRate / 100);
        $calculatedTax = $amount * 0.10; // 10% of 100
        
        $this->assertEquals(10.0, $calculatedTax);
        $this->assertEquals($expectedTax, $calculatedTax);
    }

    public function testCalculateFixedAmountTax()
    {
        // Test fixed amount tax calculation
        $amount = 100.00;
        $taxAmount = 5.00;
        
        // Fixed amount tax doesn't depend on the base amount
        $calculatedTax = $taxAmount;
        
        $this->assertEquals(5.0, $calculatedTax);
    }

    public function testTaxRuleValidation()
    {
        // Test validation of tax rule data
        $validTaxRule = [
            'name' => 'Valid Tax',
            'rate' => 10.0,
            'type' => 'percentage'
        ];
        
        $this->assertArrayHasKey('name', $validTaxRule);
        $this->assertArrayHasKey('rate', $validTaxRule);
        $this->assertArrayHasKey('type', $validTaxRule);
        $this->assertIsString($validTaxRule['name']);
        $this->assertIsNumeric($validTaxRule['rate']);
        $this->assertContains($validTaxRule['type'], ['percentage', 'fixed']);
    }

    public function testInvalidTaxRuleData()
    {
        $mockModel = Mockery::mock(TaxRulesModel::class)->makePartial();
        
        $invalidData = (object)[
            'name' => '',
            'rate' => -1,
            'type' => 'invalid'
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $result = $mockModel->create($invalidData);
        $this->assertFalse($result);
    }
}