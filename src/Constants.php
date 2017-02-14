<?php 
/**
* Copyright (c) Saint Systems, LLC.  All Rights Reserved.  
* Licensed under the MIT License.  See License in the project root 
* for license information.
* 
* OData Constants File
* PHP version 7
*
* @category  Library
* @package   SaintSystems.OData
* @copyright 2017 Saint Systems, LLC
* @license   https://opensource.org/licenses/MIT MIT License
* @version   GIT: 0.1.0
* @link      https://www.microsoft.com/en-us/dynamics365/
*/

namespace SaintSystems\OData;

class Constants
{
    const SDK_VERSION = '0.1.0';

    // ODATA Versions to be used when accessing the Web API (see: https://msdn.microsoft.com/en-us/library/gg334391.aspx)
    const MAX_ODATA_VERSION = '4.0';
    const ODATA_VERSION = '4.0';

    // Values/Keys in OData Responses
    const ODATA_ID = '@odata.id';
    const ODATA_NEXT_LINK = '@odata.id';
    const ODATA_VALUE = 'value';
    
    // Default ODATA Paging
    const ODATA_MAX_PAGE_SIZE = 'odata.maxpagesize';
    const ODATA_MAX_PAGE_SIZE_DEFAULT = 25;

    // Define error constants
    const MAX_PAGE_SIZE = 999;
    const MAX_PAGE_SIZE_ERROR = 'Page size must be less than ' . self::MAX_PAGE_SIZE;
    const TIMEOUT = 'Timeout error';

    // Define error message constants
    const BASE_URL_MISSING = 'Base URL cannot be null or empty.';
    const REQUEST_URL_MISSING = 'Request URL cannot be null or empty.';
    const REQUEST_TIMED_OUT = 'The request timed out.';
    const UNABLE_TO_CREATE_INSTANCE_OF_TYPE = 'Unable to create instance of type.';

    // Define server error constants
    const UNABLE_TO_PARSE_RESPONSE = 'The HTTP client sent back an invalid response';
}
