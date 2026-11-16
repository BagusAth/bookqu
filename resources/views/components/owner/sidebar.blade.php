{{--
    Owner sidebar (BQ-128, owner-portal.md §4.1): the single navigation definition filtered by module,
    plan and role on the server (GetOwnerShell). Desktop and tablet only; phones use the bottom bar.
    A locked menu opens the shared upgrade prompt instead of navigating.
    BQ-161: the brand aubergine frame; the active item carries a marigold bar, counts are marigold pills.
--}}
<aside
    id="owner-sidebar"
    aria-label="Navigasi utama"
    class="bq-on-dark fixed inset-y-0 left-0 z-50 hidden w-64 flex-col bg-bq-sidebar text-bq-sidebar-text md:flex"
>
    <div class="flex h-16 shrink-0 items-center gap-3 px-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white">
            <img src="{{ asset('logo_icon.png') }}" alt="" class="h-7 w-7" width="28" height="28">
        </span>
        <div class="min-w-0">
            <p class="text-sm font-bold text-white">BookQu</p>
            <p class="truncate text-xs text-bq-sidebar-muted">{{ $shell['business'] }}</p>
        </div>
    </div>

    <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 pt-2 pb-6" aria-label="Menu">
        @foreach ($shell['sections'] as $section)
            <div @class(['mt-6' => ! $loop->first])>
                @if ($section['title'])
                    <p class="mb-1.5 px-3 text-xs font-medium text-bq-sidebar-muted">{{ $section['title'] }}</p>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <li>
                            @if ($item['locked'])
                                <button
                                    type="button"
                                    data-nav-id="{{ $item['id'] }}"
                                    data-locked="true"
                                    x-data
                                    x-on:click="$dispatch('open-upgrade', { feature: @js($item['label']) }); $dispatch('open-dialog', { id: 'owner-upgrade-dialog', trigger: $el })"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-bq-sidebar-muted/80 transition-colors hover:bg-white/10"
                                >
                                    <x-owner.icon :name="$item['icon']" class="h-5 w-5 opacity-80" />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    <x-owner.icon name="lock" class="h-4 w-4" />
                                    <span class="sr-only">(perlu naik paket)</span>
                                </button>
                            @else
                                <a
                                    href="{{ $item['href'] }}"
                                    data-nav-id="{{ $item['id'] }}"
                                    @if ($item['active']) aria-current="page" @endif
                                    @class([
                                        'relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                        'bg-bq-sidebar-active font-semibold text-white before:absolute before:inset-y-1.5 before:left-0 before:w-[3px] before:rounded-full before:bg-bq-accent' => $item['active'],
                                        'text-white/90 hover:bg-white/10 hover:text-white' => ! $item['active'],
                                    ])
                                >
                                    <x-owner.icon :name="$item['icon']" @class(['h-5 w-5', 'text-bq-accent' => $item['active'], 'opacity-80' => ! $item['active']]) />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if ($item['badge'])
                                        <span
                                            title="{{ $item['badge']['label'] }}"
                                            @class([
                                                'bq-num rounded-full px-2 py-0.5 text-xs font-bold',
                                                'bg-bq-accent text-bq-ink' => $item['badge']['tone'] === 'warning',
                                                'bg-white/15 text-white' => $item['badge']['tone'] !== 'warning',
                                            ])
                                        >{{ $item['badge']['text'] }}<span class="sr-only">, {{ $item['badge']['label'] }}</span></span>
                                    @endif
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
