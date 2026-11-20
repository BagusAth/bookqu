<x-ui.panel title="Riwayat reservasi">
    @if ($history === [])
        <p class="text-bq-body text-bq-text-muted">Belum ada perubahan yang tercatat.</p>
    @else
        <ul class="divide-y divide-bq-border">
            @foreach ($history as $entry)
                <li class="flex flex-wrap justify-between gap-2 py-2 text-bq-body">
                    <span>{{ $entry['label'] }}</span>
                    <span class="bq-num text-bq-small text-bq-text-muted">{{ $entry['at'] }} WIB</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.panel>
