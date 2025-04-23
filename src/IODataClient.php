<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Query\IGrammar;
use Studiosystems\OData\Query\IProcessor;

interface IODataClient
{
    /**
     * Gets the IAuthenticationProvider for authenticating HTTP requests.
     * @var \Studiosystems\OData\IAuthenticationProvider
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
     * @return \Studiosystems\OData\Query\Builder
     */
    public function from($entitySet);

    /**
     * Begin a fluent query against an odata service
     *
     * @param array $properties
     *
     * @return \Studiosystems\OData\Query\Builder
     */
    public function select($properties = []);

    /**
     * Get a new query builder instance.
     *
     * @return \Studiosystems\OData\Query\Builder
     */
    public function query();

    /**
     * Run a GET HTTP request against the service.
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataRequest
     */
    public function get($requestUri, $bindings = []);

    /**
     * Run a GET HTTP request against the service.
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataRequest
     */
    public function getNextPage($requestUri, $bindings = []);

    /**
     * Run a GET HTTP request against the service and return a generator
     *
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataRequest
     */
    public function cursor($requestUri, $bindings = []);

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
}
