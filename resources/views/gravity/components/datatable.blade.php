@php
    $columns = $datatable->getColumns();
    $filters = $datatable->getFilters();
    $search = $datatable->getSearchQuery();
    $sortBy = $datatable->getSortBy();
    $sortDir = $datatable->getSortDir();
@endphp

<div class="datatable-wrapper">
    <!-- Controls (Search and Filters) -->
    <form method="GET" action="{{ url()->current() }}" id="gravity-table-form">
        <!-- Keep active sort values in form -->
        @if($sortBy)
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        @endif

        <div class="table-controls">
            <div class="search-filter-wrapper">
                <!-- Search Box -->
                <div class="search-box">
                    <svg class="search-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="{{ $datatable->getSearchPlaceholder() }}" 
                        class="search-input"
                        autocomplete="off"
                    >
                </div>

                <!-- Custom Filters -->
                @foreach($filters as $filter)
                    <div class="filter-group">
                        <select 
                            name="filter_{{ $filter->name }}" 
                            class="filter-select"
                            onchange="document.getElementById('gravity-table-form').submit();"
                        >
                            <option value="">-- {{ $filter->label }} --</option>
                            @foreach($filter->getOptions() as $val => $lbl)
                                <option value="{{ $val }}" {{ (string)$datatable->getFilterValue($filter->name) === (string)$val ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <!-- Action buttons (Apply / Reset) -->
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-secondary">Apply</button>
                @if($search || collect($filters)->some(fn($f) => $datatable->getFilterValue($f->name) !== null))
                    <a href="{{ $datatable->getClearUrl() }}" class="btn btn-danger">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <!-- The Data Table -->
    <div class="table-responsive">
        <table class="gravity-table">
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th>
                            @if($col->isSortable())
                                <a href="{{ $datatable->getSortUrl($col->name) }}" class="sort-link {{ $sortBy === $col->name ? 'sort-active' : '' }}">
                                    {{ $col->label }}
                                    <span class="sort-icon">
                                        @if($sortBy === $col->name)
                                            {!! $sortDir === 'desc' ? '&#9662;' : '&#9652;' !!}
                                        @else
                                            &#8597;
                                        @endif
                                    </span>
                                </a>
                            @else
                                {{ $col->label }}
                            @endif
                        </th>
                    @endforeach
                    <th style="text-align: right; width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                    <tr>
                        @foreach($columns as $col)
                            <td>
                                @if($col->name === 'status')
                                    <span class="badge badge-{{ $item->status }}">
                                        {{ $item->status }}
                                    </span>
                                @else
                                    {!! $col->renderValue($item) !!}
                                @endif
                            </td>
                        @endforeach
                        <td style="text-align: right;">
                            <div class="table-actions" style="justify-content: flex-end;">
                                <!-- Edit Button -->
                                <a href="{{ route($datatable->getRoutePrefix() . '.edit', $item->id) }}" class="action-btn" title="Edit Item">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <!-- Delete Form -->
                                <form action="{{ route($datatable->getRoutePrefix() . '.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-danger" title="Delete Item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" style="text-align: center; color: var(--text-muted); padding: 40px 0;">
                            No items found matching your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pager / Pagination controls -->
    @if($data->hasPages())
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <strong>{{ $data->firstItem() ?: 0 }}</strong> to <strong>{{ $data->lastItem() ?: 0 }}</strong> of <strong>{{ $data->total() }}</strong> entries
            </div>
            
            <div class="pagination-links">
                <!-- Previous Button -->
                @if($data->onFirstPage())
                    <span class="page-item-btn disabled">&laquo;</span>
                @else
                    <a href="{{ $data->previousPageUrl() }}" class="page-item-btn">&laquo;</a>
                @endif

                <!-- Page Numbers -->
                @php
                    $start = max(1, $data->currentPage() - 2);
                    $end = min($data->lastPage(), $data->currentPage() + 2);
                @endphp
                
                @if($start > 1)
                    <a href="{{ $data->url(1) }}" class="page-item-btn">1</a>
                    @if($start > 2)
                        <span class="page-item-btn disabled">...</span>
                    @endif
                @endif

                @for($p = $start; $p <= $end; $p++)
                    @if($p == $data->currentPage())
                        <span class="page-item-btn active">{{ $p }}</span>
                    @else
                        <a href="{{ $data->url($p) }}" class="page-item-btn">{{ $p }}</a>
                    @endif
                @endfor

                @if($end < $data->lastPage())
                    @if($end < $data->lastPage() - 1)
                        <span class="page-item-btn disabled">...</span>
                    @endif
                    <a href="{{ $data->url($data->lastPage()) }}" class="page-item-btn">{{ $data->lastPage() }}</a>
                @endif

                <!-- Next Button -->
                @if($data->hasMorePages())
                    <a href="{{ $data->nextPageUrl() }}" class="page-item-btn">&raquo;</a>
                @else
                    <span class="page-item-btn disabled">&raquo;</span>
                @endif
            </div>
        </div>
    @else
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <strong>{{ $data->firstItem() ?: 0 }}</strong> to <strong>{{ $data->lastItem() ?: 0 }}</strong> of <strong>{{ $data->total() }}</strong> entries
            </div>
        </div>
    @endif
</div>
