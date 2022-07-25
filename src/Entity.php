<?php
/**
* Copyright (c) Saint Systems, LLC.  All Rights Reserved.  Licensed under the MIT License.  See License in the project root for license information.
*
* OData Entity File
* PHP version 7
*
* @category  Library
* @package   SaintSystems.OData
* @copyright 2017 Saint Systems, LLC
* @license   https://opensource.org/licenses/MIT MIT License
* @version   GIT: 0.1.0
*/
namespace SaintSystems\OData;

// use Closure;
// use Exception;
use ArrayAccess;
use Carbon\CarbonInterface;
// use LogicException;
// use JsonSerializable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Date;
use SaintSystems\OData\Exception\MassAssignmentException;

/**
* Entity class
*
* @package   SaintSystems.OData
* @copyright 2017 Saint Systems, LLC
* @license   https://opensource.org/licenses/MIT MIT License
* @version   Release: 0.1.0
*/
class Entity implements ArrayAccess, Arrayable
{
    /**
     * The entity set name associated with the entity.
     *
     * @var string
     */
    // protected $entity;

    /**
     * The primary key for the entity.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the entity key.
     * @var string
     */
    // protected $keyType = 'int';

    /**
     * @var array
     */
    private static $globalScopes;

    /**
     * The number of entities to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    /**
     * The array of properties available
     * to the model
     *
     * @var array(string => string)
     */
    protected $properties = [];

    /**
     * The model property's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The properties that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The properties that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The properties that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The properties that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];//['*'];

    /**
     * The properties that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * The properties that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The relations to eager load on every call.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The array of booted entities.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = false;

    /**
     * The cache of the mutated properties for each class.
     *
     * @var array
     */
    protected static $mutatorCache = [];

    /**
     * @var bool
     */
    private $exists;

    /**
     * @var string
     */
    private $entity;

    /**
    * Construct a new Entity
    *
    * @param array $properties A list of properties to set
    *
    * @return Entity
    */
    function __construct($properties = array())
    {
        $this->bootIfNotBooted();

        $this->syncOriginal();

        $this->fill($properties);

        return $this;
    }

