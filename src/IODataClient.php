<?php

namespace SaintSystems\OData;

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
     * @param  string  $entitySet
     * @return \SaintSystems\OData\Query\Builder
     */
    public function from($entitySet);

    /**
     * Get a new query builder instance.
     *
     * @return \SaintSystems\OData\Query\Builder
     */
    public function query();
}
