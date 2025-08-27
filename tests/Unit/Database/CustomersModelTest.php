<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use App\Database\CustomersModel;
use Mockery;

/**
 * Unit tests for CustomersModel
 * Tests customer data management and SQL generation
 */
class CustomersModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateMethodStructure()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $testCustomerData = (object)[
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'discount' => 5.0
        ];

        $mockModel->shouldReceive('insert')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'INSERT INTO customers') === 0;
                }),
                Mockery::type('array')
            )
            ->andReturn(123);

        $result = $mockModel->create($testCustomerData);
        $this->assertEquals(123, $result);
    }

    public function testGetMethodWithId()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $expectedCustomer = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM customers') === 0 &&
                           strpos($sql, 'WHERE') !== false;
                }),
                Mockery::on(function($placeholders) {
                    return isset($placeholders[':id']) && $placeholders[':id'] === 1;
                })
            )
            ->andReturn($expectedCustomer);

        $result = $mockModel->get(1);
        $this->assertEquals($expectedCustomer, $result);
    }

    public function testGetAllCustomers()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $expectedCustomers = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with('SELECT * FROM customers', [])
            ->andReturn($expectedCustomers);

        $result = $mockModel->get();
        $this->assertEquals($expectedCustomers, $result);
    }

    public function testUpdateCustomer()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $updateData = (object)[
            'id' => 1,
            'name' => 'John Updated',
            'email' => 'john.updated@example.com'
        ];

        $mockModel->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'UPDATE customers SET') === 0 &&
                           strpos($sql, 'WHERE id = :id') !== false;
                }),
                Mockery::type('array')
            )
            ->andReturn(true);

        $result = $mockModel->update($updateData);
        $this->assertTrue($result);
    }

    public function testDeleteCustomer()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $mockModel->shouldReceive('delete')
            ->once()
            ->with(
                'DELETE FROM customers WHERE id = :id',
                [':id' => 1]
            )
            ->andReturn(true);

        $result = $mockModel->delete(1);
        $this->assertTrue($result);
    }

    public function testSearchCustomers()
    {
        $mockModel = Mockery::mock(CustomersModel::class)->makePartial();
        
        $searchResults = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
        ];

        $mockModel->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(function($sql) {
                    return strpos($sql, 'SELECT * FROM customers') === 0 &&
                           strpos($sql, 'WHERE') !== false &&
                           (strpos($sql, 'LIKE') !== false || strpos($sql, 'name') !== false);
                }),
                Mockery::type('array')
            )
            ->andReturn($searchResults);

        $result = $mockModel->search('John');
        $this->assertEquals($searchResults, $result);
    }
}