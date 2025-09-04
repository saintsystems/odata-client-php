<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataResponse;

class LargeNumbersTest extends TestCase
{
    public function testLargeNumberPreservation()
    {
        // Test that large numbers are preserved as strings instead of converted to scientific notation
        $largeNumber = '80000000000000000000000';
        $jsonResponse = '{"id": ' . $largeNumber . ', "name": "test", "smallId": 123}';
        
        $mockRequest = new \stdClass();
        $response = new ODataResponse($mockRequest, $jsonResponse, 200, []);
        
        $body = $response->getBody();
        
        // Large number should be preserved as string
        $this->assertEquals($largeNumber, $body['id']);
        $this->assertIsString($body['id']);
        
        // Small numbers should remain as integers
        $this->assertEquals(123, $body['smallId']);
        $this->assertIsInt($body['smallId']);
        
        // Other data should be unaffected
        $this->assertEquals('test', $body['name']);
        $this->assertIsString($body['name']);
    }

    public function testLargeNumbersInArrayResponse()
    {
        // Test OData response with array of entities containing large numbers
        $largeNumber1 = '80000000000000000000000';
        $largeNumber2 = '90000000000000000000000';
        
        $jsonResponse = '{
            "value": [
                {"id": ' . $largeNumber1 . ', "name": "entity1"},
                {"id": ' . $largeNumber2 . ', "name": "entity2"}
            ]
        }';
        
        $mockRequest = new \stdClass();
        $response = new ODataResponse($mockRequest, $jsonResponse, 200, []);
        
        $body = $response->getBody();
        
        // Verify large numbers are preserved in array responses
        $this->assertEquals($largeNumber1, $body['value'][0]['id']);
        $this->assertIsString($body['value'][0]['id']);
        
        $this->assertEquals($largeNumber2, $body['value'][1]['id']);
        $this->assertIsString($body['value'][1]['id']);
    }

    public function testMixedNumberTypesPreservation()
    {
        // Test that various number types are handled correctly
        $jsonResponse = '{
            "largeInt": 80000000000000000000000,
            "normalInt": 123,
            "float": 123.45,
            "negativeInt": -456,
            "largeLong": 9223372036854775808
        }';
        
        $mockRequest = new \stdClass();
        $response = new ODataResponse($mockRequest, $jsonResponse, 200, []);
        
        $body = $response->getBody();
        
        // Very large number should be preserved as string
        $this->assertEquals('80000000000000000000000', $body['largeInt']);
        $this->assertIsString($body['largeInt']);
        
        // Normal integers should remain as integers
        $this->assertEquals(123, $body['normalInt']);
        $this->assertIsInt($body['normalInt']);
        
        // Floats should remain as floats
        $this->assertEquals(123.45, $body['float']);
        $this->assertIsFloat($body['float']);
        
        // Negative integers should remain as integers
        $this->assertEquals(-456, $body['negativeInt']);
        $this->assertIsInt($body['negativeInt']);
        
        // Numbers larger than PHP_INT_MAX should be strings
        $this->assertEquals('9223372036854775808', $body['largeLong']);
        $this->assertIsString($body['largeLong']);
    }
}