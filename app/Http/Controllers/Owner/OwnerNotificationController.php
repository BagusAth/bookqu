<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $tenant = $this->resolveTenant() ?? new Tenant();

        if ($request->wantsJson() || $request->ajax()) {
            $limit = (int) $request->input('limit', 10);
            $notifications = $user->notifications()
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    $data = $notification->data ?? [];
                    return [
                        'id'            => $notification->id,
                        'is_read'       => !is_null($notification->read_at),
                        'read_at'       => $notification->read_at,
                        'created_at'    => $notification->created_at->diffForHumans(),
                        'created_at_dt' => $notification->created_at->translatedFormat('d M Y H:i'),
                        'event_type'    => $data['event_type'] ?? 'info',
                        'title'         => $data['title'] ?? 'Pemberitahuan',
                        'message'       => $data['message'] ?? '',
                        'booking_id'    => $data['booking_id'] ?? null,
                        'booking_code'  => $data['booking_code'] ?? null,
                        'customer_name' => $data['customer_name'] ?? null,
                        'service_name'  => $data['service_name'] ?? null,
                        'tanggal'       => $data['tanggal'] ?? null,
                        'jam'           => $data['jam'] ?? null,
                        'badge_color'   => $data['badge_color'] ?? 'blue',
                        'icon'          => $data['icon'] ?? 'bell',
                        'url'           => $data['url'] ?? route('owner.bookings'),
                    ];
                });

            return response()->json([
                'success'      => true,
                'unread_count' => $user->unreadNotifications()->count(),
                'data'         => $notifications,
            ]);
        }

        // Full page view
        $filter = $request->input('filter', 'all');

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'new_booking') {
            $query->where('data->event_type', 'new_booking');
        } elseif ($filter === 'status_change') {
            $query->whereIn('data->event_type', ['cancelled', 'rescheduled', 'completed', 'status_updated']);
        }

        $notifications = $query->paginate(15)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();
        $totalCount = $user->notifications()->count();

        return view('owner.notifications', compact('tenant', 'notifications', 'unreadCount', 'totalCount', 'filter'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        $redirectUrl = $request->input('redirect') ?: back()->getTargetUrl();
        return redirect($redirectUrl);
    }

    /**
     * Mark all notifications as read for current owner.
     */
    public function markAllAsRead(Request $request)
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'unread_count' => 0,
                'message'      => 'Semua notifikasi telah ditandai sebagai dibaca.',
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
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->delete();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return back()->with('sukses', 'Notifikasi berhasil dihapus.');
    }
}
