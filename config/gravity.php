<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gravity Dynamic Models Configuration
    |--------------------------------------------------------------------------
    |
    | Define the schema, layout, and rules for dynamic CRUD operations
    | handled by the GravityController.
    |
    */

    'resources' => [
        'products' => [
            'model' => \App\Models\Product::class,
            'title' => 'Product Catalog',
            'single_name' => 'Product',
            'plural_name' => 'Products',
            'route_prefix' => 'products',
            'search_placeholder' => 'Search by SKU or Name...',
            'per_page' => 10,
            'columns' => [
                'id' => [
                    'label' => 'ID',
                    'sortable' => true,
                ],
                'name' => [
                    'label' => 'Product Name',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'sku' => [
                    'label' => 'SKU',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'price' => [
                    'label' => 'Price',
                    'sortable' => true,
                    'format' => function($val) {
                        return '$' . number_format((float)$val, 2);
                    }
                ],
                'stock' => [
                    'label' => 'Stock',
                    'sortable' => true,
                    'format' => function($val) {
                        if ($val <= 10) {
                            return sprintf('<strong style="color: var(--danger);">%d (Low)</strong>', $val);
                        }
                        return $val;
                    }
                ],
                'status' => [
                    'label' => 'Status',
                ],
                'released_at' => [
                    'label' => 'Release Date',
                    'sortable' => true,
                ],
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                    'type' => 'select',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
                    ],
                ],
                'stock_status' => [
                    'label' => 'Stock Level',
                    'type' => 'select',
                    'options' => [
                        'low' => 'Low Stock (<= 10)',
                        'in_stock' => 'In Stock (> 10)',
                    ],
                    'query' => function($query, $value) {
                        if ($value === 'low') {
                            return $query->where('stock', '<=', 10);
                        } elseif ($value === 'in_stock') {
                            return $query->where('stock', '>', 10);
                        }
                        return $query;
                    }
                ],
            ],
            'fields' => [
                'name' => [
                    'label' => 'Product Name',
                    'type' => 'text',
                    'rules' => 'required|string|max:255',
                    'attributes' => [
                        'placeholder' => 'Enter item name...',
                        'required' => true,
                    ],
                ],
                'sku' => [
                    'label' => 'SKU (Catalog Code)',
                    'type' => 'text',
                    'rules' => 'required|string|max:50',
                    'attributes' => [
                        'placeholder' => 'GRV-XXX-999...',
                        'required' => true,
                    ],
                ],
                'price' => [
                    'label' => 'Unit Price ($)',
                    'type' => 'number',
                    'rules' => 'required|numeric|min:0',
                    'attributes' => [
                        'step' => '0.01',
                        'placeholder' => '0.00',
                        'required' => true,
                        'min' => '0',
                    ],
                ],
                'stock' => [
                    'label' => 'Current Stock Level',
                    'type' => 'number',
                    'rules' => 'required|integer|min:0',
                    'attributes' => [
                        'placeholder' => '0',
                        'required' => true,
                        'min' => '0',
                    ],
                ],
                'status' => [
                    'label' => 'Product Status',
                    'type' => 'select',
                    'rules' => 'required|in:active,inactive,draft',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
                    ],
                    'default' => 'draft',
                    'attributes' => [
                        'required' => true,
                    ],
                ],
                'released_at' => [
                    'label' => 'Release Date',
                    'type' => 'date',
                    'rules' => 'nullable|date',
                    'default' => 'today', // handled dynamically in controller
                ],
                'description' => [
                    'label' => 'Detailed Technical Specifications',
                    'type' => 'textarea',
                    'rules' => 'nullable|string',
                    'attributes' => [
                        'placeholder' => 'Enter detailed description and parameters...',
                    ],
                ],
            ],
        ],

        'users' => [
            'model' => \App\Models\User::class,
            'title' => 'Staff Directory',
            'single_name' => 'User',
            'plural_name' => 'User',
            'route_prefix' => 'users',
            'search_placeholder' => 'Search by name or email...',
            'per_page' => 10,
            'columns' => [
                'id' => [
                    'label' => 'ID',
                    'sortable' => true,
                ],
                'name' => [
                    'label' => 'Full Name',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'email' => [
                    'label' => 'Email Address',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'created_at' => [
                    'label' => 'Registered At',
                    'sortable' => true,
                ],
            ],
            'filters' => [],
            'fields' => [
                'name' => [
                    'label' => 'Full Name',
                    'type' => 'text',
                    'rules' => 'required|string|max:255',
                    'attributes' => [
                        'placeholder' => 'Enter name...',
                        'required' => true,
                    ],
                ],
                'email' => [
                    'label' => 'Email Address',
                    'type' => 'email',
                    'rules' => 'required|email|max:255',
                    'attributes' => [
                        'placeholder' => 'user@gravity.com',
                        'required' => true,
                    ],
                ],
                'password' => [
                    'label' => 'Security Key (Password)',
                    'type' => 'password',
                    'rules' => 'nullable_or_required_on_create', // Handled dynamically based on whether we are editing
                    'attributes' => [
                        'placeholder' => 'Minimum 6 characters...',
                    ],
                ],
            ],
        ],
        'categories' => [
            'title' => 'Category',
            'single_name' => 'Category',
            'plural_name' => 'Categories',
            'route_prefix' => 'categories',
            'search_placeholder' => 'Search by category...',
            'per_page' => 10,
            'columns' => [
                'id' => [
                    'label' => 'ID',
                    'sortable' => true,
                ],
                'name' => [
                    'label' => 'Category Name',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'slug' => [
                    'label' => 'Slug',
                    'sortable' => true,
                    'searchable' => true,
                ],
                'is_active' => [
                    'label' => 'Is Active',
                    'sortable' => true,
                ],
            ],
            'filters' => [],
            'fields' => [
                'name' => [
                    'label' => 'Category Name',
                    'type' => 'text',
                    'rules' => 'required|string|max:255',
                    'attributes' => [
                        'placeholder' => 'Enter name...',
                        'required' => true,
                    ],
                ],
                'slug' => [
                    'label' => 'Slug',
                    'type' => 'text',
                    'rules' => 'required|string|max:255',
                    'attributes' => [
                        'placeholder' => 'Enter slug...',
                        'required' => true,
                    ],
                ],
                'is_active' => [
                    'label' => 'Is Active',
                    'type' => 'select',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ],
                    'rules' => 'required',
                    'attributes' => [
                        'required' => true,
                    ],
                ],
            ],
        ],
    ],
];
