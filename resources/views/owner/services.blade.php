@extends('layouts.owner-layout')

@section('title', 'Layanan')

{{--
    Layanan (BQ-132, owner-portal.md §8.6): tab Layanan (services, usage meter) and tab Kategori.
    Data comes from App\Actions\Service\GetLayananPage; this view only renders it.
--}}

@php
    $isServicesTab = $activeTab === 'layanan';
    $oldContext = old('form_context');
    $serviceUpdateUrl = route('owner.services.update', ['program' => '__ID__']);
    $serviceToggleUrl = route('owner.services.toggle', ['id' => '__ID__']);
    $serviceDestroyUrl = route('owner.services.destroy', ['program' => '__ID__']);
    $categoryUpdateUrl = route('owner.categories.update', ['id' => '__ID__']);
    $categoryDestroyUrl = route('owner.categories.destroy', ['id' => '__ID__']);
    $inputClass = 'mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/30';
    $labelClass = 'block text-sm font-medium text-bq-text';
    $buttonSecondary = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
    $buttonPrimary = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover';
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        service: {},
        category: {},
        blankService() {
            return { id: null, namalayanan: '', idcategory: '', harga: '', satuan_harga: 'sesi', durasi: 60, satuan_durasi: 'menit', deskripsi: '', is_popular: false, is_active: true, imageUrl: null, remove_image: false, resource_ids: [] };
        },
        blankCategory() {
            return { id: null, name: '', description: '', color: 'purple', sort_order: 0, is_active: true, imageUrl: null, remove_image: false };
        },
        openService(data, trigger) {
            this.service = Object.assign(this.blankService(), data || {});
            $dispatch('open-dialog', { id: 'service-form-dialog', trigger });
        },
        openCategory(data, trigger) {
            this.category = Object.assign(this.blankCategory(), data || {});
            $dispatch('open-dialog', { id: 'category-form-dialog', trigger });
        },
        url(template, id) {
            return template.replace('__ID__', encodeURIComponent(id));
        },
        init() {
            this.service = this.blankService();
            this.category = this.blankCategory();
            const old = @js($oldContext ? old() : null);
            if (!old) return;
            const toBool = (value) => value === true || value === '1' || value === 1;
            this.$nextTick(() => {
                if (old.form_context === 'service') {
                    this.openService({
                        id: old.editing_id || null, namalayanan: old.namalayanan || '', idcategory: old.idcategory || '',
                        harga: old.harga || '', satuan_harga: old.satuan_harga || 'sesi', durasi: old.durasi || 60,
                        satuan_durasi: old.satuan_durasi || 'menit', deskripsi: old.deskripsi || '',
                        is_popular: toBool(old.is_popular), is_active: toBool(old.is_active),
                        resource_ids: Array.isArray(old.resource_ids) ? old.resource_ids : [],
                    }, null);
                } else if (old.form_context === 'category') {
                    this.openCategory({
                        id: old.editing_id || null, name: old.name || '', description: old.description || '',
                        color: old.color || 'purple', sort_order: old.sort_order || 0, is_active: toBool(old.is_active),
                    }, null);
                }
            });
        },
    }"
