<?php

namespace SaintSystems\OData;

class Option
{
    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public $name;

    public $value;
}
