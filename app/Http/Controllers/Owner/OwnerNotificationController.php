<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Team\GetNotificationsPage;
use App\Actions\Team\GetVisibleNotifications;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\InternalRedirect;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class OwnerNotificationController extends Controller
{
    use ResolvesOwnerTenant;

    /**
     * Display a listing of owner notifications (supports both JSON API for topbar & Blade view).
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = auth()->user();
        $tenant = $this->resolveTenant() ?? new Tenant;

        if ($request->wantsJson() || $request->ajax()) {
            $limit = (int) $request->input('limit', 10);
            $notifications = $this->visibleNotifications($user)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    $data = $notification->data ?? [];

                    return [
                        'id' => $notification->id,
                        'is_read' => ! is_null($notification->read_at),
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at->diffForHumans(),
                        'created_at_dt' => $notification->created_at->translatedFormat('d M Y H:i'),
                        'event_type' => $data['event_type'] ?? 'info',
                        'title' => $data['title'] ?? 'Pemberitahuan',
                        'message' => $data['message'] ?? '',
                        'booking_id' => $data['booking_id'] ?? null,
                        'booking_code' => $data['booking_code'] ?? null,
                        'customer_name' => $data['customer_name'] ?? null,
                        'service_name' => $data['service_name'] ?? null,
                        'tanggal' => $data['tanggal'] ?? null,
                        'jam' => $data['jam'] ?? null,
                        'badge_color' => $data['badge_color'] ?? 'blue',
                        'icon' => $data['icon'] ?? 'bell',
                        'url' => $data['url'] ?? route('owner.bookings'),
                    ];
                });

            return response()->json([
                'success' => true,
                'unread_count' => $this->visibleNotifications($user)->whereNull('read_at')->count(),
                'data' => $notifications,
            ]);
        }

        // Full page (BQ-145, owner-portal.md §8.16): unread and type filters for what this role may see.
        $page = app(GetNotificationsPage::class)->execute(
            $user,
            is_string($request->query('filter')) ? $request->query('filter') : null,
            is_string($request->query('type')) ? $request->query('type') : null,
        );

        return view('owner.notifications', $page + ['tenant' => $tenant]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = auth()->user();
        $notification = $this->visibleNotifications($user)->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $this->visibleNotifications($user)->whereNull('read_at')->count(),
            ]);
        }

        // AC-NTF-01: land on the item's target. BQ-102: only targets inside this application are followed.
        $origin = $request->getSchemeAndHttpHost();
        $target = InternalRedirect::target($request->input('redirect'), $origin)
            ?? InternalRedirect::target($notification?->data['url'] ?? null, $origin)
            ?? route('owner.notifications');

        return redirect($target);
    }

    /**
     * Mark all notifications as read for current owner.
     */
    public function markAllAsRead(Request $request)
    {
        $user = auth()->user();
        $this->visibleNotifications($user)->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
                'message' => 'Semua notifikasi telah ditandai sebagai dibaca.',
            ]);
        }

        return back()->with('sukses', 'Semua notifikasi berhasil ditandai sebagai dibaca.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $user = auth()->user();
        $notification = $this->visibleNotifications($user)->where('id', $id)->first();

        if ($notification) {
            $notification->delete();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $this->visibleNotifications($user)->whereNull('read_at')->count(),
            ]);
        }

        return back()->with('sukses', 'Notifikasi berhasil dihapus.');
    }

    /** @return MorphMany<DatabaseNotification, User> */
    private function visibleNotifications(User $user): MorphMany
    {
        return app(GetVisibleNotifications::class)->execute($user);
    }
}
