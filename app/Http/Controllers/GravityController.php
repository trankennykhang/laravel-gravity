<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Gravity\Table\Datatable;
use App\Gravity\Form\FormBuilder;
use Illuminate\Support\Str;

class GravityController extends Controller
{
    /**
     * Resolve the active resource configuration based on route or request URL path.
     */
    protected function resolveResource(Request $request): array
    {
        // First get from route parameter '{resource}' if set
        $resourceName = $request->route('resource');
        
        if (!$resourceName) {
            // Fallback to first path segment
            $resourceName = $request->segment(1);
        }

        // Standardize to lowercase
        $resourceName = strtolower($resourceName);

        $resources = config('gravity.resources', []);
        $matchedKey = null;
        $config = null;

        // Direct match check
        if (isset($resources[$resourceName])) {
            $matchedKey = $resourceName;
            $config = $resources[$resourceName];
        } else {
            // Try plural match
            $plural = Str::plural($resourceName);
            if (isset($resources[$plural])) {
                $matchedKey = $plural;
                $config = $resources[$plural];
            } else {
                // Try singular match
                $singular = Str::singular($resourceName);
                if (isset($resources[$singular])) {
                    $matchedKey = $singular;
                    $config = $resources[$singular];
                }
            }
        }

        // Fallback for homepage/dashboard index: default to first configured resource (products)
        if (!$config && (empty($resourceName) || $resourceName === 'index')) {
            $matchedKey = array_key_first($resources);
            $config = $resources[$matchedKey];
        }

        if (!$config) {
            abort(404, "Gravity resource '{$resourceName}' is not defined in configuration.");
        }

        // Dynamic model evaluation if the model class does not exist or is omitted
        $modelClass = $config['model'] ?? null;
        if (!$modelClass || !class_exists($modelClass)) {
            $className = $modelClass ?: 'App\\Models\\' . Str::studly(Str::singular($matchedKey));
            
            if (!class_exists($className)) {
                $baseName = class_basename($className);
                $namespace = Str::beforeLast($className, '\\');
                
                // Declare model class dynamically at runtime extending DynamicJsonModel
                eval("namespace {$namespace}; class {$baseName} extends \App\Gravity\Database\DynamicJsonModel {}");
            }

            // Register configuration schemas dynamically
            $table = $matchedKey;
            $fillable = array_keys($config['fields'] ?? []);
            
            \App\Gravity\Database\DynamicJsonModel::register($className, $table, $fillable);
            $config['model'] = $className;
        }

        return [$matchedKey, $config];
    }

    /**
     * Dynamic index action listing the resource records.
     */
    public function index(Request $request)
    {
        list($resourceName, $config) = $this->resolveResource($request);
        
        $modelClass = $config['model'];

        // Instantiate and configure Datatable
        $datatable = Datatable::make($modelClass)
            ->setRoutePrefix($resourceName)
            ->setTitle($config['title'])
            ->setPerPage($config['per_page'] ?? 10)
            ->setSearchPlaceholder($config['search_placeholder'] ?? 'Search...');

        // Add columns from configuration
        foreach ($config['columns'] as $name => $colOptions) {
            $label = $colOptions['label'] ?? ucfirst($name);
            $options = [];
            
            if (!empty($colOptions['sortable'])) {
                $options['sortable'] = true;
            }
            if (!empty($colOptions['searchable'])) {
                $options['searchable'] = true;
            }
            if (!empty($colOptions['format'])) {
                $options['format'] = $colOptions['format'];
            }

            $datatable->addColumn($name, $label, $options);
        }

        // Add filters from configuration
        foreach ($config['filters'] ?? [] as $name => $filterOptions) {
            $label = $filterOptions['label'] ?? ucfirst($name);
            $type = $filterOptions['type'] ?? 'select';
            $options = $filterOptions['options'] ?? [];
            $callback = $filterOptions['query'] ?? null;

            $datatable->addFilter($name, $label, $type, $options, $callback);
        }

        $data = $datatable->execute();

        return view('gravity.index', compact('datatable', 'data'));
    }

