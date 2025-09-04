<?php

namespace SaintSystems\OData;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SaintSystems\OData\Exception\HttpClientException;

class GuzzleHttpProvider implements IHttpProvider
{
    /**
    * The Guzzle client used to make the HTTP request
    *
    * @var Client
    */
    protected $http;

    /**
    * The timeout, in seconds
    *
    * @var string
    */
    protected $timeout;

    protected $extra_options;

    /**
     * Creates a new HttpProvider
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->http = new Client($config);
        $this->timeout = 0;
        $this->extra_options = array();
    }

    /**
     * Gets the timeout limit of the cURL request
     * @return integer  The timeout in ms
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the timeout limit of the cURL request
     *
     * @param integer $timeout The timeout in ms
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Configures the default options for the client.
     *
     * @param array $config
     */
    public function configureDefaults($config)
    {
        $this->http->configureDefaults($config);
    }

    public function setExtraOptions($options)
    {
        $this->extra_options = $options;
    }

    /**
    * Executes the HTTP request using Guzzle
    *
    * @param HttpRequestMessage $request
    *
    * @return ResponseInterface PSR-7 response
    */
    public function send(HttpRequestMessage $request): ResponseInterface
    {
        $options = [
            'headers' => $request->headers,
            'stream' =>  $request->returnsStream,
            'timeout' => $this->timeout
        ];

        foreach ($this->extra_options as $key => $value)
        {
            $options[$key] = $value;
        }

        if ($request->method == HttpMethod::POST || $request->method == HttpMethod::PUT || $request->method == HttpMethod::PATCH) {
            $options['body'] = $request->body;
        }

        $result = $this->http->request(
            $request->method,
            $request->requestUri,
            $options
        );

        return $result;
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
        $options = [
            'timeout' => $this->timeout
        ];

        foreach ($this->extra_options as $key => $value)
        {
            $options[$key] = $value;
        }

        try {
            return $this->http->sendRequest($request, $options);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // Wrap Guzzle exceptions in PSR-18 exceptions
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
