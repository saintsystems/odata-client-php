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

namespace Studiosystems\OData;

class Constants
{
    public const SDK_VERSION = '0.7.2';

    // ODATA Versions to be used when accessing the Web API (see: https://msdn.microsoft.com/en-us/library/gg334391.aspx)
    public const MAX_ODATA_VERSION = '4.0';
    public const ODATA_VERSION = '4.0';

    // Values/Keys in OData Responses
    public const ODATA_ID = '@odata.id';
    public const ODATA_NEXT_LINK = '@odata.nextLink';
    public const ODATA_VALUE = 'value';

    // Default ODATA Paging
    public const ODATA_MAX_PAGE_SIZE = 'odata.maxpagesize';
    public const ODATA_MAX_PAGE_SIZE_DEFAULT = 25;

    // Define error constants
    public const MAX_PAGE_SIZE = 999;
    public const MAX_PAGE_SIZE_ERROR = 'Page size must be less than ' . self::MAX_PAGE_SIZE;
    public const TIMEOUT = 'Timeout error';

    // Define error message constants
    public const BASE_URL_MISSING = 'Base URL cannot be null or empty.';
    public const REQUEST_URL_MISSING = 'Request URL cannot be null or empty.';
    public const REQUEST_TIMED_OUT = 'The request timed out.';
    public const UNABLE_TO_CREATE_INSTANCE_OF_TYPE = 'Unable to create instance of type.';

    // Query error message constants
    public const ENTITY_SET_REQUIRED = 'Entity Set cannot be null or empty. Please make sure you have specified a \'from\' in your query.';

    // Define server error constants
    public const UNABLE_TO_PARSE_RESPONSE = 'The HTTP client sent back an invalid response';
}