    /**
     * Check if the entity needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            // $this->fireModelEvent('booting', false);

            static::boot();

            // $this->fireModelEvent('booted', false);
        }
    }

    /**
     * The "booting" method of the entity.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the entity.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the list of booted entities so they will be re-booted.
     *
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];
        static::$globalScopes = [];
    }

    /**
     * Fill the entity with an array of properties.
     *
     * @param  array  $properties
     * @return $this
     *
     * @throws MassAssignmentException
     */
    public function fill(array $properties)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($properties) as $key => $value) {
            // $key = $this->removeTableFromKey($key);

            // The developers may choose to place some properties in the "fillable"
            // array, which means only those properties may be set through mass
            // assignment to the model, and all others will just be ignored.
            if ($this->isFillable($key)) {
                $this->setProperty($key, $value);
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException($key);
            }
        }

        return $this;
    }

    /**
     * Fill the model with an array of properties. Force mass assignment.
     *
     * @param  array  $properties
     * @return $this
     */
    public function forceFill(array $properties)
    {
        return static::unguarded(function () use ($properties) {
            return $this->fill($properties);
        });
    }

    /**
     * Get the fillable properties of a given array.
     *
     * @param  array  $properties
     * @return array
     */
    protected function fillableFromArray(array $properties)
    {
        if (count($this->getFillable()) > 0 && ! static::$unguarded) {
            return array_intersect_key($properties, array_flip($this->getFillable()));
        }

        return $properties;
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $properties
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($properties = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static((array) $properties);

        $model->exists = $exists;

        return $model;
    }

    /**
     * Get the entity name associated with the entity.
     *
     * @return string
     */
    public function getEntity()
    {
        if (isset($this->entity)) {
            return $this->entity;
        }

        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }

    /**
     * Set the entity name associated with the model.
     *
     * @param string $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get the value of the entity's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the primary key for the entity.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the entity.
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Get the number of entities to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of entities to return per page.
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Get the hidden properties for the model.
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden properties for the model.
     *
     * @param  array  $hidden
     * @return $this
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Add hidden properties for the model.
     *
     * @param  array|string|null  $properties
     * @return void
     */
    public function addHidden($properties = null)
    {
        $properties = is_array($properties) ? $properties : func_get_args();

        $this->hidden = array_merge($this->hidden, $properties);
    }

    /**
     * Make the given, typically hidden, properties visible.
     *
     * @param  array|string  $properties
     * @return $this
     */
    public function makeVisible($properties)
    {
        $this->hidden = array_diff($this->hidden, (array) $properties);

        if (! empty($this->visible)) {
            $this->addVisible($properties);
        }

        return $this;
    }

    /**
     * Make the given, typically visible, properties hidden.
     *
     * @param  array|string  $properties
     * @return $this
     */
    public function makeHidden($properties)
    {
        $properties = (array) $properties;

        $this->visible = array_diff($this->visible, $properties);

        $this->hidden = array_unique(array_merge($this->hidden, $properties));

        return $this;
    }

    /**
     * Get the visible properties for the model.
     *
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible properties for the model.
     *
     * @param  array  $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Add visible properties for the model.
     *
     * @param  array|string|null  $properties
     * @return void
     */
    public function addVisible($properties = null)
    {
        $properties = is_array($properties) ? $properties : func_get_args();

        $this->visible = array_merge($this->visible, $properties);
    }

    /**
     * Set the accessors to append to entity arrays.
     *
     * @param  array  $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;

        return $this;
    }

    /**
     * Get the mutated properties for a given instance.
     *
     * @return array
     */
    public function getMutatedProperties()
    {
        $class = static::class;

        if (!isset(static::$mutatorCache[$class])) {
            static::cacheMutatedProperties($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated properties of a class.
     *
     * @param  string  $class
     * @return void
     */
    public static function cacheMutatedProperties($class)
    {
        static::$mutatorCache[$class] = collect(static::getMutatorMethods($class))->map(function ($match) {
            return lcfirst(static::$snakePropreties ? Str::snake($match) : $match);
        })->all();
    }

    /**
     * Get all of the property mutator methods.
     *
     * @param  mixed  $class
     * @return array
     */
    protected static function getMutatorMethods($class)
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Property(;|$)/', implode(';', get_class_methods($class)), $matches);

        return $matches[1];
    }

    /**
     * Get the fillable properties for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set the fillable properties for the model.
     *
     * @param  array  $fillable
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the guarded properties for the model.
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    /**
     * Set the guarded properties for the model.
     *
     * @param  array  $guarded
     * @return $this
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;

        return $this;
    }

    /**
     * Disable all mass assignable restrictions.
     *
     * @param  bool  $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
    }

    /**
     * Enable the mass assignment restrictions.
     *
     * @return void
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    /**
     * Determine if current state is "unguarded".
     *
     * @return bool
     */
    public static function isUnguarded()
    {
        return static::$unguarded;
    }

    /**
     * Run the given callable while being unguarded.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }

        static::unguard();

        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        if (static::$unguarded) {
            return true;
        }

        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
        if (in_array($key, $this->getFillable())) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->getFillable()) && ! Str::startsWith($key, '_');
    }

    /**
     * Determine if the given key is guarded.
     *
     * @param  string  $key
     * @return bool
     */
    public function isGuarded($key)
    {
        return in_array($key, $this->getGuarded()) || $this->getGuarded() == ['*'];
    }

    /**
     * Determine if the model is totally guarded.
     *
     * @return bool
     */
    public function totallyGuarded()
    {
        return count($this->getFillable()) == 0 && $this->getGuarded() == ['*'];
    }

    /**
    * Gets the property dictionary of the Entity
    *
    * @return array The list of properties
    */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set the array of entity properties. No checking is done.
     *
     * @param  array  $properties
     * @param  bool  $sync
     * @return $this
     */
    public function setRawProperties(array $properties, $sync = false)
    {
        $this->properties = $properties;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Get the entity's original property values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed|array
     */
    public function getOriginal($key = null, $default = null)
    {
        return Arr::get($this->original, $key, $default);
    }

    /**
     * Sync the original properties with the current.
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->properties;

        return $this;
    }

    /**
     * Sync a single original property with its current value.
     *
     * @param  string  $property
     * @return $this
     */
    public function syncOriginalProperty($property)
    {
        $this->original[$property] = $this->properties[$property];

        return $this;
    }

    /**
     * Dynamically retrieve properties on the entity.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Dynamically set properties on the entity.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setProperty($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Determine if a property or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getProperty($key));
    }

    /**
     * Unset a property on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->properties[$key], $this->relations[$key]);
    }

    /**
     * Determine whether a property should be cast to a native type.
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        // if ($this->getIncrementing()) {
        //     return array_merge([
        //         $this->getKeyName() => $this->keyType,
        //     ], $this->casts);
        // }

        return $this->casts;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        return $this->hasCast($key, ['date', 'datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Get the type of cast for a entity property.
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Cast a property to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castProperty($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->fromFloat($value);
            case 'decimal':
                return $this->asDecimal($value, explode(':', $this->getCasts()[$key], 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            //case 'collection':
                //return new BaseCollection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
            case 'custom_datetime':
                return $this->asDateTime($value);
            case 'timestamp':
                return $this->asTimeStamp($value);
            default:
                return $value;
        }
    }

    /**
     * Set a given property on the entity.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setProperty($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the property as it is set on
        // the entity, such as "json_encoding" a listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Property';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && (in_array($key, $this->getDates()) || $this->isDateCastable($key))) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isJsonCastable($key) && ! is_null($value)) {
            $value = $this->asJson($value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Determine if a set mutator exists for a property.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Property');
    }

    /**
     * Get the properties that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        // $defaults = [static::CREATED_AT, static::UPDATED_AT];

        //return $this->timestamps ? array_merge($this->dates, $defaults) : $this->dates;
        return $this->dates;
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDate($value)
    {
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }
        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }
        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }
        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }
        $format = $this->getDateFormat();
        // https://bugs.php.net/bug.php?id=75577
        if (version_compare(PHP_VERSION, '7.3.0-dev', '<')) {
            $format = str_replace('.v', '.u', $format);
        }
        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Date::createFromFormat($format, $value);
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateFormat()
        );
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimeStamp($value)
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->getDateFormat());
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat;// ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Set the date format used by the model.
     *
     * @param  string  $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Encode the given value as JSON.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function fromFloat($value)
    {
        switch ((string) $value) {
            case 'Infinity':
                return INF;
            case '-Infinity':
                return -INF;
            case 'NaN':
                return NAN;
            default:
                return (float) $value;
        }
    }

    /**
     * Return a decimal as string.
     *
     * @param  float  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal($value, $decimals)
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->propertiesToArray(), $this->relationsToArray());
    }

    /**
     * Convert the model's properties to an array.
     *
     * @return array
     */
    public function propertiesToArray()
    {
        // If a property is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing properties vs. arraying / JSONing a model.
        $properties = $this->addDatePropertiesToArray(
            $properties = $this->getArrayableProperties()
        );

        $properties = $this->addMutatedPropertiesToArray(
            $properties,
            $mutatedProperties = $this->getMutatedProperties()
        );

        // Next we will handle any casts that have been setup for this entity and cast
        // the values to their appropriate type. If the property has a mutator we
        // will not perform the cast on those properties to avoid any confusion.
        $properties = $this->addCastPropertiesToArray(
            $properties,
            $mutatedProperties
        );

        // Here we will grab all of the appended, calculated properties to this model
        // as these properties are not really in the properties array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $properties[$key] = $this->mutatePropertyForArray($key, null);
        }

        return $properties;
    }

    /**
     * Add the date properties to the properties array.
     *
     * @param  array  $properties
     * @return array
     */
    protected function addDatePropertiesToArray(array $properties)
    {
        foreach ($this->getDates() as $key) {
            if (! isset($properties[$key])) {
                continue;
            }

            $properties[$key] = $this->serializeDate(
                $this->asDateTime($properties[$key])
            );
        }

        return $properties;
    }

    /**
     * Add the mutated properties to the properties array.
     *
     * @param  array  $properties
     * @param  array  $mutatedProperties
     * @return array
     */
    protected function addMutatedPropertiesToArray(array $properties, array $mutatedProperties)
    {
        foreach ($mutatedProperties as $key) {
            // We want to spin through all the mutated properties for this model and call
            // the mutator for the properties. We cache off every mutated properties so
            // we don't have to constantly check on properties that actually change.
            if (! array_key_exists($key, $properties)) {
                continue;
            }

            // Next, we will call the mutator for this properties so that we can get these
            // mutated property's actual values. After we finish mutating each of the
            // properties we will return this final array of the mutated properties.
            $properties[$key] = $this->mutatePropertyForArray(
                $key, $properties[$key]
            );
        }

        return $properties;
    }

    /**
     * Add the casted properties to the properties array.
     *
     * @param  array  $properties
     * @param  array  $mutatedProperties
     * @return array
     */
    protected function addCastPropertiesToArray(array $properties, array $mutatedProperties)
    {
        foreach ($this->getCasts() as $key => $value) {
            if (! array_key_exists($key, $properties) || in_array($key, $mutatedProperties)) {
                continue;
            }

            // Here we will cast the property. Then, if the cast is a date or datetime cast
            // then we will serialize the date for the array. This will convert the dates
            // to strings based on the date format specified for these Entity models.
            $properties[$key] = $this->castProperty(
                $key, $properties[$key]
            );

            // If the property cast was a date or a datetime, we will serialize the date as
            // a string. This allows the developers to customize how dates are serialized
            // into an array without affecting how they are persisted into the storage.
            if ($properties[$key] &&
                ($value === 'date' || $value === 'datetime')) {
                $properties[$key] = $this->serializeDate($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * Get a property array of all arrayable properties.
     *
     * @return array
     */
    protected function getArrayableProperties()
    {
        return $this->getArrayableItems($this->properties);
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $properties = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // properties since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation property is snake cased in this returned
            // array to the developers, making this consistent with properties.
            // if (static::$snakeproperties) {
            //     $key = Str::snake($key);
            // }

            // If the relation value has been set, we will set it on this properties
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $properties[$key] = $relation;
            }

            unset($relation);
        }

        return $properties;
    }

    /**
     * Get a property array of all arrayable relations.
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get a property array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    /**
     * Get a property from the entity.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getProperty($key)
    {
        if (! $key) {
            return;
        }

        if ($key === 'id') {
            $key = $this->primaryKey;
        }

        // If the property exists in the properties array or has a "get" mutator we will
        // get the property's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (array_key_exists($key, $this->properties) ||
            $this->hasGetMutator($key)) {
            return $this->getPropertyValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return;
        }

        // return $this->getRelationValue($key);
        return null;
    }

    /**
     * Get a plain property (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getPropertyValue($key)
    {
        $value = $this->getPropertyFromArray($key);

        // If the property has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            return $this->mutateProperty($key, $value);
        }

        // If the property exists within the cast array, we will convert it to
        // an appropriate native PHP type dependant upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
        if ($this->hasCast($key)) {
            return $this->castProperty($key, $value);
        }

        // If the property is listed as a date, we will convert it to a DateTime
        // instance on retrieval, which makes it quite convenient to work with
        // date fields without having to create a mutator for each property.
        if (in_array($key, $this->getDates()) &&
            ! is_null($value)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * Get a property from the $properties array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getPropertyFromArray($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
    }

    /**
     * Determine if a get mutator exists for a property.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Property');
        //return method_exists($this, 'get_'.$key);
    }

    /**
     * Get the value of a property using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateProperty($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Property'}($value);
        // return $this->{'get_'.$key}($value);
    }

    /**
     * Get the value of a property using its mutator for array conversion.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutatePropertyForArray($key, $value)
    {
        $value = $this->mutateProperty($key, $value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }
}
