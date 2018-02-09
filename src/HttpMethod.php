<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Core\Enum;

class HttpMethod extends Enum
{
    /**
     * Represents an HTTP DELETE protocol method.
     */
    const DELETE = 'DELETE';

    /**
     * Get Represents an HTTP GET protocol method.
     */
    const GET = 'GET';

    /**
     * Represents an HTTP HEAD protocol method. The HEAD method is identical to GET except that 
     * the server only returns message-headers in the response, without a message-body.
     */
    const HEAD = 'HEAD';

    /**
     * An HTTP method.
     */
    // const METHOD;
    
    /**
     * Represents an HTTP OPTIONS protocol method.
     */
    const OPTIONS = 'OPTIONS';

    /**
     * Represents an HTTP POST protocol method that is used to post a new entity as an addition to a URI.
     */
    const POST = 'POST';

    /**
     * Represents an HTTP PUT protocol method that is used to replace an entity identified by a URI.
     */
    const PUT = 'PUT';

    /**
     * Represents an HTTP PATCH protocol method that is used to update an entity.
     */
    const PATCH = 'PATCH';

    /**
     * Represents an HTTP TRACE protocol method.
     */
    const TRACE = 'TRACE';

    public function __toString()
    {
        return $this->value();
    }
}
