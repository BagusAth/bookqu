{{--
    Owner sidebar (BQ-128, owner-portal.md §4.1): the single navigation definition filtered by module,
    plan and role on the server (GetOwnerShell). Desktop and tablet only; phones use the bottom bar.
    A locked menu opens the shared upgrade prompt instead of navigating.
--}}
<aside
    id="owner-sidebar"
    aria-label="Navigasi utama"
    class="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r border-bq-border bg-bq-surface md:flex"
>
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-bq-border px-5">
        <img src="{{ asset('logo_icon.png') }}" alt="" class="h-8 w-8 rounded-lg" width="32" height="32">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-bq-text">BookQu</p>
            <p class="truncate text-xs text-bq-text-muted">{{ $shell['business'] }}</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Menu">
        @foreach ($shell['sections'] as $section)
            <div @class(['mt-5' => ! $loop->first])>
                @if ($section['title'])
                    <p class="mb-1 px-3 text-xs font-medium text-bq-text-muted">{{ $section['title'] }}</p>
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
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-bq-text-muted hover:bg-bq-background"
                                >
                                    <x-owner.icon :name="$item['icon']" />
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
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm',
                                        'bg-bq-primary-light font-medium text-bq-primary' => $item['active'],
                                        'text-bq-text hover:bg-bq-background' => ! $item['active'],
                                    ])
                                >
                                    <x-owner.icon :name="$item['icon']" />
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if ($item['badge'])
                                        <span
                                            title="{{ $item['badge']['label'] }}"
                                            @class([
                                                'rounded-full px-2 py-0.5 text-xs font-medium tabular-nums',
                                                'bg-bq-warning-bg text-bq-warning' => $item['badge']['tone'] === 'warning',
                                                'bg-bq-info-bg text-bq-info' => $item['badge']['tone'] !== 'warning',
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
