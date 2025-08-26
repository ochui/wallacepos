<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use App\Database\SalesModel;
use Mockery;

/**
 * Unit tests for SalesModel
 * Tests sales-specific database operations and SQL generation
 */
class SalesModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateMethodGeneratesCorrectSQL()
    {
        // Mock the SalesModel to test SQL generation without database
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        
        $testData = [
            'ref' => 'SALE004',
            'data' => '{"items":[{"id":1,"qty":2}]}',
            'status' => 'completed',
            'userId' => 1,
            'deviceId' => 1,
            'locationId' => 1,
            'custId' => 1,
            'discount' => 0.00,
            'rounding' => 0.00,
            'cost' => 20.00,
            'total' => 25.50,
            'processdt' => '2024-01-01 12:00:00'
        ];

        $expectedSQL = "INSERT INTO sales (ref, type, channel, data, userid, deviceid, locationid, custid, discount, rounding, cost, total, status, processdt, dt) VALUES (:ref, 'sale', 'pos', :data, :userid, :deviceid, :locationid, :custid, :discount, :rounding, :cost, :total, :status, :processdt, '" . date("Y-m-d H:i:s") . "')";
        
        $expectedPlaceholders = [
            ':ref' => $testData['ref'],
            ':data' => $testData['data'],
            ':userid' => $testData['userId'],
            ':deviceid' => $testData['deviceId'],
            ':locationid' => $testData['locationId'],
            ':custid' => $testData['custId'],
            ':discount' => $testData['discount'],
            ':rounding' => $testData['rounding'],
            ':cost' => $testData['cost'],
            ':total' => $testData['total'],
            ':status' => $testData['status'],
            ':processdt' => $testData['processdt']
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function($sql) {
                return strpos($sql, "INSERT INTO sales") === 0 && 
                       strpos($sql, "VALUES (:ref, 'sale', 'pos'") !== false;
            }), $expectedPlaceholders)
            ->andReturn(123);

        $result = $mockModel->create(
            $testData['ref'],
            $testData['data'],
            $testData['status'],
            $testData['userId'],
            $testData['deviceId'],
            $testData['locationId'],
            $testData['custId'],
            $testData['discount'],
            $testData['rounding'],
            $testData['cost'],
            $testData['total'],
            $testData['processdt']
        );

        $this->assertEquals(123, $result);
    }

    public function testColumnsPropertyExists()
    {
        $reflection = new \ReflectionClass(SalesModel::class);
        $property = $reflection->getProperty('_columns');
        $property->setAccessible(true);
        
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        $columns = $property->getValue($mockModel);
        
        $expectedColumns = ['id', 'ref', 'type', 'channel', 'data', 'userid', 'deviceid', 'locationid', 'custid', 'discount', 'total', 'status', 'processdt', 'dt'];
        $this->assertEquals($expectedColumns, $columns);
    }

    public function testCreateWithMinimalData()
    {
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(456);
        
        $result = $mockModel->create(
            'MINIMAL001',
            '{}',
            'pending',
            1,
            1,
            1,
            0,
            0.00,
            0.00,
            0.00,
            0.00,
            date('Y-m-d H:i:s')
        );
        
        $this->assertEquals(456, $result);
    }

    public function testCreateWithComplexData()
    {
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        
        $complexData = json_encode([
            'items' => [
                ['id' => 1, 'qty' => 2, 'price' => 10.99],
                ['id' => 2, 'qty' => 1, 'price' => 15.50]
            ],
            'payment' => [
                'method' => 'cash',
                'amount' => 37.48
            ]
        ]);
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(789);
        
        $result = $mockModel->create(
            'COMPLEX001',
            $complexData,
            'completed',
            2,
            1,
            1,
            5,
            2.50,
            0.02,
            25.00,
            37.48,
            date('Y-m-d H:i:s')
        );
        
        $this->assertEquals(789, $result);
    }

    public function testCreateFailure()
    {
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(false);
        
        $result = $mockModel->create(
            '',
            '',
            '',
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            ''
        );
        
        $this->assertFalse($result);
    }

    public function testCreateUniqueConstraintViolation()
    {
        $mockModel = Mockery::mock(SalesModel::class)->makePartial();
        
        // Simulate unique constraint violation (typically returns -1)
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(-1);
        
        $result = $mockModel->create(
            'DUPLICATE001',
            '{}',
            'pending',
            1,
            1,
            1,
            0,
            0,
            0,
            0,
            0,
            date('Y-m-d H:i:s')
        );
        
        $this->assertEquals(-1, $result);
    }
}