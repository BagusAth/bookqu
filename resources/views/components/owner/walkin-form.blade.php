{{--
    Walk-in form (BQ-129, owner-portal.md §9, FR-BOOKING-010): the one server action
    `owner.bookings.walkin`. Pass `fixed-slot` (['id' => …, 'label' => …]) for a fixed slot, or
    `slots` (a list of them) for a choice. Participants and add-ons appear only once their
    backend exists (BQ-151, BQ-150): no control without a backend (§3 rule 6).
--}}
@props([
    'fixedSlot' => null,
    'slots' => [],
    'idPrefix' => 'walkin',
])

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $methods =['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS', 'manual' => 'Lainnya (manual)'];
    $field = 'w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20';
    $labelClass = 'mb-1 block text-sm font-medium text-bq-text';
@endphp

<form method="POST" action="{{ route('owner.bookings.walkin') }}" {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @csrf

    <div>
        <label for="{{ $idPrefix }}-slot" class="{{ $labelClass }}">Slot</label>
        @if ($fixedSlot)
            <input type="hidden" name="idschedule" value="{{ $fixedSlot['id'] }}">
            <p id="{{ $idPrefix }}-slot" class="rounded-lg bg-bq-background px-3 py-2 text-sm text-bq-text">{{ $fixedSlot['label'] }}</p>
        @else
            <select id="{{ $idPrefix }}-slot" name="idschedule" required class="{{ $field }}">
                <option value="">Pilih slot</option>
                @foreach ($slots as $option)
                    <option value="{{ $option['id'] }}" @selected((string) old('idschedule') === (string) $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @endif
        @error('idschedule')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $idPrefix }}-name" class="{{ $labelClass }}">Nama pelanggan</label>
            <input id="{{ $idPrefix }}-name" type="text" name="namapelanggan" required maxlength="150" value="{{ old('namapelanggan') }}" autocomplete="off" class="{{ $field }}">
            @error('namapelanggan')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="{{ $idPrefix }}-phone" class="{{ $labelClass }}">Nomor HP</label>
            <input id="{{ $idPrefix }}-phone" type="tel" name="nomorhp" required maxlength="30" value="{{ old('nomorhp') }}" autocomplete="off" class="{{ $field }}">
            @error('nomorhp')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="{{ $idPrefix }}-email" class="{{ $labelClass }}">Email <span class="font-normal text-bq-text-muted">(opsional)</span></label>
        <input id="{{ $idPrefix }}-email" type="email" name="email" maxlength="100" value="{{ old('email') }}" autocomplete="off" class="{{ $field }}">
        @error('email')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
    </div>

    <fieldset>
        <legend class="{{ $labelClass }}">Metode pembayaran</legend>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($methods as $value => $methodLabel)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bq-border px-3 py-2 text-sm text-bq-text has-[:checked]:border-bq-primary has-[:checked]:bg-bq-primary-light">
                    <input type="radio" name="metode" value="{{ $value }}" @checked(old('metode', 'cash') === $value) class="text-bq-primary focus:ring-bq-primary">
                    {{ $methodLabel }}
                </label>
            @endforeach
        </div>
        @error('metode')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
    </fieldset>

    <div>
        <label for="{{ $idPrefix }}-note" class="{{ $labelClass }}">Catatan <span class="font-normal text-bq-text-muted">(opsional)</span></label>
        <textarea id="{{ $idPrefix }}-note" name="catatan" rows="2" maxlength="500" class="{{ $field }}">{{ old('catatan') }}</textarea>
        @error('catatan')<p class="mt-1 text-xs text-bq-danger">{{ $message }}</p>@enderror
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">Simpan walk-in</button>
    </div>
</form>
