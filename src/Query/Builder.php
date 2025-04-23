<?php

namespace Studiosystems\OData\Query;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use stdClass;
use Studiosystems\OData\Constants;
use Studiosystems\OData\Exception\ODataQueryException;
use Studiosystems\OData\IODataClient;
use Studiosystems\OData\IODataRequest;
use Studiosystems\OData\QueryOptions;

class Builder
{
    /**
     * Gets the IODataClient for handling requests.
     */
    public IODataClient $client;

    /**
     * Gets the URL for the built request, without query string.
     */
    public string $requestUrl;

    /**
     * Gets the URL for the built request, without query string.
     */
    public object $returnType;

    /**
     * The current query value bindings.
     */
    public array $bindings = [
        'select' => [],
        'where' => [],
        'order' => [],
    ];

    /**
     * The entity set which the query is targeting.
     */
    public string $entitySet;

    /**
     * The entity key of the entity set which the query is targeting.
     */
    public string $entityKey;

    /**
     * The placeholder property for the ? operator in the OData querystring
     */
    public string $queryString = '?';

    /**
     * An aggregate function to be run.
     */
    public bool $count;

    /**
     * Whether to include a total count of items matching
     * the request be returned along with the result
     */
    public bool $totalCount;

    /**
     * The specific set of properties to return for this entity or complex type
     */
    public array $properties;

    /**
     * The where constraints for the query.
     */
    public array $wheres;

    /**
     * The groupings for the query.
     */
    public array $groups;

    /**
     * The orderings for the query.
     */
    public array $orders;

    /**
     * The maximum number of records to return.
     */
    public int $take;

    /**
     * The desired page size.
     */
    public int $pageSize;

    /**
     * The number of records to skip.
     */
    public int $skip;

    /**
     * The skiptoken.
     */
    public int $skiptoken;

    /**
     * All the available clause operators.
     */
    public array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    public array $select = [];

    public array $expands;

    private IProcessor $processor;

    private IGrammar $grammar;

    /**
     * Create a new query builder instance.
     */
    public function __construct(
        IODataClient $client,
        ?IGrammar $grammar = null,
        ?IProcessor $processor = null
    ) {
        $this->client = $client;
        $this->grammar = $grammar ?? $client->getQueryGrammar();
        $this->processor = $processor ?? $client->getPostProcessor();
    }

    /**
     * Set the properties to be selected.
     */
    public function select(mixed $properties = []): static
    {
        $this->properties = is_array($properties) ? $properties : func_get_args();
        return $this;
    }

    /**
     * Add a new properties to the $select query option.
     */
    public function addSelect(mixed $select): static
    {
        $select = is_array($select) ? $select : func_get_args();
        $this->select = array_merge($this->select, $select);
        return $this;
    }

    /**
     * Set the entity set which the query is targeting.
     */
    public function from(string $entitySet): static
    {
        $this->entitySet = $entitySet;
        return $this;
    }

    /**
     * Filter the entity set on the primary key.
     */
    public function whereKey(string $id): static
    {
        $this->entityKey = $id;
        $this->client->setEntityKey($this->entityKey);
        return $this;
    }

