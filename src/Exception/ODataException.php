<?php

/**
* Copyright (c) Saint Systems, LLC.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*
* ODataException File
* PHP version 7
*
* @category  Library
* @package   SaintSystems.OData
* @copyright 2017 Saint Systems, LLC
* @license   https://opensource.org/licenses/MIT MIT License
* @version   GIT: 0.1.0
*/

namespace Studiosystems\OData\Exception;

use Exception;

/**
 * Class ODataException
 *
 * @category Library
 * @package  SaintSystems.OData
 * @license  https://opensource.org/licenses/MIT MIT License
 */
class ODataException extends Exception
{
    /**
    * Construct a new ODataException handler
    *
    * @param string    $message  The error to send
    * @param int       $code     The error code associated with the error
    * @param Exception $previous The last error sent, defaults to null
    */
    public function __construct(string $message, int $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
    * Stringify the returned error and message
    *
    * @return string The returned string message
    */
    public function __toString()
    {
        return __CLASS__ . ": [$this->code]: $this->message\n";
    }
}
