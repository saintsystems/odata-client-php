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

namespace SaintSystems\OData;

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
    * The body of the response
    *
    * @var string
    */
    private $body;

    /**
    * The body of the response, 
    * decoded into an array
    *
    * @var array(string)
    */
    private $decodedBody;

    /**
    * The headers of the response
    *
    * @var array(string)
    */
    private $headers;

    /**
    * The status code of the response
    *
    * @var string
    */
    private $httpStatusCode;

    /**
    * Creates a new OData HTTP response entity
    *
    * @param object $request        The request
    * @param string $body           The body of the response
    * @param string $httpStatusCode The returned status code
    * @param array  $headers        The returned headers
    */
    public function __construct($request, $body = null, $httpStatusCode = null, $headers = array())
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        $this->decodedBody = $this->decodeBody();
    }

    /**
    * Decode the JSON response into an array
    *
    * @return array The decoded response
    */
    private function decodeBody()
    {
        $decodedBody = json_decode($this->body, true);
        if ($decodedBody === null) {
            $decodedBody = array();
        }
        return $decodedBody;
    }

    /**
    * Get the decoded body of the HTTP response
    *
    * @return array The decoded body
    */
    public function getBody()
    {
        return $this->decodedBody;
    }

    /**
    * Get the undecoded body of the HTTP response
    *
    * @return array The undecoded body
    */
    public function getRawBody()
    {
        return $this->body;
    }

    /**
    * Get the status of the HTTP response
    *
    * @return string The HTTP status
    */
    public function getStatus()
    {
        return $this->httpStatusCode;
    }

    /**
    * Get the headers of the response
    *
    * @return array The response headers
    */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
    * Converts the response JSON object to a OData SDK object
    *
    * @param mixed $returnType The type to convert the object(s) to
    *
    * @return mixed object or array of objects of type $returnType
    */
    public function getResponseAsObject($returnType)
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
            return new $class($result);
        }
    }

    /**
    * Gets the skip token of a response object from OData
    *
    * @return string skip token, if provided
    */
    public function getSkipToken()
    {
        if (array_key_exists(Constants::ODATA_NEXT_LINK, $this->getBody())) {
            $nextLink = $this->getBody()[Constants::ODATA_NEXT_LINK];
            $url = explode("?", $nextLink)[1];
            $url = explode("skiptoken=", $url);
            if (count($url) > 1) {
                return $url[1];
            }
            return null;
        }
        return null;
    }

    /**
    * Gets the Id of response object (if set) from OData
    *
    * @return mixed id if this was an insert, if provided
    */
    public function getId()
    {
        if (array_key_exists(Constants::ODATA_ID, $this->getHeaders())) {
            $id = $this->getBody()[Constants::ODATA_ID];
            return $id;
        }
        return null;
    }
}
