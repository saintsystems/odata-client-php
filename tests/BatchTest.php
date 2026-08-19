<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\BatchRequestBuilder;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\HttpRequestMessage;
use Psr\Http\Message\ResponseInterface;

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

    public function testBatchSubrequestHeadersAreIncludedInTheRequestBody(): void
    {
        $batch = $this->client
            ->batch()
            ->startChangeset()
            ->post('People', ['FirstName' => 'Test'], 'create-person', ['X-Custom' => 'value'])
            ->endChangeset();

        $method = new \ReflectionMethod($batch, 'buildBatchContent');
        $method->setAccessible(true);
        $content = $method->invoke($batch, 'batch_test');

        $this->assertStringContainsString("X-Custom: value\r\n", $content);
    }

    public function testBatchSubrequestHeadersRejectInjection(): void
    {
        $invalidHeaders = [
            'line break in header name' => ["X-Injected\r\nHeader" => 'value'],
            'line break in header value' => ['X-Custom' => "value\r\nX-Injected: true"],
            'invalid header name character' => ['X Custom' => 'value'],
            'non-string header value' => ['X-Custom' => 1],
        ];

        foreach($invalidHeaders as $label => $headers) {
            try{
                $this->client->batch()->get('People', null, $headers);
                $this->fail("Expected invalid headers for {$label} to be rejected.");
            }
            catch (\InvalidArgumentException $exception) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testBatchDisablesHttpErrorsWhileExecuting(): void
    {
        $httpProvider = new class extends GuzzleHttpProvider {
            public array $options = [];

            public ?bool $httpErrorsDuringRequest = null;

            public function setExtraOptions($options)
            {
                $this->options = $options;
            }

            public function executeWithExtraOptions(array $options, callable $callback)
            {
                $originalOptions = $this->options;
                $this->options = array_merge($originalOptions, $options);

                try {
                    return $callback();
                } finally {
                    $this->options = $originalOptions;
                }
            }

            public function send(HttpRequestMessage $request): ResponseInterface
            {
                $this->httpErrorsDuringRequest = $this->options['http_errors'];

                return new \GuzzleHttp\Psr7\Response(412, ['Content-Type' => 'multipart/mixed; boundary=batch_response'], <<<'BATCH'
--batch_response
Content-Type: application/http

HTTP/1.1 412 Precondition Failed
Content-Type: application/json

{"error":{"code":"0x80040333"}}
--batch_response--
BATCH);
            }
        };
        $client = new ODataClient('https://example.com/odata', null, $httpProvider);
        $httpProvider->setExtraOptions(['http_errors' => true]);

        $response = $client->batch()->get('People')->execute();

        $this->assertFalse($httpProvider->httpErrorsDuringRequest);
        $this->assertSame(['http_errors' => true], $httpProvider->options);
        $this->assertSame(412, $response->getResponse(0)->getStatus());
        $this->assertSame('0x80040333', $response->getResponse(0)->getBody()['error']['code']);
    }

    public function testIODataClientInterfaceHasBatchMethod()
    {
        $reflection = new \ReflectionClass('SaintSystems\OData\IODataClient');
        $this->assertTrue($reflection->hasMethod('batch'), 'IODataClient interface should have batch method');
    }
}
