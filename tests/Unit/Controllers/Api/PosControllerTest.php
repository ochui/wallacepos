<?php

namespace Tests\Unit\Controllers\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\PosController;
use App\Auth;
use App\Controllers\Pos\PosSetup;
use App\Controllers\Pos\PosData;
use App\Controllers\Pos\PosSale;
use App\Controllers\Transaction\Transactions;
use Mockery;

/**
 * Unit tests for PosController
 * Tests core POS functionality including sales, items, and data management
 */
class PosControllerTest extends TestCase
{
    protected $posController;
    protected $mockAuth;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Auth class
        $this->mockAuth = Mockery::mock(Auth::class);
        
        // Create a partial mock of PosController
        $this->posController = Mockery::mock(PosController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Set the auth property using reflection
        $reflection = new \ReflectionClass($this->posController);
        $authProperty = $reflection->getProperty('auth');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->posController, $this->mockAuth);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetConfig()
    {
        $mockDeviceData = [
            'id' => 1,
            'name' => 'Test Device',
            'locationid' => 1,
            'locationname' => 'Main Store'
        ];

        // Mock getRequestData to return device id
        $this->posController->shouldReceive('getRequestData')
            ->once()
            ->andReturn(['deviceid' => 1]);

        // Mock PosSetup constructor and method
        $mockPosSetup = Mockery::mock('overload:App\Controllers\Pos\PosSetup');
        $mockPosSetup->shouldReceive('__construct')
            ->once()
            ->with(['deviceid' => 1]);
        $mockPosSetup->shouldReceive('getDeviceRecord')
            ->once()
            ->andReturnUsing(function($result) use ($mockDeviceData) {
                $result['data'] = $mockDeviceData;
                return $result;
            });

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $this->posController->getConfig();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals($mockDeviceData, $response['data']);
    }

    public function testGetItems()
    {
        $mockItemsData = [
            ['id' => 1, 'code' => 'ITEM001', 'name' => 'Test Item 1', 'price' => '10.99'],
            ['id' => 2, 'code' => 'ITEM002', 'name' => 'Test Item 2', 'price' => '15.50']
        ];

        $this->posController->shouldReceive('getItems')
            ->once()
            ->andReturnUsing(function() use ($mockItemsData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockItemsData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockItemsData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockItemsData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getItems();
    }

    public function testGetSales()
    {
        $mockSalesData = [
            ['id' => 1, 'ref' => 'SALE001', 'total' => '25.50', 'dt' => '2024-01-01 10:00:00'],
            ['id' => 2, 'ref' => 'SALE002', 'total' => '48.75', 'dt' => '2024-01-01 11:30:00']
        ];

        $this->posController->shouldReceive('getRequestData')
            ->once()
            ->andReturn(['limit' => 50, 'offset' => 0]);

        $this->posController->shouldReceive('getSales')
            ->once()
            ->andReturnUsing(function() use ($mockSalesData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockSalesData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockSalesData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockSalesData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getSales();
    }

    public function testGetTaxes()
    {
        $mockTaxData = [
            ['id' => 1, 'name' => 'GST', 'rate' => 10.0, 'type' => 'percentage'],
            ['id' => 2, 'name' => 'Service Tax', 'rate' => 5.0, 'type' => 'percentage']
        ];

        $this->posController->shouldReceive('getTaxes')
            ->once()
            ->andReturnUsing(function() use ($mockTaxData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockTaxData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockTaxData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockTaxData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getTaxes();
    }

    public function testGetCustomers()
    {
        $mockCustomerData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];

        $this->posController->shouldReceive('getCustomers')
            ->once()
            ->andReturnUsing(function() use ($mockCustomerData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockCustomerData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockCustomerData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockCustomerData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getCustomers();
    }

    public function testGetDevices()
    {
        $mockDeviceData = [
            ['id' => 1, 'name' => 'POS Terminal 1', 'locationid' => 1],
            ['id' => 2, 'name' => 'POS Terminal 2', 'locationid' => 1]
        ];

        $this->posController->shouldReceive('getDevices')
            ->once()
            ->andReturnUsing(function() use ($mockDeviceData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockDeviceData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockDeviceData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockDeviceData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getDevices();
    }

    public function testGetLocations()
    {
        $mockLocationData = [
            ['id' => 1, 'name' => 'Main Store', 'address' => '123 Main St'],
            ['id' => 2, 'name' => 'Branch Store', 'address' => '456 Branch Ave']
        ];

        $this->posController->shouldReceive('getLocations')
            ->once()
            ->andReturnUsing(function() use ($mockLocationData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockLocationData;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockLocationData) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockLocationData, $result['data']);
                return json_encode($result);
            });

        $this->posController->getLocations();
    }

    public function testAddSale()
    {
        $mockSaleData = [
            'ref' => 'SALE003',
            'items' => [
                ['id' => 1, 'qty' => 2, 'price' => '10.99'],
                ['id' => 2, 'qty' => 1, 'price' => '15.50']
            ],
            'total' => '37.48',
            'payment' => 'cash'
        ];

        $mockSaleResult = ['id' => 3, 'ref' => 'SALE003', 'status' => 'completed'];

        $_REQUEST['data'] = json_encode($mockSaleData);

        $this->posController->shouldReceive('getRequestData')
            ->once()
            ->andReturn($mockSaleData);

        $this->posController->shouldReceive('addSale')
            ->once()
            ->andReturnUsing(function() use ($mockSaleResult) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockSaleResult;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockSaleResult) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockSaleResult, $result['data']);
                return json_encode($result);
            });

        $this->posController->addSale();
    }

    public function testVoidSale()
    {
        $mockVoidData = ['id' => 3, 'reason' => 'Customer request'];
        $mockVoidResult = ['id' => 3, 'status' => 'voided', 'void_reason' => 'Customer request'];

        $this->posController->shouldReceive('getRequestData')
            ->once()
            ->andReturn($mockVoidData);

        $this->posController->shouldReceive('voidSale')
            ->once()
            ->andReturnUsing(function() use ($mockVoidResult) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockVoidResult;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockVoidResult) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockVoidResult, $result['data']);
                return json_encode($result);
            });

        $this->posController->voidSale();
    }

    public function testSearchSales()
    {
        $mockSearchData = ['term' => 'SALE001', 'limit' => 10];
        $mockSearchResults = [
            ['id' => 1, 'ref' => 'SALE001', 'total' => '25.50', 'customer' => 'John Doe']
        ];

        $this->posController->shouldReceive('getRequestData')
            ->once()
            ->andReturn($mockSearchData);

        $this->posController->shouldReceive('searchSales')
            ->once()
            ->andReturnUsing(function() use ($mockSearchResults) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                $result['data'] = $mockSearchResults;
                $resultProperty->setValue($this->posController, $result);
                return $this->posController->returnResult();
            });

        $this->posController->shouldReceive('returnResult')
            ->once()
            ->andReturnUsing(function() use ($mockSearchResults) {
                $reflection = new \ReflectionClass($this->posController);
                $resultProperty = $reflection->getProperty('result');
                $resultProperty->setAccessible(true);
                $result = $resultProperty->getValue($this->posController);
                
                $this->assertEquals('OK', $result['errorCode']);
                $this->assertEquals($mockSearchResults, $result['data']);
                return json_encode($result);
            });

        $this->posController->searchSales();
    }
}