@extends('layouts.owner-layout')

@section('title', 'Layanan')

{{--
    Layanan (BQ-132, owner-portal.md §8.6): tab Layanan (services, usage meter) and tab Kategori.
    Data comes from App\Actions\Service\GetLayananPage; this view only renders it.
    Visual style follows the existing owner pages (craft cards, icon tiles, soft shadows).
--}}

@php
    $isServicesTab = $activeTab === 'layanan';
    $oldContext = old('form_context');
    $serviceUpdateUrl = route('owner.services.update', ['program' => '__ID__']);
    $serviceToggleUrl = route('owner.services.toggle', ['id' => '__ID__']);
    $serviceDestroyUrl = route('owner.services.destroy', ['program' => '__ID__']);
    $categoryUpdateUrl = route('owner.categories.update', ['id' => '__ID__']);
    $categoryDestroyUrl = route('owner.categories.destroy', ['id' => '__ID__']);
    $inputClass = 'mt-1.5 block w-full rounded-xl border border-bq-border bg-bq-surface px-3.5 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20';
    $filterClass = 'w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 px-3 text-xs sm:text-sm text-bq-text transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20';
    $labelClass = 'block text-sm font-medium text-bq-text';
    $buttonPrimary = 'craft-btn inline-flex items-center justify-center gap-2 rounded-xl bg-bq-primary px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0';
    $buttonSecondary = 'craft-btn inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-3 py-2 text-xs font-bold text-[#6e6584] shadow-2xs hover:bg-[#f3effe] hover:text-[#382186]';
    $iconButton = 'craft-btn rounded-xl p-1.5 text-[#6e6584] transition hover:bg-[#f3effe] hover:text-[#382186] cursor-pointer';
    $softCard = 'rounded-2xl border border-[#e7e2f7] bg-white shadow-[0_4px_20px_rgba(35,26,61,0.03)]';
    $colorTiles = [
        'purple' => 'bg-[#f3effe] text-[#382186] border-[#e7e2f7]',
        'indigo' => 'bg-[#f3effe] text-[#382186] border-[#e7e2f7]',
        'lavender' => 'bg-[#f8f6ff] text-[#7a5af8] border-[#e7e2f7]',
        'violet' => 'bg-violet-50 text-violet-700 border-violet-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
    ];
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
                <button type="button" id="service-create-button" class="{{ $buttonPrimary }} w-full sm:w-auto" x-on:click="openService(null, $el)">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah layanan</span>
                </button>
            @elseif (! $isServicesTab)
                <button type="button" id="category-create-button" class="{{ $buttonPrimary }} w-full sm:w-auto" x-on:click="openCategory(null, $el)">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah kategori</span>
                </button>
            @endif
        </x-slot:action>
    </x-owner.page-header>

    <x-owner.tabs :tabs="['layanan' => 'Layanan', 'kategori' => 'Kategori']" :active="$activeTab" label="Bagian halaman Layanan" />

    @if ($isServicesTab)
        {{-- ── Usage meter ── --}}
        @php
            $percent = $usage['unlimited'] || $usage['max'] <= 0 ? 0 : min(100, (int) round($usage['used'] / $usage['max'] * 100));
        @endphp
        <section aria-labelledby="usage-meter-label" class="{{ $softCard }} flex items-center gap-4 p-4 sm:p-5">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[#b499ff]/20 bg-[#f3effe] text-[#382186]" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p id="usage-meter-label" class="text-sm font-extrabold text-[#231a3d]">
                        @if ($usage['unlimited'])
                            {{ $usage['used'] }} layanan · tanpa batas di paket Anda
                        @else
                            {{ $usage['used'] }} dari {{ $usage['max'] }} layanan
                        @endif
                    </p>
                    @unless ($usage['unlimited'])
                        <p class="text-[11px] font-semibold text-[#6e6584]">Batas jumlah layanan mengikuti paket langganan.</p>
                    @endunless
                </div>
                @unless ($usage['unlimited'])
                    <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-[#f3effe]" role="progressbar" aria-labelledby="usage-meter-label" aria-valuemin="0" aria-valuemax="{{ $usage['max'] }}" aria-valuenow="{{ $usage['used'] }}">
                        <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-amber-500' : 'bg-[#382186]' }}" style="width: {{ $percent }}%"></div>
                    </div>
                @endunless
            </div>
        </section>

        @unless ($usage['canCreate'])
            {{-- AC-LYN-01: the server refuses a service above the limit; this explains why and where to go. --}}
            <x-owner.upgrade-prompt feature="Layanan tambahan" plan="yang lebih tinggi" :description="$usage['message']" />
        @endunless

        {{-- ── Search & filter ── --}}
        <form method="GET" action="{{ route('owner.services') }}" class="flex flex-col gap-2.5 sm:flex-row sm:items-center" role="search">
            <input type="hidden" name="tab" value="layanan">
            <div class="relative w-full sm:max-w-xs">
                <label for="service-search" class="sr-only">Cari layanan</label>
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="service-search" type="search" name="katakunci" value="{{ $filters['katakunci'] }}" placeholder="Cari nama layanan..." class="{{ $filterClass }} pl-10 pr-4">
            </div>
            <div class="w-full sm:w-auto">
                <label for="service-category-filter" class="sr-only">Kategori</label>
                <select id="service-category-filter" name="category" class="{{ $filterClass }} sm:w-auto" onchange="this.form.requestSubmit()">
                    <option value="">Semua kategori</option>
                    @foreach ($categoryOptions as $option)
                        <option value="{{ $option['id'] }}" @selected($filters['category'] === $option['id'])>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="{{ $buttonSecondary }} py-2.5">Cari</button>
        </form>

        @if (count($serviceCards) === 0)
            @if ($filters['katakunci'] !== '' || $filters['category'] !== null)
                <x-owner.empty-state title="Tidak ada layanan yang cocok" description="Ubah kata kunci atau kategori untuk melihat layanan lain." action-label="Tampilkan semua" :action-href="route('owner.services')" />
            @else
                <x-owner.empty-state title="Belum ada layanan" description="Tambahkan layanan pertama Anda. Setelah disimpan, buka Jam Buka & Harga agar pelanggan bisa memilih slot.">
                    @if ($usage['canCreate'])
                        <x-slot:action>
                            <button type="button" class="{{ $buttonPrimary }}" x-on:click="openService(null, $el)">+ Tambah layanan</button>
                        </x-slot:action>
                    @endif
                </x-owner.empty-state>
            @endif
        @else
            {{-- ── Service cards ── --}}
            <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar layanan">
                @foreach ($serviceCards as $card)
                    <li class="group flex flex-col overflow-hidden rounded-2xl border border-bq-border bg-bq-surface transition-all duration-300 hover:border-[#b499ff] hover:shadow-md {{ $card['isActive'] ? '' : 'opacity-75' }}">
                        <div class="relative flex aspect-square w-full items-center justify-center overflow-hidden bg-gradient-to-br from-[#f8f6ff] to-[#ede8fc]">
                            @if ($card['imageUrl'])
                                <div class="pointer-events-none absolute inset-0 select-none overflow-hidden" aria-hidden="true">
                                    <img src="{{ $card['imageUrl'] }}" alt="" class="h-full w-full scale-125 object-cover opacity-35 blur-2xl brightness-105">
                                    <div class="absolute inset-0 bg-white/20 backdrop-blur-xs"></div>
                                </div>
                                <div class="relative z-10 flex h-full w-full items-center justify-center p-2.5">
                                    <img src="{{ $card['imageUrl'] }}" alt="{{ $card['name'] }}" class="max-h-full max-w-full rounded-xl object-contain shadow-xs transition-transform duration-300 group-hover:scale-[1.02]" loading="lazy">
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center p-4 text-center text-[#b499ff]" aria-hidden="true">
                                    <svg class="h-12 w-12 stroke-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="mt-1 text-[11px] font-bold text-[#6e6584] opacity-70">Belum ada gambar</span>
                                </div>
                            @endif
                            @if ($card['isPopular'])
                                <span class="absolute left-3 top-3 z-20 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-800 shadow-xs">Populer</span>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="min-w-0 break-words text-sm font-extrabold text-[#231a3d]">{{ $card['name'] }}</h3>
                                <x-owner.status-badge type="availability" :status="$card['isActive'] ? 'active' : 'inactive'" />
                            </div>
                            @if ($card['categoryName'])
                                <p class="flex items-center gap-1.5 text-[11px] font-semibold text-[#6e6584]">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $card['categoryColor'] }}" aria-hidden="true"></span>
                                    {{ $card['categoryName'] }}
                                </p>
                            @endif
                            <p class="text-sm">
                                <span class="font-black text-[#382186]">{{ $card['priceLabel'] }}</span>
                                <span class="text-xs font-semibold text-[#6e6584]">/ {{ $card['priceUnit'] }} · {{ $card['duration'] }} {{ $card['durationUnit'] }}</span>
                            </p>
                            <p class="text-[11px] font-semibold text-[#6e6584]">{{ $card['bookingCount'] > 0 ? $card['bookingCount'].' booking tercatat' : 'Belum pernah dibooking' }}</p>

                            <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-[#e7e2f7] pt-3">
                                <button type="button" class="{{ $buttonSecondary }}" x-on:click="openService(@js([
                                    'id' => $card['id'], 'namalayanan' => $card['name'], 'idcategory' => $card['categoryId'] ?? '',
                                    'harga' => $card['price'], 'satuan_harga' => $card['priceUnit'], 'durasi' => $card['duration'],
                                    'satuan_durasi' => $card['durationUnit'], 'deskripsi' => $card['description'],
                                    'is_popular' => $card['isPopular'], 'is_active' => $card['isActive'], 'imageUrl' => $card['imageUrl'],
                                    'resource_ids' => $card['resourceIds'],
                                ]), $el)">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Ubah
                                </button>

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
                                        class="{{ $iconButton }} ml-auto hover:bg-rose-50 hover:text-rose-600"
                                        aria-label="Hapus layanan {{ $card['name'] }}"
                                        data-service-delete="{{ $card['id'] }}"
                                        x-on:click="$dispatch('open-confirm', {
                                            title: 'Hapus layanan?',
                                            message: @js('Layanan "'.$card['name'].'" akan dihapus permanen. Layanan ini belum pernah dibooking.'),
                                            actionUrl: url(@js($serviceDestroyUrl), @js($card['id'])),
                                            method: 'DELETE',
                                            trigger: $el,
                                        })"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @else
                                    <p class="w-full text-[11px] font-semibold text-[#6e6584]">Sudah pernah dibooking, jadi hanya bisa dinonaktifkan.</p>
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

                <p class="text-sm font-extrabold text-[#231a3d]" x-text="service.id ? 'Ubah layanan' : 'Tambah layanan'"></p>

                <div>
                    <label for="service-name" class="{{ $labelClass }}">Nama layanan</label>
                    <input id="service-name" name="namalayanan" type="text" required maxlength="255" x-model="service.namalayanan" class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="service-category" class="{{ $labelClass }}">Kategori <span class="font-normal text-bq-text-subtle">(opsional)</span></label>
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
                        <p class="mt-1 text-xs text-bq-text-subtle">Tampil ke pelanggan, misalnya "Rp 150.000 / sesi".</p>
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
                <p x-show="service.id" class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs font-semibold text-amber-800">
                    Perubahan harga dan durasi hanya berlaku untuk booking baru. Booking yang sudah ada tetap memakai jam dan nominalnya.
                </p>

                <div>
                    <label for="service-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-subtle">(opsional)</span></label>
                    <textarea id="service-description" name="deskripsi" rows="3" maxlength="1000" x-model="service.deskripsi" class="{{ $inputClass }}"></textarea>
                </div>

                <div>
                    <label for="service-image" class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-subtle">(JPG, PNG, WEBP, maks. 10 MB)</span></label>
                    <template x-if="service.imageUrl && !service.remove_image">
                        <img x-bind:src="service.imageUrl" alt="" class="mt-2 h-24 w-24 rounded-xl border border-[#e7e2f7] object-cover">
                    </template>
                    <input id="service-image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-bq-text-muted file:mr-3 file:rounded-xl file:border-0 file:bg-[#f3effe] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#382186] hover:file:bg-[#e7e2f7]">
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
                <button type="button" class="{{ $buttonSecondary }} py-2.5" x-on:click="$dispatch('close-dialog', { id: 'service-form-dialog' })">Batal</button>
                <button type="submit" form="service-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
    @else
        {{-- ── Tab Kategori: toolbar ── --}}
        <div class="{{ $softCard }} flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('owner.categories') }}" role="search" class="relative w-full sm:max-w-xs">
                @if ($filters['status'] !== 'all')<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                @if ($filters['sort'] !== 'order')<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif
                <label for="category-search" class="sr-only">Cari kategori</label>
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6e6584]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="category-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari kategori..." class="w-full rounded-xl border border-[#e7e2f7] bg-white py-2 pl-9 pr-3.5 text-xs text-[#231a3d] placeholder-[#6e6584] shadow-2xs transition focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30">
            </form>
            <div class="flex flex-wrap items-center gap-2">
                <nav aria-label="Status kategori" class="flex items-center rounded-xl border border-[#e7e2f7] bg-[#f7f7fa] p-1 text-xs">
                    @foreach (['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $statusKey => $statusLabel)
                        <a
                            href="{{ route('owner.categories', array_filter(['search' => $filters['search'] ?: null, 'sort' => $filters['sort'] !== 'order' ? $filters['sort'] : null, 'status' => $statusKey !== 'all' ? $statusKey : null])) }}"
                            @if ($filters['status'] === $statusKey) aria-current="page" @endif
                            class="rounded-lg px-2.5 py-1 font-bold transition {{ $filters['status'] === $statusKey ? 'bg-white text-[#382186] shadow-2xs' : 'text-[#6e6584] hover:text-[#231a3d]' }}"
                        >{{ $statusLabel }}</a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('owner.categories') }}">
                    @if ($filters['search'] !== '')<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                    @if ($filters['status'] !== 'all')<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                    <label for="category-sort" class="sr-only">Urutkan</label>
                    <select id="category-sort" name="sort" onchange="this.form.requestSubmit()" class="rounded-xl border border-[#e7e2f7] bg-white px-3 py-2 text-xs font-bold text-[#231a3d] shadow-2xs transition focus:border-[#382186] focus:outline-none focus:ring-2 focus:ring-[#b499ff]/30">
                        <option value="order" @selected($filters['sort'] === 'order')>Urutan tampil</option>
                        <option value="newest" @selected($filters['sort'] === 'newest')>Terbaru</option>
                        <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Nama (A - Z)</option>
                        <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Nama (Z - A)</option>
                        <option value="services" @selected($filters['sort'] === 'services')>Layanan terbanyak</option>
                    </select>
                </form>
            </div>
        </div>

        @if (count($categories) === 0)
            @if ($filters['search'] !== '' || $filters['status'] !== 'all')
                <x-owner.empty-state title="Tidak ada kategori yang cocok" description="Ubah pencarian atau status untuk melihat kategori lain." action-label="Tampilkan semua" :action-href="route('owner.categories')" />
            @else
                <x-owner.empty-state title="Belum ada kategori" description="Kategori mengelompokkan layanan di halaman booking, misalnya Wisuda, Keluarga atau Prewedding.">
                    <x-slot:action>
                        <button type="button" class="{{ $buttonPrimary }}" x-on:click="openCategory(null, $el)">+ Tambah kategori</button>
                    </x-slot:action>
                </x-owner.empty-state>
            @endif
        @else
            {{-- ── Category cards ── --}}
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar kategori">
                @foreach ($categories as $row)
                    <li class="craft-card {{ $softCard }} flex flex-col justify-between p-5 hover:border-[#b499ff]">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if ($row['imageUrl'])
                                        <img src="{{ $row['imageUrl'] }}" alt="" class="h-10 w-10 shrink-0 rounded-xl border border-[#e7e2f7] object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border text-sm font-black {{ $colorTiles[$row['color']] ?? $colorTiles['purple'] }}" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <h3 class="break-words text-sm font-extrabold text-[#231a3d]">{{ $row['name'] }}</h3>
                                        <span class="text-[11px] font-semibold text-[#6e6584]">{{ $row['serviceCount'] }} layanan · Urutan {{ $row['sortOrder'] }}</span>
                                    </div>
                                </div>
                                <x-owner.status-badge type="availability" :status="$row['isActive'] ? 'active' : 'inactive'" />
                            </div>
                            <p class="mt-3 line-clamp-2 text-xs leading-relaxed text-[#6e6584]">{{ $row['description'] !== '' ? $row['description'] : 'Tidak ada deskripsi.' }}</p>
                            <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#6e6584]">
                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $row['colorHex'] }}" aria-hidden="true"></span>{{ $colors[$row['color']]['label'] }}
                            </p>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-[#e7e2f7] pt-3.5 text-xs">
                            <a href="{{ route('owner.services', ['tab' => 'layanan', 'category' => $row['id']]) }}" class="inline-flex items-center gap-1 whitespace-nowrap text-[11px] font-bold text-[#382186] hover:underline">Lihat layanan &rarr;</a>
                            <div class="flex items-center gap-1">
                                <form method="POST" action="{{ route('owner.categories.toggle', ['id' => $row['id']]) }}">
                                    @csrf
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="craft-btn rounded-xl px-2 py-1.5 text-[11px] font-bold text-[#6e6584] transition hover:bg-[#f3effe] hover:text-[#382186]">{{ $row['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <button type="button" class="{{ $iconButton }}" aria-label="Ubah kategori {{ $row['name'] }}" x-on:click="openCategory(@js([
                                    'id' => $row['id'], 'name' => $row['name'], 'description' => $row['description'], 'color' => $row['color'],
                                    'sort_order' => $row['sortOrder'], 'is_active' => $row['isActive'], 'imageUrl' => $row['imageUrl'],
                                ]), $el)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @if ($row['canDelete'])
                                    <button
                                        type="button"
                                        class="{{ $iconButton }} hover:bg-rose-50 hover:text-rose-600"
                                        aria-label="Hapus kategori {{ $row['name'] }}"
                                        data-category-delete="{{ $row['id'] }}"
                                        x-on:click="$dispatch('open-confirm', {
                                            title: 'Hapus kategori?',
                                            message: @js('Kategori "'.$row['name'].'" akan dihapus permanen.'),
                                            actionUrl: url(@js($categoryDestroyUrl), @js($row['id'])),
                                            method: 'DELETE',
                                            trigger: $el,
                                        })"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="text-[11px] font-semibold text-[#6e6584]">Kategori yang masih dipakai layanan tidak bisa dihapus. Halaman booking menampilkan kategori menurut urutannya, dari angka terkecil.</p>
        @endif

        {{-- Category form (create and edit) --}}
        <x-owner.dialog id="category-form-dialog" title="Kategori">
            <form id="category-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-bind:action="category.id ? url(@js($categoryUpdateUrl), category.id) : @js(route('owner.categories.store'))">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!category.id">
                <input type="hidden" name="form_context" value="category">
                <input type="hidden" name="editing_id" x-bind:value="category.id || ''">

                <p class="text-sm font-extrabold text-[#231a3d]" x-text="category.id ? 'Ubah kategori' : 'Tambah kategori'"></p>

                <div>
                    <label for="category-name" class="{{ $labelClass }}">Nama kategori</label>
                    <input id="category-name" name="name" type="text" required maxlength="100" x-model="category.name" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="category-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-subtle">(opsional)</span></label>
                    <textarea id="category-description" name="description" rows="2" maxlength="500" x-model="category.description" class="{{ $inputClass }}"></textarea>
                </div>
                <fieldset>
                    <legend class="{{ $labelClass }}">Label warna</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($colors as $key => $color)
                            <label class="craft-btn inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-[#e7e2f7] bg-white px-2.5 py-1.5 text-xs font-bold text-[#6e6584] has-[:checked]:border-[#382186] has-[:checked]:bg-[#f3effe] has-[:checked]:text-[#382186]">
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
                    <p class="mt-1 text-xs text-bq-text-subtle">Angka kecil tampil lebih dulu di halaman booking.</p>
                </div>
                <div>
                    <label for="category-image" class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-subtle">(opsional, JPG, PNG, WEBP, maks. 5 MB)</span></label>
                    <template x-if="category.imageUrl && !category.remove_image">
                        <img x-bind:src="category.imageUrl" alt="" class="mt-2 h-16 w-16 rounded-xl border border-[#e7e2f7] object-cover">
                    </template>
                    <input id="category-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-bq-text-muted file:mr-3 file:rounded-xl file:border-0 file:bg-[#f3effe] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#382186] hover:file:bg-[#e7e2f7]">
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
                <button type="button" class="{{ $buttonSecondary }} py-2.5" x-on:click="$dispatch('close-dialog', { id: 'category-form-dialog' })">Batal</button>
                <button type="submit" form="category-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
    @endif
</div>
@endsection
