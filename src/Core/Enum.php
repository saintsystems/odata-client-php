<?php

/**
* Copyright (c) Saint Systems, LLC.  All Rights Reserved.
* Licensed under the MIT License.  See License in the project root
* for license information.
*
* Enum File
* PHP version 7
*
* @category  Library
* @package   SaintSystems.OData
* @copyright 2017 Saint Systems, LLC
* @license   https://opensource.org/licenses/MIT MIT License
* @version   GIT: 0.1.0
*/

namespace Studiosystems\OData\Core;

use ReflectionClass;
use Studiosystems\OData\Exception\ApplicationException;

/**
 * Class Enum
 *
 * @category Library
 * @package  SaintSystems.OData
 * @license  https://opensource.org/licenses/MIT MIT License
 */
abstract class Enum
{
    private static array $constants = [];

    private string $_value;


    public function __construct(string $value)
    {
        if (!self::has($value)) {
            throw new ApplicationException("Invalid enum value $value");
        }
        $this->_value = $value;
    }

    public function has(string $value): bool
    {
        return in_array($value, self::toArray(), true);
    }

    public function is(string $value): bool
    {
        return $this->_value === $value;
    }

    public function toArray(): mixed
    {
        $class = get_called_class();

        if (!(array_key_exists($class, self::$constants))) {
            $reflectionObj = new ReflectionClass($class);
            self::$constants[$class] = $reflectionObj->getConstants();
        }
        return self::$constants[$class];
    }

    public function value(): string
    {
        return $this->_value;
    }
}
