@extends('gravity.layout')

@section('title', ($form->getModel() ? 'Edit ' . class_basename($form->getModel()) : 'Register ' . class_basename($form->getModelClass())) . ' - Gravity')

@section('content')
    <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
        <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 600; margin-bottom: 25px;">
            {{ $form->getModel() ? 'Edit ' . class_basename($form->getModel()) . ': ' . $form->getModel()->name : 'Register New ' . class_basename($form->getModelClass()) }}
        </h2>
        
        <!-- Render Dynamic Form component -->
        @include('gravity.components.form', [
            'form' => $form
        ])
    </div>
@endsection
