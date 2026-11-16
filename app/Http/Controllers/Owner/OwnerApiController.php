<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Integrations\ManageOwnerApiCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\SaveApiCredentialRequest;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class OwnerApiController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(ManageOwnerApiCredentials $action): View
    {
        return view('owner.api', $action->overview($this->tenant()));
    }

    public function createKey(SaveApiCredentialRequest $request, ManageOwnerApiCredentials $action): Response
    {
        $tenant = $this->tenant();
        $secret = $action->createKey($tenant, (string) $request->validated('name'));

        return $this->showOnce($action, $tenant, 'Kunci API baru', $secret);
    }

    public function revokeKey(int $id, ManageOwnerApiCredentials $action): RedirectResponse
    {
        $action->revoke($this->tenant(), $id);

        return back()->with('sukses', 'Kunci API dicabut.');
    }

    public function createWebhook(SaveApiCredentialRequest $request, ManageOwnerApiCredentials $action): Response
    {
        $tenant = $this->tenant();
        $secret = $action->createWebhook($tenant, (string) $request->validated('name'), (string) $request->validated('url'));

        return $this->showOnce($action, $tenant, 'Secret webhook baru', $secret);
    }

    public function deleteWebhook(int $id, ManageOwnerApiCredentials $action): RedirectResponse
    {
        $action->deleteWebhook($this->tenant(), $id);

        return back()->with('sukses', 'Endpoint webhook dihapus.');
    }

    private function showOnce(ManageOwnerApiCredentials $action, Tenant $tenant, string $label, #[\SensitiveParameter] string $credential): Response
    {
        return response()->view('owner.api', $action->overview($tenant) + compact('label', 'credential'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function tenant(): Tenant
    {
        return $this->resolveTenant() ?? abort(404);
    }
}
