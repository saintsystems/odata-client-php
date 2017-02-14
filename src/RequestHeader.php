<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Core\Enum;

class RequestHeader extends Enum
{
    const ACCEPT = 'Accept';

    const AUTHORIZATION = 'Authorization';

    const CACHE_CONTROL = 'Cache-Control';

    const CONTENT_TYPE = 'Content-Type';

    const HOST = 'Host';

    const IF_MATCH = 'If-Match';

    const IF_NONE_MATCH = 'If-None-Match';

    const ODATA_VERSION = 'OData-Version';

    const ODATA_MAX_VERSION = 'OData-MaxVersion';

    const ODATA_ISOLUTION = 'OData-Isolation';

    const PREFER = 'Prefer';

    const USER_AGENT = 'User-Agent';

    public function __toString()
    {
        return $this->value();
    }
}
