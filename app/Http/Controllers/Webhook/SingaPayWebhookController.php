<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Actions\Payment\ProcessSingaPayWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SingaPayWebhookController extends Controller
{
    /**
     * Handle SingaPay Money In notification webhook for booking payments.
     */
    public function handle(Request $request, ProcessSingaPayWebhook $processWebhook): JsonResponse
    {
        $result = $processWebhook->execute($request);

        return response()->json(
            [
                'status'  => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
            ],
            $result['code']
        );
    }
}
