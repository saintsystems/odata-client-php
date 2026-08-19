<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataResponseFactory;
use SaintSystems\OData\ODataResponse;
use SaintSystems\OData\ODataBatchResponse;
use SaintSystems\OData\IODataRequest;

class ODataResponseFactoryTest extends TestCase
{
    public function testCreateReturnsODataResponseForNonBatchContent(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $headers = ['Content-Type' => 'application/json'];
        $body = '{"test": "data"}';

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataResponse::class, $response);
        $this->assertNotInstanceOf(ODataBatchResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
    }

    public function testCreateReturnsBatchResponseForMultipartMixedContent(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'batch_boundary_123';
        $headers = ['Content-Type' => "multipart/mixed; boundary=$boundary"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateReturnsBatchResponseWithQuotedBoundary(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'batch_boundary_456';
        $headers = ['Content-Type' => "multipart/mixed; boundary=\"$boundary\""];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateReturnsBatchResponseWithSingleQuotedBoundary(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'batch_boundary_789';
        $headers = ['Content-Type' => "multipart/mixed; boundary='$boundary'"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateReturnsODataResponseForMissingContentType(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $headers = ['test' => 'header'];
        $body = '{"test": "data"}';

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataResponse::class, $response);
        $this->assertNotInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateHandlesContentTypeAsArray(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'array_boundary';
        $headers = ['Content-Type' => ["multipart/mixed; boundary=$boundary", 'charset=utf-8']];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateReturnsODataResponseForArrayContentTypeWithoutBoundary(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $headers = ['Content-Type' => ['application/json', 'charset=utf-8']];
        $body = '{"test": "data"}';

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataResponse::class, $response);
        $this->assertNotInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateHandlesCaseInsensitiveContentTypeHeader(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'case_boundary';
        $headers = ['content-type' => "multipart/mixed; boundary=$boundary"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateHandlesMixedCaseContentTypeHeader(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'mixed_case_boundary';
        $headers = ['Content-TYPE' => "multipart/mixed; boundary=$boundary"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateWithEmptyBody(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $headers = ['Content-Type' => 'application/json'];

        $response = ODataResponseFactory::create($request, '', 200, $headers);

        $this->assertInstanceOf(ODataResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
    }

    public function testCreateWithMultipleHeadersIncludingBatchContentType(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'multi_header_boundary';
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => "multipart/mixed; boundary=$boundary",
            'OData-Version' => '4.0',
            'Authorization' => 'Bearer token'
        ];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
        $this->assertSame($headers, $response->getHeaders());
    }

    public function testCreateWithBoundaryContainingSpecialCharacters(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'batch-boundary_123.456+789';
        $headers = ['Content-Type' => "multipart/mixed; boundary=$boundary"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreateWithWhitespaceAroundBoundary(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $boundary = 'whitespace_boundary';
        // Extra whitespace around boundary
        $headers = ['Content-Type' => "multipart/mixed;  boundary=$boundary"];
        $body = "--$boundary\nContent-Type: application/http\n\nHTTP/1.1 200 OK\n\n[]\n--$boundary--";

        $response = ODataResponseFactory::create($request, $body, 200, $headers);

        $this->assertInstanceOf(ODataBatchResponse::class, $response);
    }

    public function testCreatePreservesAllParametersInResponse(): void
    {
        $request = $this->createMock(IODataRequest::class);
        $headers = ['Content-Type' => 'application/json; charset=utf-8', 'X-Custom' => 'value'];
        $body = '{"id": 123, "name": "test"}';
        $statusCode = 201;

        $response = ODataResponseFactory::create($request, $body, $statusCode, $headers);

        $this->assertInstanceOf(ODataResponse::class, $response);
        $this->assertSame($statusCode, $response->getStatus());
        $this->assertSame($body, $response->getRawBody());
        $this->assertSame($headers, $response->getHeaders());
    }
}
