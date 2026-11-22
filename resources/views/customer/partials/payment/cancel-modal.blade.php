<form id="customer-cancel-payment" method="POST" hidden
    action="{{ \App\Support\CustomerBookingRoutes::url('customer.booking.cancel', [$tenant->slug, $payment]) }}">
    @csrf
</form>
<x-owner.confirm-modal />
