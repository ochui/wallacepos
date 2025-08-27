<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use App\Database\StoredItemsModel;
use Mockery;

/**
 * Unit tests for StoredItemsModel
 * Tests item management database operations and SQL generation
 */
class StoredItemsModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateMethodGeneratesCorrectSQL()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $testItemData = (object)[
            'code' => 'ITEM004',
            'name' => 'Test Item 4',
            'price' => '29.99',
            'supplierid' => 1,
            'categoryid' => 2,
            'description' => 'A test item for unit testing',
            'cost' => '20.00'
        ];

        $expectedSQL = "INSERT INTO stored_items (`data`, `supplierid`, `categoryid`, `code`, `name`, `price`) VALUES (:data, :supplierid, :categoryid, :code, :name, :price);";
        $expectedPlaceholders = [
            ":data" => json_encode($testItemData),
            ":supplierid" => $testItemData->supplierid,
            ":categoryid" => $testItemData->categoryid,
            ":code" => $testItemData->code,
            ":name" => $testItemData->name,
            ":price" => $testItemData->price
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->with($expectedSQL, $expectedPlaceholders)
            ->andReturn(123);

        $result = $mockModel->create($testItemData);

        $this->assertEquals(123, $result);
    }

    public function testColumnsPropertyExists()
    {
        $reflection = new \ReflectionClass(StoredItemsModel::class);
        $property = $reflection->getProperty('_columns');
        $property->setAccessible(true);
        
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        $columns = $property->getValue($mockModel);
        
        $expectedColumns = ['id', 'data', 'supplierid', 'categoryid', 'code', 'name', 'price'];
        $this->assertEquals($expectedColumns, $columns);
    }

    public function testCreateWithMinimalItemData()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $minimalData = (object)[
            'code' => 'MIN001',
            'name' => 'Minimal Item',
            'price' => '1.00',
            'supplierid' => 0,
            'categoryid' => 0
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(456);

        $result = $mockModel->create($minimalData);

        $this->assertEquals(456, $result);
    }

    public function testCreateWithComplexItemData()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $complexData = (object)[
            'code' => 'COMPLEX001',
            'name' => 'Complex Item with Modifiers',
            'price' => '99.99',
            'cost' => '50.00',
            'supplierid' => 3,
            'categoryid' => 5,
            'description' => 'A complex item with multiple attributes',
            'barcode' => '1234567890123',
            'modifiers' => [
                ['name' => 'Size', 'options' => ['Small', 'Medium', 'Large']],
                ['name' => 'Color', 'options' => ['Red', 'Blue', 'Green']]
            ],
            'tax_included' => true,
            'track_stock' => true
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function($placeholders) use ($complexData) {
                    return $placeholders[':code'] === $complexData->code &&
                           $placeholders[':name'] === $complexData->name &&
                           $placeholders[':price'] === $complexData->price &&
                           $placeholders[':supplierid'] === $complexData->supplierid &&
                           $placeholders[':categoryid'] === $complexData->categoryid &&
                           json_decode($placeholders[':data']) == $complexData;
                })
            )
            ->andReturn(789);

        $result = $mockModel->create($complexData);

        $this->assertEquals(789, $result);
    }

    public function testGetMethodWithNoParameters()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $expectedItems = [
            ['id' => 1, 'code' => 'ITEM001', 'name' => 'Item 1'],
            ['id' => 2, 'code' => 'ITEM002', 'name' => 'Item 2']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM stored_items', [])
            ->andReturn($expectedItems);

        $result = $mockModel->get();

        $this->assertEquals($expectedItems, $result);
    }

    public function testGetMethodWithId()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $expectedItem = [
            ['id' => 1, 'code' => 'ITEM001', 'name' => 'Item 1', 'price' => '10.99']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM stored_items') === 0 &&
                           strpos($sql, 'WHERE') !== false;
                }),
                Mockery::on(function($placeholders) {
                    return isset($placeholders[':id']) && $placeholders[':id'] === 1;
                })
            )
            ->andReturn($expectedItem);

        $result = $mockModel->get(1);

        $this->assertEquals($expectedItem, $result);
    }

    public function testGetMethodWithCode()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $expectedItem = [
            ['id' => 1, 'code' => 'ITEM001', 'name' => 'Item 1', 'price' => '10.99']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM stored_items') === 0 &&
                           strpos($sql, 'WHERE') !== false;
                }),
                Mockery::on(function($placeholders) {
                    return isset($placeholders[':code']) && $placeholders[':code'] === 'ITEM001';
                })
            )
            ->andReturn($expectedItem);

        $result = $mockModel->get(null, 'ITEM001');

        $this->assertEquals($expectedItem, $result);
    }

    public function testGetMethodWithBothIdAndCode()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $expectedItem = [
            ['id' => 1, 'code' => 'ITEM001', 'name' => 'Item 1', 'price' => '10.99']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM stored_items') === 0 &&
                           strpos($sql, 'WHERE') !== false &&
                           substr_count($sql, 'AND') >= 1;
                }),
                Mockery::on(function($placeholders) {
                    return isset($placeholders[':id']) && $placeholders[':id'] === 1 &&
                           isset($placeholders[':code']) && $placeholders[':code'] === 'ITEM001';
                })
            )
            ->andReturn($expectedItem);

        $result = $mockModel->get(1, 'ITEM001');

        $this->assertEquals($expectedItem, $result);
    }

    public function testCreateFailure()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $failData = (object)[
            'code' => '',
            'name' => '',
            'price' => '',
            'supplierid' => 0,
            'categoryid' => 0
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $result = $mockModel->create($failData);

        $this->assertFalse($result);
    }

    public function testCreateUniqueConstraintViolation()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $duplicateData = (object)[
            'code' => 'EXISTING001',
            'name' => 'Existing Item',
            'price' => '10.00',
            'supplierid' => 1,
            'categoryid' => 1
        ];

        // Simulate unique constraint violation
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(-1);

        $result = $mockModel->create($duplicateData);

        $this->assertEquals(-1, $result);
    }

    public function testGetMethodReturnsEmptyArray()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $mockModel->shouldReceive('select')
            ->once()
            ->andReturn([]);

        $result = $mockModel->get();

        $this->assertEquals([], $result);
    }

    public function testGetMethodReturnsFalseOnError()
    {
        $mockModel = Mockery::mock(StoredItemsModel::class)->makePartial();
        
        $mockModel->shouldReceive('select')
            ->once()
            ->andReturn(false);

        $result = $mockModel->get();

        $this->assertFalse($result);
    }
}