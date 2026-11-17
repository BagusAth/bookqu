<div @class(['grid gap-1' => $inMenu ?? false, 'flex flex-wrap gap-2' => ! ($inMenu ?? false)])>
    <button @if ($inMenu ?? false) role="menuitem" tabindex="-1" @endif type="button" class="{{ $buttonSecondary }}" x-on:click="openService(@js([
        'id' => $card['id'], 'namalayanan' => $card['name'], 'idcategory' => $card['categoryId'] ?? '',
        'harga' => $card['price'], 'satuan_harga' => $card['priceUnit'], 'durasi' => $card['duration'],
        'satuan_durasi' => $card['durationUnit'], 'deskripsi' => $card['description'],
        'is_popular' => $card['isPopular'], 'auto_open_slots' => $card['autoOpenSlots'], 'is_active' => $card['isActive'], 'imageUrl' => $card['imageUrl'],
        'image_asset_id' => $card['imageAssetId'] !== null ? (string) $card['imageAssetId'] : '',
        'kapasitas' => $card['capacity'], 'unit_ids' => $card['unitIds'],
        'additional_item_ids' => $card['addOnIds'],
    ]), $el)">Ubah</button>

    <form method="POST" id="toggle-service-{{ $card['id'] }}" x-bind:action="url(@js($serviceToggleUrl), @js($card['id']))">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
        <button @if ($inMenu ?? false) role="menuitem" tabindex="-1" @endif class="{{ $buttonSecondary }}" data-service-{{ $card['isActive'] ? 'deactivate' : 'activate' }}="{{ $card['id'] }}"
            @if ($card['isActive'] && $card['futureBookingCount'] > 0)
                type="button" @click="$dispatch('open-confirm', { title: 'Nonaktifkan layanan?', message: @js($card['futureBookingCount'].' booking mendatang tetap berlaku. Pelanggan tidak dapat membuat booking baru untuk layanan ini.'), formId: $el.form.id, confirmText: 'Nonaktifkan' })"
            @else
                type="submit"
            @endif>
            {{ $card['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
    </form>

    @if ($card['canDelete'])
        <button
            @if ($inMenu ?? false) role="menuitem" tabindex="-1" @endif
            type="button"
            class="{{ $buttonSecondary }} text-bq-danger"
            data-service-delete="{{ $card['id'] }}"
            x-on:click="$dispatch('open-confirm', {
                title: 'Hapus layanan?',
                message: @js('Layanan "'.$card['name'].'" akan dihapus permanen. Layanan ini belum pernah dibooking.'),
                actionUrl: url(@js($serviceDestroyUrl), @js($card['id'])),
                method: 'DELETE',
                trigger: $el,
            })"
        >Hapus</button>
    @else
        <p class="w-full text-xs text-bq-text-muted">Sudah pernah dibooking, jadi hanya bisa dinonaktifkan.</p>
    @endif
</div>
