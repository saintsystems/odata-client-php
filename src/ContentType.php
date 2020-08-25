<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Core\Enum;

class ContentType extends Enum
{
    const APPLICATION_JSON = 'application/json';

    const APPLICATION_XML = 'application/xml';

    public function __toString()
    {
        return $this->value();
    }
}
