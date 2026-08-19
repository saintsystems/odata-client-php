<?php

namespace SaintSystems\OData;

interface IODataResponse
{
    /**
     * Get the decoded body of the HTTP response
     *
     * @var array The decoded body
     */
    public function getBody();

    /**
     * Get the undecoded body of the HTTP response
     *
     * @var string The undecoded body
     */
    public function getRawBody();

    /**
     * Get the status of the HTTP response
     *
     * @var string The HTTP status
     */
    public function getStatus();

    /**
     * Get the headers of the response
     *
     * @var array The response headers
     */
    public function getHeaders();
}