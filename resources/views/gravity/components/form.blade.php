<form action="{{ $form->getAction() }}" method="POST">
    @csrf
    
    @if(in_array($form->getMethod(), ['PUT', 'PATCH', 'DELETE']))
        @method($form->getMethod())
    @endif

    <div class="gravity-form-grid">
        @foreach($form->getFields() as $field)
            <div class="form-group {{ in_array($field->type, ['textarea']) ? 'form-full-width' : '' }}">
                <label for="{{ $field->name }}" class="form-label">{{ $field->label }}</label>
                
                @if($field->type === 'textarea')
                    <textarea 
                        name="{{ $field->name }}" 
                        id="{{ $field->name }}" 
                        class="form-textarea @error($field->name) is-invalid @enderror"
                        {!! $field->renderAttributes() !!}
                    >{{ $form->getValue($field->name) }}</textarea>
                    
                @elseif($field->type === 'select')
                    <select 
                        name="{{ $field->name }}" 
                        id="{{ $field->name }}" 
                        class="form-select @error($field->name) is-invalid @enderror"
                        {!! $field->renderAttributes() !!}
                    >
                        <option value="">Select option...</option>
                        @foreach($field->getOptions() as $val => $lbl)
                            <option value="{{ $val }}" {{ (string)$form->getValue($field->name) === (string)$val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                    
                @else
                    <input 
                        type="{{ $field->type }}" 
                        name="{{ $field->name }}" 
                        id="{{ $field->name }}" 
                        value="{{ $form->getValue($field->name) }}"
                        class="form-input @error($field->name) is-invalid @enderror"
                        {!! $field->renderAttributes() !!}
                    >
                @endif
                
                @error($field->name)
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        @endforeach
    </div>

    <div class="form-actions">
        <a href="{{ $form->getCancelUrl() ?: route('products.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            {{ $form->getSubmitLabel() }}
        </button>
    </div>
</form>
