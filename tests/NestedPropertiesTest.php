<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\Entity;

class NestedPropertiesTest extends TestCase
{
    private $testData;

    public function setUp(): void
    {
        // Create test data that simulates what OData might return with nested properties
        $this->testData = [
            'Id' => '123',
            'Name' => 'Test Folder',
            'Info' => [
                'IsAHomeFolder' => true,
                'Description' => 'Test description',
                'Settings' => [
                    'AllowPublicSharing' => false,
                    'MaxFileSize' => 1024
                ]
            ],
            'Children' => [
                ['Id' => '456', 'Name' => 'Child 1', 'Type' => 'Folder'],
                ['Id' => '789', 'Name' => 'Child 2', 'Type' => 'File']
            ],
            'CreationDate' => '2023-01-01T10:00:00Z'
        ];
    }

    public function testBasicPropertyAccess()
    {
        $entity = new Entity($this->testData);
        
        $this->assertEquals('123', $entity->Id);
        $this->assertEquals('Test Folder', $entity->Name);
        $this->assertEquals('2023-01-01T10:00:00Z', $entity->CreationDate);
    }

    public function testNestedObjectPropertyAccess()
    {
        $entity = new Entity($this->testData);
        
        // Accessing nested object should return an Entity instance
        $info = $entity->Info;
        $this->assertInstanceOf(Entity::class, $info);
        
        // Test nested property access
        $this->assertTrue($entity->Info->IsAHomeFolder);
        $this->assertEquals('Test description', $entity->Info->Description);
        
        // Test deeply nested properties
        $this->assertInstanceOf(Entity::class, $entity->Info->Settings);
        $this->assertFalse($entity->Info->Settings->AllowPublicSharing);
        $this->assertEquals(1024, $entity->Info->Settings->MaxFileSize);
    }

    public function testDotNotationPropertyAccess()
    {
        $entity = new Entity($this->testData);
        
        // Test direct dot notation access
        $this->assertTrue($entity->getProperty('Info.IsAHomeFolder'));
        $this->assertEquals('Test description', $entity->getProperty('Info.Description'));
        
        // Test deeply nested dot notation access
        $this->assertFalse($entity->getProperty('Info.Settings.AllowPublicSharing'));
        $this->assertEquals(1024, $entity->getProperty('Info.Settings.MaxFileSize'));
        
        // Test non-existent properties
        $this->assertNull($entity->getProperty('Info.NonExistent'));
        $this->assertNull($entity->getProperty('NonExistent.Property'));
    }

    public function testArrayPropertyAccess()
    {
        $entity = new Entity($this->testData);
        
        // Children should remain as an array (not converted to Entity)
        $children = $entity->Children;
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        
        // Test accessing array elements
        $this->assertEquals('456', $children[0]['Id']);
        $this->assertEquals('Child 1', $children[0]['Name']);
        $this->assertEquals('Folder', $children[0]['Type']);
    }

    public function testArrayAccessCompatibility()
    {
        $entity = new Entity($this->testData);
        
        // Array access should still work as before
        $this->assertEquals('123', $entity['Id']);
        $this->assertEquals('Test Folder', $entity['Name']);
        
        // Nested array access should work
        $this->assertTrue($entity['Info']['IsAHomeFolder']);
        $this->assertEquals('Test description', $entity['Info']['Description']);
        $this->assertFalse($entity['Info']['Settings']['AllowPublicSharing']);
        
        // Array of arrays should work
        $this->assertEquals('456', $entity['Children'][0]['Id']);
        $this->assertEquals('Child 1', $entity['Children'][0]['Name']);
    }

    public function testIssetFunctionality()
    {
        $entity = new Entity($this->testData);
        
        // Basic isset checks
        $this->assertTrue(isset($entity->Id));
        $this->assertTrue(isset($entity->Name));
        $this->assertTrue(isset($entity->Info));
        
        // Nested isset checks
        $this->assertTrue(isset($entity->Info->IsAHomeFolder));
        $this->assertTrue(isset($entity->Info->Description));
        $this->assertTrue(isset($entity->Info->Settings));
        $this->assertTrue(isset($entity->Info->Settings->AllowPublicSharing));
        
        // Non-existent properties
        $this->assertFalse(isset($entity->NonExistent));
        $this->assertFalse(isset($entity->Info->NonExistent));
    }

    public function testBackwardCompatibility()
    {
        // Test with simple, flat data structure
        $simpleData = [
            'name' => 'Simple Entity',
            'value' => 42,
            'flag' => true
        ];
        
        $entity = new Entity($simpleData);
        
        $this->assertEquals('Simple Entity', $entity->name);
        $this->assertEquals(42, $entity->value);
        $this->assertTrue($entity->flag);
        
        // Array access should work
        $this->assertEquals('Simple Entity', $entity['name']);
        $this->assertEquals(42, $entity['value']);
        $this->assertTrue($entity['flag']);
    }

    public function testEmptyAndNullValues()
    {
        $data = [
            'name' => 'Test',
            'emptyArray' => [],
            'nullValue' => null,
            'emptyObject' => [],
            'info' => [
                'value' => 'test',
                'nullSub' => null
            ]
        ];
        
        $entity = new Entity($data);
        
        $this->assertEquals('Test', $entity->name);
        $this->assertIsArray($entity->emptyArray);
        $this->assertNull($entity->nullValue);
        $this->assertNull($entity->getProperty('info.nullSub'));
        $this->assertEquals('test', $entity->getProperty('info.value'));
    }
}