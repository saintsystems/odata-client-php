<?php

/**
 * Copyright (c) Saint Systems, LLC.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 *
 * ODataResponse File
 * PHP version 7
 *
 * @category  Library
 * @package   SaintSystems.OData
 * @copyright 2017 Saint Systems, LLC
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   GIT: 0.1.0
 * @link      https://www.microsoft.com/en-us/OData365/
 */

namespace Studiosystems\OData;

/**
 * Class ODataResponse
 *
 * @category Library
 * @package  SaintSystems.OData
 * @license  https://opensource.org/licenses/MIT MIT License
 */
class ODataResponse
{
    /**
     * The request
     */
    public object $request;

    /**
     * The body of the response
     */
    private ?string $body;

    /**
     * The body of the response,
     * decoded into an array
     */
    private array $decodedBody;

    /**
     * The headers of the response
     */
    private array $headers;

    /**
     * The status code of the response
     */
    private ?string $httpStatusCode;

    /**
     * Creates a new OData HTTP response entity
     */
    public function __construct(object $request, string $body = null, string $httpStatusCode = null, array $headers = array())
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        $this->decodedBody = $this->body ? $this->decodeBody() : [];
    }

    /**
     * Decode the JSON response into an array
     */
    private function decodeBody(): array
    {
        $decodedBody = json_decode($this->body, true);
        if ($decodedBody === null) {
            $matches = null;
            preg_match('~\{(?:[^{}]|(?R))*}~', $this->body, $matches);
            $decodedBody = json_decode($matches[0], true);
            if ($decodedBody === null) {
                $decodedBody = array();
            }
        }
        return $decodedBody;
    }

    /**
     * Get the decoded body of the HTTP response
     */
    public function getBody(): array
    {
        return $this->decodedBody;
    }

    /**
     * Get the undecoded body of the HTTP response
     */
    public function getRawBody(): ?string
    {
        return $this->body;
    }

    /**
     * Get the status of the HTTP response
     */
    public function getStatus(): ?string
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the headers of the response
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Converts the response JSON object to a OData SDK object
     */
    public function getResponseAsObject(mixed $returnType): array
    {
        $class = $returnType;
        $result = $this->getBody();

        //If more than one object is returned
        if (array_key_exists(Constants::ODATA_VALUE, $result)) {
            $objArray = array();
            foreach ($result[Constants::ODATA_VALUE] as $obj) {
                $objArray[] = new $class($obj);
            }
            return $objArray;
        } else {
            return [new $class($result)];
        }
    }

    /**
     * Gets the @odata.nextLink of a response object from OData
     */
    public function getNextLink(): ?string
    {
        if (array_key_exists(Constants::ODATA_NEXT_LINK, $this->getBody())) {
            return $this->getBody()[Constants::ODATA_NEXT_LINK];
        }
        return null;
    }

    /**
     * Gets the skip token of a response object from OData
     */
    public function getSkipToken(): ?string
    {
        $nextLink = $this->getNextLink();
        if (is_null($nextLink)) {
            return null;
        }
        $url = explode("?", $nextLink)[1];
        $url = explode("skiptoken=", $url);
        if (count($url) > 1) {
            return $url[1];
        }
        return null;
    }

    /**
     * Gets the ID of response object (if set) from OData
     */
    public function getId(): mixed
    {
        if (array_key_exists(Constants::ODATA_ID, $this->getHeaders())) {
            return $this->getBody()[Constants::ODATA_ID];
        }
        return null;
    }
}
