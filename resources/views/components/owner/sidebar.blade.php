{{--
    Owner sidebar (BQ-128, owner-portal.md §4.1): the single navigation definition filtered by module,
    plan and role on the server (GetOwnerShell). Desktop and tablet only; phones use the bottom bar.
    A locked menu opens the shared upgrade prompt instead of navigating.
--}}
<aside
    id="owner-sidebar"
    aria-label="Navigasi utama"
    class="fixed inset-y-0 left-0 z-50 hidden w-bq-rail flex-col border-r border-bq-border bg-bq-sidebar text-bq-sidebar-text md:flex owner-wide:w-bq-sidebar"
>
    <div class="flex h-17 shrink-0 items-center justify-center gap-3 px-3 owner-wide:h-19 owner-wide:justify-start owner-wide:px-5">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white">
            <img src="{{ asset('logo_icon.png') }}" alt="" class="h-7 w-7" width="28" height="28">
        </span>
        <div class="hidden min-w-0 owner-wide:block">
            <p class="text-bq-form font-bold">BookQu</p>
            <p class="truncate text-bq-small text-bq-sidebar-muted" title="{{ $shell['business'] }}">{{ $shell['business'] }}</p>
        </div>
    </div>

    <nav class="sidebar-scroll flex-1 overflow-y-auto px-2 pb-4 owner-wide:px-3" aria-label="Menu">
        @foreach ($shell['sections'] as $section)
            <div @class(['mt-2 border-t border-bq-border pt-2 owner-wide:mt-5 owner-wide:border-0 owner-wide:pt-0' => ! $loop->first])>
                @if ($section['title'])
                    <p class="mb-1 hidden px-3 text-bq-small font-medium text-bq-sidebar-muted owner-wide:block">{{ $section['title'] }}</p>
                @endif
                <ul class="space-y-1">
                    @foreach ($section['items'] as $item)
                        <li>
                            @if ($item['locked'])
                                <button
                                    type="button"
                                    data-nav-id="{{ $item['id'] }}"
                                    title="{{ $item['label'] }}"
                                    data-locked="true"
                                    x-data
                                    x-on:click="$dispatch('open-upgrade', { feature: @js($item['label']) }); $dispatch('open-dialog', { id: 'owner-upgrade-dialog', trigger: $el })"
                                    class="relative flex h-10 w-full items-center justify-center gap-3 rounded-bq-control text-left text-bq-body font-medium text-bq-sidebar-muted hover:bg-bq-background owner-wide:h-9 owner-wide:justify-start owner-wide:px-3"
                                >
                                    <x-owner.icon :name="$item['icon']" class="h-5 w-5 opacity-80" />
                                    <span class="sr-only owner-wide:not-sr-only owner-wide:flex-1">{{ $item['label'] }}</span>
                                    <x-owner.icon name="lock" class="absolute right-1 bottom-0.5 size-3 owner-wide:static owner-wide:size-4" />
                                    <span class="sr-only">(perlu naik paket)</span>
                                </button>
                            @else
                                <a
                                    href="{{ $item['href'] }}"
                                    data-nav-id="{{ $item['id'] }}"
                                    title="{{ $item['label'] }}"
                                    @if ($item['active']) aria-current="page" @endif
                                    @class([
                                        'relative flex h-10 items-center justify-center gap-3 rounded-bq-control text-bq-body owner-wide:h-9 owner-wide:justify-start owner-wide:px-3',
                                        'bg-bq-sidebar-active font-semibold text-bq-sidebar-active-text' => $item['active'],
                                        'font-medium hover:bg-bq-background' => ! $item['active'],
                                    ])
                                >
                                    <x-owner.icon :name="$item['icon']" class="size-5 shrink-0" />
                                    <span class="sr-only owner-wide:not-sr-only owner-wide:flex-1">{{ $item['label'] }}</span>
                                    @if ($item['badge'])
                                        <span
                                            title="{{ $item['badge']['label'] }}"
                                            @class([
                                                'bq-num absolute right-0 top-0 rounded-full px-1 text-xs font-bold owner-wide:static owner-wide:px-2',
                                                'bg-bq-accent text-bq-ink' => $item['badge']['tone'] === 'warning',
                                                'bg-bq-surface-2 text-bq-sidebar-muted' => $item['badge']['tone'] !== 'warning',
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
