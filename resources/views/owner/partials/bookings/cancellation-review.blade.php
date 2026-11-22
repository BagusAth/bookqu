<p>Diajukan oleh {{ $item->requester ?? 'Akun tidak tersedia' }} · {{ $item->reason }}</p>
<div class="flex flex-wrap gap-2">
    @foreach (['approve' => 'Setujui pembatalan', 'reject' => 'Tolak permintaan'] as $decision => $label)
        <form id="cancellation-{{ $item->id }}-{{ $decision }}" method="POST" action="{{ route('owner.bookings.cancellation-requests.decide', $item->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="decision" value="{{ $decision }}">
            <button type="button" class="bq-btn bq-btn-sm {{ $decision === 'approve' ? 'bq-btn-danger' : 'bq-btn-secondary' }}"
                @click="$dispatch('open-confirm', { title: @js($label.'?'), message: @js($decision === 'approve' ? 'Booking akan dibatalkan.' : 'Booking tetap berlaku.'), formId: $el.form.id, confirmText: @js($label), tone: @js($decision === 'approve' ? 'danger' : 'primary') })">{{ $label }}</button>
        </form>
    @endforeach
</div>
