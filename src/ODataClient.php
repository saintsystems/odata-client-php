<?php

namespace SaintSystems\OData;

use Closure;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\Processor;
use SaintSystems\OData\Exception\ODataException;

class ODataClient implements IODataClient
{
    /**
     * The base service URL. For example, "http://services.odata.org/V4/TripPinService/"
     * @var string
     */
    private $baseUrl;

    /**
     * The IAuthenticationProvider for authenticating request messages.
     * @var \Microsoft\Core\Http\IAuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * The IHttpProvider for sending HTTP requests.
     * @var \Microsoft\Core\Http\IHttpProvider
     */
    private $httpProvider;

    /**
     * The query grammar implementation.
     *
     * @var \SaintSystems\OData\Grammar
     */
    protected $queryGrammar;

    /**
     * The query post processor implementation.
     *
     * @var \SaintSystems\OData\Processor
     */
    protected $postProcessor;

    /**
     * Constructs a new ODataClient.
     * @param string                  $baseUrl                The base service URL.
     * @param IAuthenticationProvider $authenticationProvider The IAuthenticationProvider for authenticating request messages.
     * @param IHttpProvider|null      $httpProvider           The IHttpProvider for sending requests.
     */
    public function __construct($baseUrl, 
                                Closure $authenticationProvider = null, 
                                IHttpProvider $httpProvider = null)
    {
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
     * @return \SaintSystems\OData\Grammar
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
     * @return \SaintSystems\OData\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Gets the IAuthenticationProvider for authenticating requests.
     * @var IAuthenticationProvider
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * Gets the base URL for requests of the client.
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Sets the base URL for requests of the client.
     * @param void
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
     * @var \Microsoft\Core\Http\IHttpProvider
     */
    public function getHttpProvider()
    {
        return $this->httpProvider;
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param  string  $entitySet
     * @return \SaintSystems\OData\Query\Builder
     */
    public function from($entitySet)
    {
        return $this->query()->from($entitySet);
    }

    /**
     * Begin a fluent query against an odata service
     *
     * @param  array  $properties
     * @return \SaintSystems\OData\Query\Builder
     */
    public function select($properties = [])
    {
        $properties = is_array($properties) ? $properties : func_get_args();
        return $this->query()->select($properties);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \SaintSystems\OData\Query\Builder
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
     * @param  string  $requestUri
     * @param  array  $bindings
     * @return array
     */
    public function get($requestUri, $bindings = [])
    {
        return $this->request(HttpMethod::GET, $requestUri);
    }

    /**
     * Return an ODataRequest
     *
     * @param  string                           $requestUri
     * @param  \Microsoft\Core\Http\HttpMethod  $method
     * @return IODataRequest
     *
     * @throws \SaintSystems\OData\ODataException
     */
    public function request($method, $requestUri)
    {
        $request = new ODataRequest($method, $this->baseUrl.$requestUri, $this);
        return $request->execute();
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \SaintSystems\OData\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  \SaintSystems\OData\Grammar  $grammar
     * @return void
     */
    public function setQueryGrammar(Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \SaintSystems\OData\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param  \SaintSystems\OData\Processor  $processor
     * @return void
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;
    }
}


