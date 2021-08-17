<?php

namespace SaintSystems\OData;

use GuzzleHttp\Client;

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

    protected $additionalHeaders = [];

    /**
     * Creates a new HttpProvider
     */
    public function __construct()
    {
        $this->http = new Client();
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

    public function setExtraOptions($options)
    {
        $this->extra_options = $options;
    }

    /**
    * Executes the HTTP request using Guzzle
    *
    * @param HttpRequestMessage $request
    *
    * @return mixed object or array of objects
    *         of class $returnType
    */
    public function send(HttpRequestMessage $request)
    {
        $options = [
            'headers' => array_merge($request->headers, $this->additionalHeaders),
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

    public function setAdditionalHeader($key, $value)
    {
        if ($value === null) {
            unset($this->additionalHeaders[$key]);
        } else {
            $this->additionalHeaders[$key] = $value;
        }
    }
}