    /**
     * Add an $expand clause to the query.
     */
    public function expand(mixed $properties = []): static
    {
        $this->expands = is_array($properties) ? $properties : func_get_args();
        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     */
    public function when(bool $value, Closure $callback, ?Closure $default = null): static
    {
        $builder = $this;
        if ($value) {
            $builder = $callback($builder);
        } elseif ($default) {
            $builder = $default($builder);
        }
        return $builder;
    }

    /**
     * Set the properties to be ordered.
     */
    public function order(mixed $properties = []): static
    {
        $order = is_array($properties) && count(func_get_args()) === 1 ? $properties : func_get_args();
        if (!(isset($order[0]) && is_array($order[0]))) {
            $order = [$order];
        }
        $this->orders = $this->buildOrders($order);
        return $this;
    }

    /**
     * Set the sql property to be ordered.
     */
    public function orderBySQL(string $sql = ''): static
    {
        $this->orders = [['sql' => $sql]];
        return $this;
    }

    /**
     * Reformat array to match grammar structure
     */
    private function buildOrders(array $orders = []): array
    {
        $_orders = [];
        foreach ($orders as $order) {
            $column = $order['column'] ?? $order[0];
            $direction = $order['direction'] ?? ($order[1] ?? 'asc');
            $_orders[] = [
                'column' => $column,
                'direction' => $direction
            ];
        }
        return $_orders;
    }

    /**
     * Merge an array of where clauses and bindings.
     */
    public function mergeWheres(array $wheres, array $bindings): void
    {
        $this->wheres = array_merge($this->wheres, $wheres);
        $this->bindings['where'] = array_values(
            array_merge($this->bindings['where'], $bindings)
        );
    }

    /**
     * Add a basic where ($filter) clause to the query.
     */
    public function where(string|array|Closure $column, ?string $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() == 2
        );
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator != '=');
        }
        $type = 'Basic';
        if ($this->isOperatorAFunction($operator)) {
            $type = 'Function';
        }
        $this->wheres[] = compact(
            'type',
            'column',
            'operator',
            'value',
            'boolean'
        );
        if (! $value instanceof Expression) {
            $this->addBinding($value);
        }
        return $this;
    }

    /**
     * Add an array of where clauses to the query.
     */
    protected function addArrayOfWheres(array $column, string $boolean, string $method = 'where'): static
    {
        return $this->whereNested(function ($query) use ($column, $method) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->$method($key, '=', $value);
                }
            }
        }, $boolean);
    }

    /**
     * Determine if the given operator is actually a function.
     */
    protected function isOperatorAFunction(string $operator): bool
    {
        return in_array(strtolower($operator), $this->grammar->getFunctions(), true);
    }

    /**
     * Prepare the value and operator for a where clause.
     * @throws InvalidArgumentException
     */
    protected function prepareValueAndOperator(string $value, string $operator, bool $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }
        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     * Prevents using Null values with invalid operators.
     */
    protected function invalidOperatorAndValue(string $operator, mixed $value): bool
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Determine if the given operator is supported.
     */
    protected function invalidOperator(string $operator): bool
    {
        return ! in_array(strtolower($operator), $this->operators, true) &&
            ! in_array(strtolower($operator), $this->grammar->getOperatorsAndFunctions(), true);
    }

    /**
     * Add an "or where" clause to the query.
     */
    public function orWhere(string|Closure $column, ?string $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereRaw(string $rawString, string $boolean = 'and'): static
    {
        $type = 'Raw';
        $this->wheres[] = compact(
            'type',
            'rawString',
            'boolean'
        );
        return $this;
    }

    public function orWhereRaw(string $rawString): static
    {
        return $this->whereRaw($rawString, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     */
    public function whereColumn(string|array $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): static
    {
        if (is_array($first)) {
            return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
        }
        [$second, $operator] = $this->prepareValueAndOperator(
            $second,
            $operator,
            func_num_args() == 2
        );
        if ($this->invalidOperator($operator)) {
            [$second, $operator] = [$operator, '='];
        }
        $type = 'Column';
        $this->wheres[] = compact(
            'type',
            'first',
            'operator',
            'second',
            'boolean'
        );
        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     */
    public function orWhereColumn(string|array $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    /**
     * Add a nested where statement to the query.
     */
    public function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $callback($query = $this->forNestedWhere());
        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Create a new query instance for nested where condition.
     */
    public function forNestedWhere(): static
    {
        return $this->newQuery()->from($this->entitySet);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     */
    public function addNestedWhereQuery(Builder $query, string $boolean = 'and'): static
    {
        if (count($query->wheres)) {
            $type = 'Nested';
            $this->wheres[] = compact('type', 'query', 'boolean');
            $this->addBinding($query->getBindings(), 'where');
        }
        return $this;
    }

    /**
     * Add a full sub-select to the query.
     */
    protected function whereSub(string $column, string $operator, Closure $callback, string $boolean): static
    {
        $type = 'Sub';
        $callback($query = $this->newQuery());
        $this->wheres[] = compact(
            'type',
            'column',
            'operator',
            'query',
            'boolean'
        );
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'NotNull' : 'Null';
        $this->wheres[] = compact('type', 'column', 'boolean');
        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     */
    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
     */
    public function whereNotNull(string $column, string $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add an "or where not null" clause to the query.
     */
    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     */
    public function whereIn(string $column, array $list, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'NotIn' : 'In';
        $this->wheres[] = compact('type', 'column', 'list', 'boolean');
        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     */
    public function orWhereIn(string $column, array $list): static
    {
        return $this->whereIn($column, $list, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     */
    public function whereNotIn(string $column, array $list, string $boolean = 'and'): static
    {
        return $this->whereIn($column, $list, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     */
    public function orWhereNotIn(string $column, array $list): static
    {
        return $this->whereNotIn($column, $list, 'or');
    }

    /**
     * Get the HTTP Request representation of the query.
     */
    public function toRequest(): string
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute a query for a single record by ID. Single and multipart IDs are supported.
     * @throws ODataQueryException
     */
    public function find(int|string|array $id, array $properties = []): stdClass|array|null
    {
        if (!isset($this->entitySet)) {
            throw new ODataQueryException(Constants::ENTITY_SET_REQUIRED);
        }
        return $this->whereKey($id)->first($properties);
    }

    /**
     * Get a single property's value from the first result of a query.
     */
    public function value(string $property): mixed
    {
        $result = (array) $this->first([$property]);
        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result.
     */
    public function first(array $properties = []): stdClass|array|null
    {
        return $this->take(1)->get($properties)->first();
    }

    /**
     * Set the "$skip" value of the query.
     */
    public function skip(int $value): static
    {
        $this->skip = $value;
        return $this;
    }

    /**
     * Set the "$skiptoken" value of the query.
     */
    public function skipToken(int $value): static
    {
        $this->skiptoken = $value;
        return $this;
    }

    /**
     * Set the "$top" value of the query.
     */
    public function take(int $value): static
    {
        $this->take = $value;
        return $this;
    }

    /**
     * Set the desired pagesize of the query;
     */
    public function pageSize(int $value): static
    {
        $this->pageSize = $value;
        $this->client->setPageSize($this->pageSize);
        return $this;
    }

    /**
     * Execute the query as a "GET" request.
     */
    public function get(array $properties = [], ?array $options = null): Collection
    {
        if (is_numeric($properties)) {
            $options = $properties;
            $properties = [];
        }
        if (isset($options)) {
            $include_count = $options & QueryOptions::INCLUDE_COUNT;
            if ($include_count) {
                $this->totalCount = true;
            }
        }
        $original = $this->properties;
        if (is_null($original)) {
            $this->properties = $properties;
        }
        $results = $this->processor->processSelect($this, $this->runGet());
        $this->properties = $original;
        return collect($results);
    }

    /**
     * Execute the query as a "POST" request.
     */
    public function post(array $body = [], array $properties = [], ?array $options = null): Collection
    {
        if (is_numeric($properties)) {
            $options = $properties;
            $properties = [];
        }
        if (isset($options)) {
            $include_count = $options & QueryOptions::INCLUDE_COUNT;
            if ($include_count) {
                $this->totalCount = true;
            }
        }
        $original = $this->properties;
        if (is_null($original)) {
            $this->properties = $properties;
        }
        $results = $this->processor->processSelect($this, $this->runPost($body));
        $this->properties = $original;
        return collect($results);
    }

    /**
     * Execute the query as a "DELETE" request.
     */
    public function delete(?array $options = null): bool
    {
        $results = $this->processor->processSelect($this, $this->runDelete());
        return true;
    }

    /**
     * Execute the query as a "PATCH" request.
     */
    public function patch(array $body, array $properties = [], ?array $options = null): Collection
    {
        if (is_numeric($properties)) {
            $options = $properties;
            $properties = [];
        }
        if (isset($options)) {
            $include_count = $options & QueryOptions::INCLUDE_COUNT;
            if ($include_count) {
                $this->totalCount = true;
            }
        }
        $original = $this->properties;
        if (is_null($original)) {
            $this->properties = $properties;
        }
        $results = $this->processor->processSelect($this, $this->runPatch($body));
        $this->properties = $original;
        return collect($results);
    }

    /**
     * Run the query as a "GET" request against the client.
     * @return IODataRequest
     */
    protected function runGet(): IODataRequest
    {
        return $this->client->get(
            $this->grammar->compileSelect($this),
            $this->getBindings()
        );
    }

    /**
     * Get a lazy collection for the given request.
     */
    public function cursor(): LazyCollection
    {
        return new LazyCollection(function () {
            yield from $this->client->cursor(
                $this->grammar->compileSelect($this),
                $this->getBindings()
            );
        });
    }

    /**
     * Run the query as a "GET" request against the client.
     */
    protected function runPatch(array $body): IODataRequest
    {
        return $this->client->patch(
            $this->grammar->compileSelect($this),
            $body
        );
    }

    /**
     * Run the query as a "GET" request against the client.
     */
    protected function runPost(array $body): IODataRequest
    {
        return $this->client->post(
            $this->grammar->compileSelect($this),
            $body
        );
    }

    /**
     * Run the query as a "GET" request against the client.
     */
    protected function runDelete(): IODataRequest
    {
        return $this->client->delete(
            $this->grammar->compileSelect($this)
        );
    }

    /**
     * Retrieve the "count" result of the query.
     */
    public function count(): int
    {
        $this->count = true;
        $results = $this->get();
        if (! $results->isEmpty()) {
            return (int) preg_replace('/[^0-9,.]/', '', $results[0]);
        }
        return 0;
    }

    /**
     * Insert a new record into the database.
     */
    public function insert(array $values): bool|IODataRequest
    {
        if (empty($values)) {
            return true;
        }
        if (! is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }
        return $this->client->post(
            $this->grammar->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     */
    public function insertGetId(array $values): mixed
    {
        $results = $this->insert($values);
        return $results->getId();
    }

    /**
     * Get a new instance of the query builder.
     */
    public function newQuery(): static
    {
        return new static($this->client, $this->grammar, $this->processor);
    }

    /**
     * Get the current query value bindings in a flattened array.
     * @return array
     */
    public function getBindings(): array
    {
        return Arr::flatten($this->bindings);
    }

    /**
     * Remove all the expressions from a list of bindings.
     * @param array $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings): array
    {
        return array_values(array_filter($bindings, function ($binding) {
            return true;
        }));
    }

    /**
     * Add a binding to the query.
     */
    public function addBinding(mixed $value, string $type = 'where'): static
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }
        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }
        return $this;
    }

    /**
     * Get the IODataClient instance.
     */
    public function getClient(): IODataClient
    {
        return $this->client;
    }
}
