<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Booking\GetScheduleSelection;
use App\Actions\Booking\ValidateBookingDate;
use App\Actions\Booking\ValidateCheckoutSlots;
use App\Domain\Booking\BookingRules;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\CustomerBookingRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class BookingScheduleController extends StorefrontBookingController
{
    public function showScheduleSelection(Request $request, string $slug_usaha, GetScheduleSelection $selection): View|RedirectResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);
        abort_unless($tenant !== null, 404);
        $booking = $this->drafts->read();
        if ($booking->tenantId !== null && (int) $booking->tenantId !== $tenant->id) {
            $this->drafts->clear();

            return $this->restartBooking($slug_usaha);
        }
        $service = $this->resolveService($tenant->id, (int) ($booking->serviceId ?? 0));
        if (! $service || ! $service->hasActiveFulfillment()) {
            return $this->restartBooking($slug_usaha);
        }
        $date = $request->query('tanggal', $booking->date ?? null);
        $data = $selection->execute($tenant, $service, is_string($date) ? $date : null);
        $sameDate = ($booking->date ?? null) === $data['selectedDate'];
        $times = $sameDate ? ($booking->times ?? []) : [];
        $selectedTimes = is_array($times) ? $times : [$times];
        $selectedScheduleIds = $sameDate ? ($booking->scheduleIds ?? []) : [];

        return view('customer.booking.schedule-selection', array_merge($data, compact('tenant', 'service', 'selectedTimes', 'selectedScheduleIds')));
    }

    public function getScheduleSlots(Request $request, string $slug_usaha, GetScheduleSelection $selection): JsonResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);
        if (! $tenant) {
            return response()->json(['error' => 'Bisnis tidak ditemukan.'], 404);
        }
        $booking = $this->drafts->read();
        if ($booking->tenantId !== null && (int) $booking->tenantId !== $tenant->id) {
            return response()->json(['error' => 'Pilihan layanan tidak tersedia. Silakan pilih layanan kembali.'], 400);
        }
        $service = $this->resolveService($tenant->id, (int) ($booking->serviceId ?? 0));
        if (! $service || ! $service->hasActiveFulfillment()) {
            return response()->json(['error' => 'Silakan pilih layanan yang tersedia terlebih dahulu.'], 400);
        }
        $validator = Validator::make($request->only('tanggal'), ['tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->toDateString(), 'before_or_equal:'.now('Asia/Jakarta')->addDays(BookingRules::maximumAdvanceDays($tenant))->toDateString()]]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Periksa kembali tanggal yang dipilih.', 'errors' => $validator->errors()], 422);
        }
        $validated = $validator->validated();
        $data = $selection->execute($tenant, $service, $validated['tanggal']);

        return response()->json([
            'success' => true, 'date' => $data['selectedDate'], 'is_blocked' => $data['isBlocked'],
            'morning' => $data['morningSlots'], 'afternoon' => $data['afternoonSlots'], 'evening' => $data['eveningSlots'],
            'date_formatted' => $data['selectedDateFormatted'], 'date_label' => $data['selectedDateLabel'],
        ]);
    }

    public function selectSchedule(Request $request, string $slug_usaha): RedirectResponse
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = $this->drafts->read();
        $sessionTenantId = $booking->tenantId ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking->serviceId ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        try {
            $selectedDate = app(ValidateBookingDate::class)->execute($tenant, $service, $request->input('tanggal'), $simulate);
            $this->drafts->selectDate($tenant->id, $selectedDate, $booking->date !== $selectedDate);

            return $this->storeSelectedTime($request, $slug_usaha, $tenant, $service, $selectedDate, $simulate, 'customer.booking.schedule');
        } catch (ValidationException $e) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)->withErrors($e->errors());
        }
    }

    public function showDateSelection(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }

    public function selectDate(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = $this->drafts->read();
        $sessionTenantId = $booking->tenantId ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking->serviceId ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        try {
            $selectedDate = app(ValidateBookingDate::class)->execute($tenant, $service, $request->input('tanggal'), $simulate);
        } catch (ValidationException $e) {
            return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha)->withErrors($e->errors());
        }
        $this->drafts->selectDate($tenant->id, $selectedDate, clearSlots: true, legacyStep: true);

        return CustomerBookingRoutes::route('customer.booking.time', $slug_usaha);
    }

    public function showTimeSelection(string $slug_usaha)
    {
        session()->reflash();

        return CustomerBookingRoutes::route('customer.booking.schedule', $slug_usaha);
    }

    public function selectTime(Request $request, string $slug_usaha)
    {
        $tenant = $this->resolveTenant($slug_usaha);

        $simulate = $request->boolean('simulate') && app()->environment('local');

        if (! $tenant) {
            abort(404);
        }

        $booking = $this->drafts->read();
        $sessionTenantId = $booking->tenantId ?? null;

        if ($sessionTenantId && (int) $sessionTenantId !== $tenant->id) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $serviceId = $booking->serviceId ?? null;
        $selectedDate = $booking->date ?? null;

        if (! $serviceId) {
            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        if (! $selectedDate) {
            return CustomerBookingRoutes::route('customer.booking.date', $slug_usaha);
        }

        $service = $this->resolveService($tenant->id, (int) $serviceId);

        if (! $service || ! $service->hasActiveFulfillment()) {
            $this->drafts->clear();

            return CustomerBookingRoutes::route('customer.booking.program', $slug_usaha);
        }

        $this->drafts->bindTenant($tenant->id);

        return $this->storeSelectedTime($request, $slug_usaha, $tenant, $service, $selectedDate, $simulate, 'customer.booking.time');
    }

    /**
     * Back to the first step, keeping a message flashed by the request that sent the customer here.
     */
    private function storeSelectedTime(Request $request, string $slug_usaha, Tenant $tenant, Service $service, string $selectedDate, bool $simulate, string $errorRoute): RedirectResponse
    {
        $wasOriginalJamArray = is_array($request->input('jam')) || ($request->has('schedule_ids') && is_array($request->input('schedule_ids')));
        $rawJam = $request->input('jam');
        $rawScheduleIds = $request->input('schedule_ids') ?? ($request->has('schedule_id') ? [$request->input('schedule_id')] : null);

        if (is_string($rawJam)) {
            $rawJam = [$rawJam];
        }
        if (is_numeric($rawScheduleIds)) {
            $rawScheduleIds = [(int) $rawScheduleIds];
        }

        if ($rawJam !== null || $rawScheduleIds !== null) {
            $request->merge([
                'jam' => $rawJam,
                'schedule_ids' => $rawScheduleIds,
            ]);
        }

        // Validate arrays for multi-select
        $rules = [
            'jam' => ['required', 'array', 'min:1'],
            'jam.*' => ['required', 'date_format:H:i', 'distinct'],
        ];

        if ($simulate) {
            $rules['schedule_ids'] = ['nullable', 'array'];
            $rules['schedule_ids.*'] = ['nullable', 'integer'];
        } else {
            $rules['schedule_ids'] = ['required', 'array', 'min:1'];
            $rules['schedule_ids.*'] = ['required', 'integer', 'distinct'];
        }

        $validated = $request->validate($rules);

        $jamArray = $validated['jam'];
        $scheduleIdArray = $validated['schedule_ids'] ?? [];

        if ($simulate) {
            $this->drafts->selectSlots(count($jamArray) === 1 ? $jamArray[0] : $jamArray, $scheduleIdArray);

            return CustomerBookingRoutes::route('customer.booking.checkout', [
                'slug_usaha' => $slug_usaha,
                'simulate' => 1,
            ]);
        }

        if (count($jamArray) !== count($scheduleIdArray)) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                ->withErrors(['jam' => 'Jumlah jam dan jadwal tidak sesuai.']);
        }

        if (count($scheduleIdArray) !== count(array_unique($scheduleIdArray))) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)
                ->withErrors(['jam' => 'Jadwal yang dipilih tidak boleh duplikat.']);
        }

        try {
            ['ids' => $validatedScheduleIds, 'times' => $validatedTimes] = app(ValidateCheckoutSlots::class)
                ->execute($tenant, $service, $selectedDate, $jamArray, $scheduleIdArray);
        } catch (ValidationException $e) {
            return CustomerBookingRoutes::route($errorRoute, $slug_usaha)->withErrors($e->errors());
        }

        // Store both scalar (for single slot test assertion) and array in session
        $storedJam = $wasOriginalJamArray ? $validatedTimes : (count($validatedTimes) === 1 ? $validatedTimes[0] : $validatedTimes);
        $this->drafts->selectSlots($storedJam, $validatedScheduleIds);

        return CustomerBookingRoutes::route('customer.booking.checkout', $slug_usaha);
    }
}
