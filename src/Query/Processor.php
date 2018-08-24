<?php

namespace SaintSystems\OData\Query;

class Processor implements IProcessor
{
    /**
     * @inheritdoc
     */
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }
}