    /**
     * Show form for creating a new resource record.
     */
    public function create(Request $request)
    {
        list($resourceName, $config) = $this->resolveResource($request);

        $form = $this->buildForm($resourceName, $config)
            ->setAction(route($resourceName . '.store'))
            ->setMethod('POST')
            ->setCancelUrl(route($resourceName . '.index'))
            ->setSubmitLabel('Create ' . ($config['single_name'] ?? 'Record'));

        return view('gravity.create-edit', compact('form'));
    }

    /**
     * Store new resource record.
     */
    public function store(Request $request)
    {
        list($resourceName, $config) = $this->resolveResource($request);

        $form = $this->buildForm($resourceName, $config);
        $rules = $form->getValidationRules();
        $validated = $request->validate($rules);

        // Security password hashing
        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $form->save($validated);

        return redirect()
            ->route($resourceName . '.index')
            ->with('success', ($config['single_name'] ?? 'Record') . ' registered successfully!');
    }

    /**
     * Show form for editing an existing resource record.
     */
    public function edit(Request $request, $id)
    {
        list($resourceName, $config) = $this->resolveResource($request);

        $modelClass = $config['model'];
        $model = $modelClass::findOrFail($id);

        $form = $this->buildForm($resourceName, $config, $model)
            ->setAction(route($resourceName . '.update', $model->id))
            ->setMethod('PUT')
            ->setCancelUrl(route($resourceName . '.index'))
            ->setSubmitLabel('Update ' . ($config['single_name'] ?? 'Record'));

        return view('gravity.create-edit', compact('form'));
    }

    /**
     * Update an existing resource record.
     */
    public function update(Request $request, $id)
    {
        list($resourceName, $config) = $this->resolveResource($request);

        $modelClass = $config['model'];
        $model = $modelClass::findOrFail($id);

        $form = $this->buildForm($resourceName, $config, $model);
        $rules = $form->getValidationRules();
        $validated = $request->validate($rules);

        // Password update security
        if (array_key_exists('password', $validated)) {
            if (!empty($validated['password'])) {
                $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
            } else {
                unset($validated['password']); // Preserve existing password
            }
        }

        $form->save($validated);

        return redirect()
            ->route($resourceName . '.index')
            ->with('success', ($config['single_name'] ?? 'Record') . ' updated successfully!');
    }

    /**
     * Remove the specified resource record.
     */
    public function destroy(Request $request, $id)
    {
        list($resourceName, $config) = $this->resolveResource($request);

        // Session termination safety prevention
        if ($config['model'] === \App\Models\User::class && $id == auth()->id()) {
            return redirect()
                ->route($resourceName . '.index')
                ->with('error', 'Authentication protection trigger: cannot delete your own session.');
        }

        $modelClass = $config['model'];
        $model = $modelClass::findOrFail($id);
        $model->delete();

        return redirect()
            ->route($resourceName . '.index')
            ->with('success', ($config['single_name'] ?? 'Record') . ' deleted successfully.');
    }

    /**
     * Reusable helper to build FormBuilder fields dynamically.
     */
    protected function buildForm(string $resourceName, array $config, $model = null): FormBuilder
    {
        $modelClass = $config['model'];
        $form = FormBuilder::make($modelClass, $model);

        foreach ($config['fields'] as $name => $fieldOptions) {
            $label = $fieldOptions['label'] ?? ucfirst($name);
            $type = $fieldOptions['type'] ?? 'text';
            $options = [];

            // Password creation vs editing rule handling
            $rules = $fieldOptions['rules'] ?? '';
            if ($name === 'password') {
                $rules = $model ? 'nullable|string|min:6' : 'required|string|min:6';
            }
            $options['rules'] = $rules;

            if (isset($fieldOptions['options'])) {
                $options['options'] = $fieldOptions['options'];
            }

            if (isset($fieldOptions['default'])) {
                $default = $fieldOptions['default'];
                if ($default === 'today') {
                    $default = now()->toDateString();
                }
                $options['default'] = $default;
            }

            if (isset($fieldOptions['attributes'])) {
                $options['attributes'] = $fieldOptions['attributes'];
            }

            // Edit placeholder adjustment
            if ($name === 'password' && $model) {
                if (!isset($options['attributes'])) {
                    $options['attributes'] = [];
                }
                $options['attributes']['placeholder'] = 'Leave blank to preserve current password...';
            }

            $form->addField($name, $label, $type, $options);
        }

        return $form;
    }
}
