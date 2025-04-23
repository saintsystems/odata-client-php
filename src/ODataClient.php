<?php

namespace Studiosystems\OData;

use Closure;
use Studiosystems\OData\Exception\ODataException;
use Studiosystems\OData\IODataRequest\Processor;
use Studiosystems\OData\Query\Builder;
use Studiosystems\OData\Query\Grammar;
use Studiosystems\OData\Query\IGrammar;
use Studiosystems\OData\Query\IProcessor;
use Studiosystems\OData\Query\Processor;
use Illuminate\Support\LazyCollection;

class ODataClient implements IODataClient
{
    /**
     * The base service URL. For example, "https://services.odata.org/V4/TripPinService/"
     */
    private string $baseUrl;

    /**
     * The IAuthenticationProvider for authenticating request messages.
     */
    private IAuthenticationProvider $authenticationProvider;

    /**
     * The IHttpProvider for sending HTTP requests.
     */
    private IHttpProvider $httpProvider;

    /**
     * The query grammar implementation.
     */
    protected IGrammar $queryGrammar;

    /**
     * The query post processor implementation.
     */
    protected IProcessor $postProcessor;

    /**
     * The return type for the entities
     */
    private string $entityReturnType;

    /**
     * The page size
     */
    private int $pageSize;

    /**
     * The entityKey to be found
     */
    private mixed $entityKey;

    /**
     * Constructs a new ODataClient.
     * @throws ODataException
     */
    public function __construct(
        string $baseUrl,
        ?callable $authenticationProvider = null,
        ?IHttpProvider $httpProvider = null
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
     */
    public function useDefaultQueryGrammar(): void
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     */
    protected function getDefaultQueryGrammar(): Grammar|IGrammar
    {
        return new Grammar();
    }

    /**
     * Set the query post processor to the default implementation.
     */
    public function useDefaultPostProcessor(): void
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     */
    protected function getDefaultPostProcessor(): IProcessor|Processor
    {
        return new Processor();
    }

    /**
     * Gets the IAuthenticationProvider for authenticating requests.
     */
    public function getAuthenticationProvider(): callable|IAuthenticationProvider|Closure|null
    {
        return $this->authenticationProvider;
    }

    /**
     * Gets the base URL for requests of the client.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Sets the base URL for requests of the client.
     */
    public function setBaseUrl(mixed $value): void
    {
        if (empty($value)) {
            throw new ODataException(Constants::BASE_URL_MISSING);
        }

        $this->baseUrl = rtrim($value, '/') . '/';
    }

    /**
     * Gets the IHttpProvider for sending HTTP requests.
     */
    public function getHttpProvider(): GuzzleHttpProvider|IHttpProvider
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
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
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
        list($response, $nextPage) = $this->getNextPage($requestUri, $bindings);
        return $response;
    }

    /**
     * Run a GET HTTP request against the service.
     *
     * @param string $requestUri
     * @param array  $bindings
     *
     * @return IODataRequest
     */
    public function getNextPage($requestUri, $bindings = [])
    {
        return $this->request(HttpMethod::GET, $requestUri, $bindings);
    }

    /**
     * Run a GET HTTP request against the service and return a generator.
     *
     * @param string $requestUri
     * @param array  $bindings
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor($requestUri, $bindings = [])
    {
        return LazyCollection::make(function () use ($requestUri, $bindings) {

            $nextPage = $requestUri;

            while (!is_null($nextPage)) {
                list($data, $nextPage) = $this->getNextPage($nextPage, $bindings);

                if (!is_null($nextPage)) {
                    $nextPage = str_replace($this->baseUrl, '', $nextPage);
                }

                yield from $data;
            }
        });
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

    /**
     * Set the odata.maxpagesize value of the request.
     *
     * @param int $pageSize
     *
     * @return IODataClient
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * Gets the page size
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Set the entityKey to be found.
     *
     * @param mixed $entityKey
     *
     * @return IODataClient
     */
    public function setEntityKey($entityKey)
    {
        $this->entityKey = $entityKey;
        return $this;
    }

    /**
     * Gets the entity key
     *
     * @return mixed
     */
    public function getEntityKey()
    {
        return $this->entityKey;
    }
}
