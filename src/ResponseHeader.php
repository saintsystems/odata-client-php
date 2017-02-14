<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Core\Enum;

class ResponseHeader extends Enum
{
	const ETAG = 'ETag';

	const LOCATION = 'Location';

	const ODATA_ENTITY_ID = 'OData-EntityId';

    const PREFERERENCE_APPLIED = 'Preference-Applied';

    const RETRY_AFTER = 'Retry-After';

    public function __toString()
    {
        return $this->value();
    }
}
