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

    /**
     * Converts the response JSON object to a OData SDK object
     *
     * @param mixed $returnType The type to convert the object(s) to
     *
     * @return mixed object or array of objects of type $returnType
     */
    public function getResponseAsObject($returnType);

    /**
     * Gets the skip token of a response object from OData
     *
     * @return string skip token, if provided
     */
    public function getSkipToken();

    /**
     * Gets the Id of response object (if set) from OData
     *
     * @return mixed id if this was an insert, if provided
     */
    public function getId();
}
