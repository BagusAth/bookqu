{{--
    Reservation ticket on the success page (design A §6, §7.5): date block, service, time, location, a tear line
    and the reservation code with a copy button. All values are prepared by the controller.
--}}
@props(['month', 'day', 'weekday', 'service', 'time', 'location' => null, 'code'])

<article aria-label="Tiket reservasi" {{ $attributes->class('sf-card-raised') }}>
    <div class="flex items-center gap-4 p-5">
        <div class="w-[76px] shrink-0 overflow-hidden rounded-sf-card-small bg-sf-tint text-center">
            <p class="bg-sf-primary py-1 text-xs font-bold text-sf-on-primary">{{ $month }}</p>
            <p class="sf-num text-[30px] font-bold leading-[38px] text-sf-primary-text">{{ $day }}</p>
            <p class="pb-1.5 text-xs font-semibold text-sf-muted">{{ $weekday }}</p>
        </div>
        <div class="min-w-0">
            <p class="text-[17px] font-bold leading-[23px]">{{ $service }}</p>
            <p class="sf-num mt-1 text-[15px] font-bold text-sf-primary-text">{{ $time }}</p>
            @if ($location)
                <p class="mt-0.5 text-[13px] text-sf-muted">{{ $location }}</p>
            @endif
        </div>
    </div>
    <div class="sf-ticket-tear" aria-hidden="true"></div>
    <div class="flex items-center justify-between gap-3 px-5 pb-[18px] pt-3.5" x-data="{ copied: false }">
        <div>
            <p class="text-xs text-sf-muted">Kode reservasi</p>
            <p class="sf-num break-all text-lg font-bold tracking-[1px] md:text-[21px] md:tracking-[2px]">{{ $code }}</p>
        </div>
        <button type="button" class="sf-btn-secondary px-3.5 text-sm"
            x-on:click="navigator.clipboard && navigator.clipboard.writeText(@js($code)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
            <x-customer.icon name="copy" :size="16" :stroke="2" />
            <span x-text="copied ? 'Disalin' : 'Salin'">Salin</span>
        </button>
        <span class="sr-only" aria-live="polite" x-text="copied ? 'Kode reservasi disalin' : ''"></span>
    </div>
    {{ $slot }}
</article>
