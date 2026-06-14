<?php

namespace App\Gravity\Database;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ArrayAccess;
use JsonSerializable;

abstract class JsonModel implements ArrayAccess, JsonSerializable
{
    protected static $table;
    protected $fillable = [];
    protected $attributes = [];
    protected $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function getTable()
    {
        if (static::$table) {
            return static::$table;
        }
        return Str::plural(Str::snake(class_basename(static::class)));
    }

    public static function getStoragePath()
    {
        $dir = database_path('json_db');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/' . static::getTable() . '.json';
    }

    public static function all(): Collection
    {
        $path = static::getStoragePath();
        if (!file_exists($path)) {
            return collect();
        }
        $data = json_decode(file_get_contents($path), true) ?: [];
        
        return collect($data)->map(function ($item) {
            $model = new static($item);
            $model->exists = true;
            return $model;
        });
    }

    public static function query(): JsonQueryBuilder
    {
        return new JsonQueryBuilder(static::class, static::all());
    }

    public static function find($id)
    {
        return static::all()->first(function ($model) use ($id) {
            return $model->id == $id;
        });
    }

    public static function findOrFail($id)
    {
        $model = static::find($id);
        if (!$model) {
            abort(404, "Model of type " . static::class . " with ID $id not found.");
        }
        return $model;
    }

    public static function create(array $attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->getFillable()) || $key === 'id' || $key === 'created_at' || $key === 'updated_at') {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save()
    {
        $models = static::all();
        $now = now()->toDateTimeString();

        if (!$this->exists || empty($this->attributes['id'])) {
            $this->attributes['id'] = $models->max('id') + 1;
            $this->attributes['created_at'] = $now;
            $this->attributes['updated_at'] = $now;
            $this->exists = true;
            $models->push($this);
        } else {
            $this->attributes['updated_at'] = $now;
            $models = $models->map(function ($model) {
                if ($model->id == $this->id) {
                    return $this;
                }
                return $model;
            });
        }

        static::writeStorage($models);
        return true;
    }

    public function update(array $attributes = [])
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete()
    {
        if (!$this->exists) {
            return false;
        }

        $models = static::all()->filter(function ($model) {
            return $model->id != $this->id;
        });

        static::writeStorage($models);
        $this->exists = false;
        return true;
    }

    protected static function writeStorage(Collection $models)
    {
        $path = static::getStoragePath();
        $data = $models->map(fn($m) => $m->toArray())->toArray();
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    // Magic methods for attribute access
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    // Pass static calls to query builder
    public static function __callStatic($method, $parameters)
    {
        return static::query()->$method(...$parameters);
    }
}
