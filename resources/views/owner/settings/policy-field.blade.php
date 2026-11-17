@php $usesHours = str_ends_with($field, '_hours'); @endphp
<label class="grid gap-1 text-sm" @if ($usesHours) x-data="{ hours: @js(old($field, $bookingPolicy[$field])) }" @endif>{{ $label }}
    <input type="number" name="{{ $field }}" min="{{ $min }}" max="{{ $max }}" value="{{ old($field, $bookingPolicy[$field]) }}"
        @if ($usesHours) x-model.number="hours" @endif required class="bq-input">
    @if ($usesHours)<span class="text-xs text-bq-text-muted" aria-live="polite" x-text="hours === '' ? '' : formatHours(hours)"></span>@endif
    <x-owner.field-error :name="$field" />
</label>
