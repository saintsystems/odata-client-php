<?php

namespace SaintSystems\OData;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds PSR-7 compliant HTTP requests from HttpRequestMessage objects
 */
class HttpRequestBuilder
{
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Create a new HttpRequestBuilder
     * 
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     */
    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Build a PSR-7 request from an HttpRequestMessage
     * 
     * @param HttpRequestMessage $message The OData HTTP request message
     * @return RequestInterface PSR-7 request
     */
    public function buildRequest(HttpRequestMessage $message): RequestInterface
    {
        // Create the base request
        $request = $this->requestFactory->createRequest(
            $message->method,
            $message->requestUri
        );

        // Add headers
        foreach ($message->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Add body if present
        if (!empty($message->body)) {
            $stream = $this->streamFactory->createStream($message->body);
            $request = $request->withBody($stream);
        }

        return $request;
    }
}