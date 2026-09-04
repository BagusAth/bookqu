<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Voucher;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerVoucherController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $search = $request->input('search', '');

        $vouchers = Voucher::where('idtenant', $tenant->id)
            ->when($search, function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->get();

        $services = Service::where('idtenant', $tenant->id)
            ->where('is_active', true)
            ->orderBy('namalayanan')
            ->get();

        return view('owner.owner-vouchers', compact('tenant', 'vouchers', 'services', 'search'));
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $validated = $request->validate([
            'code'                => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('vouchers', 'code')->where('idtenant', $tenant->id)],
            'discount_type'       => 'required|in:fixed,percentage',
            'discount_value'      => 'required|numeric|min:1',
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_active'           => 'nullable|boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*'=> ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $voucher = Voucher::create([
            'idtenant'            => $tenant->id,
            'code'                => strtoupper($validated['code']),
            'discount_type'       => $validated['discount_type'],
            'discount_value'      => $validated['discount_value'],
            'min_order_amount'    => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'usage_limit'         => $validated['usage_limit'] ?? null,
            'used_count'          => 0,
            'start_date'          => $validated['start_date'] ?? null,
            'end_date'            => $validated['end_date'] ?? null,
            'applicable_services' => $validated['applicable_services'] ?? null,
            'is_active'           => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        return redirect()->route('owner.vouchers')
            ->with('sukses', 'Voucher "' . $voucher->code . '" berhasil dibuat!');
    }

    public function update(Request $request, int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'code'                => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('vouchers', 'code')->where('idtenant', $tenant->id)->ignore($voucher->id)],
            'discount_type'       => 'required|in:fixed,percentage',
            'discount_value'      => 'required|numeric|min:1',
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_active'           => 'required|boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*'=> ['integer', Rule::exists('services', 'id')->where('idtenant', $tenant->id)],
        ]);

        $voucher->update([
            'code'                => strtoupper($validated['code']),
            'discount_type'       => $validated['discount_type'],
            'discount_value'      => $validated['discount_value'],
            'min_order_amount'    => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'usage_limit'         => $validated['usage_limit'] ?? null,
            'start_date'          => $validated['start_date'] ?? null,
            'end_date'            => $validated['end_date'] ?? null,
            'applicable_services' => $validated['applicable_services'] ?? null,
            'is_active'           => (bool) $validated['is_active'],
        ]);

        return redirect()->route('owner.vouchers')
            ->with('sukses', 'Voucher "' . $voucher->code . '" berhasil diperbarui!');
    }

    public function destroy(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);
        $code = $voucher->code;
        $voucher->delete();

        return redirect()->route('owner.vouchers')
            ->with('sukses', 'Voucher "' . $code . '" berhasil dihapus!');
    }

    public function toggleStatus(int $id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $voucher = Voucher::where('idtenant', $tenant->id)->findOrFail($id);
        $voucher->update(['is_active' => !$voucher->is_active]);

        $statusText = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('owner.vouchers')
            ->with('sukses', 'Voucher "' . $voucher->code . '" berhasil ' . $statusText . '!');
    }
}
