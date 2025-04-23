<?php

declare(strict_types=1);

namespace Studiosystems\OData;

use ArrayAccess;
use Carbon\CarbonInterface;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Date;
use Studiosystems\OData\Exception\MassAssignmentException;

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
    protected string $primaryKey = 'id';
    private static array $globalScopes = [];
    protected int $perPage = 25;
    protected array $properties = [];
    protected array $original = [];
    protected array $relations = [];
    protected array $hidden = [];
    protected array $visible = [];
    protected array $appends = [];
    protected array $fillable = [];
    protected array $guarded = [];
    protected array $dates = [];
    protected string $dateFormat;
    protected array $casts = [];
    protected array $with = [];
    protected static array $booted = [];
    protected static bool $unguarded = false;
    protected static array $mutatorCache = [];
    private bool $exists = false;
    private string $entity;

    /**
     * @throws MassAssignmentException
     */
    public function __construct(array $properties = [])
    {
        $this->bootIfNotBooted();
        $this->syncOriginal();
        $this->fill($properties);
    }

    protected function bootIfNotBooted(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
        }
    }

    protected static function boot(): void
    {
        static::bootTraits();
    }

    protected static function bootTraits(): void
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot' . class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    public static function clearBootedModels(): void
    {
        static::$booted = [];
        static::$globalScopes = [];
    }

    /**
     * @throws MassAssignmentException
     */
    public function fill(array $properties): static
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($properties) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setProperty($key, $value);
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException($key);
            }
        }

        return $this;
    }

    public function forceFill(array $properties): static
    {
        return static::unguarded(/**
         * @throws MassAssignmentException
         */ fn () => $this->fill($properties));
    }

    protected function fillableFromArray(array $properties): array
    {
        if (count($this->getFillable()) > 0 && !static::$unguarded) {
            return array_intersect_key($properties, array_flip($this->getFillable()));
        }

        return $properties;
    }

    public function newInstance(array $properties = [], bool $exists = false): static
    {
        $model = new static($properties);
        $model->exists = $exists;

        return $model;
    }

    public function getEntity(): string
    {
        return $this->entity ?? str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }

    public function setEntity(string $entity): static
    {
        $this->entity = $entity;
        return $this;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function setKeyName(string $key): static
    {
        $this->primaryKey = $key;
        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function getHidden(): array
    {
        return $this->hidden;
    }

    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function addHidden(array|string|null $properties = null): void
    {
        $properties = is_array($properties) ? $properties : func_get_args();
        $this->hidden = array_merge($this->hidden, $properties);
    }

    public function makeVisible(array|string $properties): static
    {
        $this->hidden = array_diff($this->hidden, (array) $properties);

        if (!empty($this->visible)) {
            $this->addVisible($properties);
        }

        return $this;
    }

    public function makeHidden(array|string $properties): static
    {
        $properties = (array) $properties;
        $this->visible = array_diff($this->visible, $properties);
        $this->hidden = array_unique(array_merge($this->hidden, $properties));

        return $this;
    }

    public function getVisible(): array
    {
        return $this->visible;
    }

    public function setVisible(array $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function addVisible(array|string|null $properties = null): void
    {
        $properties = is_array($properties) ? $properties : func_get_args();
        $this->visible = array_merge($this->visible, $properties);
    }

    public function setAppends(array $appends): static
    {
        $this->appends = $appends;
        return $this;
    }

    public function getMutatedProperties(): array
    {
        $class = static::class;

        if (!isset(static::$mutatorCache[$class])) {
            static::cacheMutatedProperties($class);
        }

        return static::$mutatorCache[$class];
    }

    public static function cacheMutatedProperties(string $class): void
    {
        static::$mutatorCache[$class] = collect(static::getMutatorMethods($class))->map(fn ($match) => lcfirst(Str::snake($match)))->all();
    }

    protected static function getMutatorMethods(string $class): array
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Property(;|$)/', implode(';', get_class_methods($class)), $matches);
        return $matches[1];
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function fillable(array $fillable): static
    {
        $this->fillable = $fillable;
        return $this;
    }

    public function getGuarded(): array
    {
        return $this->guarded;
    }

    public function guard(array $guarded): static
    {
        $this->guarded = $guarded;
        return $this;
    }

    public static function unguard(bool $state = true): void
    {
        static::$unguarded = $state;
    }

    public static function reguard(): void
    {
        static::$unguarded = false;
    }

    public static function isUnguarded(): bool
    {
        return static::$unguarded;
    }

    public static function unguarded(callable $callback): mixed
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

    public function isFillable(string $key): bool
    {
        if (static::$unguarded) {
            return true;
        }

        if (in_array($key, $this->getFillable())) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->getFillable());
    }

    public function isGuarded(string $key): bool
    {
        return in_array($key, $this->getGuarded()) || $this->getGuarded() == ['*'];
    }

    public function totallyGuarded(): bool
    {
        return count($this->getFillable()) == 0 && $this->getGuarded() == ['*'];
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setRawProperties(array $properties, bool $sync = false): static
    {
        $this->properties = $properties;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function getOriginal(string|null $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->original, $key, $default);
    }

    public function syncOriginal(): static
    {
        $this->original = $this->properties;
        return $this;
    }

    public function syncOriginalProperty(string $property): static
    {
        $this->original[$property] = $this->properties[$property];
        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->getProperty($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setProperty($key, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }

    public function __isset(string $key): bool
    {
        return !is_null($this->getProperty($key));
    }

    public function __unset(string $key): void
    {
        unset($this->properties[$key], $this->relations[$key]);
    }

    public function hasCast(string $key, array|string|null $types = null): bool
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    protected function isDateCastable(string $key): bool
    {
        return $this->hasCast($key, ['date', 'datetime']);
    }

    protected function isJsonCastable(string $key): bool
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    protected function getCastType(string $key): string
    {
        return trim(strtolower($this->getCasts()[$key]));
    }

    protected function castProperty(string $key, mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return match ($this->getCastType($key)) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => $this->fromFloat($value),
            'decimal' => $this->asDecimal($value, (int) explode(':', $this->getCasts()[$key], 2)[1]),
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object' => $this->fromJson($value, true),
            'array', 'json' => $this->fromJson($value),
            'date' => $this->asDate($value),
            'datetime', 'custom_datetime' => $this->asDateTime($value),
            'timestamp' => $this->asTimeStamp($value),
            default => $value,
        };
    }

    public function setProperty(string $key, mixed $value): static
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set' . Str::studly($key) . 'Property';
            return $this->{$method}($value);
        }

        if ($value && (in_array($key, $this->getDates()) || $this->isDateCastable($key))) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isJsonCastable($key) && !is_null($value)) {
            $value = $this->asJson($value);
        }

        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->properties[$key] = $value;
        return $this;
    }

    public function hasSetMutator(string $key): bool
    {
        return method_exists($this, 'set' . Str::studly($key) . 'Property');
    }

    public function getDates(): array
    {
        return $this->dates;
    }

    protected function asDate(mixed $value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    protected function asDateTime(mixed $value): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        if ($value instanceof DateTimeInterface) {
            return Date::parse($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }

        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        return Date::createFromFormat($this->getDateFormat(), $value);
    }

    protected function isStandardDateFormat(string $value): bool
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    public function fromDateTime(mixed $value): ?string
    {
        return empty($value) ? $value : $this->asDateTime($value)->format($this->getDateFormat());
    }

    protected function asTimeStamp(mixed $value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format($this->getDateFormat());
    }

    protected function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $format): static
    {
        $this->dateFormat = $format;
        return $this;
    }

    protected function asJson(mixed $value): string
    {
        return json_encode($value);
    }

    public function fromJson(string $value, bool $asObject = false): mixed
    {
        return json_decode($value, !$asObject);
    }

    public function fromFloat(mixed $value): float
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    protected function asDecimal(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return array_merge($this->propertiesToArray(), $this->relationsToArray());
    }

    public function propertiesToArray(): array
    {
        $properties = $this->addDatePropertiesToArray($this->getArrayableProperties());
        $properties = $this->addMutatedPropertiesToArray($properties, $this->getMutatedProperties());
        $properties = $this->addCastPropertiesToArray($properties, $this->getMutatedProperties());

        foreach ($this->getArrayableAppends() as $key) {
            $properties[$key] = $this->mutatePropertyForArray($key, null);
        }

        return $properties;
    }

    protected function addDatePropertiesToArray(array $properties): array
    {
        foreach ($this->getDates() as $key) {
            if (!isset($properties[$key])) {
                continue;
            }

            $properties[$key] = $this->serializeDate($this->asDateTime($properties[$key]));
        }

        return $properties;
    }

    protected function addMutatedPropertiesToArray(array $properties, array $mutatedProperties): array
    {
        foreach ($mutatedProperties as $key) {
            if (!array_key_exists($key, $properties)) {
                continue;
            }

            $properties[$key] = $this->mutatePropertyForArray($key, $properties[$key]);
        }

        return $properties;
    }

    protected function addCastPropertiesToArray(array $properties, array $mutatedProperties): array
    {
        foreach ($this->getCasts() as $key => $value) {
            if (!array_key_exists($key, $properties) || in_array($key, $mutatedProperties)) {
                continue;
            }

            $properties[$key] = $this->castProperty($key, $properties[$key]);

            if ($properties[$key] && ($value === 'date' || $value === 'datetime')) {
                $properties[$key] = $this->serializeDate($properties[$key]);
            }
        }

        return $properties;
    }

    protected function getArrayableProperties(): array
    {
        return $this->getArrayableItems($this->properties);
    }

    protected function getArrayableAppends(): array
    {
        if (!count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(array_combine($this->appends, $this->appends));
    }

    public function relationsToArray(): array
    {
        $properties = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            } elseif (is_null($value)) {
                $relation = $value;
            }

            if (isset($relation) || is_null($value)) {
                $properties[$key] = $relation;
            }

            unset($relation);
        }

        return $properties;
    }

    protected function getArrayableRelations(): array
    {
        return $this->getArrayableItems($this->relations);
    }

    protected function getArrayableItems(array $values): array
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    public function getProperty(string $key): mixed
    {
        if (!$key) {
            return null;
        }

        if ($key === 'id') {
            $key = $this->primaryKey;
        }

        if (array_key_exists($key, $this->properties) || $this->hasGetMutator($key)) {
            return $this->getPropertyValue($key);
        }

        return null;

    }

    public function getPropertyValue(string $key): mixed
    {
        $value = $this->getPropertyFromArray($key);

        if ($this->hasGetMutator($key)) {
            return $this->mutateProperty($key, $value);
        }

        if ($this->hasCast($key)) {
            return $this->castProperty($key, $value);
        }

        if (in_array($key, $this->getDates()) && !is_null($value)) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    protected function getPropertyFromArray(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    public function hasGetMutator(string $key): bool
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Property');
    }

    protected function mutateProperty(string $key, mixed $value): mixed
    {
        return $this->{'get' . Str::studly($key) . 'Property'}($value);
    }

    protected function mutatePropertyForArray(string $key, mixed $value): mixed
    {
        $value = $this->mutateProperty($key, $value);
        return $value instanceof Arrayable ? $value->toArray() : $value;
    }
}
