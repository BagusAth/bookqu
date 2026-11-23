{{--
    Time buttons (design A §6): start time and a note (Penuh, Sisa n tempat), three columns on phone and tablet,
    four on desktop, `aria-pressed`; the full range is the accessible name. Rendered from an Alpine collection of
    server-prepared slots (`id`, `time` or `jam_mulai`, `display_time`, `is_available`, optional `detail`); the page script
    provides `isSlotSelected()` and `selectSlot()`. Availability comes from the server only.
--}}
@props(['items' => 'timeSlots', 'label' => 'Pilih jam'])

<div role="group" aria-label="{{ $label }}" {{ $attributes->class('grid grid-cols-3 gap-2 lg:grid-cols-4') }}>
    <template x-for="slot in {{ $items }}" :key="slot.id">
        <button type="button" class="sf-tile min-h-14 px-2"
            :disabled="!slot.is_available" :aria-pressed="String(!!isSlotSelected(slot))"
            :aria-label="(slot.display_time || slot.time_range) + ' WIB' + (slot.detail ? ', ' + slot.detail : '')"
            @click="selectSlot(slot)">
            <span class="sf-num whitespace-nowrap text-[15px]" x-text="String(slot.time || slot.jam_mulai || slot.display_time || '').slice(0, 5).replace(':', '.')"></span>
            <span class="sf-tile-note" x-show="slot.detail" x-text="slot.detail"></span>
        </button>
    </template>
</div>
