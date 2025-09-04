<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Query\IGrammar;
use SaintSystems\OData\Query\IProcessor;

interface IODataClient
{
    /**
     * Gets the IAuthenticationProvider for authenticating HTTP requests.
     * @var \SaintSystems\OData\IAuthenticationProvider
     */
    public function getAuthenticationProvider();

    /**
     * Set the odata.maxpagesize value of the request.
     *
     * @param int $pageSize
     *
     * @return IODataClient
     */
    public function setPageSize($pageSize);

    /**
     * Gets the page size
     *
     * @return int
     */
    public function getPageSize();

    /**
     * Set the entityKey to be found.
     *
     * @param mixed $entityKey
     *
     * @return IODataClient
     */
    public function setEntityKey($entityKey);

    /**
     * Gets the entity key
     *
     * @return mixed
     */
    public function getEntityKey();

    /**
     * Gets the base URL for requests of the client.
     * @var string
     */
    public function getBaseUrl();

    /**
     * Gets the IHttpProvider for sending HTTP requests.
     * @var IHttpProvider
     */
    public function getHttpProvider();

    /**
     * Begin a fluent query against an OData service
     *
     * @param string $entitySet
     *
     * @return \SaintSystems\OData\Query\Builder
     */
    public function from($entitySet);

    /**
     * Begin a fluent query against an odata service
     *
     * @param array $properties
     *
     * @return \SaintSystems\OData\Query\Builder
     */
    public function select($properties = []);

    /**
     * Get a new query builder instance.
     *
     * @return \SaintSystems\OData\Query\Builder
     */
    public function query();

    /**
     * Run a GET HTTP request against the service.
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataResponse
     */
    public function get($requestUri, $bindings = []);

    /**
     * Run a POST request against the service.
     *
     * @param string $requestUri
     * @param mixed  $postData
     *
     * @return IODataResponse
     */
    public function post($requestUri, $postData);

    /**
     * Run a PATCH request against the service.
     *
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataResponse
     */
    public function patch($requestUri, $body);

    /**
     * Run a DELETE request against the service.
     *
     * @param string $requestUri
     *
     * @return IODataResponse
     */
    public function delete($requestUri);

    /**
     * Return an ODataRequest
     *
     * @param string $method
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataResponse
     *
     * @throws ODataException
     */
    public function request($method, $requestUri, $body = null);

    /**
     * Run a GET HTTP request against the service.
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataResponse
     */
    public function getNextPage($requestUri, $bindings = []);

    /**
     * Run a GET HTTP request against the service and return a generator
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataResponse
     */
    public function cursor($requestUri, $bindings = []);

    /**
     * Run a PUT request against the service.
     *
     * @param string $requestUri
     * @param mixed  $body
     *
     * @return IODataResponse
     */
    public function put($requestUri, $body);

    /**
     * Get the query grammar used by the connection.
     *
     * @return IGrammar
     */
    public function getQueryGrammar();

    /**
     * Set the query grammar used by the connection.
     *
     * @param IGrammar $grammar
     *
     * @return void
     */
    public function setQueryGrammar(IGrammar $grammar);

    /**
     * Get the query post processor used by the connection.
     *
     * @return IProcessor
     */
    public function getPostProcessor();

    /**
     * Set the query post processor used by the connection.
     *
     * @param IProcessor $processor
     *
     * @return void
     */
    public function setPostProcessor(IProcessor $processor);

    /**
     * Set custom headers for requests.
     *
     * @param array $headers
     * @return IODataClient
     */
    public function setHeaders(array $headers);

    /**
     * Get custom headers for requests.
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Add a custom header to requests.
     *
     * @param string $name
     * @param string $value
     * @return IODataClient
     */
    public function addHeader($name, $value);
}
