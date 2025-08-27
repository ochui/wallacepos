<?php

namespace Tests\Unit\Controllers\Api;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\PosController;
use Mockery;

/**
 * Unit tests for PosController
 * Tests core POS functionality without stubbing the methods under test
 */
class PosControllerTest extends TestCase
{
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

        // Mock PosSetup constructor and method using overload
        $mockPosSetup = Mockery::mock('overload:App\Controllers\Pos\PosSetup');
        $mockPosSetup->shouldReceive('__construct')->once();
        $mockPosSetup->shouldReceive('getDeviceRecord')
            ->once()
            ->andReturnUsing(function($result) use ($mockDeviceData) {
                $result['data'] = $mockDeviceData;
                return $result;
            });

        // Create a real PosController instance
        $controller = new PosController();

        // Mock getRequestData method
        $partialController = Mockery::mock($controller)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
            
        $partialController->shouldReceive('getRequestData')
            ->once()
            ->andReturn(['deviceid' => 1]);

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $partialController->getConfig();
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
            ['id' => 1, 'name' => 'Item 1', 'price' => '10.00'],
            ['id' => 2, 'name' => 'Item 2', 'price' => '15.50']
        ];

        // Mock PosData using overload
        $mockPosData = Mockery::mock('overload:App\Controllers\Pos\PosData');
        $mockPosData->shouldReceive('__construct')->once();
        $mockPosData->shouldReceive('getItems')
            ->once()
            ->andReturnUsing(function($result) use ($mockItemsData) {
                $result['data'] = $mockItemsData;
                return $result;
            });

        // Create a real PosController instance
        $controller = new PosController();

        // Capture output since returnResult() uses echo and die()
        ob_start();
        try {
            $controller->getItems();
        } catch (\Exception $e) {
            // die() or exit() was called, which is expected
        }
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertEquals('OK', $response['errorCode']);
        $this->assertEquals($mockItemsData, $response['data']);
    }

    public function testControllerInstantiation()
    {
        // Simple test to ensure controller can be instantiated
        $controller = new PosController();
        $this->assertInstanceOf(PosController::class, $controller);
    }
}