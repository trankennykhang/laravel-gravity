<?php

namespace App\Gravity\Table;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class Datatable
{
    protected $modelClass;
    protected $query;
    protected $columns = [];
    protected $filters = [];
    protected $perPage = 10;
    protected $routePrefix = 'products';
    protected $title = 'Product Catalog';

    public function setRoutePrefix(string $prefix): self
    {
        $this->routePrefix = $prefix;
        return $this;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    // Request values
    protected $searchQuery;
    protected $sortBy;
    protected $sortDir = 'asc';

    public function __construct(string $modelClass = null, $query = null)
    {
        $this->modelClass = $modelClass;
        $this->query = $query;
    }

    public static function make(string $modelClass = null, $query = null): self
    {
        return new static($modelClass, $query);
    }

    public function addColumn(string $name, string $label, array $options = []): self
    {
        $this->columns[] = new Column($name, $label, $options);
        return $this;
    }

    public function addFilter(string $name, string $label, string $type = 'select', array $options = [], \Closure $callback = null): self
    {
        $this->filters[] = new Filter($name, $label, $type, $options, $callback);
        return $this;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function setSearchPlaceholder(string $placeholder): self
    {
        $this->searchPlaceholder = $placeholder;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder;
    }

    public function getSearchQuery()
    {
        return $this->searchQuery;
    }

    public function getSortBy()
    {
        return $this->sortBy;
    }

    public function getSortDir()
    {
        return $this->sortDir;
    }

    /**
     * Get sort URL for a specific column, toggling direction and maintaining other query params.
     */
    public function getSortUrl(string $columnName): string
    {
        $params = request()->query();
        $params['sort_by'] = $columnName;
        
        $currentSort = request()->input('sort_by');
        $currentDir = request()->input('sort_dir', 'asc');
        
        if ($currentSort === $columnName) {
            $params['sort_dir'] = $currentDir === 'asc' ? 'desc' : 'asc';
        } else {
            $params['sort_dir'] = 'asc';
        }
        
        // Return page to 1 when changing sorting
        $params['page'] = 1;
        
        return request()->fullUrlWithQuery($params);
    }

    /**
     * Get current active filter value.
     */
    public function getFilterValue(string $filterName)
    {
        return request()->input('filter_' . $filterName);
    }

    /**
     * Get clear URL to reset search and filters.
     */
    public function getClearUrl(): string
    {
        // Strip out search, page, sort, and filters
        $params = request()->query();
        unset($params['search'], $params['page'], $params['sort_by'], $params['sort_dir']);
        
        foreach ($this->filters as $filter) {
            unset($params['filter_' . $filter->name]);
        }
        
        return request()->url() . ($params ? '?' . http_build_query($params) : '');
    }

    /**
     * Execute and retrieve the paginated data structure.
     */
    public function execute(): LengthAwarePaginator
    {
        $request = request();
        $this->searchQuery = $request->input('search');
        $this->sortBy = $request->input('sort_by');
        $this->sortDir = $request->input('sort_dir', 'asc');

        // Initialize Query Builder
        $query = $this->query ?: ($this->modelClass)::query();

        // 1. Apply Filters
        foreach ($this->filters as $filter) {
            $val = $request->input('filter_' . $filter->name);
            if ($val !== null && $val !== '') {
                $filter->apply($query, $val);
            }
        }

        // 2. Apply Search (multi-column)
        if ($this->searchQuery !== null && $this->searchQuery !== '') {
            $searchableColumns = collect($this->columns)->filter(fn($c) => $c->isSearchable());
            if ($searchableColumns->isNotEmpty()) {
                $query->where(function ($q) use ($searchableColumns) {
                    foreach ($searchableColumns as $index => $col) {
                        if ($index === 0) {
                            $q->where($col->name, 'like', '%' . $this->searchQuery . '%');
                        } else {
                            $q->orWhere($col->name, 'like', '%' . $this->searchQuery . '%');
                        }
                    }
                });
            }
        }

        // 3. Apply Sorting
        if ($this->sortBy) {
            $col = collect($this->columns)->first(fn($c) => $c->name === $this->sortBy && $c->isSortable());
            if ($col) {
                $query->orderBy($this->sortBy, $this->sortDir === 'desc' ? 'desc' : 'asc');
            }
        } else {
            // Default sorting order
            $query->orderBy('id', 'desc');
        }

        // 4. Return Paginator
        return $query->paginate($this->perPage);
    }
}
