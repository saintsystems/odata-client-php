<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Core\Enum;

class ResponseHeader extends Enum
{
    public const ETAG = 'ETag';

    public const LOCATION = 'Location';

    public const ODATA_ENTITY_ID = 'OData-EntityId';

    public const PREFERERENCE_APPLIED = 'Preference-Applied';

    public const RETRY_AFTER = 'Retry-After';

    public function __toString()
    {
        return $this->value();
    }
}
