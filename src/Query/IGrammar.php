<?php

namespace Studiosystems\OData\Query;

use Illuminate\Contracts\Database\Query\Expression;

interface IGrammar
{
    /**
     * Compile a select query into OData Uri
     */
    public function compileSelect(Builder $query): string;

    /**
     * Get the grammar specific operators.
     */
    public function getOperators(): array;

    /**
     * Get the grammar specific functions.
     */
    public function getFunctions(): array;

    /**
     * Get the grammar specific operators and functions.
     */
    public function getOperatorsAndFunctions(): array;

    /**
     * Prepare the appropriate URI value for a where value.
     */
    public function prepareValue(mixed $value): string;

    /**
     * Get the appropriate query parameter place-holder for a value.
     */
    public function parameter(mixed $value): string;

    /**
     * Determine if the given value is a raw expression.
     */
    public function isExpression(mixed $value): bool;

    /**
     * Get the value of a raw expression.
     */
    public function getValue(Expression $expression): string;

    /**
     * Convert an array of property names into a delimited string.
     */
    public function columnize(array $properties): string;
}
