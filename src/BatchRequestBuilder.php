<?php

namespace SaintSystems\OData;

use SaintSystems\OData\HttpMethod;

/**
 * OData Batch Request Builder
 * 
 * Provides functionality to build batch requests for OData services.
 * Batch requests allow multiple operations to be sent in a single HTTP request.
 */
class BatchRequestBuilder
{
    /**
     * The OData client instance
     * @var IODataClient
     */
    private $client;

    /**
     * The batch requests to be executed
     * @var array
     */
    private $requests = [];

    /**
     * The changesets for atomic operations
     * @var array
     */
    private $changesets = [];

    /**
     * Current changeset being built
     * @var array|null
     */
    private $currentChangeset = null;

    /**
     * Create a new batch request builder
     * 
     * @param IODataClient $client
     */
    public function __construct(IODataClient $client)
    {
        $this->client = $client;
    }

    /**
     * Add a GET request to the batch
     * 
     * @param string $uri The request URI
     * @param string|null $id Optional request ID for referencing in responses
     * @return $this
     */
    public function get($uri, $id = null)
    {
        $this->addRequest(HttpMethod::GET, $uri, null, $id);
        return $this;
    }

    /**
     * Add a POST request to the batch
     * 
     * @param string $uri The request URI
     * @param mixed $data The data to post
     * @param string|null $id Optional request ID for referencing in responses
     * @return $this
     */
    public function post($uri, $data, $id = null)
    {
        $this->addRequest(HttpMethod::POST, $uri, $data, $id);
        return $this;
    }

    /**
     * Add a PUT request to the batch
     * 
     * @param string $uri The request URI
     * @param mixed $data The data to put
     * @param string|null $id Optional request ID for referencing in responses
     * @return $this
     */
    public function put($uri, $data, $id = null)
    {
        $this->addRequest(HttpMethod::PUT, $uri, $data, $id);
        return $this;
    }

    /**
     * Add a PATCH request to the batch
     * 
     * @param string $uri The request URI
     * @param mixed $data The data to patch
     * @param string|null $id Optional request ID for referencing in responses
     * @return $this
     */
    public function patch($uri, $data, $id = null)
    {
        $this->addRequest(HttpMethod::PATCH, $uri, $data, $id);
        return $this;
    }

    /**
     * Add a DELETE request to the batch
     * 
     * @param string $uri The request URI
     * @param string|null $id Optional request ID for referencing in responses
     * @return $this
     */
    public function delete($uri, $id = null)
    {
        $this->addRequest(HttpMethod::DELETE, $uri, null, $id);
        return $this;
    }

    /**
     * Start a new changeset for atomic operations
     * 
     * @return $this
     */
    public function startChangeset()
    {
        if ($this->currentChangeset !== null) {
            $this->endChangeset();
        }
        $this->currentChangeset = [];
        return $this;
    }

    /**
     * End the current changeset
     * 
     * @return $this
     */
    public function endChangeset()
    {
        if ($this->currentChangeset !== null) {
            $this->changesets[] = $this->currentChangeset;
            $this->currentChangeset = null;
        }
        return $this;
    }

    /**
     * Execute the batch request
     * 
     * @return IODataResponse
     */
    public function execute()
    {
        // End any open changeset
        if ($this->currentChangeset !== null) {
            $this->endChangeset();
        }

        $boundary = $this->generateBoundary();
        $batchContent = $this->buildBatchContent($boundary);

        // Create a custom request for batch operation with proper content type
        $request = $this->client->request(
            HttpMethod::POST,
            '$batch',
            $batchContent
        );

        return $request;
    }

    /**
     * Add a request to the batch
     * 
     * @param string $method The HTTP method
     * @param string $uri The request URI
     * @param mixed $data The request data
     * @param string|null $id Optional request ID
     */
    private function addRequest($method, $uri, $data = null, $id = null)
    {
        $request = [
            'method' => $method,
            'uri' => $uri,
            'data' => $data,
            'id' => $id ?: uniqid('request_')
        ];

        if ($this->currentChangeset !== null) {
            $this->currentChangeset[] = $request;
        } else {
            $this->requests[] = $request;
        }
    }

    /**
     * Build the batch content with multipart MIME format
     * 
     * @param string $boundary The boundary string to use
     * @return string The formatted batch content
     */
    private function buildBatchContent($boundary)
    {
        $content = '';

        // Add individual requests
        foreach ($this->requests as $request) {
            $content .= "--{$boundary}\r\n";
            $content .= "Content-Type: application/http\r\n";
            $content .= "Content-Transfer-Encoding: binary\r\n";
            if ($request['id']) {
                $content .= "Content-ID: {$request['id']}\r\n";
            }
            $content .= "\r\n";
            
            $content .= $this->buildHttpRequest($request);
            $content .= "\r\n";
        }

        // Add changesets
        foreach ($this->changesets as $changeset) {
            $changesetBoundary = $this->generateBoundary('changeset_');
            $content .= "--{$boundary}\r\n";
            $content .= "Content-Type: multipart/mixed; boundary={$changesetBoundary}\r\n";
            $content .= "\r\n";

            foreach ($changeset as $request) {
                $content .= "--{$changesetBoundary}\r\n";
                $content .= "Content-Type: application/http\r\n";
                $content .= "Content-Transfer-Encoding: binary\r\n";
                if ($request['id']) {
                    $content .= "Content-ID: {$request['id']}\r\n";
                }
                $content .= "\r\n";
                
                $content .= $this->buildHttpRequest($request);
                $content .= "\r\n";
            }

            $content .= "--{$changesetBoundary}--\r\n";
        }

        $content .= "--{$boundary}--\r\n";

        return $content;
    }

    /**
     * Build an individual HTTP request
     * 
     * @param array $request The request details
     * @return string The formatted HTTP request
     */
    private function buildHttpRequest($request)
    {
        $baseUrl = rtrim($this->client->getBaseUrl(), '/');
        $uri = ltrim($request['uri'], '/');
        $fullUrl = "{$baseUrl}/{$uri}";

        $httpRequest = "{$request['method']} {$fullUrl} HTTP/1.1\r\n";
        
        if ($request['data'] && in_array($request['method'], [HttpMethod::POST, HttpMethod::PUT, HttpMethod::PATCH])) {
            $jsonData = is_string($request['data']) ? $request['data'] : json_encode($request['data']);
            $httpRequest .= "Content-Type: application/json\r\n";
            $httpRequest .= "Content-Length: " . strlen($jsonData) . "\r\n";
            $httpRequest .= "\r\n";
            $httpRequest .= $jsonData;
        } else {
            $httpRequest .= "\r\n";
        }

        return $httpRequest;
    }

    /**
     * Generate a unique boundary string
     * 
     * @param string $prefix Optional prefix for the boundary
     * @return string The boundary string
     */
    private function generateBoundary($prefix = 'batch_')
    {
        return $prefix . uniqid() . '_' . time();
    }
}