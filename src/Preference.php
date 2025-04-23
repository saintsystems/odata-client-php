<?php

namespace Studiosystems\OData;

use Studiosystems\OData\Core\Enum;

class Preference extends Enum
{
    public const ODATA_ALLOW_ENTITY_REFERENCES  = 'odata.allow-entityreferences';

    public const ODATA_CALLBACK = 'odata.callback';

    public const ODATA_CONTINUE_ON_ERROR = 'odata.continue-on-error';

    public const ODATA_INCLUDE_ANNOTATIONS = 'odata.include-annotations';

    public const ODATA_MAX_PAGE_SIZE = 'odata.maxpagesize';

    public const ODATA_TRACK_CHANGES = 'odata.track-changes';

    public const RETURN_REPRESENTATION = 'return=representation';

    public const RETURN_MINIMAL = 'return=minimal';

    public const RESPOND_ASYNC = 'respond-async';

    public const WAIT = 'wait';

    public function __toString()
    {
        return $this->value();
    }
}
