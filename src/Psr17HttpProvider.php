<?php

namespace SaintSystems\OData;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class Psr17HttpProvider implements IHttpProvider
{
    /**
     * The PSR-18 HTTP client
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * The PSR-17 Request factory
     *
     * @var RequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * The PSR-17 Stream factory
     *
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * The timeout, in seconds
     *
     * @var int
     */
    protected $timeout = 0;

    /**
     * Extra options to pass to the HTTP client
     *
     * @var array
     */
    protected $extraOptions = [];

    /**
     * Creates a new Psr17HttpProvider
     *
     * @param ClientInterface $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Gets the timeout limit of the request
     * @return int The timeout in seconds
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the timeout limit of the request
     *
     * @param int $timeout The timeout in seconds
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set extra options for the HTTP client
     *
     * @param array $options
     * @return void
     */
    public function setExtraOptions($options)
    {
        $this->extraOptions = $options;
    }

    /**
     * Executes the HTTP request using PSR-17/PSR-18
     *
     * @param HttpRequestMessage $request
     *
     * @return ResponseInterface
     */
    public function send(HttpRequestMessage $request): ResponseInterface
    {
        // Create PSR-7 request
        $psrRequest = $this->requestFactory->createRequest(
            $request->method,
            $request->requestUri
        );

        // Add headers
        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        // Add body if present
        if (!empty($request->body)) {
            $stream = $this->streamFactory->createStream($request->body);
            $psrRequest = $psrRequest->withBody($stream);
        }

        // Send the request
        // Note: PSR-18 doesn't have built-in timeout support
        // Implementations should handle this via client configuration
        return $this->httpClient->sendRequest($psrRequest);
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response
     * 
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // The wrapped client already implements PSR-18
        return $this->httpClient->sendRequest($request);
    }
}