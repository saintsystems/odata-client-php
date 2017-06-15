<?php

namespace SaintSystems\OData;

class Option
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    /**
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
