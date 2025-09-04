<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\ODataRequest;
use SaintSystems\OData\HttpRequestMessage;
use SaintSystems\OData\HttpMethod;
use SaintSystems\OData\IHttpProvider;

class MockHttpProvider implements IHttpProvider
{
    public $lastRequest = null;

    public function send(HttpRequestMessage $request)
    {
        $this->lastRequest = $request;
        
        // Return a mock response
        return new class {
            public function getBody() {
                return '{"value": []}';
            }
            public function getStatusCode() {
                return 200;
            }
            public function getHeaders() {
                return [];
            }
        };
    }
}

class CustomHeadersTest extends TestCase
{
    private $baseUrl;
    private $mockHttpProvider;
    private $odataClient;

    public function setUp(): void
    {
        $this->baseUrl = 'https://example.com/odata';
        $this->mockHttpProvider = new MockHttpProvider();
        $this->odataClient = new ODataClient($this->baseUrl, null, $this->mockHttpProvider);
    }

    public function testODataClientSetHeaders()
    {
        $headers = ['X-Custom-Header' => 'CustomValue', 'Authorization' => 'Bearer token123'];
        
        $result = $this->odataClient->setHeaders($headers);
        
        // Should return the client for fluent interface
        $this->assertSame($this->odataClient, $result);
        $this->assertEquals($headers, $this->odataClient->getHeaders());
    }

    public function testODataClientAddHeader()
    {
        $result = $this->odataClient->addHeader('X-Test', 'TestValue');
        
        // Should return the client for fluent interface
        $this->assertSame($this->odataClient, $result);
        $this->assertEquals(['X-Test' => 'TestValue'], $this->odataClient->getHeaders());
        
        // Add another header
        $this->odataClient->addHeader('X-Another', 'AnotherValue');
        $this->assertEquals([
            'X-Test' => 'TestValue',
            'X-Another' => 'AnotherValue'
        ], $this->odataClient->getHeaders());
    }

    public function testCustomHeadersArePassedToHttpProvider()
    {
        // Set custom headers
        $this->odataClient->setHeaders(['X-Custom' => 'CustomValue']);
        
        // Make a request
        $this->odataClient->get('TestEntity');
        
        // Verify the custom header was included in the request
        $this->assertNotNull($this->mockHttpProvider->lastRequest);
        $this->assertArrayHasKey('X-Custom', $this->mockHttpProvider->lastRequest->headers);
        $this->assertEquals('CustomValue', $this->mockHttpProvider->lastRequest->headers['X-Custom']);
    }

    public function testQueryBuilderWithHeaders()
    {
        // Use fluent interface with custom headers
        $this->odataClient->from('TestEntity')
            ->withHeader('X-Query-Header', 'QueryValue')
            ->withHeaders(['X-Multiple' => 'MultiValue', 'X-Another' => 'AnotherValue'])
            ->get();
        
        // Verify headers were included
        $this->assertNotNull($this->mockHttpProvider->lastRequest);
        $this->assertArrayHasKey('X-Query-Header', $this->mockHttpProvider->lastRequest->headers);
        $this->assertEquals('QueryValue', $this->mockHttpProvider->lastRequest->headers['X-Query-Header']);
        $this->assertArrayHasKey('X-Multiple', $this->mockHttpProvider->lastRequest->headers);
        $this->assertEquals('MultiValue', $this->mockHttpProvider->lastRequest->headers['X-Multiple']);
        $this->assertArrayHasKey('X-Another', $this->mockHttpProvider->lastRequest->headers);
        $this->assertEquals('AnotherValue', $this->mockHttpProvider->lastRequest->headers['X-Another']);
    }

    public function testDefaultHeadersAreNotOverridden()
    {
        // Make a request with custom headers
        $this->odataClient->addHeader('X-Custom', 'CustomValue');
        $this->odataClient->get('TestEntity');
        
        // Verify default headers are still present
        $this->assertNotNull($this->mockHttpProvider->lastRequest);
        $this->assertArrayHasKey('Content-Type', $this->mockHttpProvider->lastRequest->headers);
        $this->assertArrayHasKey('OData-MaxVersion', $this->mockHttpProvider->lastRequest->headers);
        $this->assertArrayHasKey('OData-Version', $this->mockHttpProvider->lastRequest->headers);
        $this->assertArrayHasKey('X-Custom', $this->mockHttpProvider->lastRequest->headers);
    }

    public function testEmptyHeadersInitialization()
    {
        $client = new ODataClient('https://example.com', null, $this->mockHttpProvider);
        $this->assertEquals([], $client->getHeaders());
    }

    public function testBuilderHeadersTemporarilyOverrideClientHeaders()
    {
        // Set headers on client
        $this->odataClient->setHeaders(['X-Client' => 'ClientValue']);
        
        // Make request with builder headers
        $this->odataClient->from('TestEntity')
            ->withHeader('X-Builder', 'BuilderValue')
            ->get();
        
        // Verify both headers were sent
        $this->assertArrayHasKey('X-Client', $this->mockHttpProvider->lastRequest->headers);
        $this->assertArrayHasKey('X-Builder', $this->mockHttpProvider->lastRequest->headers);
        
        // Verify client headers are still intact after request
        $this->assertEquals(['X-Client' => 'ClientValue'], $this->odataClient->getHeaders());
    }
}