<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Core\Enum;

class HttpMethod extends Enum
{
    /**
     * Represents an HTTP DELETE protocol method.
     */
    public const DELETE = 'DELETE';

    /**
     * Get Represents an HTTP GET protocol method.
     */
    public const GET = 'GET';

    /**
     * Represents an HTTP HEAD protocol method. The HEAD method is identical to GET except that
     * the server only returns message-headers in the response, without a message-body.
     */
    public const HEAD = 'HEAD';

    /**
     * An HTTP method.
     */
    // const METHOD;

    /**
     * Represents an HTTP OPTIONS protocol method.
     */
    public const OPTIONS = 'OPTIONS';

    /**
     * Represents an HTTP POST protocol method that is used to post a new entity as an addition to a URI.
     */
    public const POST = 'POST';

    /**
     * Represents an HTTP PUT protocol method that is used to replace an entity identified by a URI.
     */
    public const PUT = 'PUT';

    /**
     * Represents an HTTP PATCH protocol method that is used to update an entity.
     */
    public const PATCH = 'PATCH';

    /**
     * Represents an HTTP TRACE protocol method.
     */
    public const TRACE = 'TRACE';

    public function __toString()
    {
        return $this->value();
    }
}
