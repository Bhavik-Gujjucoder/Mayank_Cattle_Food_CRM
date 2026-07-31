@php
    $name = $name ?? 'transport_id';
    $selectClass = $selectClass ?? '';
    $selected = $selected ?? null;
    $label = $label ?? 'Transport';
@endphp
@if ($label !== '')
    <label class="col-form-label">{{ $label }}</label>
@endif
<div class="wr-transport-field">
    <select name="{{ $name }}" class="form-select form-select-sm {{ $selectClass }} wr-transport-select">
        <option value="">— Select —</option>
        @foreach ($transporters as $tp)
            <option value="{{ $tp->id }}"
                data-phone="{{ $tp->phone_no }}"
                {{ (string) $selected === (string) $tp->id ? 'selected' : '' }}>
                {{ $tp->name }}
            </option>
        @endforeach
    </select>
    @can('add-transporter')
        <div class="mt-1">
            <a href="javascript:void(0)" class="text-primary wr-quick-add-link wr-quick-add-transporter">
                <i class="ti ti-plus me-1"></i>Add Transporter
            </a>
        </div>
    @endcan
</div>
