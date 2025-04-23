<?php

namespace Studiosystems\OData\IODataRequest;

use Studiosystems\OData\Query\Builder;
use Studiosystems\OData\Query\IProcessor;

class Processor implements IProcessor
{
    /**
     * @inheritdoc
     */
    public function processSelect(Builder $query, $results): \Studiosystems\OData\IODataRequest
    {
        return $results;
    }
}
