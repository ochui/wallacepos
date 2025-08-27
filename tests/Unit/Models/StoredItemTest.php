<?php

namespace Tests\Unit\Models;

use App\Models\StoredItem;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StoredItem model
 */
class StoredItemTest extends TestCase
{
    public function testConstructorWithValidData()
    {
        $data = [
            'code' => 'TEST001',
            'name' => 'Test Item',
            'description' => 'A test item for unit testing',
            'price' => '10.99',
            'qty' => '5',
            'taxid' => 2,
            'type' => 'general'
        ];

        $item = new StoredItem($data);

        $this->assertEquals('TEST001', $item->code);
        $this->assertEquals('Test Item', $item->name);
        $this->assertEquals('A test item for unit testing', $item->description);
        $this->assertEquals('10.99', $item->price);
        $this->assertEquals('5', $item->qty);
        $this->assertEquals(2, $item->taxid);
        $this->assertEquals('general', $item->type);
    }

    public function testConstructorWithPartialData()
    {
        $data = [
            'code' => 'PARTIAL001',
            'name' => 'Partial Item'
        ];

        $item = new StoredItem($data);

        $this->assertEquals('PARTIAL001', $item->code);
        $this->assertEquals('Partial Item', $item->name);
        // Check default values remain
        $this->assertEquals('', $item->description);
        $this->assertEquals('', $item->price);
        $this->assertEquals(1, $item->taxid);
        $this->assertEquals('general', $item->type);
        $this->assertEquals([], $item->modifiers);
    }

    public function testConstructorIgnoresInvalidProperties()
    {
        $data = [
            'code' => 'INVALID001',
            'name' => 'Invalid Property Test',
            'invalid_property' => 'This should be ignored',
            'another_invalid' => 123
        ];

        $item = new StoredItem($data);

        $this->assertEquals('INVALID001', $item->code);
        $this->assertEquals('Invalid Property Test', $item->name);
        $this->assertFalse(property_exists($item, 'invalid_property'));
        $this->assertFalse(property_exists($item, 'another_invalid'));
    }

    public function testDefaultValues()
    {
        $item = new StoredItem([]);

        $this->assertEquals('', $item->code);
        $this->assertEquals('', $item->qty);
        $this->assertEquals('', $item->name);
        $this->assertEquals('', $item->alt_name);
        $this->assertEquals('', $item->description);
        $this->assertEquals(1, $item->taxid);
        $this->assertEquals('', $item->price);
        $this->assertEquals('', $item->cost);
        $this->assertEquals(0, $item->supplierid);
        $this->assertEquals(0, $item->categoryid);
        $this->assertEquals('general', $item->type);
        $this->assertEquals([], $item->modifiers);
    }

    public function testModifiersArray()
    {
        $modifiers = [
            ['name' => 'Size', 'options' => ['Small', 'Medium', 'Large']],
            ['name' => 'Color', 'options' => ['Red', 'Blue', 'Green']]
        ];

        $data = [
            'code' => 'MOD001',
            'name' => 'Modified Item',
            'modifiers' => $modifiers
        ];

        $item = new StoredItem($data);

        $this->assertEquals($modifiers, $item->modifiers);
        $this->assertIsArray($item->modifiers);
        $this->assertCount(2, $item->modifiers);
    }
}