<?php

namespace SaintSystems\OData\Tests;

// If running standalone, load autoloader
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataResponse;
use SaintSystems\OData\ODataRequest;

// Check if PHPUnit is available, if not provide a fallback
if (class_exists('PHPUnit\Framework\TestCase')) {
    class ODataResponseTestBase extends \PHPUnit\Framework\TestCase {}
} else {
    // Fallback base class for when PHPUnit is not available
    class ODataResponseTestBase {
        protected function createMock($className) {
            return new \stdClass();
        }
        protected function assertIsArray($value) { 
            if (!is_array($value)) throw new \Exception("Expected array"); 
        }
        protected function assertCount($expected, $array) { 
            if (count($array) !== $expected) throw new \Exception("Count mismatch"); 
        }
        protected function assertInstanceOf($class, $object) { 
            if (!($object instanceof $class)) throw new \Exception("Instance mismatch"); 
        }
    }
}

class ODataResponseTest extends ODataResponseTestBase
{
    public function testGetResponseAsObjectWithArrayValue()
    {
        // Test case for traditional array response
        $responseBody = json_encode([
            'value' => [
                ['id' => 1, 'name' => 'Test 1'],
                ['id' => 2, 'name' => 'Test 2']
            ]
        ]);
        
        $request = $this->createMock(ODataRequest::class);
        $response = new ODataResponse($request, $responseBody, 200, []);
        
        $result = $response->getResponseAsObject(Entity::class);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Entity::class, $result[0]);
        $this->assertInstanceOf(Entity::class, $result[1]);
    }

    public function testGetResponseAsObjectWithStringValue()
    {
        // Test case for single string value response (the reported issue)
        $responseBody = json_encode([
            'value' => 'uniqueIDofCreatedOrder'
        ]);
        
        $request = $this->createMock(ODataRequest::class);
        $response = new ODataResponse($request, $responseBody, 200, []);
        
        $result = $response->getResponseAsObject(Entity::class);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Entity::class, $result[0]);
        
        // Verify the value is properly stored
        $entity = $result[0];
        $properties = $entity->getProperties();
        if (!isset($properties['value']) || $properties['value'] !== 'uniqueIDofCreatedOrder') {
            throw new \Exception("Value not properly stored in Entity");
        }
    }

    public function testGetResponseAsObjectWithNumericValue()
    {
        // Test case for single numeric value response
        $responseBody = json_encode([
            'value' => 12345
        ]);
        
        $request = $this->createMock(ODataRequest::class);
        $response = new ODataResponse($request, $responseBody, 200, []);
        
        $result = $response->getResponseAsObject(Entity::class);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Entity::class, $result[0]);
        
        // Verify the value is properly stored
        $entity = $result[0];
        $properties = $entity->getProperties();
        if (!isset($properties['value']) || $properties['value'] !== 12345) {
            throw new \Exception("Numeric value not properly stored in Entity");
        }
    }

    public function testGetResponseAsObjectWithoutValueKey()
    {
        // Test case for response without "value" key
        $responseBody = json_encode([
            'id' => 1,
            'name' => 'Test'
        ]);
        
        $request = $this->createMock(ODataRequest::class);
        $response = new ODataResponse($request, $responseBody, 200, []);
        
        $result = $response->getResponseAsObject(Entity::class);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Entity::class, $result[0]);
    }
}

// If running standalone (not via PHPUnit), execute the tests
if (!class_exists('PHPUnit\Framework\TestCase') && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running ODataResponse tests...\n\n";
    
    $test = new ODataResponseTest();
    $methods = ['testGetResponseAsObjectWithArrayValue', 'testGetResponseAsObjectWithStringValue', 
                'testGetResponseAsObjectWithNumericValue', 'testGetResponseAsObjectWithoutValueKey'];
    
    foreach ($methods as $method) {
        try {
            echo "Running $method... ";
            $test->$method();
            echo "✓ PASSED\n";
        } catch (Exception $e) {
            echo "✗ FAILED: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nTests completed.\n";
}