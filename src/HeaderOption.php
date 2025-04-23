<?php

namespace Studiosystems\OData;

class HeaderOption extends Option
{
    public function __toString()
    {
        return $this->value;
    }
}
