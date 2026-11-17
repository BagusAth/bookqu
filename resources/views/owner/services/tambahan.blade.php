@php
    $oldForm = $errors->any() && is_string(old('_form')) ? old('_form') : '';
    $editingId = preg_match('/^addon-edit-([1-9][0-9]*)$/', $oldForm, $match) ? (int) $match[1] : null;
    $restore = ($oldForm === 'addon-create' || $editingId !== null)
        ? \Illuminate\Support\Arr::only(old(), ['name', 'description', 'price', 'stock', 'is_active', 'service_ids']) + ['id' => $editingId]
        : null;
    $input = 'mt-1 w-full bq-input';
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{
    item: {},
    formKey: '',
    edit(data, trigger) {
        this.item = { name: '', price: '', description: '', stock: '', is_active: '1', service_ids: [], ...data };
        this.item.service_ids = Array.isArray(this.item.service_ids) ? this.item.service_ids.map(String) : [];
        this.formKey = this.item.id ? 'addon-edit-' + this.item.id : 'addon-create';
        $dispatch('open-dialog', { id: 'addon-form', trigger });
    },
    init() {
        const old = @js($restore);
        if (old) this.$nextTick(() => this.edit(old));
    }
}">
    <x-owner.page-header title="Layanan" purpose="Yang Anda jual: harga, durasi, kapasitas dan tambahan.">
        <x-slot:action>
            <button id="btn-add-addon" type="button" @click="edit(null, $el)" class="bq-btn bq-btn-primary">Tambah tambahan</button>
        </x-slot:action>
    </x-owner.page-header>

    <x-owner.tabs :tabs="['layanan' => 'Layanan', 'kategori' => 'Kategori', 'add-ons' => 'Tambahan']" active="add-ons" :counts="$tabCounts ?? []" label="Bagian halaman Layanan" />

    <form method="GET" action="{{ route('owner.services') }}" role="search" class="flex flex-wrap items-end gap-2">
        <input type="hidden" name="tab" value="add-ons">
        <label class="text-sm">Cari tambahan
            <input name="search" value="{{ $search }}" class="{{ $input }}" type="search">
        </label>
        <button class="bq-btn bq-btn-secondary">Cari</button>
    </form>

    @if ($items->isNotEmpty())
    <x-ui.table :columns="['Tambahan', 'Harga', 'Stok', 'Dipakai di', 'Terjual']" label="Daftar tambahan" actions>
        @foreach ($items as $item)
            @php
                $payload = $item->only(['id', 'name', 'description', 'price', 'stock']) + [
                    'is_active' => $item->is_active ? '1' : '0',
                    'service_ids' => $item->services->modelKeys(),
                ];
            @endphp
            <div role="row" class="bq-table-row bq-table-double">
                <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                    <span class="block truncate font-semibold" title="{{ $item->name }}">{{ $item->name }}</span>
                    <span class="bq-table-caption block truncate text-bq-small text-bq-text-muted md:hidden">
                        @if ((string) $item->stock === '0') Habis · @endif
                        {{ $item->services->count() }} layanan · {{ $sales[$item->id] ?? 0 }} terjual{{ $item->is_active ? '' : ' · Nonaktif' }}
                    </span>
                    @unless ($item->is_active)<span class="hidden text-bq-small text-bq-text-muted md:block">Nonaktif</span>@endunless
                </div>
                <div role="cell" class="bq-table-cell bq-table-end bq-num">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</div>
                <div role="cell" class="bq-table-cell text-bq-small">
                    @if ((string) $item->stock === '0')
                        <x-ui.status-dot tone="danger">Habis</x-ui.status-dot>
                    @else
                        {{ $item->stock === null ? 'Tanpa batas' : $item->stock.' tersisa' }}
                    @endif
                </div>
                <div role="cell" class="bq-table-cell text-bq-small" title="{{ $item->services->pluck('namalayanan')->implode(', ') }}">{{ $item->services->count() }} layanan</div>
                <div role="cell" class="bq-table-cell bq-num"><span class="sr-only">Terjual </span>{{ $sales[$item->id] ?? 0 }}</div>
                <div role="cell" class="bq-table-action">
                    <x-ui.menu :id="'addon-actions-'.$item->id" :label="'Tindakan '.$item->name">
                    <button role="menuitem" tabindex="-1" type="button" @click="edit(@js($payload), $el)" class="text-bq-primary">Ubah</button>
                    <form method="POST" action="{{ route($itemRoute.'.toggle', $item->id) }}">
                        @csrf
                        @method('PATCH')
                        <button role="menuitem" tabindex="-1" type="submit" class="text-bq-primary">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                    <form id="delete-addon-{{ $item->id }}" method="POST" action="{{ route($itemRoute.'.destroy', $item->id) }}">
                        @csrf
                        @method('DELETE')
                        <button role="menuitem" tabindex="-1" type="button" class="text-bq-danger"
                            @click="$dispatch('open-confirm', { title: 'Hapus tambahan?', message: 'Yakin ingin menghapus ' + @js($item->name) + '?', formId: $el.form.id, tone: 'danger' })">Hapus</button>
                    </form>
                    </x-ui.menu>
                </div>
            </div>
        @endforeach
    </x-ui.table>
    <p class="text-bq-small text-bq-text-muted">Stok berkurang saat booking dibayar. Tambahan yang habis tidak bisa dipilih pelanggan.</p>
    @else
            <x-owner.empty-state :title="$search !== '' ? 'Tambahan tidak ditemukan' : 'Belum ada tambahan'"
                :description="$search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan produk yang dapat dipilih saat pelanggan memesan layanan.'" />
    @endif

    <x-owner.dialog id="addon-form" title="Tambahan" size="lg">
        <form method="POST" class="space-y-4"
            :action="item.id ? @js(route($itemRoute.'.update', ['id' => '__ID__'])).replace('__ID__', encodeURIComponent(item.id)) : @js(route($itemRoute.'.store'))">
            @csrf
            <input type="hidden" name="_method" value="PUT" :disabled="!item.id">
            <input type="hidden" name="_form" :value="formKey">
            <div>
                <label for="addon-name" class="font-medium">Nama tambahan</label>
                <input id="addon-name" name="name" x-model="item.name" required maxlength="150" class="{{ $input }}">
                <x-owner.field-error name="name" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
            </div>
            <div>
                <label for="addon-description" class="font-medium">Deskripsi</label>
                <textarea id="addon-description" name="description" x-model="item.description" maxlength="500" rows="2" class="{{ $input }}"></textarea>
                <x-owner.field-error name="description" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="addon-price" class="font-medium">Harga (Rp)</label>
                    <input id="addon-price" name="price" type="number" x-model="item.price" step="0.01" min="0" required class="{{ $input }}">
                    <x-owner.field-error name="price" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
                </div>
                <div>
                    <label for="addon-stock" class="font-medium">Stok</label>
                    <input id="addon-stock" name="stock" type="number" x-model="item.stock" min="0" placeholder="Tanpa batas" class="{{ $input }}">
                    <p class="mt-1 text-xs text-bq-text-muted">Kosongkan untuk stok tanpa batas. Stok berkurang setelah pembayaran booking berhasil.</p>
                    <x-owner.field-error name="stock" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
                </div>
            </div>
            <fieldset class="space-y-2">
                <legend class="font-medium">Layanan yang ditautkan</legend>
                @forelse ($services as $service)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" x-model="item.service_ids">
                        {{ $service->namalayanan }}
                    </label>
                @empty
                    <p class="text-sm text-bq-text-muted">Tambahkan layanan untuk menautkannya ke tambahan ini.</p>
                @endforelse
                <x-owner.field-error name="service_ids" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
                <x-owner.field-error name="service_ids.*" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
            </fieldset>
            <div>
                <label for="addon-active" class="font-medium">Status</label>
                <select id="addon-active" name="is_active" x-model="item.is_active" class="{{ $input }}">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <x-owner.field-error name="is_active" x-cloak x-show="formKey === {{ \Illuminate\Support\Js::from($oldForm) }}" />
            </div>
            <button type="submit" class="bq-btn bq-btn-primary">Simpan tambahan</button>
        </form>
    </x-owner.dialog>
</div>