>
    <x-owner.page-header title="Layanan" purpose="Apa yang Anda jual: layanan, harga dasar, durasi dan kategorinya.">
        <x-slot:action>
            @if ($isServicesTab && $usage['canCreate'])
                <button type="button" id="service-create-button" class="{{ $buttonPrimary }}" x-on:click="openService(null, $el)">
                    <x-owner.icon name="plus" class="h-4 w-4" /> Tambah layanan
                </button>
            @elseif (! $isServicesTab)
                <button type="button" id="category-create-button" class="{{ $buttonPrimary }}" x-on:click="openCategory(null, $el)">
                    <x-owner.icon name="plus" class="h-4 w-4" /> Tambah kategori
                </button>
            @endif
        </x-slot:action>
    </x-owner.page-header>

    <x-owner.tabs :tabs="['layanan' => 'Layanan', 'kategori' => 'Kategori']" :active="$activeTab" label="Bagian halaman Layanan" />

    @if ($isServicesTab)
        {{-- ── Tab Layanan ── --}}
        <section aria-labelledby="usage-meter-label" class="rounded-xl border border-bq-border bg-bq-surface p-4">
            @php
                $percent = $usage['unlimited'] || $usage['max'] <= 0 ? 0 : min(100, (int) round($usage['used'] / $usage['max'] * 100));
            @endphp
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p id="usage-meter-label" class="text-sm font-medium text-bq-text">
                    @if ($usage['unlimited'])
                        {{ $usage['used'] }} layanan · tanpa batas di paket Anda
                    @else
                        {{ $usage['used'] }} dari {{ $usage['max'] }} layanan
                    @endif
                </p>
                @unless ($usage['unlimited'])
                    <p class="text-xs text-bq-text-muted">Batas jumlah layanan mengikuti paket langganan.</p>
                @endunless
            </div>
            @unless ($usage['unlimited'])
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-bq-background" role="progressbar" aria-labelledby="usage-meter-label" aria-valuemin="0" aria-valuemax="{{ $usage['max'] }}" aria-valuenow="{{ $usage['used'] }}">
                    <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-bq-warning' : 'bg-bq-primary' }}" style="width: {{ $percent }}%"></div>
                </div>
            @endunless
        </section>

        @unless ($usage['canCreate'])
            {{-- AC-LYN-01: the server refuses a service above the limit; this explains why and where to go. --}}
            <x-owner.upgrade-prompt feature="Layanan tambahan" plan="yang lebih tinggi" :description="$usage['message']" />
        @endunless

        <form method="GET" action="{{ route('owner.services') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end" role="search">
            <input type="hidden" name="tab" value="layanan">
            <div class="flex-1">
                <label for="service-search" class="sr-only">Cari layanan</label>
                <input id="service-search" type="search" name="katakunci" value="{{ $filters['katakunci'] }}" placeholder="Cari nama layanan" class="{{ $inputClass }} mt-0">
            </div>
            <div class="sm:w-56">
                <label for="service-category-filter" class="sr-only">Kategori</label>
                <select id="service-category-filter" name="category" class="{{ $inputClass }} mt-0" onchange="this.form.requestSubmit()">
                    <option value="">Semua kategori</option>
                    @foreach ($categoryOptions as $option)
                        <option value="{{ $option['id'] }}" @selected($filters['category'] === $option['id'])>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="{{ $buttonSecondary }} py-2">Cari</button>
        </form>

        @if (count($serviceCards) === 0)
            @if ($filters['katakunci'] !== '' || $filters['category'] !== null)
                <x-owner.empty-state title="Tidak ada layanan yang cocok" description="Ubah kata kunci atau kategori untuk melihat layanan lain." action-label="Tampilkan semua" :action-href="route('owner.services')" />
            @else
                <x-owner.empty-state title="Belum ada layanan" description="Tambahkan layanan pertama Anda. Setelah disimpan, buka Jam Buka & Harga agar pelanggan bisa memilih slot.">
                    @if ($usage['canCreate'])
                        <x-slot:action>
                            <button type="button" class="{{ $buttonPrimary }}" x-on:click="openService(null, $el)">Tambah layanan</button>
                        </x-slot:action>
                    @endif
                </x-owner.empty-state>
            @endif
        @else
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar layanan">
                @foreach ($serviceCards as $card)
                    <li class="flex flex-col overflow-hidden rounded-xl border border-bq-border bg-bq-surface {{ $card['isActive'] ? '' : 'opacity-75' }}">
                        <div class="relative aspect-[16/9] bg-bq-background">
                            @if ($card['imageUrl'])
                                <img src="{{ $card['imageUrl'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-full items-center justify-center text-bq-text-muted" aria-hidden="true">
                                    <x-owner.icon name="services" class="h-8 w-8" />
                                </div>
                            @endif
                            @if ($card['isPopular'])
                                <span class="absolute left-3 top-3 rounded-full bg-bq-warning-bg px-2 py-0.5 text-xs font-semibold text-bq-warning">Populer</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="min-w-0 break-words text-base font-semibold text-bq-text">{{ $card['name'] }}</h3>
                                <x-owner.status-badge type="availability" :status="$card['isActive'] ? 'active' : 'inactive'" />
                            </div>
                            @if ($card['categoryName'])
                                <p class="flex items-center gap-1.5 text-xs text-bq-text-muted">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $card['categoryColor'] }}" aria-hidden="true"></span>
                                    {{ $card['categoryName'] }}
                                </p>
                            @endif
                            <p class="text-sm text-bq-text">
                                <span class="font-semibold">{{ $card['priceLabel'] }}</span>
                                <span class="text-bq-text-muted">/ {{ $card['priceUnit'] }} · {{ $card['duration'] }} {{ $card['durationUnit'] }}</span>
                            </p>
                            <p class="text-xs text-bq-text-muted">{{ $card['bookingCount'] > 0 ? $card['bookingCount'].' booking tercatat' : 'Belum pernah dibooking' }}</p>

                            <div class="mt-auto flex flex-wrap gap-2 pt-2">
                                <button type="button" class="{{ $buttonSecondary }}" x-on:click="openService(@js([
                                    'id' => $card['id'], 'namalayanan' => $card['name'], 'idcategory' => $card['categoryId'] ?? '',
                                    'harga' => $card['price'], 'satuan_harga' => $card['priceUnit'], 'durasi' => $card['duration'],
                                    'satuan_durasi' => $card['durationUnit'], 'deskripsi' => $card['description'],
                                    'is_popular' => $card['isPopular'], 'is_active' => $card['isActive'], 'imageUrl' => $card['imageUrl'],
                                    'resource_ids' => $card['resourceIds'],
                                ]), $el)">Ubah</button>

                                <form method="POST" x-bind:action="url(@js($serviceToggleUrl), @js($card['id']))">
                                    @csrf
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="{{ $buttonSecondary }}" data-service-{{ $card['isActive'] ? 'deactivate' : 'activate' }}="{{ $card['id'] }}">
                                        {{ $card['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                @if ($card['canDelete'])
                                    <button
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
                        </div>
                    </li>
                @endforeach
            </ul>

            <div>{{ $services->links() }}</div>
        @endif

        {{-- Service form (create and edit) --}}
        <x-owner.dialog id="service-form-dialog" title="Layanan" size="lg">
            <form id="service-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-bind:action="service.id ? url(@js($serviceUpdateUrl), service.id) : @js(route('owner.services.store'))">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!service.id">
                <input type="hidden" name="form_context" value="service">
                <input type="hidden" name="editing_id" x-bind:value="service.id || ''">
                {{-- Unit links are managed on the Unit page; send the current ones back unchanged (BQ-045). --}}
                <template x-for="resourceId in service.resource_ids" x-bind:key="resourceId">
                    <input type="hidden" name="resource_ids[]" x-bind:value="resourceId">
                </template>

                <p class="text-sm font-semibold text-bq-text" x-text="service.id ? 'Ubah layanan' : 'Tambah layanan'"></p>

                <div>
                    <label for="service-name" class="{{ $labelClass }}">Nama layanan</label>
                    <input id="service-name" name="namalayanan" type="text" required maxlength="255" x-model="service.namalayanan" class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="service-category" class="{{ $labelClass }}">Kategori <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <select id="service-category" name="idcategory" x-model="service.idcategory" class="{{ $inputClass }}">
                        <option value="">Tanpa kategori</option>
                        @foreach ($categoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="service-price" class="{{ $labelClass }}">Harga dasar (Rp)</label>
                        <input id="service-price" name="harga" type="number" min="0" step="1" required x-model="service.harga" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="service-price-unit" class="{{ $labelClass }}">Satuan harga</label>
                        <input id="service-price-unit" name="satuan_harga" type="text" maxlength="20" list="service-price-units" x-model="service.satuan_harga" class="{{ $inputClass }}">
                        <datalist id="service-price-units"><option value="sesi"><option value="orang"><option value="jam"><option value="paket"></datalist>
                        <p class="mt-1 text-xs text-bq-text-muted">Tampil ke pelanggan, misalnya "Rp 150.000 / sesi".</p>
                    </div>
                    <div>
                        <label for="service-duration" class="{{ $labelClass }}">Durasi (menit)</label>
                        <input id="service-duration" name="durasi" type="number" min="5" max="480" step="5" required x-model="service.durasi" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="service-duration-unit" class="{{ $labelClass }}">Satuan durasi</label>
                        <input id="service-duration-unit" name="satuan_durasi" type="text" maxlength="20" list="service-duration-units" x-model="service.satuan_durasi" class="{{ $inputClass }}">
                        <datalist id="service-duration-units"><option value="menit"><option value="jam"><option value="sesi"></datalist>
                    </div>
                </div>

                {{-- AC-LYN-03 --}}
                <p x-show="service.id" class="rounded-lg bg-bq-warning-bg px-3 py-2 text-xs text-bq-warning">
                    Perubahan harga dan durasi hanya berlaku untuk booking baru. Booking yang sudah ada tetap memakai jam dan nominalnya.
                </p>

                <div>
                    <label for="service-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <textarea id="service-description" name="deskripsi" rows="3" maxlength="1000" x-model="service.deskripsi" class="{{ $inputClass }}"></textarea>
                </div>

                <div>
                    <label for="service-image" class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-muted">(JPG, PNG, WEBP, maks. 10 MB)</span></label>
                    <template x-if="service.imageUrl && !service.remove_image">
                        <img x-bind:src="service.imageUrl" alt="" class="mt-2 h-24 w-40 rounded-lg object-cover">
                    </template>
                    <input id="service-image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-bq-text-muted">
                    <label x-show="service.imageUrl" class="mt-2 inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="checkbox" name="remove_image" value="1" x-model="service.remove_image" class="rounded border-bq-border text-bq-primary"> Hapus gambar
                    </label>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="hidden" name="is_popular" value="0">
                        <input type="checkbox" name="is_popular" value="1" x-model="service.is_popular" class="rounded border-bq-border text-bq-primary">
                        Tandai populer di halaman booking
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="service.is_active" class="rounded border-bq-border text-bq-primary">
                        Aktif (bisa dibooking)
                    </label>
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'service-form-dialog' })">Batal</button>
                <button type="submit" form="service-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
    @else
        {{-- ── Tab Kategori ── --}}
        <form method="GET" action="{{ route('owner.categories') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end" role="search">
            <div class="flex-1">
                <label for="category-search" class="sr-only">Cari kategori</label>
                <input id="category-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama atau deskripsi" class="{{ $inputClass }} mt-0">
            </div>
            <div class="sm:w-40">
                <label for="category-status" class="sr-only">Status</label>
                <select id="category-status" name="status" class="{{ $inputClass }} mt-0" onchange="this.form.requestSubmit()">
                    <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="sm:w-48">
                <label for="category-sort" class="sr-only">Urutkan</label>
                <select id="category-sort" name="sort" class="{{ $inputClass }} mt-0" onchange="this.form.requestSubmit()">
                    <option value="order" @selected($filters['sort'] === 'order')>Urutan tampil</option>
                    <option value="newest" @selected($filters['sort'] === 'newest')>Terbaru</option>
                    <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Nama A–Z</option>
                    <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Nama Z–A</option>
                    <option value="services" @selected($filters['sort'] === 'services')>Layanan terbanyak</option>
                </select>
            </div>
            <button type="submit" class="{{ $buttonSecondary }} py-2">Cari</button>
        </form>

        @if (count($categories) === 0)
            @if ($filters['search'] !== '' || $filters['status'] !== 'all')
                <x-owner.empty-state title="Tidak ada kategori yang cocok" description="Ubah pencarian atau status untuk melihat kategori lain." action-label="Tampilkan semua" :action-href="route('owner.categories')" />
            @else
                <x-owner.empty-state title="Belum ada kategori" description="Kategori mengelompokkan layanan di halaman booking, misalnya Wisuda, Keluarga atau Prewedding.">
                    <x-slot:action>
                        <button type="button" class="{{ $buttonPrimary }}" x-on:click="openCategory(null, $el)">Tambah kategori</button>
                    </x-slot:action>
                </x-owner.empty-state>
            @endif
        @else
            <ul class="divide-y divide-bq-border overflow-hidden rounded-xl border border-bq-border bg-bq-surface" aria-label="Daftar kategori">
                @foreach ($categories as $row)
                    <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            @if ($row['imageUrl'])
                                <img src="{{ $row['imageUrl'] }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover" loading="lazy">
                            @else
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-bq-background" aria-hidden="true">
                                    <span class="h-3 w-3 rounded-full" style="background-color: {{ $row['colorHex'] }}"></span>
                                </span>
                            @endif
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="break-words text-sm font-semibold text-bq-text">{{ $row['name'] }}</h3>
                                    <x-owner.status-badge type="availability" :status="$row['isActive'] ? 'active' : 'inactive'" />
                                </div>
                                @if ($row['description'] !== '')
                                    <p class="mt-0.5 break-words text-sm text-bq-text-muted">{{ $row['description'] }}</p>
                                @endif
                                <p class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-bq-text-muted">
                                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full" style="background-color: {{ $row['colorHex'] }}" aria-hidden="true"></span>{{ $colors[$row['color']]['label'] }}</span>
                                    <span>Urutan {{ $row['sortOrder'] }}</span>
                                    <span>{{ $row['serviceCount'] }} layanan</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('owner.services', ['tab' => 'layanan', 'category' => $row['id']]) }}" class="{{ $buttonSecondary }}">Lihat layanan</a>
                            <button type="button" class="{{ $buttonSecondary }}" x-on:click="openCategory(@js([
                                'id' => $row['id'], 'name' => $row['name'], 'description' => $row['description'], 'color' => $row['color'],
                                'sort_order' => $row['sortOrder'], 'is_active' => $row['isActive'], 'imageUrl' => $row['imageUrl'],
                            ]), $el)">Ubah</button>
                            <form method="POST" action="{{ route('owner.categories.toggle', ['id' => $row['id']]) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="{{ $buttonSecondary }}">{{ $row['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            @if ($row['canDelete'])
                                <button
                                    type="button"
                                    class="{{ $buttonSecondary }} text-bq-danger"
                                    data-category-delete="{{ $row['id'] }}"
                                    x-on:click="$dispatch('open-confirm', {
                                        title: 'Hapus kategori?',
                                        message: @js('Kategori "'.$row['name'].'" akan dihapus permanen.'),
                                        actionUrl: url(@js($categoryDestroyUrl), @js($row['id'])),
                                        method: 'DELETE',
                                        trigger: $el,
                                    })"
                                >Hapus</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-bq-text-muted">Kategori yang masih dipakai layanan tidak bisa dihapus. Halaman booking menampilkan kategori menurut urutannya, dari angka terkecil.</p>
        @endif

        {{-- Category form (create and edit) --}}
        <x-owner.dialog id="category-form-dialog" title="Kategori">
            <form id="category-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-bind:action="category.id ? url(@js($categoryUpdateUrl), category.id) : @js(route('owner.categories.store'))">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!category.id">
                <input type="hidden" name="form_context" value="category">
                <input type="hidden" name="editing_id" x-bind:value="category.id || ''">

                <p class="text-sm font-semibold text-bq-text" x-text="category.id ? 'Ubah kategori' : 'Tambah kategori'"></p>

                <div>
                    <label for="category-name" class="{{ $labelClass }}">Nama kategori</label>
                    <input id="category-name" name="name" type="text" required maxlength="100" x-model="category.name" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="category-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <textarea id="category-description" name="description" rows="2" maxlength="500" x-model="category.description" class="{{ $inputClass }}"></textarea>
                </div>
                <fieldset>
                    <legend class="{{ $labelClass }}">Label warna</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($colors as $key => $color)
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-bq-border px-2.5 py-1 text-xs text-bq-text has-[:checked]:border-bq-primary has-[:checked]:ring-2 has-[:checked]:ring-bq-primary/30">
                                <input type="radio" name="color" value="{{ $key }}" x-model="category.color" class="sr-only">
                                <span class="h-3 w-3 rounded-full" style="background-color: {{ $color['hex'] }}" aria-hidden="true"></span>
                                {{ $color['label'] }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label for="category-order" class="{{ $labelClass }}">Urutan tampil</label>
                    <input id="category-order" name="sort_order" type="number" min="0" max="9999" step="1" x-model="category.sort_order" class="{{ $inputClass }} sm:w-32">
                    <p class="mt-1 text-xs text-bq-text-muted">Angka kecil tampil lebih dulu di halaman booking.</p>
                </div>
                <div>
                    <label for="category-image" class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-muted">(opsional, JPG, PNG, WEBP, maks. 5 MB)</span></label>
                    <template x-if="category.imageUrl && !category.remove_image">
                        <img x-bind:src="category.imageUrl" alt="" class="mt-2 h-16 w-16 rounded-lg object-cover">
                    </template>
                    <input id="category-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-bq-text-muted">
                    <label x-show="category.imageUrl" class="mt-2 inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="checkbox" name="remove_image" value="1" x-model="category.remove_image" class="rounded border-bq-border text-bq-primary"> Hapus gambar
                    </label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="category.is_active" class="rounded border-bq-border text-bq-primary">
                    Aktif (tampil di halaman booking)
                </label>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'category-form-dialog' })">Batal</button>
                <button type="submit" form="category-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
    @endif
</div>
@endsection
