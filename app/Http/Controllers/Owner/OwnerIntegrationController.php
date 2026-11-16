<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Integrations\GetOwnerIntegrations;
use App\Actions\Integrations\ManageGoogleCalendar;
use App\Actions\Integrations\SaveStorefrontTracking;
use App\Domain\Integrations\CalendarIntegrationFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\SaveTrackingSettingsRequest;
use App\Infrastructure\Integrations\GoogleCalendar;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OwnerIntegrationController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(GetOwnerIntegrations $action, GoogleCalendar $calendar): View
    {
        return view('owner.integrations', $action->execute($this->tenant(), $calendar));
    }

    public function saveTracking(SaveTrackingSettingsRequest $request, SaveStorefrontTracking $action): RedirectResponse
    {
        $action->execute($this->tenant(), $request->validated());

        return back()->with('sukses', 'Pengaturan pelacakan tersimpan.');
    }

    public function beginCalendar(Request $request, ManageGoogleCalendar $action): RedirectResponse
    {
        $url = $action->begin($this->tenant(), (int) $request->user()?->id, $request->session());

        return redirect()->away($url);
    }

    public function connectCalendar(Request $request, ManageGoogleCalendar $action): RedirectResponse
    {
        try {
            $action->connect($this->tenant(), (int) $request->user()?->id, $request->session(),
                $request->string('state')->toString(), $request->string('code')->toString());
        } catch (CalendarIntegrationFailed|LockTimeoutException) {
            return $this->connectionError();
        }

        return redirect()->route('owner.settings.integrations')->with('sukses', 'Google Calendar terhubung.')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function testCalendar(ManageGoogleCalendar $action): RedirectResponse
    {
        try {
            $action->updateConnection($this->tenant(), false);
        } catch (CalendarIntegrationFailed|LockTimeoutException) {
            return $this->connectionError();
        }

        return redirect()->route('owner.settings.integrations')->with('sukses', 'Koneksi Google Calendar berfungsi.');
    }

    public function disconnectCalendar(ManageGoogleCalendar $action): RedirectResponse
    {
        try {
            $action->updateConnection($this->tenant(), true);
        } catch (CalendarIntegrationFailed|LockTimeoutException) {
            return $this->connectionError();
        }

        return redirect()->route('owner.settings.integrations')->with('sukses', 'Google Calendar diputus.');
    }

    private function connectionError(): RedirectResponse
    {
        return redirect()->route('owner.settings.integrations')->withErrors(['calendar' => 'Google Calendar belum dapat dihubungi. Coba lagi.'])
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function tenant(): Tenant
    {
        return $this->resolveTenant() ?? abort(404);
    }
}
