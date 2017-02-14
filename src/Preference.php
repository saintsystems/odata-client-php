<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Core\Enum;

class Preference extends Enum
{
	const ODATA_ALLOW_ENTITY_REFERENCES  = 'odata.allow-entityreferences';

	const ODATA_CALLBACK = 'odata.callback';

	const ODATA_CONTINUE_ON_ERROR = 'odata.continue-on-error';

    const ODATA_INCLUDE_ANNOTATIONS = 'odata.include-annotations';

    const ODATA_MAX_PAGE_SIZE = 'odata.maxpagesize';

    const ODATA_TRACK_CHANGES = 'odata.track-changes';

    const RETURN_REPRESENTATION = 'return=representation';

    const RETURN_MINIMAL = 'return=minimal';

    const RESPOND_ASYNC = 'respond-async';

    const WAIT = 'wait';

    public function __toString()
    {
        return $this->value();
    }
}
