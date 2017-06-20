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
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'contains', 'startswith', 'endswith',
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
        //'expand',
        //'search',
        'orders',
        'skip',
        'take',
        'totalCount',
    ];

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

        $entityKey = $this->wrapKey($entityKey);

        return "($entityKey)";
    }

    protected function compileQueryString(Builder $query, $queryString)
    {
        if (isset($query->entitySet) 
            && (
                !empty($query->properties)
                || isset($query->wheres)
                || isset($query->orders)
                || isset($query->expand)
                || isset($query->take)
                || isset($query->skip)
            )) {
            return $queryString;
        }
        return '';
    }

    protected function wrapKey($entityKey)
    {
        if (is_uuid($entityKey) || is_numeric($entityKey)) {
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
            $select = '$select='.$this->columnize($properties);
        }
        
        return $select;
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
        $conjunction = '$filter=';

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
        //$value = $this->parameter($where['value']);
        $value = "'".$where['value']."'";

        return $where['column'].' '.$this->getOperatorMapping($where['operator']).' '.$value;
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
            return '$orderby='.implode(',', $this->compileOrdersToArray($query, $orders));
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
        return '$top='.(int) $take;
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
        return '$skip='.(int) $skip;
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
        return '$count=true';
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
        return implode('', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
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
        $offset = 8;

        return '('.substr($this->compileWheres($where['query']), $offset).')';
    }
}
