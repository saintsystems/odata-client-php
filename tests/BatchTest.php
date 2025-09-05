<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\BatchRequestBuilder;
use SaintSystems\OData\GuzzleHttpProvider;

class BatchTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $httpProvider = new GuzzleHttpProvider();
        $this->client = new ODataClient('https://example.com/odata', null, $httpProvider);
    }

    public function testBatchMethodExists()
    {
        $this->assertTrue(method_exists($this->client, 'batch'));
    }

    public function testBatchMethodReturnsBatchRequestBuilder()
    {
        $batch = $this->client->batch();
        $this->assertInstanceOf(BatchRequestBuilder::class, $batch);
    }

    public function testBatchBuilderHasRequiredMethods()
    {
        $batch = $this->client->batch();
        $requiredMethods = ['get', 'post', 'put', 'patch', 'delete', 'startChangeset', 'endChangeset', 'execute'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($batch, $method), "Method {$method} should exist on BatchRequestBuilder");
        }
    }

    public function testBatchBuilderFluentInterface()
    {
        $batch = $this->client->batch();
        
        $result = $batch
            ->get('People', 'test-get')
            ->startChangeset()
            ->post('People', ['test' => 'data'], 'test-post')
            ->endChangeset();
            
        $this->assertSame($batch, $result, 'Batch builder should return itself for fluent interface');
    }

    public function testBatchBuilderWithMixedOperations()
    {
        $batch = $this->client->batch();
        
        // Test that we can chain multiple operations without errors
        $result = $batch
            ->get('People', 'get-people')
            ->get('Airlines', 'get-airlines')
            ->startChangeset()
            ->post('People', ['FirstName' => 'Test'], 'create-person')
            ->patch('People(\'1\')', ['LastName' => 'Updated'], 'update-person')
            ->delete('People(\'2\')', 'delete-person')
            ->endChangeset()
            ->put('People(\'3\')', ['FullName' => 'Complete'], 'replace-person');
            
        $this->assertSame($batch, $result);
    }

    public function testIODataClientInterfaceHasBatchMethod()
    {
        $reflection = new \ReflectionClass('SaintSystems\OData\IODataClient');
        $this->assertTrue($reflection->hasMethod('batch'), 'IODataClient interface should have batch method');
    }
}