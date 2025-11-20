<?php

namespace SaintSystems\OData\Tests;

// If running standalone, load autoloader
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use SaintSystems\OData\ODataResponse;
use SaintSystems\OData\ODataRequest;

// Check if PHPUnit is available, if not provide a fallback
if (class_exists('PHPUnit\Framework\TestCase')) {
    class BatchResponseTestBase extends \PHPUnit\Framework\TestCase {}
} else {
    // Fallback base class for when PHPUnit is not available
    class BatchResponseTestBase {
        protected function createMock($className) {
            return new \stdClass();
        }
        protected function assertIsArray($value) { 
            if (!is_array($value)) throw new \Exception("Expected array"); 
        }
        protected function assertEquals($expected, $actual, $message = '') { 
            if ($expected !== $actual) throw new \Exception($message ?: "Assertion failed: $expected !== $actual"); 
        }
        protected function assertTrue($value, $message = '') { 
            if (!$value) throw new \Exception($message ?: "Assertion failed: Expected true"); 
        }
    }
}

class BatchResponseTest extends BatchResponseTestBase
{
    /**
     * Test that batch responses with multipart MIME content don't crash
     * This reproduces the issue reported in GitHub where SAP SuccessFactors
     * batch responses cause "Undefined array key 0" error
     */
    public function testBatchResponseWithMultipartContent()
    {
        // Simulate a batch response from SAP SuccessFactors
        $batchResponseBody = <<<EOT
--batch_f20d8021-677a-4f93-8e62-862b6f383d15
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 200 OK
Content-Type: application/json;charset=utf-8
DataServiceVersion: 2.0
Record-Count: 5
X-SF-Record-Count-Recursive: 5
successfactors-message: X-SF-Correlation-Id, successfactors-sourcetype are missing in request headers
Content-Length: 76038

{
"d" : {
"results" : [
  {"id": 1, "name": "Test 1"},
  {"id": 2, "name": "Test 2"}
]
}
}

--batch_f20d8021-677a-4f93-8e62-862b6f383d15--
EOT;

        $request = $this->createMock(ODataRequest::class);
        
        // This should not throw an exception
        $response = new ODataResponse($request, $batchResponseBody, 200, []);
        
        // The response should be created successfully
        $this->assertTrue(true, "ODataResponse should handle batch responses without crashing");
        
        // The body should be decoded to at least an empty array (no crash)
        $body = $response->getBody();
        $this->assertIsArray($body);
    }

    /**
     * Test that responses with no JSON content don't crash
     */
    public function testResponseWithNoJsonContent()
    {
        $nonJsonResponse = "This is plain text with no JSON at all";
        
        $request = $this->createMock(ODataRequest::class);
        
        // This should not throw an exception
        $response = new ODataResponse($request, $nonJsonResponse, 200, []);
        
        // The body should be decoded to an empty array
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertEquals([], $body);
    }

    /**
     * Test that responses with embedded JSON still work
     */
    public function testResponseWithEmbeddedJson()
    {
        $responseWithEmbeddedJson = 'Some text before {"value": "test"} some text after';
        
        $request = $this->createMock(ODataRequest::class);
        
        // This should not throw an exception
        $response = new ODataResponse($request, $responseWithEmbeddedJson, 200, []);
        
        // The body should extract the JSON portion
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertEquals('test', $body['value']);
    }

    /**
     * Test normal JSON response still works correctly
     */
    public function testNormalJsonResponse()
    {
        $jsonResponse = json_encode(['value' => 'test', 'count' => 5]);
        
        $request = $this->createMock(ODataRequest::class);
        $response = new ODataResponse($request, $jsonResponse, 200, []);
        
        $body = $response->getBody();
        $this->assertIsArray($body);
        $this->assertEquals('test', $body['value']);
        $this->assertEquals(5, $body['count']);
    }
}

// If running standalone (not via PHPUnit), execute the tests
if (!class_exists('PHPUnit\Framework\TestCase') && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running BatchResponse tests...\n\n";
    
    $test = new BatchResponseTest();
    $methods = [
        'testBatchResponseWithMultipartContent',
        'testResponseWithNoJsonContent',
        'testResponseWithEmbeddedJson',
        'testNormalJsonResponse'
    ];
    
    foreach ($methods as $method) {
        try {
            echo "Running $method... ";
            $test->$method();
            echo "✓ PASSED\n";
        } catch (\Exception $e) {
            echo "✗ FAILED: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nTests completed.\n";
}
