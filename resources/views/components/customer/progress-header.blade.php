@props([
    'step' => 1,
    'total' => 3,
    'label' => '',
    'progress' => 0,
])

<div class="booking-step">
    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">
        <span>STEP {{ $step }} OF {{ $total }}</span>
        <span class="text-[#4F46E5]">{{ $label }}</span>
    </div>
    <div class="mt-3 h-[3px] w-full rounded-full bg-[#E5E7EB]">
        <div class="h-[3px] rounded-full bg-[#4F46E5]" style="width: {{ $progress }}%"></div>
    </div>
</div>
