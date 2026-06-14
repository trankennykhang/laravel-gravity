<?php

namespace App\Gravity\Database;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Closure;

class JsonQueryBuilder
{
    protected $modelClass;
    protected $collection;
    protected $wheres = [];

    public function __construct($modelClass, Collection $collection)
    {
        $this->modelClass = $modelClass;
        $this->collection = $collection;
    }

    public function getCollection()
    {
        $this->applyWheres();
        return $this->collection;
    }

    public function get()
    {
        return $this->getCollection();
    }

    public function first()
    {
        return $this->getCollection()->first();
    }

    public function count()
    {
        return $this->getCollection()->count();
    }

    public function where($column, $operator = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->wheres[] = ['type' => 'nested', 'boolean' => 'and', 'closure' => $column];
        } else {
            if ($value === null && $operator !== null) {
                $value = $operator;
                $operator = '=';
            }
            $this->wheres[] = [
                'type' => 'basic',
                'boolean' => 'and',
                'column' => $column,
                'operator' => strtolower($operator),
                'value' => $value
            ];
        }
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->wheres[] = ['type' => 'nested', 'boolean' => 'or', 'closure' => $column];
        } else {
            if ($value === null && $operator !== null) {
                $value = $operator;
                $operator = '=';
            }
            $this->wheres[] = [
                'type' => 'basic',
                'boolean' => 'or',
                'column' => $column,
                'operator' => strtolower($operator),
                'value' => $value
            ];
        }
        return $this;
    }

    protected function applyWheres()
    {
        if (empty($this->wheres)) {
            return;
        }

        $this->collection = $this->collection->filter(function ($item) {
            $match = true; // Default for first and chain

            foreach ($this->wheres as $index => $where) {
                $currentMatch = false;

                if ($where['type'] === 'basic') {
                    $val = $item->{$where['column']};
                    $target = $where['value'];
                    $op = $where['operator'];

                    switch ($op) {
                        case '=':
                            $currentMatch = ($val == $target);
                            break;
                        case '!=':
                        case '<>':
                            $currentMatch = ($val != $target);
                            break;
                        case '>':
                            $currentMatch = ($val > $target);
                            break;
                        case '>=':
                            $currentMatch = ($val >= $target);
                            break;
                        case '<':
                            $currentMatch = ($val < $target);
                            break;
                        case '<=':
                            $currentMatch = ($val <= $target);
                            break;
                        case 'like':
                            $pattern = str_replace('%', '', $target);
                            $currentMatch = str_contains(strtolower((string)$val), strtolower($pattern));
                            break;
                        default:
                            $currentMatch = ($val == $target);
                            break;
                    }
                } elseif ($where['type'] === 'nested') {
                    $nestedBuilder = new static($this->modelClass, collect([$item]));
                    $where['closure']($nestedBuilder);
                    $currentMatch = ($nestedBuilder->count() > 0);
                }

                if ($index === 0) {
                    $match = $currentMatch;
                } else {
                    if ($where['boolean'] === 'or') {
                        $match = $match || $currentMatch;
                    } else {
                        $match = $match && $currentMatch;
                    }
                }
            }

            return $match;
        });

        // Reset wheres so they aren't reapplied
        $this->wheres = [];
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->applyWheres();
        $descending = strtolower($direction) === 'desc';
        $this->collection = $this->collection->sortBy(function ($item) use ($column) {
            return $item->{$column};
        }, SORT_REGULAR, $descending)->values();
        
        return $this;
    }

    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->applyWheres();

        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $total = $this->collection->count();
        $results = $this->collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }
}
