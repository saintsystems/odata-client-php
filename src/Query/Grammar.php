<?php

namespace SaintSystems\OData\Query;

class Grammar implements IGrammar
{
    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!='
    ];

    /**
     * All of the available clause functions.
     *
     * @var array
     */
    protected $functions = [
        'contains', 'startswith', 'endswith', 'substringof'
    ];

    protected $operatorMapping = [
        '='  => 'eq',
        '<'  => 'lt',
        '>'  => 'gt',
        '<=' => 'le',
        '>=' => 'ge',
        '!<' => 'not lt',
        '!>' => 'not gt',
        '<>' => 'ne',
        '!=' => 'ne',
    ];

    /**
     * The components that make up an OData Request.
     *
     * @var array
     */
    protected $selectComponents = [
        'entitySet',
        'entityKey',
        'count',
        'queryString',
        'properties',
        'wheres',
        'expands',
        //'search',
        'orders',
        'customOption',
        'skip',
        'skiptoken',
        'take',
        'totalCount',
    ];

    /**
     * Determine if query param is the first one added to uri
     *
     * @var bool
     */
    private $isFirstQueryParam = true;

    /**
     * @inheritdoc
     */
    public function compileSelect(Builder $query)
    {
        // If the query does not have any properties set, we'll set the properties to the
        // [] character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->properties;

        if (is_null($query->properties)) {
            $query->properties = [];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $uri = trim($this->concatenate(
            $this->compileComponents($query))
        );

        $query->properties = $original;

        //dd($uri);

        return $uri;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param Builder $query
     *
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $uri = [];

        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            if (! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $uri[$component] = $this->$method($query, $query->$component);
            }
        }
        return $uri;
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param Builder $query
     * @param string  $entitySet
     *
     * @return string
     */
    protected function compileEntitySet(Builder $query, $entitySet)
    {
        return $entitySet;
    }

    /**
     * Compile the entity key portion of the query.
     *
     * @param Builder $query
     * @param string  $entityKey
     *
     * @return string
     */
    protected function compileEntityKey(Builder $query, $entityKey)
    {
        if (is_null($entityKey)) {
            return '';
        }

        if (is_array($entityKey)) {
            $entityKey = $this->compileCompositeEntityKey($entityKey);
        } else {
            $entityKey = $this->wrapKey($entityKey);
        }

        return "($entityKey)";
    }

    /**
     * Compile the composite entity key portion of the query.
     *
     * @param Builder $query
     * @param mixed   $entityKey
     *
     * @return string
     */
    public function compileCompositeEntityKey($entityKey)
    {
        $entityKeys = [];
        foreach ($entityKey as $key => $value) {
            $entityKeys[] = $key . '=' . $this->wrapKey($value);
        }

        return implode(',', $entityKeys);
    }

    protected function compileQueryString(Builder $query, $queryString)
    {
        if (isset($query->entitySet)
            && (
                !empty($query->properties)
                || isset($query->wheres)
                || isset($query->orders)
                || isset($query->expands)
                || isset($query->take)
                || isset($query->skip)
                || isset($query->skiptoken)
            )) {
            return $queryString;
        }
        return '';
    }

    protected function wrapKey($entityKey)
    {
        if (is_uuid($entityKey) || is_int($entityKey)) {
            return $entityKey;
        }
        return "'$entityKey'";
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $query
     * @param array   $aggregate
     *
     * @return string
     */
    protected function compileCount(Builder $query, $aggregate)
    {
        return '/$count';
    }

    /**
     * Compile the "$select=" portion of the OData query.
     *
     * @param Builder $query
     * @param array   $properties
     *
     * @return string|null
     */
    protected function compileProperties(Builder $query, $properties)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (! is_null($query->count)) {
            return;
        }

        $select = '';
        if (! empty($properties)) {
            $select = $this->appendQueryParam('$select=') . $this->columnize($properties);
        }

        return $select;
    }

    /**
     * Compile the "expand" portions of the query.
     *
     * @param Builder  $query
     * @param array    $expands
     *
     * @return string
     */
    protected function compileExpands(Builder $query, $expands)
    {
        if (! empty($expands)) {
            return $this->appendQueryParam('$expand=') . implode(',', $expands);
        }

        return '';
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $query
     *
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param Builder $query
     *
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'].' '.$this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    protected function whereRaw(Builder $query, $where)
    {
        return $where['rawString'];
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param Builder $query
     * @param array   $filter
     *
     * @return string
     */
    protected function concatenateWhereClauses($query, $filter)
    {
        //$conjunction = $query instanceof JoinClause ? 'on' : 'where';
        $conjunction = $this->appendQueryParam('$filter=');

        return $conjunction . $this->removeLeadingBoolean(implode(' ', $filter));
    }

    /**
     * Compile a basic where clause.
     *
     * @param Builder $query
     * @param array   $where
     *
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        $value = $this->prepareValue($where['value']);
        return $where['column'].' '.$this->getOperatorMapping($where['operator']).' '.$value;
    }

    /**
     * Compile a where clause comparing two columns.
     *
     * @param  Builder $query
     * @param  array   $where
     * @return string
     */
    protected function whereColumn(Builder $query, $where)
    {
        return $where['first'].' '.$this->getOperatorMapping($where['operator']).' '.$where['second'];
    }

    /**
     * Compile a "where function" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereFunction(Builder $query, $where)
    {
        $value = $this->prepareValue($where['value']);
        return $where['operator'] . '(' . $where['column'] . ',' . $value . ')';
    }

    /**
     * Determines if the value is a special primitive data type (similar syntax with enums)
     *
     * @param string $value
     * @return string
     */
    protected function isSpecialPrimitiveDataType($value){
        return preg_match("/^(binary|datetime|guid|time|datetimeoffset)(\'[\w\:\-\.]+\')$/i", $value);
    }

    /**
     * Determines if the value is a URL-encoded datetime format
     *
     * @param string $value
     * @return bool
     */
    protected function isUrlEncodedDateTime($value)
    {
        if (!is_string($value)) {
            return false;
        }

        // First try to URL decode the value
        $decoded = urldecode($value);
        
        // Skip if the value wasn't actually URL encoded (decoded is same as original)
        if ($decoded === $value) {
            return false;
        }
        
        // Check if the decoded value matches common ISO 8601 datetime patterns
        $patterns = [
            // Basic ISO 8601: 2023-12-25T10:30:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
            // ISO 8601 with milliseconds: 2023-12-25T10:30:00.123
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}$/',
            // ISO 8601 with timezone: 2023-12-25T10:30:00+05:00 or 2023-12-25T10:30:00-05:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            // ISO 8601 with milliseconds and timezone: 2023-12-25T10:30:00.123+05:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}[+-]\d{2}:\d{2}$/',
            // ISO 8601 UTC: 2023-12-25T10:30:00Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            // ISO 8601 UTC with milliseconds: 2023-12-25T10:30:00.123Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}Z$/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decoded)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determines if the value is a datetime string (not URL encoded)
     *
     * @param string $value
     * @return bool
     */
    protected function isDateTime($value)
    {
        if (!is_string($value)) {
            return false;
        }
        
        // Check if it matches common ISO 8601 datetime patterns
        $patterns = [
            // Basic ISO 8601: 2023-12-25T10:30:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
            // ISO 8601 with milliseconds: 2023-12-25T10:30:00.123
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}$/',
            // ISO 8601 with timezone: 2023-12-25T10:30:00+05:00 or 2023-12-25T10:30:00-05:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            // ISO 8601 with milliseconds and timezone: 2023-12-25T10:30:00.123+05:00
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}[+-]\d{2}:\d{2}$/',
            // ISO 8601 UTC: 2023-12-25T10:30:00Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            // ISO 8601 UTC with milliseconds: 2023-12-25T10:30:00.123Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,6}Z$/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder  $query
     * @param array    $orders
     *
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        if (! empty($orders)) {
            return $this->appendQueryParam('$orderby=') . implode(',', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
     * @param Builder $query
     * @param array   $orders
     *
     * @return array
     */
    protected function compileOrdersToArray(Builder $query, $orders)
    {
        return array_map(function ($order) {
            return ! isset($order['sql'])
                        ? $order['column'].' '.$order['direction']
                        : $order['sql'];
        }, $orders);
    }

    /**
     * Compile the custom options portion of the query.
     *
     * @param Builder $query The query builder instance
     * @param string|array|null $customOption The custom options to compile
     *
     * @return string The compiled custom options as query parameters
     */
    protected function compileCustomOption(Builder $query, $customOption)
    {
        if (is_null($customOption) || (is_array($customOption) && empty($customOption))) {
            return '';
        }

        if (is_array($customOption)) {
            $customOption = $this->compileCompositeCustomOption($customOption);
        }

        return $this->appendQueryParam($customOption);
    }

    /**
     * Compile the composite Custom Options into a query parameter string.
     *
     * Converts an associative array of custom options into a 'key=value&key2=value2' format
     * suitable for URL query parameters.
     *
     * @param array $customOption Associative array of custom options
     *
     * @return string Compiled custom options string
     */
    public function compileCompositeCustomOption($customOption)
    {
        $customOptions = [];
        foreach ($customOption as $key => $value) {
            // URL encode both key and value to handle special characters
            $encodedKey = urlencode($key);
            $encodedValue = urlencode($value);
            $customOptions[] = $encodedKey . '=' . $encodedValue;
        }

        return implode('&', $customOptions);
    }    

    /**
     * Compile the "$top" portions of the query.
     *
     * @param Builder $query
     * @param int     $take
     *
     * @return string
     */
    protected function compileTake(Builder $query, $take)
    {
        // If we have an entity key $top is redundant and invalid, so bail
        if (! empty($query->entityKey)) {
            return '';
        }
        return $this->appendQueryParam('$top=') . (int) $take;
    }

    /**
     * Compile the "$skip" portions of the query.
     *
     * @param Builder $query
     * @param int     $skip
     *
     * @return string
     */
    protected function compileSkip(Builder $query, $skip)
    {
        return $this->appendQueryParam('$skip=') . (int) $skip;
    }

    /**
     * Compile the "$skiptoken" portions of the query.
     *
     * @param Builder $query
     * @param int     $skip
     *
     * @return string
     */
    protected function compileSkipToken(Builder $query, $skiptoken)
    {
        return $this->appendQueryParam('$skiptoken=') . $skiptoken;
    }

    /**
     * Compile the "$count" portions of the query.
     *
     * @param Builder $query
     * @param int     $totalCount
     *
     * @return string
     */
    protected function compileTotalCount(Builder $query, $totalCount)
    {
        if (isset($query->entityKey)) {
            return '';
        }
        return $this->appendQueryParam('$count=true');
    }

    /**
     * @inheritdoc
     */
    public function columnize(array $properties)
    {
        return implode(',', $properties);
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param array $segments
     *
     * @return string
     */
    protected function concatenate($segments)
    {
        $uri = '';
        $queryParams = [];
        $hasQueryString = false;
        $hasEntitySet = false;
        
        foreach ($segments as $segment => $value) {
            if ((string) $value !== '') {
                if ($segment === 'entitySet') {
                    $hasEntitySet = true;
                    $uri .= $value;
                } else if ($segment === 'entityKey' || $segment === 'count') {
                    // These are path segments, not query parameters
                    $uri .= $value;
                } else if ($segment === 'queryString') {
                    // queryString already includes the '?' 
                    $hasQueryString = true;
                    // Skip it if empty or just '?'
                    if ($value !== '?') {
                        $uri .= $value;
                    }
                } else {
                    // This is a query parameter - collect it
                    $queryParams[] = $value;
                }
            }
        }
        
        // Add query parameters if any
        if (!empty($queryParams)) {
            // Only add '?' if we have an entity set or already have content
            if ($hasEntitySet || strlen($uri) > 0) {
                // If we already have a queryString with '?', use '&' to join
                if ($hasQueryString && strpos($uri, '?') !== false) {
                    $uri .= '&' . implode('&', $queryParams);
                } else {
                    $uri .= '?' . implode('&', $queryParams);
                }
            } else {
                // No entity set, just return the query params without '?'
                $uri .= implode('&', $queryParams);
            }
        }
        
        return $uri;
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param string $value
     *
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * @inheritdoc
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @inheritdoc
     */
    public function getOperatorsAndFunctions()
    {
        return array_merge($this->operators, $this->functions);
    }

    /**
     * Get the OData operator for the passed operator
     *
     * @param string $operator The passed operator
     *
     * @return string The OData operator
     */
    protected function getOperatorMapping($operator)
    {
        if (array_key_exists($operator, $this->operatorMapping)) {
            return $this->operatorMapping[$operator];
        }
        return $operator;
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value)
    {
        //$value = $this->parameter($value);

        // stringify all values if it has NOT an odata enum or special syntax primitive data type
        // (ex. Microsoft.OData.SampleService.Models.TripPin.PersonGender'Female' or datetime'1970-01-01T00:00:00')
        if (!preg_match("/^([\w]+\.)+([\w]+)(\'[\w]+\')$/", $value) && !$this->isSpecialPrimitiveDataType($value)) {
            // Check if the value is a URL-encoded datetime or a regular datetime
            if ($this->isUrlEncodedDateTime($value) || $this->isDateTime($value)) {
                // Don't wrap datetime values in quotes - they should be passed as-is
                return $value;
            } 
            // Check if the value is a string and should be quoted
            else if (is_string($value)) {
                $value = "'".$value."'";
            } else if(is_bool($value)){
                $value = $value ? 'true' : 'false';
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * @inheritdoc
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * @inheritdoc
     */
    public function getValue($expression)
    {
        return $expression->getValue();
    }

    /**
     * Compile a nested where clause.
     *
     * @param Builder $query
     * @param array   $where
     *
     * @return string
     */
    protected function whereNested(Builder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "$filter=" of queries.
        // $offset = $query instanceof JoinClause ? 3 : 6;
        $wheres = $this->compileWheres($where['query']);
        $offset = (substr($wheres, 0, 1) === '&') ? 9 : 8;
        return '('.substr($wheres, $offset).')';
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        return $where['column'] . ' eq null';
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        return $where['column'] . ' ne null';
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(Builder $query, $where)
    {
        return $where['column'] . ' in (\'' . implode('\',\'', $where['list'])  . '\')';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(Builder $query, $where)
    {
        return 'not(' . $where['column'] . ' in (\'' . implode('\',\'', $where['list'])  . '\'))';
    }

    /**
     * Compile a "where contains" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereContains(Builder $query, $where)
    {
        $value = $this->prepareValue($where['value']);
        return 'contains(' . $where['column'] . ',' . $value . ')';
    }

    /**
     * Compile a "where not contains" clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotContains(Builder $query, $where)
    {
        $value = $this->prepareValue($where['value']);
        return 'indexof(' . $where['column'] . ',' . $value . ') eq -1';
    }

    /**
     * Compile a "where any" lambda clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereAny(Builder $query, $where)
    {
        $lambdaVariable = strtolower(substr($where['navigationProperty'], 0, 1));
        $nestedWhere = $this->compileWheres($where['query']);
        
        // Extract the condition part from the nested where clause
        $condition = $this->extractLambdaCondition($nestedWhere, $lambdaVariable);
        
        return $where['navigationProperty'] . '/any(' . $lambdaVariable . ': ' . $condition . ')';
    }

    /**
     * Compile a "where all" lambda clause.
     *
     * @param  Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereAll(Builder $query, $where)
    {
        $lambdaVariable = strtolower(substr($where['navigationProperty'], 0, 1));
        $nestedWhere = $this->compileWheres($where['query']);
        
        // Extract the condition part from the nested where clause
        $condition = $this->extractLambdaCondition($nestedWhere, $lambdaVariable);
        
        return $where['navigationProperty'] . '/all(' . $lambdaVariable . ': ' . $condition . ')';
    }

    /**
     * Extract the lambda condition from nested where clause and prefix columns with lambda variable.
     *
     * @param  string  $nestedWhere
     * @param  string  $lambdaVariable
     * @return string
     */
    protected function extractLambdaCondition($nestedWhere, $lambdaVariable)
    {
        // Remove the $filter= prefix from nested where clause
        $offset = (substr($nestedWhere, 0, 1) === '&') ? 9 : 8;
        $condition = substr($nestedWhere, $offset);
        
        // If the condition starts with '(' and ends with ')', it's already properly nested
        // This happens when multiple where clauses are used in the lambda
        if (substr($condition, 0, 1) === '(' && substr($condition, -1) === ')') {
            // Remove outer parentheses temporarily to process inner content
            $innerCondition = substr($condition, 1, -1);
            $processedInner = $this->prefixColumnsWithLambdaVariable($innerCondition, $lambdaVariable);
            return '(' . $processedInner . ')';
        } else {
            // Single condition, process normally
            return $this->prefixColumnsWithLambdaVariable($condition, $lambdaVariable);
        }
    }

    /**
     * Prefix column names with lambda variable.
     *
     * @param  string  $condition
     * @param  string  $lambdaVariable
     * @return string
     */
    protected function prefixColumnsWithLambdaVariable($condition, $lambdaVariable)
    {
        // Replace column references with lambda variable prefix
        // Use a more precise pattern that only matches property names at the start of comparisons
        return preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s+(eq|ne|gt|ge|lt|le)\s+/', function($matches) use ($lambdaVariable) {
            $property = $matches[1];
            $operator = $matches[2];
            
            // Don't prefix if it's already prefixed, a keyword, or looks like it's already processed
            if (strpos($property, '/') !== false || 
                in_array($property, ['and', 'or', 'not', 'eq', 'ne', 'gt', 'ge', 'lt', 'le', 'true', 'false', 'null']) ||
                strlen($property) < 2) {
                return $matches[0];
            }
            
            return $lambdaVariable . '/' . $property . ' ' . $operator . ' ';
        }, $condition);
    }

    /**
     * Append query param to existing uri
     *
     * @param string $value
     * @return mixed
     */
    private function appendQueryParam(string $value)
    {
        //$param = $this->isFirstQueryParam ? $value : '&' . $value;
        //$this->isFirstQueryParam = false;
        return $value;
    }
}
