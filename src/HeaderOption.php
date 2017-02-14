<?php

namespace SaintSystems\OData;

class HeaderOption extends Option
{
    public function __toString()
    {
        return $this->value();
    }
}
