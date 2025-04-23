<?php

namespace Studiosystems\OData\Query;

use Closure;
use InvalidArgumentException;

class ExpandClause extends Builder
{
    /**
     * The property to be expanded
     */
    public string $property;

    /**
     * The parent query builder instance.
     */
    private Builder $parentQuery;

    /**
     * Create a new expand clause instance.
     */
    public function __construct(Builder $parentQuery, string $property)
    {
        $this->property = $property;
        $this->parentQuery = $parentQuery;

        parent::__construct(
            $parentQuery->getConnection(),
            $parentQuery->getGrammar(),
            $parentQuery->getProcessor()
        );
    }

    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`
     *
     * @throws InvalidArgumentException
     */
    public function on(Closure|string $first, ?string $operator = null, ?string $second = null, string $boolean = 'and'): self
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     */
    public function orOn(Closure|string $first, ?string $operator = null, ?string $second = null): ExpandClause
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return ExpandClause
     */
    public function newQuery(): static
    {
        return new static($this->parentQuery, $this->property);
    }
}
