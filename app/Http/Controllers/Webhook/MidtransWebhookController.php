<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Actions\Payment\ProcessPaymentWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    /**
     * Handle Midtrans notification webhook for customer booking and subscription payments.
     */
    public function handle(Request $request, ProcessPaymentWebhook $processWebhook): JsonResponse
    {
        $result = $processWebhook->execute($request->all());

        return response()->json(
            ['message' => $result['message']],
            $result['code']
        );
    }
}
