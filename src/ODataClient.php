<?php

namespace SaintSystems\OData;

use Closure;
use SaintSystems\OData\Exception\ODataException;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\IGrammar;
use SaintSystems\OData\Query\IProcessor;
use SaintSystems\OData\Query\Processor;

class ODataClient implements IODataClient
{
    /**
     * The base service URL. For example, "http://services.odata.org/V4/TripPinService/"
     * @var string
     */
    private $baseUrl;

    /**
     * The IAuthenticationProvider for authenticating request messages.
     * @var IAuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * The IHttpProvider for sending HTTP requests.
     * @var IHttpProvider
     */
    private $httpProvider;

    /**
     * The query grammar implementation.
     *
     * @var IGrammar
     */
    protected $queryGrammar;

    /**
     * The query post processor implementation.
     *
     * @var IProcessor
     */
    protected $postProcessor;

    /**
     * The return type for the entities
     *
     * @var string
     */
    private $entityReturnType;

    /**
     * Constructs a new ODataClient.
     * @param string                  $baseUrl                The base service URL.
     * @param IAuthenticationProvider $authenticationProvider The IAuthenticationProvider for authenticating request messages.
     * @param IHttpProvider|null      $httpProvider           The IHttpProvider for sending requests.
     */
    public function __construct(
        $baseUrl,
        Closure $authenticationProvider = null,
        IHttpProvider $httpProvider = null
    ) {
        $this->setBaseUrl($baseUrl);
        $this->authenticationProvider = $authenticationProvider;
        $this->httpProvider = $httpProvider ?: new GuzzleHttpProvider();

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the OData abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return IGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Grammar;
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return IProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }

    /**
     * Gets the IAuthenticationProvider for authenticating requests.
     * @var IAuthenticationProvider
     *
     * @return Closure|IAuthenticationProvider
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * Gets the base URL for requests of the client.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Sets the base URL for requests of the client.
     * @param mixed $value
     *
     * @throws ODataException
     */
    public function setBaseUrl($value)
    {
        if (empty($value))
        {
            throw new ODataException(Constants::BASE_URL_MISSING);
        }

        $this->baseUrl = rtrim($value, '/').'/';
    }

    /**
     * Gets the IHttpProvider for sending HTTP requests.
     *
     * @return IHttpProvider
     */
    public function getHttpProvider()
    {
        return $this->httpProvider;
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param string $entitySet
     *
     * @return Builder
     */
    public function from($entitySet)
    {
        return $this->query()->from($entitySet);
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param array $properties
     *
     * @return Builder
     */
    public function select($properties = [])
    {
        $properties = is_array($properties) ? $properties : func_get_args();

        return $this->query()->select($properties);
    }

    /**
     * Get a new query builder instance.
     *
     * @return Builder
     */
    public function query()
    {
        return new Builder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Run a GET HTTP request against the service.
     *
     * @param string $requestUri
     * @param array  $bindings
     *
     * @return IODataRequest
     */
    public function get($requestUri, $bindings = [])
    {
        return $this->request(HttpMethod::GET, $requestUri);
    }

    /**
     * Run a POST request against the service.
     *
     * @param string $requestUri
     * @param mixed  $postData
     *
     * @return IODataRequest
     */
    public function post($requestUri, $postData)
    {
        return $this->request(HttpMethod::POST, $requestUri, $postData);
    }

    /**
     * Run a PATCH request against the service.
     *
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataRequest
     */
    public function patch($requestUri, $body)
    {
        return $this->request(HttpMethod::PATCH, $requestUri, $body);
    }

    /**
     * Run a DELETE request against the service.
     *
     * @param string $requestUri
     *
     * @return IODataRequest
     */
    public function delete($requestUri)
    {
        return $this->request(HttpMethod::DELETE, $requestUri);
    }

    /**
     * Return an ODataRequest
     *
     * @param string $method
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataRequest
     *
     * @throws ODataException
     */
    public function request($method, $requestUri, $body = null)
    {
        $request = new ODataRequest($method, $this->baseUrl.$requestUri, $this, $this->entityReturnType);

        if ($body) {
            $request->attachBody($body);
        }
        if ($method === 'PATCH' || $method === 'DELETE') {
            // TODO: find a better solution for this
            $request->addHeaders(array('If-Match' => '*'));
        }
        return $request->execute();
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return IGrammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  IGrammar  $grammar
     *
     * @return void
     */
    public function setQueryGrammar(IGrammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return IProcessor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param IProcessor $processor
     *
     * @return void
     */
    public function setPostProcessor(IProcessor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Set the entity return type
     *
     * @param string $entityReturnType
     */
    public function setEntityReturnType($entityReturnType)
    {
        $this->entityReturnType = $entityReturnType;
    }
}
