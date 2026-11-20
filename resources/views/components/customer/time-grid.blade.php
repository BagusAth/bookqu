@props(['items' => 'timeSlots', 'label' => 'Pilih jam'])

<div role="group" aria-label="{{ $label }}" {{ $attributes->class('grid gap-2 min-[360px]:grid-cols-2 sm:grid-cols-3') }}>
    <template x-for="slot in {{ $items }}" :key="slot.id">
        <button type="button" class="bq-btn bq-btn-secondary min-h-11 flex-col gap-0 px-2"
            :disabled="!slot.is_available" :aria-pressed="isSlotSelected(slot)"
            :class="{ 'bg-bq-primary-light border-bq-primary': isSlotSelected(slot) }"
            @click="selectSlot(slot)">
            <span class="bq-num whitespace-nowrap" x-text="slot.time_range"></span>
            <span class="text-bq-small" x-show="slot.detail" x-text="slot.detail"></span>
        </button>
    </template>
</div>
