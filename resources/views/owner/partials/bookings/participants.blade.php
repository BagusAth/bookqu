<p class="text-xs text-bq-text-muted">
    {{ $rows[$booking->id]['participants'] }} peserta
    @if ($rows[$booking->id]['units']) · {{ $rows[$booking->id]['units'] }} @endif
</p>
