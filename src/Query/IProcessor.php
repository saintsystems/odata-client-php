<?php

namespace Studiosystems\OData\Query;

use Studiosystems\OData\IODataRequest;

interface IProcessor
{
    /**
     * Process the results of a "select" query.
     */
    public function processSelect(Builder $query, IODataRequest $results): IODataRequest;
}
