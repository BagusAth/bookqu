<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Booking\BookingPricingReservations;
use App\Actions\Booking\GetPromoFigures;
use App\Actions\Schedule\ClaimSlots;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Voucher;
use App\Traits\ResolvesOwnerTenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OwnerVoucherController extends Controller
{
    use ResolvesOwnerTenant;

    public function legacyIndex(Request $request)
    {
        return redirect()->route('owner.promos', $request->query(), 301);
    }

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');

        $today = Carbon::today()->toDateString();
        $promoTabs = ['active' => 'Aktif', 'scheduled' => 'Terjadwal', 'expired' => 'Berakhir', 'all' => 'Semua'];
        $requestedTab = $request->string('tab')->toString();
        $tab = isset($promoTabs[$requestedTab]) ? $requestedTab : 'all';
        $allVouchers = Voucher::where('idtenant', $tenant->id);
        $active = (clone $allVouchers)->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today));
        $promoFigures = app(GetPromoFigures::class)->execute($tenant) + ['active' => $active->count()];
        $vouchers = match ($tab) {
            'active' => $active,
            'scheduled' => $allVouchers->where('is_active', true)->whereDate('start_date', '>', $today),
            'expired' => $allVouchers->whereDate('end_date', '<', $today),
            default => $allVouchers,
        };
        $vouchers = $vouchers
            ->when($search, function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->get();

        $services = Service::where('idtenant', $tenant->id)
            ->orderBy('namalayanan')
            ->get();

        $promoRoute = 'owner.promos';
        $serviceNames = $services->pluck('namalayanan', 'id');
        $cards = $vouchers->mapWithKeys(function (Voucher $promo) use ($today, $serviceNames): array {
            $services = $promo->applicable_services;

            return [$promo->id => [
                'state' => match (true) {
                    $promo->end_date !== null && Carbon::parse($promo->end_date)->toDateString() < $today => 'Berakhir',
                    ! $promo->is_active => 'Nonaktif',
                    $promo->start_date !== null && Carbon::parse($promo->start_date)->toDateString() > $today => 'Terjadwal',
                    default => 'Aktif',
                },
                'services' => ! $services || $services === 'all' ? 'Semua layanan' : collect(explode(',', $services))
                    ->map(fn (string $id): string => $serviceNames->get((int) $id, 'Layanan dihapus'))->implode(', '),
            ]];
        });

        return view('owner.vouchers', compact('vouchers', 'services', 'search', 'promoRoute', 'promoTabs', 'tab', 'promoFigures', 'cards'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('vouchers', 'code')->where('idtenant', $tenant->id)],
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => ['required', 'numeric', 'min:1', Rule::when($request->input('discount_type') === 'percentage', 'max:100')],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $applicableServices = ! empty($validated['applicable_services'])
            ? implode(',', $validated['applicable_services'])
            : 'all';

        $voucher = Voucher::create([
            'idtenant' => $tenant->id,
            'code' => strtoupper($validated['code']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_spending' => $validated['min_order_amount'] ?? 0,
            'max_discount' => $validated['max_discount_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'used_count' => 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'applicable_services' => $applicableServices,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        return redirect()->route('owner.promos')
            ->with('sukses', 'Voucher "'.$voucher->code.'" berhasil dibuat!');
    }

    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('vouchers', 'code')->where('idtenant', $tenant->id)->ignore($voucher->id)],
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => ['required', 'numeric', 'min:1', Rule::when($request->input('discount_type') === 'percentage', 'max:100')],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $applicableServices = ! empty($validated['applicable_services'])
            ? implode(',', $validated['applicable_services'])
            : 'all';

        DB::transaction(function () use ($tenant, $voucher, $validated, $applicableServices): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $voucher->refresh();
            $held = app(BookingPricingReservations::class)->held('voucher_id', $voucher->id);
            if (isset($validated['usage_limit']) && (int) $validated['usage_limit'] < $voucher->used_count + $held) {
                throw ValidationException::withMessages(['usage_limit' => 'Kuota lebih kecil dari pemakaian dan pesanan aktif.']);
            }
            $voucher->update([
                'code' => strtoupper($validated['code']),
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'min_spending' => $validated['min_order_amount'] ?? 0,
                'max_discount' => array_key_exists('max_discount_amount', $validated)
                    ? $validated['max_discount_amount']
                    : $voucher->max_discount,
                'usage_limit' => $validated['usage_limit'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'applicable_services' => array_key_exists('applicable_services', $validated)
                    ? $applicableServices
                    : $voucher->applicable_services,
                'is_active' => (bool) $validated['is_active'],
            ]);

        });

        return redirect()->route('owner.promos')
            ->with('sukses', 'Voucher "'.$voucher->code.'" berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);
        $code = $voucher->code;
        DB::transaction(function () use ($tenant, $voucher): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            if (app(BookingPricingReservations::class)->held('voucher_id', $voucher->id) > 0) {
                throw ValidationException::withMessages(['voucher' => 'Promo masih dipesan oleh pembayaran aktif.']);
            }
            $voucher->delete();
        });

        return redirect()->route('owner.promos')
            ->with('sukses', 'Voucher "'.$code.'" berhasil dihapus!');
    }

    public function toggleStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);
        DB::transaction(function () use ($tenant, $voucher): void {
            ClaimSlots::lockTenant((int) $tenant->id);
            $voucher->refresh();
            $voucher->update(['is_active' => ! $voucher->is_active]);
        });

        $statusText = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('owner.promos')
            ->with('sukses', 'Voucher "'.$voucher->code.'" berhasil '.$statusText.'!');
    }
}
