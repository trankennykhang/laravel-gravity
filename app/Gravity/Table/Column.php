<?php

namespace App\Gravity\Table;

use Closure;

class Column
{
    public $name;
    public $label;
    public $sortable = false;
    public $searchable = false;
    public $formatCallback = null;

    public function __construct(string $name, string $label, array $options = [])
    {
        $this->name = $name;
        $this->label = $label;
        $this->sortable = $options['sortable'] ?? false;
        $this->searchable = $options['searchable'] ?? false;
        $this->formatCallback = $options['format'] ?? null;
    }

    /**
     * Render the column value for a given model.
     */
    public function renderValue($model)
    {
        $value = $model->{$this->name};

        if ($this->formatCallback instanceof Closure) {
            return ($this->formatCallback)($value, $model);
        }

        return $value;
    }

    /**
     * Check if this column is sortable.
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * Check if this column is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }
}
