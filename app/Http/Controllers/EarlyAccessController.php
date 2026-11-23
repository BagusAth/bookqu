<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EarlyAccess\CreateEarlyAccessSignup;
use App\Http\Requests\EarlyAccess\StoreEarlyAccessSignupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public early access page and its sign-up form (BQ-175, FR-PUBLIC-009).
 */
class EarlyAccessController extends Controller
{
    public function show(): View
    {
        return view('early-access', [
            'supportWhatsapp' => config('support.whatsapp'),
        ]);
    }

    public function store(StoreEarlyAccessSignupRequest $request, CreateEarlyAccessSignup $createSignup): RedirectResponse
    {
        $formUrl = route('early-access').'#daftar';

        try {
            $createSignup->execute($request->safe()->only(['full_name', 'business_name', 'business_type', 'email', 'whatsapp_number']));
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($formUrl);
        }

        return redirect()->to($formUrl)
            ->with('early_access_registered', 'Terima kasih! Pendaftaran early access kamu sudah kami terima. Tim BookQu akan menghubungi lewat WhatsApp dalam 1–2 hari kerja.');
    }
}
