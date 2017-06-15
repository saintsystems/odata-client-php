<?php

namespace SaintSystems\OData\Query;

use Closure;

class ExpandClause extends Builder
{
    /**
     * The property to be expanded
     *
     * @var string
     */
    public $property;

    /**
     * The parent query builder instance.
     *
     * @var Builder
     */
    private $parentQuery;

    /**
     * Create a new expand clause instance.
     *
     * @param Builder $parentQuery
     * @param string  $property
     */
    public function __construct(Builder $parentQuery, $property)
    {
        $this->property = $property;
        $this->parentQuery = $parentQuery;

        parent::__construct(
            $parentQuery->getConnection(), $parentQuery->getGrammar(), $parentQuery->getProcessor()
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
     * @param \Closure|string $first
     * @param string|null     $operator
     * @param string|null     $second
     * @param string          $boolean
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param \Closure|string $first
     * @param string|null     $operator
     * @param string|null     $second
     *
     * @return ExpandClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return ExpandClause
     */
    public function newQuery()
    {
        return new static($this->parentQuery, $this->property);
    }
}
