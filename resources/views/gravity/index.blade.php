@extends('gravity.layout')

@section('title', $datatable->getTitle() . ' - Gravity')

@section('header_actions')
    @php
        $config = config('gravity.resources.' . $datatable->getRoutePrefix());
        $singleName = $config['single_name'] ?? ucfirst(\Illuminate\Support\Str::singular($datatable->getRoutePrefix()));
    @endphp
    <a href="{{ route($datatable->getRoutePrefix() . '.create') }}" class="btn btn-primary">
        <span style="font-size: 16px; font-weight: 700; margin-right: 4px;">+</span> Add {{ $singleName }}
    </a>
@endsection

@section('content')
    <div class="glass-card">
        <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 600; margin-bottom: 25px;">{{ $datatable->getTitle() }}</h2>
        
        <!-- Render Dynamic Datatable component -->
        @include('gravity.components.datatable', [
            'datatable' => $datatable,
            'data' => $data
        ])
    </div>
@endsection
