{{--
    Gallery image picker frame (BQ-129, owner-portal.md §9): one gallery per business. The page
    passes `images` (['id' => …, 'url' => …, 'alt' => …]) from that gallery; the chosen id goes into
    the hidden input `name`. Uploading lives on the gallery page (`gallery-url`).
--}}
@props([
    'name',
    'images' => [],
    'selected' => null,
    'galleryUrl' => null,
    'label' => 'Pilih gambar',
])

@php $pickerId = 'gallery-'.\Illuminate\Support\Str::slug($name); @endphp

<div x-data="{ chosen: @js($selected !== null ? (string) $selected : '') }" {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <input type="hidden" name="{{ $name }}" x-bind:value="chosen">

    @if (count($images) === 0)
        <x-owner.empty-state
            title="Galeri masih kosong"
            description="Unggah gambar ke galeri bisnis, lalu pilih di sini."
            :action-label="$galleryUrl ? 'Buka galeri' : null"
            :action-href="$galleryUrl"
        />
    @else
        <div role="radiogroup" aria-label="{{ $label }}" class="grid grid-cols-3 gap-2 sm:grid-cols-4">
            @foreach ($images as $image)
                <button
                    type="button"
                    role="radio"
                    x-bind:aria-checked="chosen === @js((string) $image['id']) ? 'true' : 'false'"
                    x-on:click="chosen = @js((string) $image['id'])"
                    class="overflow-hidden rounded-lg border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-bq-primary/40"
                    x-bind:class="chosen === @js((string) $image['id']) ? 'border-bq-primary' : 'border-transparent'"
                >
                    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? '' }}" class="aspect-square w-full object-cover" loading="lazy">
                </button>
            @endforeach
        </div>
        <div class="flex items-center gap-3 text-sm">
            <button type="button" x-show="chosen !== ''" x-on:click="chosen = ''" class="text-bq-text-muted hover:text-bq-text">Hapus pilihan</button>
            @if ($galleryUrl)
                <a href="{{ $galleryUrl }}" id="{{ $pickerId }}-gallery" class="text-bq-primary hover:text-bq-primary-hover">Kelola galeri</a>
            @endif
        </div>
    @endif
</div>
