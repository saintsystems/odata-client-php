<?php

namespace Studiosystems\OData\Query;

use Illuminate\Contracts\Database\Query\Expression;

class Grammar implements IGrammar
{
    /**
     * All the available clause operators.
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!='
    ];

    /**
     * All the available clause functions.
     */
    protected array $functions = [
        'contains', 'startswith', 'endswith', 'substringof'
    ];

    protected array $operatorMapping = [
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
     */
    protected array $selectComponents = [
        'entitySet',
        'entityKey',
        'count',
        'queryString',
        'properties',
        'wheres',
        'expands',
        //'search',
        'orders',
        'skip',
        'skiptoken',
        'take',
        'totalCount',
    ];

    /**
     * Determine if query param is the first one added to uri
     */
    private bool $isFirstQueryParam = true;

    /**
     * @inheritdoc
     */
    public function compileSelect(Builder $query): string
    {
        // If the query does not have any properties set, we'll set the properties to the
        // [] character to just get all the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->properties;

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $uri = trim(
            $this->concatenate(
                $this->compileComponents($query)
            )
        );

        $query->properties = $original;

        //dd($uri);

        return $uri;
    }

    /**
     * Compile the components necessary for a select clause.
     */
    protected function compileComponents(Builder $query): array
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
     */
    protected function compileEntitySet(Builder $query, string $entitySet): string
    {
        return $entitySet;
    }

    /**
     * Compile the entity key portion of the query.
     */
    protected function compileEntityKey(Builder $query, string|array|null $entityKey): string
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
     */
    public function compileCompositeEntityKey(mixed $entityKey): string
    {
        $entityKeys = [];
        foreach ($entityKey as $key => $value) {
            $entityKeys[] = $key . '=' . $this->wrapKey($value);
        }

        return implode(',', $entityKeys);
    }

    protected function compileQueryString(Builder $query, $queryString): string
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

    protected function wrapKey($entityKey): int|string
    {
        if (is_uuid($entityKey) || is_int($entityKey)) {
            return $entityKey;
        }
        return "'$entityKey'";
    }

    /**
     * Compile an aggregated select clause.
     */
    protected function compileCount(Builder $query, array $aggregate): string
    {
        return '/$count';
    }

    /**
     * Compile the "$select=" portion of the OData query.
     */
    protected function compileProperties(Builder $query, array $properties): ?string
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (! is_null($query->count)) {
            return null;
        }

        $select = '';
        if (! empty($properties)) {
            $select = $this->appendQueryParam('$select=') . $this->columnize($properties);
        }

        return $select;
    }

    /**
     * Compile the "expand" portions of the query.
     */
    protected function compileExpands(Builder $query, array $expands): string
    {
        if (! empty($expands)) {
            return $this->appendQueryParam('$expand=') . implode(',', $expands);
        }

        return '';
    }

    /**
     * Compile the "where" portions of the query.
     */
    protected function compileWheres(Builder $query): string
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some 'where' clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     */
    protected function compileWheresToArray(Builder $query): array
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
     */
    protected function concatenateWhereClauses(Builder $query, array $filter): string
    {
        //$conjunction = $query instanceof JoinClause ? 'on' : 'where';
        $conjunction = $this->appendQueryParam('$filter=');

        return $conjunction . $this->removeLeadingBoolean(implode(' ', $filter));
    }

    /**
     * Compile a basic where clause.
     */
    protected function whereBasic(Builder $query, array $where): string
    {
        $value = $this->prepareValue($where['value']);
        return $where['column'].' '.$this->getOperatorMapping($where['operator']).' '.$value;
    }

    /**
     * Compile a where clause comparing two columns.
     */
    protected function whereColumn(Builder $query, array $where): string
    {
        return $where['first'].' '.$this->getOperatorMapping($where['operator']).' '.$where['second'];
    }

    /**
     * Compile a "where function" clause.
     */
    protected function whereFunction(Builder $query, array $where): string
    {
        $value = $this->prepareValue($where['value']);
        return $where['operator'] . '(' . $where['column'] . ',' . $value . ')';
    }

    /**
     * Determines if the value is a special primitive data type (similar syntax with enums)
     */
    protected function isSpecialPrimitiveDataType(string $value): false|int
    {
        return preg_match("/^(binary|datetime|guid|time|datetimeoffset)('[\w:\-.]+')$/i", $value);
    }

