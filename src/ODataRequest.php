<?php

namespace SaintSystems\OData;

use GuzzleHttp\Client;
use SaintSystems\OData\Exception\ODataException;

/**
 * The base request class.
 */
class ODataRequest implements IODataRequest
{
    /**
     * The URL for the request
     *
     * @var string
     */
    protected $requestUrl;

    /**
     * The Guzzle client used to make the HTTP request
     *
     * @var Client
     */
    protected $http;

    /**
     * An array of headers to send with the request
     *
     * @var array(string => string)
     */
    protected $headers;

    /**
     * The body of the request (optional)
     *
     * @var string
     */
    protected $requestBody;

    /**
     * The type of request to make ("GET", "POST", etc.)
     *
     * @var object
     */
    protected $method;

    /**
     * True if the response should be returned as
     * a stream
     *
     * @var bool
     */
    protected $returnsStream;

    /**
     * The return type to cast the response as
     *
     * @var object
     */
    protected $returnType;

    /**
     * The timeout, in seconds
     *
     * @var string
     */
    protected $timeout;

    /**
     * @var IODataClient
     */
    private $client;

    /**
     * Constructs a new ODataRequest object
     * @param string       $method     The HTTP method to use, e.g. "GET" or "POST"
     * @param string       $requestUrl The URL for the OData request
     * @param IODataClient $client     The ODataClient used to make the request
     * @param [type]       $returnType Optional return type for the OData request (defaults to Entity)
     *
     * @throws ODataException
     */
    public function __construct(
        $method,
        $requestUrl,
        IODataClient $client,
        $returnType = null
    ) {
        $this->method = $method;
        $this->requestUrl = $requestUrl;
        $this->client = $client;
        $this->setReturnType($returnType);

        if (empty($this->requestUrl)) {
            throw new ODataException(Constants::REQUEST_URL_MISSING);
        }
        $this->timeout = 0;
        $this->headers = $this->getDefaultHeaders();
        $pageSize = $this->client->getPageSize();
        if (!is_null($pageSize) && is_int($pageSize)) {
            $this->setPageSize($pageSize);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $pageSize
     * @return void
     */
    public function setPageSize($pageSize) {
        $this->headers[RequestHeader::PREFER] = Constants::ODATA_MAX_PAGE_SIZE . '=' . $pageSize;
    }

    /**
     * Sets the return type of the response object
     *
     * @param mixed $returnClass The object class to use
     *
     * @return ODataRequest object
     */
    public function setReturnType($returnClass)
    {
        if (is_null($returnClass)) return $this;
        $this->returnType = $returnClass;
        if (strcasecmp($this->returnType, 'stream') == 0) {
            $this->returnsStream  = true;
        } else {
            $this->returnsStream = false;
        }
        return $this;
    }

    /**
     * Adds custom headers to the request
     *
     * @param array $headers An array of custom headers
     *
     * @return ODataRequest object
     */
    public function addHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get the request headers
     *
     * @return array of headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Attach a body to the request. Will JSON encode
     * any SaintSystems\OData\Entity objects as well as arrays
     *
     * @param mixed $obj The object to include in the request
     *
     * @return ODataRequest object
     */
    public function attachBody($obj)
    {
        // Attach streams & JSON automatically
        if (is_string($obj) || is_a($obj, 'GuzzleHttp\\Psr7\\Stream')) {
            $this->requestBody = $obj;
        }
        // JSON-encode the model object's property dictionary
        else if (is_object($obj) && method_exists($obj, 'getProperties')) {
            $class = get_class($obj);
            $class = explode("\\", $class);
            $model = strtolower(end($class));

            $body = $this->flattenDictionary($obj->getProperties());
            $this->requestBody = "{" . $model . ":" . json_encode($body) . "}";
        }
        // By default, JSON-encode (i.e. arrays)
        else {
            $this->requestBody = json_encode($obj);
        }
        return $this;
    }

    /**
     * Get the body of the request
     *
     * @return mixed request body of any type
     */
    public function getBody()
    {
        return $this->requestBody;
    }

    /**
     * Sets the timeout limit of the HTTP request
     *
     * @param string $timeout The timeout in ms
     *
     * @return ODataRequest object
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Executes the HTTP request using Guzzle
     *
     * @throws ODataException if response is invalid
     *
     * @return If $returnType === 'stream': GuzzleHttp\Psr7\Response
     *         Otherwise: array with two values
     *         - First value: array of objects
     *           .. of class $returnType if $returnType !== false
     *           .. of class ODataResponse if $returnType === false
     *         - Second value: string containing the "next link" URL
     */
    public function execute()
    {
        if (empty($this->requestUrl))
        {
            throw new ODataException(Constants::REQUEST_URL_MISSING);
        }

        $request = $this->getHttpRequestMessage();
        $request->body = $this->requestBody;

        $this->authenticateRequest($request);

        // if (strpos($this->requestUrl, '$skiptoken') !== false) {
            // echo PHP_EOL;
            // echo 'Sending request: '. $this->requestUrl;
            // echo PHP_EOL;
        // }
        $result = $this->client->getHttpProvider()->send($request);

        // Reset
        $this->client->setEntityKey(null);

        //Send back the bare response
        if ($this->returnsStream) {
            return $result;
        }

        if ($this->isAggregate()) {
            return [(string) $result->getBody(), null];
        }

        // Wrap response in ODataResponse layer
        try {
            $response = new ODataResponse(
                $this,
                (string) $result->getBody(),
                $result->getStatusCode(),
                $result->getHeaders()
            );
        } catch (\Exception $e) {
            throw new ODataException(Constants::UNABLE_TO_PARSE_RESPONSE);
        }

        // If no return type is specified, return ODataResponse
        $returnObj = [$response];

        $returnType = is_null($this->returnType) ? Entity::class : $this->returnType;

        if ($returnType) {
            $returnObj = $response->getResponseAsObject($returnType);
        }
        $nextLink = $response->getNextLink();

        return [$returnObj, $nextLink];
    }

    /**
     * Executes the HTTP request asynchronously using Guzzle
     *
     * @param mixed $client The Http client to use in the request
     *
     * @return mixed object or array of objects
     *         of class $returnType
     */
    public function executeAsync($client = null)
    {
        if (is_null($client)) {
            $client = $this->createHttpClient();
        }

        $promise = $client->requestAsync(
            $this->requestType,
            $this->getRequestUrl(),
            [
                'body' => $this->requestBody,
                'stream' => $this->returnsStream,
                'timeout' => $this->timeout
            ]
        )->then(
            // On success, return the result/response
            function ($result) {
                $response = new ODataResponse(
                    $this,
                    (string) $result->getBody(),
                    $result->getStatusCode(),
                    $result->getHeaders()
                );
                $returnObject = $response;
                if ($this->returnType) {
                    $returnObject = $response->getResponseAsObject(
                        $this->returnType
                    );
                }
                return $returnObject;
            },
            // On fail, log the error and return null
            function ($reason) {
                trigger_error("Async call failed: " . $reason->getMessage());
                return null;
            }
        );
        return $promise;
    }

    /**
     * Get a list of headers for the request
     *
     * @return array The headers for the request
     */
    private function getDefaultHeaders()
    {
        $headers = [
            RequestHeader::CONTENT_TYPE => ContentType::APPLICATION_JSON,
            RequestHeader::ODATA_MAX_VERSION => Constants::MAX_ODATA_VERSION,
            RequestHeader::ODATA_VERSION => Constants::ODATA_VERSION,
            RequestHeader::PREFER => Constants::ODATA_MAX_PAGE_SIZE . '=' . Constants::ODATA_MAX_PAGE_SIZE_DEFAULT,
            RequestHeader::USER_AGENT => 'odata-sdk-php-' . Constants::SDK_VERSION,
        ];

        if (!$this->isAggregate()) {
            $headers[RequestHeader::ACCEPT] = ContentType::APPLICATION_JSON ;
        }
        return $headers;
    }

    /**
     * Gets the <see cref="HttpRequestMessage"/> representation of the request.
     *
     * <returns>The <see cref="HttpRequestMessage"/> representation of the request.</returns>
     */
    public function getHttpRequestMessage()
    {
        $request = new HttpRequestMessage(new HttpMethod($this->method), $this->requestUrl);

        $this->addHeadersToRequest($request);

        return $request;
    }

    /**
     * Returns whether or not the request is an OData aggregate request ($count, etc.)
     */
    private function isAggregate()
    {
        return strpos($this->requestUrl, '/$count') !== false;
    }

    /**
     * Adds all of the headers from the header collection to the request.
     * @param \SaintSystems\OData\HttpRequestMessage $request The HttpRequestMessage representation of the request.
     */
    private function addHeadersToRequest(HttpRequestMessage $request)
    {
        $request->headers = array_merge($this->headers, $request->headers);
        if (strpos($request->requestUri, '/$count') !== false || !is_null($this->client->getEntityKey())) {
            $request->headers = array_filter($request->headers, function($key) {
                return $key !== RequestHeader::PREFER;
             }, ARRAY_FILTER_USE_KEY);
        }
    }

    /**
     * Adds the authentication header to the request.
     *
     * @param HttpRequestMessage $request The representation of the request.
     *
     * @return
     */
    private function authenticateRequest(HttpRequestMessage $request)
    {
        $authenticationProvider = $this->client->getAuthenticationProvider();
        if ( ! is_null($authenticationProvider) && is_callable($authenticationProvider)) {
            return $authenticationProvider($request);
        }
    }

    /**
     * Flattens the property dictionaries into
     * JSON-friendly arrays
     *
     * @param mixed $obj the object to flatten
     *
     * @return array flattened object
     */
    protected function flattenDictionary($obj) {
        foreach ($obj as $arrayKey => $arrayValue) {
            if (method_exists($arrayValue, 'getProperties')) {
                $data = $arrayValue->getProperties();
                $obj[$arrayKey] = $data;
            } else {
                $data = $arrayValue;
            }
            if (is_array($data)) {
                $newItem = $this->flattenDictionary($data);
                $obj[$arrayKey] = $newItem;
            }
        }
        return $obj;
    }
}
