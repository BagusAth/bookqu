<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Integrations\ManageOwnerApiCredentials;
use App\Actions\Integrations\QueueManualWebhook;
use App\Actions\Owner\GetOwnerSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\SaveApiCredentialRequest;
use App\Models\Tenant;
use App\Traits\ResolvesOwnerTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class OwnerApiController extends Controller
{
    use ResolvesOwnerTenant;

    public function index(): RedirectResponse
    {
        return redirect()->to(route('owner.settings.business', ['tab' => 'integrasi']).'#api', 301);
    }

    public function createKey(SaveApiCredentialRequest $request, ManageOwnerApiCredentials $action): Response
    {
        $tenant = $this->tenant();
        $secret = $action->createKey($tenant, (string) $request->validated('name'));

        return $this->showOnce($tenant, 'Kunci API baru', $secret);
    }

    public function revokeKey(int $id, ManageOwnerApiCredentials $action): RedirectResponse
    {
        $action->revoke($this->tenant(), $id);

        return redirect()->to(route('owner.settings.business', ['tab' => 'integrasi']).'#api')->with('sukses', 'Kunci API dicabut.');
    }

    public function createWebhook(SaveApiCredentialRequest $request, ManageOwnerApiCredentials $action): Response
    {
        $tenant = $this->tenant();
        $secret = $action->createWebhook($tenant, (string) $request->validated('name'), (string) $request->validated('url'));

        return $this->showOnce($tenant, 'Secret webhook baru', $secret);
    }

    public function deleteWebhook(int $id, ManageOwnerApiCredentials $action): RedirectResponse
    {
        $action->deleteWebhook($this->tenant(), $id);

        return redirect()->to(route('owner.settings.business', ['tab' => 'integrasi']).'#api')->with('sukses', 'Endpoint webhook dihapus.');
    }

    public function testWebhook(int $id, QueueManualWebhook $webhook): RedirectResponse
    {
        $webhook->test($this->tenant(), $id);

        return redirect()->to(route('owner.settings.business', ['tab' => 'integrasi']).'#api')
            ->with('sukses', 'Webhook uji masuk antrean pengiriman.');
    }

    public function redeliverWebhook(int $id, QueueManualWebhook $webhook): RedirectResponse
    {
        $webhook->redeliver($this->tenant(), $id);

        return redirect()->to(route('owner.settings.business', ['tab' => 'integrasi']).'#api')
            ->with('sukses', 'Webhook masuk antrean pengiriman ulang.');
    }

    private function showOnce(Tenant $tenant, string $label, #[\SensitiveParameter] string $credential): Response
    {
        return response()->view('owner.settings', app(GetOwnerSettings::class)->execute($tenant, request()->user(), 'integrasi')
            + compact('label', 'credential'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function tenant(): Tenant
    {
        return $this->resolveTenant() ?? abort(404);
    }
}
