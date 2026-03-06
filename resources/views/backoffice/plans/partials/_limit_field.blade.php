{{-- Limit field with unlimited toggle --}}
<div class="col-md-6">
    <div class="mb-3">
        <label class="form-label d-flex align-items-center">
            <i class="isax {{ $icon }} me-1"></i> {{ $label }}
        </label>
        <div class="input-group">
            <input type="number" min="1"
                class="form-control limit-input @error($field) is-invalid @enderror"
                name="{{ $field }}"
                value="{{ $unlimited ? '' : $value }}"
                placeholder="Ex: 10"
                id="input_{{ $field }}_{{ $plan->id ?? 'new' }}"
                {{ $unlimited ? 'disabled' : '' }}>
            <div class="input-group-text">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input unlimited-toggle" type="checkbox"
                        name="{{ $field }}_unlimited" value="1"
                        id="toggle_{{ $field }}_{{ $plan->id ?? 'new' }}"
                        data-target="input_{{ $field }}_{{ $plan->id ?? 'new' }}"
                        {{ $unlimited ? 'checked' : '' }}>
                    <label class="form-check-label fs-12" for="toggle_{{ $field }}_{{ $plan->id ?? 'new' }}">Illimité</label>
                </div>
            </div>
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
