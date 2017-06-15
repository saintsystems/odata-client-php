<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\Processor;

interface IODataClient
{
    /**
     * Gets the IAuthenticationProvider for authenticating HTTP requests.
     * @var \SaintSystems\OData\IAuthenticationProvider
     */
    public function getAuthenticationProvider();

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
     * @param $requestUri
     * @param array $bindings
     *
     * @return IODataRequest
     */
    public function get($requestUri, $bindings = []);

    /**
     * Get the query grammar used by the connection.
     *
     * @return Grammar
     */
    public function getQueryGrammar();

    /**
     * Set the query grammar used by the connection.
     *
     * @param Grammar $grammar
     *
     * @return void
     */
    public function setQueryGrammar(Grammar $grammar);

    /**
     * Get the query post processor used by the connection.
     *
     * @return Processor
     */
    public function getPostProcessor();

    /**
     * Set the query post processor used by the connection.
     *
     * @param Processor $processor
     *
     * @return void
     */
    public function setPostProcessor(Processor $processor);
}
