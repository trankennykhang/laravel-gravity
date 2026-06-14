<?php

namespace App\Gravity\Table;

use Closure;

class Filter
{
    public $name;
    public $label;
    public $type; // 'select', 'text'
    public $options = [];
    public $callback = null;

    public function __construct(string $name, string $label, string $type = 'select', array $options = [], Closure $callback = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->options = $options;
        $this->callback = $callback;
    }

    /**
     * Resolve options array if it's a callback.
     */
    public function getOptions(): array
    {
        if ($this->options instanceof Closure) {
            return ($this->options)();
        }
        return $this->options;
    }

    /**
     * Apply filter logic to the query.
     */
    public function apply($query, $value)
    {
        if ($this->callback instanceof Closure) {
            return ($this->callback)($query, $value);
        }

        // Default filter behavior: exact match or basic where
        if ($value !== null && $value !== '') {
            return $query->where($this->name, $value);
        }

        return $query;
    }
}
