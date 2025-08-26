<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use App\Database\CategoriesModel;
use Mockery;

/**
 * Unit tests for CategoriesModel
 * Note: These tests use mocking to avoid database dependencies
 */
class CategoriesModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateMethodGeneratesCorrectSQL()
    {
        // Mock the CategoriesModel to test SQL generation without database
        $mockModel = Mockery::mock(CategoriesModel::class)->makePartial();
        
        // Test the create method's SQL and placeholders
        $categoryName = 'Test Category';
        
        // Mock the insert method to capture SQL and placeholders
        $expectedSQL = "INSERT INTO stored_categories (`name`, `dt`) VALUES (:name, now());";
        $expectedPlaceholders = [":name" => $categoryName];
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->with($expectedSQL, $expectedPlaceholders)
            ->andReturn(123); // Mock successful insert with ID
        
        $result = $mockModel->create($categoryName);
        
        $this->assertEquals(123, $result);
    }

    public function testColumnsPropertyExists()
    {
        $reflection = new \ReflectionClass(CategoriesModel::class);
        $property = $reflection->getProperty('_columns');
        $property->setAccessible(true);
        
        // Create a mock to avoid database connection
        $mockModel = Mockery::mock(CategoriesModel::class)->makePartial();
        $columns = $property->getValue($mockModel);
        
        $expectedColumns = ['id', 'name', 'dt'];
        $this->assertEquals($expectedColumns, $columns);
    }

    public function testCreateWithEmptyName()
    {
        $mockModel = Mockery::mock(CategoriesModel::class)->makePartial();
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->andReturn(false); // Mock failure
        
        $result = $mockModel->create('');
        
        $this->assertFalse($result);
    }

    public function testCreateWithSpecialCharacters()
    {
        $mockModel = Mockery::mock(CategoriesModel::class)->makePartial();
        
        $categoryName = "Test & Category's \"Special\" <Characters>";
        
        $expectedSQL = "INSERT INTO stored_categories (`name`, `dt`) VALUES (:name, now());";
        $expectedPlaceholders = [":name" => $categoryName];
        
        $mockModel->shouldReceive('insert')
            ->once()
            ->with($expectedSQL, $expectedPlaceholders)
            ->andReturn(456);
        
        $result = $mockModel->create($categoryName);
        
        $this->assertEquals(456, $result);
    }
}