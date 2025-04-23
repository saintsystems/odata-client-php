<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Core\Enum;

class ContentType extends Enum
{
    public const APPLICATION_JSON = 'application/json';

    public const APPLICATION_XML = 'application/xml';

    public function __toString()
    {
        return $this->value();
    }
}