    /**
     * Compile the "order by" portions of the query.
     */
    protected function compileOrders(Builder $query, array $orders): string
    {
        if (! empty($orders)) {
            return $this->appendQueryParam('$orderby=') . implode(',', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     */
    protected function compileOrdersToArray(Builder $query, array $orders): array
    {
        return array_map(function ($order) {
            return ! isset($order['sql'])
                        ? $order['column'].' '.$order['direction']
                        : $order['sql'];
        }, $orders);
    }

    /**
     * Compile the "$top" portions of the query.
     */
    protected function compileTake(Builder $query, int $take): string
    {
        // If we have an entity key $top is redundant and invalid, so bail
        if (! empty($query->entityKey)) {
            return '';
        }
        return $this->appendQueryParam('$top=') . $take;
    }

    /**
     * Compile the "$skip" portions of the query.
     */
    protected function compileSkip(Builder $query, int $skip): string
    {
        return $this->appendQueryParam('$skip=') . $skip;
    }

    /**
     * Compile the "$skiptoken" portions of the query.
     */
    protected function compileSkipToken(Builder $query, int $skiptoken): string
    {
        return $this->appendQueryParam('$skiptoken=') . $skiptoken;
    }

    /**
     * Compile the "$count" portions of the query.
     */
    protected function compileTotalCount(Builder $query, int $totalCount): string
    {
        if (isset($query->entityKey)) {
            return '';
        }
        return $this->appendQueryParam('$count=true');
    }

    public function columnize(array $properties): string
    {
        return implode(',', $properties);
    }

    /**
     * Concatenate an array of segments, removing empties.
     */
    protected function concatenate(array $segments): string
    {
        // return implode('', array_filter($segments, function ($value) {
        //     return (string) $value !== '';
        // }));
        $uri = '';
        foreach ($segments as $segment => $value) {
            if ((string) $value !== '') {
                $uri .= strpos($uri, '?$') ? '&' . $value : $value;
            }
        }
        return $uri;
    }

    /**
     * Remove the leading boolean from a statement.
     */
    protected function removeLeadingBoolean(string $value): string
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    public function getOperators(): array
    {
        return $this->operators;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getOperatorsAndFunctions(): array
    {
        return array_merge($this->operators, $this->functions);
    }

    /**
     * Get the OData operator for the passed operator
     */
    protected function getOperatorMapping(string $operator): string
    {
        if (array_key_exists($operator, $this->operatorMapping)) {
            return $this->operatorMapping[$operator];
        }
        return $operator;
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value): string
    {
        //$value = $this->parameter($value);

        // stringify all values if it has NOT an odata enum or special syntax primitive data type
        // (ex. Microsoft.OData.SampleService.Models.TripPin.PersonGender Female or datetime'1970-01-01T00:00:00')
        if (!preg_match("/^(\w+\.)+(\w+)('\w+')$/", $value) && !$this->isSpecialPrimitiveDataType($value)) {
            // Check if the value is a string and NOT a date
            if (is_string($value) && !\DateTime::createFromFormat('Y-m-d\TH:i:sT', $value)) {
                $value = "'".$value."'";
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parameter($value): string
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * @inheritdoc
     */
    public function isExpression($value): bool
    {
        return $value instanceof Expression;
    }

    /**
     * @inheritdoc
     */
    public function getValue($expression): string
    {
        return $expression->getValue();
    }

    /**
     * Compile a nested where clause.
     */
    protected function whereNested(Builder $query, array $where): string
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "$filter=" of queries.
        // $offset = $query instanceof JoinClause ? 3 : 6;
        $wheres = $this->compileWheres($where['query']);
        $offset = (str_starts_with($wheres, '&')) ? 9 : 8;
        return '('.substr($wheres, $offset).')';
    }

    /**
     * Compile a "where null" clause.
     */
    protected function whereNull(Builder $query, array  $where): string
    {
        return $where['column'] . ' eq null';
    }

    /**
     * Compile a "where not null" clause.
     */
    protected function whereNotNull(Builder $query, array  $where): string
    {
        return $where['column'] . ' ne null';
    }

    /**
     * Compile a "where in" clause.
     */
    protected function whereIn(Builder $query, array $where): string
    {
        return $where['column'] . ' in (\'' . implode('\',\'', $where['list'])  . '\')';
    }

    /**
     * Compile a "where not in" clause.
     */
    protected function whereNotIn(Builder $query, array $where): string
    {
        return 'not(' . $where['column'] . ' in (\'' . implode('\',\'', $where['list'])  . '\'))';
    }

    /**
     * Append query param to existing uri
     */
    private function appendQueryParam(string $value): string
    {
        //$param = $this->isFirstQueryParam ? $value : '&' . $value;
        //$this->isFirstQueryParam = false;
        return $value;
    }
}
