<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Core\Enum;

class RequestHeader extends Enum
{
    public const ACCEPT = 'Accept';

    public const AUTHORIZATION = 'Authorization';

    public const CACHE_CONTROL = 'Cache-Control';

    public const CONTENT_TYPE = 'Content-Type';

    public const HOST = 'Host';

    public const IF_MATCH = 'If-Match';

    public const IF_NONE_MATCH = 'If-None-Match';

    public const ODATA_VERSION = 'OData-Version';

    public const ODATA_MAX_VERSION = 'OData-MaxVersion';

    public const ODATA_ISOLUTION = 'OData-Isolation';

    public const PREFER = 'Prefer';

    public const USER_AGENT = 'User-Agent';

    public function __toString()
    {
        return $this->value();
    }
}
