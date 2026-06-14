<?php

namespace App\Gravity\Form;

use Closure;

class Field
{
    public $name;
    public $label;
    public $type; // 'text', 'number', 'email', 'textarea', 'select', 'date'
    public $rules = '';
    public $options = [];
    public $defaultValue = null;
    public $attributes = [];

    public function __construct(string $name, string $label, string $type = 'text', array $options = [])
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        
        $this->rules = $options['rules'] ?? '';
        $this->defaultValue = $options['default'] ?? null;
        $this->attributes = $options['attributes'] ?? [];

        // For select dropdowns, set options
        if (isset($options['options'])) {
            $this->options = $options['options'];
        }
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
     * Render HTML input attributes.
     */
    public function renderAttributes(): string
    {
        $html = [];
        foreach ($this->attributes as $key => $val) {
            if ($val === true) {
                $html[] = $key;
            } elseif ($val !== false && $val !== null) {
                $html[] = sprintf('%s="%s"', $key, e($val));
            }
        }
        return implode(' ', $html);
    }
}
