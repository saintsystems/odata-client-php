<?php

namespace Studiosystems\OData;

use GuzzleHttp\Client;
use Studiosystems\OData\Exception\ApplicationException;
use Studiosystems\OData\Exception\ODataException;

/**
 * The base request class.
 */
class ODataRequest implements IODataRequest
{
    /**
     * The URL for the request
     */
    protected string $requestUrl;

    /**
     * The Guzzle client used to make the HTTP request
     */
    protected Client $http;

    /**
     * An array of headers to send with the request
     */
    protected array $headers;

    /**
     * The body of the request (optional)
     */
    protected string $requestBody;

    /**
     * The type of request to make ("GET", "POST", etc.)
     */
    protected string|object $method;

    /**
     * True if the response should be returned as
     * a stream
     */
    protected bool $returnsStream;

    /**
     * The return type to cast the response as
     */
    protected object $returnType;

    /**
     * The timeout, in seconds
     */
    protected string|int $timeout;

    private IODataClient $client;

    /**
     * Constructs a new ODataRequest object
     * @param string $method     The HTTP method to use, e.g. "GET" or "POST"
     * @param string $requestUrl The URL for the OData request
     * @param IODataClient $client     The ODataClient used to make the request
     * @param [type]       $returnType Optional return type for the OData request (defaults to Entity)
     *
     * @throws ODataException
     */
    public function __construct(
        string $method,
        string $requestUrl,
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
        if (is_int($pageSize)) {
            $this->setPageSize($pageSize);
        }
    }

    /**
     * Undocumented function
     */
    public function setPageSize($pageSize): void
    {
        $this->headers[RequestHeader::PREFER] = Constants::ODATA_MAX_PAGE_SIZE . '=' . $pageSize;
    }

    /**
     * Sets the return type of the response object
     */
    public function setReturnType(mixed $returnClass): static
    {
        if (is_null($returnClass)) {
            return $this;
        }
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
     */
    public function addHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get the request headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Attach a body to the request. Will JSON encode
     * any Studiosystems\OData\Entity objects as well as arrays
     */
    public function attachBody(mixed $obj): static
    {
        // Attach streams & JSON automatically
        if (is_string($obj) || is_a($obj, 'GuzzleHttp\\Psr7\\Stream')) {
            $this->requestBody = $obj;
        }
        // JSON-encode the model object's property dictionary
        elseif (is_object($obj) && method_exists($obj, 'getProperties')) {
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
     */
    public function getBody(): string
    {
        return $this->requestBody;
    }

    /**
     * Sets the timeout limit of the HTTP request
     */
    public function setTimeout(string $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Executes the HTTP request using Guzzle
     *
     * @throws ODataException if response is invalid
     */
    public function execute(): array
    {
        if (empty($this->requestUrl)) {
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
     */
    public function executeAsync(mixed $client = null): mixed
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
     */
    private function getDefaultHeaders(): array
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
     * @throws ApplicationException
     */
    public function getHttpRequestMessage(): HttpRequestMessage
    {
        $request = new HttpRequestMessage(new HttpMethod($this->method), $this->requestUrl);

        $this->addHeadersToRequest($request);

        return $request;
    }

    /**
     * Returns whether or not the request is an OData aggregate request ($count, etc.)
     */
    private function isAggregate(): bool
    {
        return str_contains($this->requestUrl, '/$count');
    }

    /**
     * Adds all the headers from the header collection to the request.
     * @param HttpRequestMessage $request The HttpRequestMessage representation of the request.
     */
    private function addHeadersToRequest(HttpRequestMessage $request): void
    {
        $request->headers = array_merge($this->headers, $request->headers);
        if (str_contains($request->requestUri, '/$count') || !is_null($this->client->getEntityKey())) {
            $request->headers = array_filter($request->headers, function ($key) {
                return $key !== RequestHeader::PREFER;
            }, ARRAY_FILTER_USE_KEY);
        }
    }

    /**
     * Adds the authentication header to the request.
     */
    private function authenticateRequest(HttpRequestMessage $request): void
    {
        $authenticationProvider = $this->client->getAuthenticationProvider();
        if (! is_null($authenticationProvider) && is_callable($authenticationProvider)) {
            $authenticationProvider($request);
        }
    }

    /**
     * Flattens the property dictionaries into
     * JSON-friendly arrays
     */
    protected function flattenDictionary(mixed $obj): array
    {
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
