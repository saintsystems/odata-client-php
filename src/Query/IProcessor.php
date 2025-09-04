<?php

namespace SaintSystems\OData\Query;

use SaintSystems\OData\IODataResponse;

interface IProcessor
{
    /**
     * Process the results of a "select" query.
     *
     * @param Builder       $query
     * @param IODataResponse $results
     *
     * @return IODataResponse
     */
    public function processSelect(Builder $query, $results);
}
