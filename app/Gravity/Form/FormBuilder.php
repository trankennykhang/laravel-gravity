<?php

namespace App\Gravity\Form;

use Illuminate\Support\Collection;

class FormBuilder
{
    protected $modelClass;
    protected $model;
    protected $fields = [];
    protected $action = '';
    protected $method = 'POST';
    protected $submitLabel = 'Save';
    protected $cancelUrl = '';

    public function setCancelUrl(string $url): self
    {
        $this->cancelUrl = $url;
        return $this;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function __construct(string $modelClass = null, $model = null)
    {
        $this->modelClass = $modelClass;
        $this->model = $model;

        if ($model) {
            $this->modelClass = get_class($model);
            $this->method = 'PUT';
        }
    }

    public static function make(string $modelClass = null, $model = null): self
    {
        return new static($modelClass, $model);
    }

    public function addField(string $name, string $label, string $type = 'text', array $options = []): self
    {
        $this->fields[] = new Field($name, $label, $type, $options);
        return $this;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function setSubmitLabel(string $label): self
    {
        $this->submitLabel = $label;
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Generate Laravel validation rules for all fields.
     */
    public function getValidationRules(): array
    {
        $rules = [];
        foreach ($this->fields as $field) {
            if ($field->rules) {
                $rules[$field->name] = $field->rules;
            }
        }
        return $rules;
    }

    /**
     * Resolve the value of a field, checking old input, model attributes, and defaults.
     */
    public function getValue(string $fieldName)
    {
        // 1. Check if there is flashed old input
        if (old($fieldName) !== null) {
            return old($fieldName);
        }

        // 2. Check if there is a bound model
        if ($this->model && isset($this->model->{$fieldName})) {
            return $this->model->{$fieldName};
        }

        // 3. Fallback to field default value
        $field = collect($this->fields)->first(fn($f) => $f->name === $fieldName);
        return $field ? $field->defaultValue : null;
    }

    /**
     * Save the form, creating or updating the model.
     */
    public function save(array $requestData)
    {
        $model = $this->model ?: new $this->modelClass();
        
        $fillData = [];
        foreach ($this->fields as $field) {
            // Include value from request or use null/default if absent
            $fillData[$field->name] = $requestData[$field->name] ?? null;
        }

        $model->fill($fillData);
        $model->save();
        
        $this->model = $model;
        return $model;
    }
}
