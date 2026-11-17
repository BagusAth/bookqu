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
            <x-ui.table :columns="['Kategori', 'Keterangan', 'Layanan']" label="Daftar kategori" actions>
                @foreach ($categories as $row)
                    <div role="row" class="bq-table-row bq-table-double">
                        <div role="cell" class="bq-table-cell bq-table-main min-w-0">
                            <span class="flex min-w-0 items-center gap-2 font-semibold">
                                <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $row['colorHex'] }}" aria-hidden="true"></span>
                                <span class="truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                            </span>
                        </div>
                        <div role="cell" class="bq-table-cell bq-table-sub truncate text-bq-small text-bq-text-muted" title="{{ $row['description'] }}">
                            {{ $row['description'] ?: '—' }}{{ $row['isActive'] ? '' : ' · Nonaktif' }}
                        </div>
                        <div role="cell" class="bq-table-cell bq-table-end"><a class="bq-link" href="{{ route('owner.services', ['tab' => 'layanan', 'category' => $row['id']]) }}">{{ $row['serviceCount'] }} layanan</a></div>
                        <div role="cell" class="bq-table-action">
                          <x-ui.menu :id="'category-actions-'.$row['id']" :label="'Tindakan '.$row['name']">
                            <a href="{{ route('owner.services', ['tab' => 'layanan', 'category' => $row['id']]) }}" class="{{ $buttonSecondary }}" role="menuitem" tabindex="-1">Lihat layanan</a>
                            <button role="menuitem" tabindex="-1" type="button" class="{{ $buttonSecondary }}" x-on:click="openCategory(@js([
                                'id' => $row['id'], 'name' => $row['name'], 'description' => $row['description'], 'color' => $row['color'],
                                'sort_order' => $row['sortOrder'], 'is_active' => $row['isActive'], 'imageUrl' => $row['imageUrl'],
                            ]), $el)">Ubah</button>
                            <form method="POST" action="{{ route('owner.categories.toggle', ['id' => $row['id']]) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PATCH">
                                <button role="menuitem" tabindex="-1" type="submit" class="{{ $buttonSecondary }}">{{ $row['isActive'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            @if ($row['canDelete'])
                                <button
                                    role="menuitem" tabindex="-1" type="button"
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
                          </x-ui.menu>
                        </div>
                    </div>
                @endforeach
            </x-ui.table>
            <p class="text-xs text-bq-text-muted">Kategori yang masih dipakai layanan tidak bisa dihapus. Halaman booking menampilkan kategori menurut urutannya, dari angka terkecil.</p>
        @endif

        {{-- Category form (create and edit) --}}
        <x-owner.dialog id="category-form-dialog" title="Kategori">
            <form id="category-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  x-bind:action="category.id ? url(@js($categoryUpdateUrl), category.id) : @js(route('owner.categories.store'))">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!category.id">
                <input type="hidden" name="_form" :value="category.id ? 'category-edit-' + category.id : 'category-create'">
                <input type="hidden" name="editing_id" x-bind:value="category.id || ''">

                <p class="text-sm font-semibold text-bq-text" x-text="category.id ? 'Ubah kategori' : 'Tambah kategori'"></p>

                <div>
                    <label for="category-name" class="{{ $labelClass }}">Nama kategori</label>
                    <input id="category-name" name="name" type="text" required maxlength="100" x-model="category.name" class="{{ $inputClass }}">
                    <x-owner.field-error name="name" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                </div>
                <div>
                    <label for="category-description" class="{{ $labelClass }}">Deskripsi <span class="font-normal text-bq-text-muted">(opsional)</span></label>
                    <textarea id="category-description" name="description" rows="2" maxlength="500" x-model="category.description" class="{{ $inputClass }}"></textarea>
                    <x-owner.field-error name="description" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                </div>
                <fieldset>
                    <legend class="{{ $labelClass }}">Label warna</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($colors as $key => $color)
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-bq-border px-2.5 py-1 text-xs text-bq-text has-[:checked]:border-bq-primary has-[:checked]:ring-2 has-[:checked]:ring-bq-primary/30">
                                <input type="radio" name="color" value="{{ $key }}" x-model="category.color" class="sr-only">
                    <x-owner.field-error name="color" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                                <span class="h-3 w-3 rounded-full" style="background-color: {{ $color['hex'] }}" aria-hidden="true"></span>
                                {{ $color['label'] }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label for="category-order" class="{{ $labelClass }}">Urutan tampil</label>
                    <input id="category-order" name="sort_order" type="number" min="0" max="9999" step="1" x-model="category.sort_order" class="{{ $inputClass }} sm:w-32">
                    <x-owner.field-error name="sort_order" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                    <p class="mt-1 text-xs text-bq-text-muted">Angka kecil tampil lebih dulu di halaman booking.</p>
                </div>
                <div>
                    <label for="category-image" class="{{ $labelClass }}">Gambar <span class="font-normal text-bq-text-muted">(opsional, JPG, PNG, WEBP, maks. 5 MB)</span></label>
                    <template x-if="category.imageUrl && !category.remove_image">
                        <img x-bind:src="category.imageUrl" alt="" class="mt-2 h-16 w-16 rounded-lg object-cover">
                    </template>
                    <input id="category-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-bq-text-muted">
                    <x-owner.field-error name="image" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                    <label x-show="category.imageUrl" class="mt-2 inline-flex items-center gap-2 text-sm text-bq-text">
                        <input type="checkbox" name="remove_image" value="1" x-model="category.remove_image" class="rounded border-bq-border text-bq-primary"> Hapus gambar
                    </label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-bq-text">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="category.is_active" class="rounded border-bq-border text-bq-primary">
                    <x-owner.field-error name="is_active" :form="$oldForm" x-cloak x-show="@js($oldForm) === (category.id ? 'category-edit-' + category.id : 'category-create')" />
                    Aktif (tampil di halaman booking)
                </label>
            </form>
            <x-slot:footer>
                <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'category-form-dialog' })">Batal</button>
                <button type="submit" form="category-form" class="{{ $buttonPrimary }}">Simpan</button>
            </x-slot:footer>
        </x-owner.dialog>
