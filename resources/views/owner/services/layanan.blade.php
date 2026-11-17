        {{-- ── Tab Layanan ── --}}
        <section aria-labelledby="usage-meter-label" class="bq-panel p-4">
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
            @if (request('tampil') === 'kisi')<input type="hidden" name="tampil" value="kisi">@endif
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

        <x-ui.segmented label="Tampilan layanan" :selected="request('tampil') === 'kisi' ? 'grid' : 'list'" :options="[
            'list' => ['label' => 'Daftar', 'href' => route('owner.services', request()->except(['tampil', 'page']))],
            'grid' => ['label' => 'Kisi bergambar', 'href' => route('owner.services', ['tampil' => 'kisi'] + request()->except('page'))],
        ]" />

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
            @if (request('tampil') === 'kisi')
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar layanan">
                @foreach ($serviceCards as $card)
                    <li class="flex flex-col overflow-hidden bq-panel {{ $card['isActive'] ? '' : 'opacity-75' }}">
                        <div @class(['relative', 'aspect-[16/9] bg-bq-surface-2' => $card['imageUrl'], 'h-20 bg-bq-primary-light' => ! $card['imageUrl']])>
                            @if ($card['imageUrl'])
                                <img src="{{ $card['imageUrl'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-full items-center justify-end px-4 text-bq-primary/40" aria-hidden="true">
                                    <x-owner.icon name="services" class="h-10 w-10" />
                                </div>
                            @endif
                            @if ($card['isPopular'])
                                <span class="absolute left-3 top-3 rounded-full bg-bq-accent px-2.5 py-0.5 text-xs font-bold text-bq-ink">Populer</span>
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
                                <span class="text-bq-text-muted">/ {{ $card['priceUnit'] }} · {{ $card['duration'] }} menit</span>
                            </p>
                            <p class="text-xs text-bq-text-muted">{{ $card['bookingCount'] > 0 ? $card['bookingCount'].' booking tercatat' : 'Belum pernah dibooking' }}</p>

                            @include('owner.services.service-actions')
                        </div>
                    </li>
                @endforeach
            </ul>

            @else
                @php
                    $columns = $unitOptions === null ? ['Layanan', 'Harga', 'Durasi'] : ['Layanan', 'Harga', 'Durasi', 'Unit'];
                @endphp
                <x-ui.table :columns="$columns" label="Daftar layanan" actions>
                    @if ($commonSlotDate)
                        <x-slot:note>
                            <x-ui.table-note>
                                Slot layanan terbuka sampai {{ \Carbon\Carbon::parse($commonSlotDate)->locale('id')->translatedFormat('j M') }}, kecuali yang ditandai.
                                <x-slot:action><a class="bq-link" href="{{ route('owner.schedule', ['tab' => 'slots']) }}">Atur slot</a></x-slot:action>
                            </x-ui.table-note>
                        </x-slot:note>
                    @endif
                    @foreach ($serviceCards as $card)
                        @php
                            $until = $slotRunway[$card['id']] ?? null;
                            $slotNote = ! $card['isActive'] ? 'Nonaktif' : match (true) {
                                $until === null => 'Belum ada slot terbuka',
                                $until !== $commonSlotDate => 'Slot sampai '.\Carbon\Carbon::parse($until)->locale('id')->translatedFormat('j M'),
                                default => '',
                            };
                            $caption = implode(' · ', array_filter([$card['categoryName'], $card['capacity'] > 1 ? 'maks. '.$card['capacity'].' orang' : '', $slotNote]));
                            $unitNames = implode(', ', $card['unitNames']);
                        @endphp
                        <div role="row" class="bq-table-row bq-table-double">
                            <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                                <span class="block truncate font-semibold" title="{{ $card['name'] }}">{{ $card['name'] }}</span>
                                @if ($caption)<span class="bq-table-caption block truncate text-bq-small text-bq-text-muted" title="{{ $caption }}">{{ $caption }}</span>@endif
                            </div>
                            <div role="cell" class="bq-table-cell bq-table-end bq-num">{{ $card['priceLabel'] }}</div>
                            <div role="cell" class="bq-table-cell"><x-ui.duration :minutes="$card['duration']" /></div>
                            @if ($unitOptions !== null)
                                <div role="cell" class="bq-table-cell truncate" title="{{ $unitNames }}">{{ $unitNames ?: '—' }}</div>
                            @endif
                            <div role="cell" class="bq-table-action">
                                <x-ui.menu :id="'service-actions-'.$card['id']" :label="'Tindakan '.$card['name']">
                                    @include('owner.services.service-actions', ['inMenu' => true])
                                </x-ui.menu>
                            </div>
                        </div>
                    @endforeach
                </x-ui.table>
            @endif

            <div>{{ $services->links() }}</div>
        @endif

        {{-- Service form (create and edit) --}}
        <x-owner.dialog id="service-form-dialog" title="Layanan" size="lg">
            <form id="service-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-bind:action="service.id ? url(@js($serviceUpdateUrl), service.id) : @js(route('owner.services.store'))">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!service.id">
                <input type="hidden" name="_form" :value="service.id ? 'service-edit-' + service.id : 'service-create'">
                <input type="hidden" name="editing_id" x-bind:value="service.id || ''">
                {{-- Unit links are managed on the Unit page; send the current ones back unchanged (BQ-045). --}}

                <p class="text-sm font-semibold text-bq-text" x-text="service.id ? 'Ubah layanan' : 'Tambah layanan'"></p>

                <div>
                    <label for="service-name" class="{{ $labelClass }}">Nama layanan</label>
                    <input id="service-name" name="namalayanan" type="text" required maxlength="255" x-model="service.namalayanan" class="{{ $inputClass }}">
                    <x-owner.field-error name="namalayanan" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                </div>

                <div>
                    <label for="service-category" class="{{ $labelClass }}">Kategori <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <select id="service-category" name="idcategory" x-model="service.idcategory" class="{{ $inputClass }}">
                        <option value="">Tanpa kategori</option>
                        @foreach ($categoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                    <x-owner.field-error name="idcategory" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="service-price" class="{{ $labelClass }}">Harga dasar (Rp)</label>
                        <input id="service-price" name="harga" type="number" min="0" step="1" required x-model="service.harga" class="{{ $inputClass }}">
                    <x-owner.field-error name="harga" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                    </div>
                    <div>
                        <label for="service-price-unit" class="{{ $labelClass }}">Satuan harga</label>
                        <input id="service-price-unit" name="satuan_harga" type="text" maxlength="20" list="service-price-units" x-model="service.satuan_harga" class="{{ $inputClass }}">
                    <x-owner.field-error name="satuan_harga" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                        <datalist id="service-price-units"><option value="sesi"><option value="orang"><option value="jam"><option value="paket"></datalist>
                        <p class="mt-1 text-xs text-bq-text-muted">Tampil ke pelanggan, misalnya "Rp 150.000 / sesi".</p>
                    </div>
                    <div>
                        <label for="service-duration" class="{{ $labelClass }}">Durasi (menit)</label>
                        <input id="service-duration" name="durasi" type="number" min="5" max="480" step="5" required x-model="service.durasi" class="{{ $inputClass }}">
                    <x-owner.field-error name="durasi" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                    </div>
                    <div>
                        <label for="service-duration-unit" class="{{ $labelClass }}">Label durasi di halaman booking</label>
                        <input id="service-duration-unit" name="satuan_durasi" type="text" maxlength="20" list="service-duration-units" x-model="service.satuan_durasi" class="{{ $inputClass }}">
                    <x-owner.field-error name="satuan_durasi" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                        <datalist id="service-duration-units"><option value="menit"><option value="jam"><option value="sesi"></datalist>
                    </div>
                    <div class="sm:col-span-2 text-xs" data-duration-preview>
                        <p class="text-bq-text-muted">Pelanggan melihat: <strong class="text-bq-text" x-text="(service.durasi || 0) + ' ' + ((service.satuan_durasi || '').trim() || 'menit')"></strong></p>
                        <p x-cloak x-show="['jam', 'hari'].includes((service.satuan_durasi || '').trim().toLowerCase())" class="mt-1 text-bq-warning">
                            Durasi diisi dalam menit. Dengan label ini pelanggan melihat angka menit diikuti "<span x-text="(service.satuan_durasi || '').trim()"></span>". Pakai label "menit", atau label seperti "sesi".
                        </p>
                    </div>
                </div>

                {{-- AC-LYN-03 --}}
                <div>
                    <label for="service-capacity" class="{{ $labelClass }}">Kapasitas per slot</label>
                    <input id="service-capacity" name="kapasitas" type="number" min="1" max="9999" required x-model="service.kapasitas" class="{{ $inputClass }}">
                    <x-owner.field-error name="kapasitas" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                </div>
                @if ($unitOptions !== null)
                    <fieldset>
                        <legend class="{{ $labelClass }}">Unit</legend>
                        <input type="hidden" name="unit_ids" value="">
                        @forelse ($unitOptions as $unit)
                            <label class="mt-2 flex items-center gap-2 text-sm">
                                <input type="checkbox" name="unit_ids[]" value="{{ $unit['id'] }}" x-model="service.unit_ids">
                                {{ $unit['name'] }}
                            </label>
                        @empty
                            <p class="text-sm text-bq-text-muted">Belum ada unit. <a class="text-bq-primary" href="{{ route('owner.units') }}">Kelola unit</a></p>
                        @endforelse
                        <x-owner.field-error name="unit_ids.*" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                    </fieldset>
                @endif

                @isset($addOnOptions)
                    <fieldset class="space-y-2">
                        <legend class="{{ $labelClass }}">Tambahan yang ditautkan</legend>
                        <input type="hidden" name="additional_item_ids" value="">
                        @forelse ($addOnOptions as $item)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="additional_item_ids[]" value="{{ $item->id }}" x-model="service.additional_item_ids">
                                {{ $item->name }}
                            </label>
                        @empty
                            <p class="text-sm text-bq-text-muted">Belum ada tambahan. <a class="text-bq-primary" href="{{ route('owner.services', ['tab' => 'add-ons']) }}">Kelola tambahan</a></p>
                        @endforelse
                        <x-owner.field-error name="additional_item_ids.*" :form="$oldForm" />
                    </fieldset>
                @endisset

                <p x-show="service.id" class="rounded-lg bg-bq-warning-bg px-3 py-2 text-xs text-bq-warning">
                    Perubahan harga dan durasi hanya berlaku untuk booking baru. Booking yang sudah ada tetap memakai jam dan nominalnya.
                </p>

                <div>
                    <label for="service-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <textarea id="service-description" name="deskripsi" rows="3" maxlength="1000" x-model="service.deskripsi" class="{{ $inputClass }}"></textarea>
                    <x-owner.field-error name="deskripsi" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                </div>

                {{-- BQ-142 (FR-ASSET-005): pick from the gallery, or upload a new image into it --}}
                <fieldset class="space-y-2">
                    <legend class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-muted">(opsional)</span></legend>
                    <x-owner.gallery-picker
                        name="image_asset_id"
                        :images="$pickerImages"
                        model="service.image_asset_id"
                        label="Pilih gambar layanan dari galeri"
                        :gallery-url="$galleryUrl"
                    />
                    <x-owner.field-error name="image_asset_id" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                    <label for="service-image" class="block text-xs text-bq-text-muted">Atau unggah gambar baru ke galeri (JPG, PNG, WEBP, maks. 10 MB)</label>
                    <input id="service-image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-bq-text-muted">
                    <x-owner.field-error name="cover_image" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                    <label x-show="service.imageUrl" class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="checkbox" name="remove_image" value="1" x-model="service.remove_image" class="rounded border-bq-border text-bq-primary"> Lepas gambar dari layanan
                    </label>
                </fieldset>

                <div class="grid gap-1">
                    <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="hidden" name="auto_open_slots" value="0">
                        <input type="checkbox" name="auto_open_slots" value="1" x-model="service.auto_open_slots" class="rounded border-bq-border text-bq-primary">
                        Buka slot otomatis dari jam buka
                    </label>
                    <p class="text-bq-small text-bq-text-muted">Setiap malam sistem membuka slot sampai 30 hari ke depan memakai durasi layanan. Slot yang sudah ada atau Anda tutup tidak diubah.</p>
                    <x-owner.field-error name="auto_open_slots" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="hidden" name="is_popular" value="0">
                        <input type="checkbox" name="is_popular" value="1" x-model="service.is_popular" class="rounded border-bq-border text-bq-primary">
                    <x-owner.field-error name="is_popular" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                        Tandai populer di halaman booking
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="service.is_active" class="rounded border-bq-border text-bq-primary">
                    <x-owner.field-error name="is_active" :form="$oldForm" x-cloak x-show="@js($oldForm) === (service.id ? 'service-edit-' + service.id : 'service-create')" />
                        Aktif (bisa dibooking)
                    </label>
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'service-form-dialog' })">Batal</button>
                <button type="submit" form="service-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
