#!/usr/bin/env python3
"""Apply a guarded security/CI audit patch to a local checkout of BookQu branch agathan.

The script stages no changes and makes no network calls. It refuses to run unless the
current Git branch is named `agathan`, and it validates every target text block before
writing any modified file. Run it from the repository root.
"""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path.cwd()


def fail(message: str) -> "NoReturn":
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(2)


def replace_exact(content: str, old: str, new: str, label: str) -> str:
    count = content.count(old)
    if count != 1:
        fail(f"{label}: expected exactly one source block, found {count}. No files were changed.")
    return content.replace(old, new, 1)


def read_repo_file(relative: str) -> str:
    path = ROOT / relative
    if not path.is_file():
        fail(f"Required file not found: {relative}. Run this script from the BookQu repository root.")
    return path.read_text(encoding="utf-8")


def main() -> None:
    try:
        branch = subprocess.check_output(
            ["git", "rev-parse", "--abbrev-ref", "HEAD"], cwd=ROOT, text=True,
            stderr=subprocess.DEVNULL,
        ).strip()
    except (FileNotFoundError, subprocess.CalledProcessError):
        fail("This directory is not a Git working tree.")

    if branch != "agathan":
        fail(f"Current branch is '{branch}', not 'agathan'. Checkout agathan before applying this patch.")

    targets = {
        "tests/Feature/Payment/SingaPayIntegrationTest.php": read_repo_file(
            "tests/Feature/Payment/SingaPayIntegrationTest.php"
        ),
        "app/Actions/Payment/ProcessSingaPayWebhook.php": read_repo_file(
            "app/Actions/Payment/ProcessSingaPayWebhook.php"
        ),
        "app/Actions/Payment/ProcessPaymentWebhook.php": read_repo_file(
            "app/Actions/Payment/ProcessPaymentWebhook.php"
        ),
        ".github/workflows/tests.yml": read_repo_file(".github/workflows/tests.yml"),
        "tests/Feature/Payment/PaymentActionTest.php": read_repo_file(
            "tests/Feature/Payment/PaymentActionTest.php"
        ),
        "app/Actions/Owner/RequestOwnerPayout.php": read_repo_file(
            "app/Actions/Owner/RequestOwnerPayout.php"
        ),
        "app/Http/Requests/Owner/RequestPayoutRequest.php": read_repo_file(
            "app/Http/Requests/Owner/RequestPayoutRequest.php"
        ),
        "tests/Feature/Owner/SettingsTest.php": read_repo_file(
            "tests/Feature/Owner/SettingsTest.php"
        ),
        "app/Http/Controllers/Owner/OwnerAssetController.php": read_repo_file(
            "app/Http/Controllers/Owner/OwnerAssetController.php"
        ),
        "app/Actions/Payment/CreateSubscriptionPayment.php": read_repo_file(
            "app/Actions/Payment/CreateSubscriptionPayment.php"
        ),
        "app/Actions/Payment/CreateBookingPayment.php": read_repo_file(
            "app/Actions/Payment/CreateBookingPayment.php"
        ),
    }

    # 1. Correct the OAuth contract assertion to match the v1.1 client implementation:
    # X-CLIENT-ID, X-PARTNER-ID and HMAC-SHA512 X-Signature, not Basic Authorization.
    path = "tests/Feature/Payment/SingaPayIntegrationTest.php"
    targets[path] = replace_exact(
        targets[path],
        '''        Http::assertSent(function (Request $request) {
            $expectedBasicAuth = 'Basic ' . base64_encode('test_client_id:' . $this->clientSecret);

            return $request->url() === 'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b'
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === $expectedBasicAuth
                && $request->header('X-PARTNER-ID')[0] === $this->apiKey
                && $request->header('Content-Type')[0] === 'application/json'
                && $request['grant_type'] === 'client_credentials';
        });''',
        '''        Http::assertSent(function (Request $request) {
            // SingaPay Payment Gateway v1.1 uses client/partner headers and an
            // HMAC-SHA512 X-Signature; this endpoint does not use Basic Authorization.
            $date = now('Asia/Jakarta')->format('Ymd');
            $expectedSignature = hash_hmac(
                'sha512',
                'test_client_id_' . $this->clientSecret . '_' . $date,
                $this->clientSecret
            );

            return $request->url() === 'https://payment-b2b.singapay.id/api/v1.1/access-token/b2b'
                && $request->method() === 'POST'
                && ($request->header('X-CLIENT-ID')[0] ?? null) === 'test_client_id'
                && ($request->header('X-PARTNER-ID')[0] ?? null) === $this->apiKey
                && ($request->header('X-Signature')[0] ?? null) === $expectedSignature
                && ($request->header('Content-Type')[0] ?? null) === 'application/json'
                && ($request->header('Authorization')[0] ?? null) === null
                && $request['grant_type'] === 'client_credentials';
        });''',
        "SingaPay OAuth request contract test",
    )

    # Regression tests inserted before the existing currency-mismatch webhook test.
    targets[path] = replace_exact(
        targets[path],
        '''    public function test_webhook_currency_mismatch_rejected(): void
    {''',
        '''    public function test_missing_transaction_status_is_rejected_without_confirming_payment(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-MISSING-STATUS',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Missing Status Test',
            'email_pembayar' => 'missing-status@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'paid');
        unset($payload['data']['transaction']['status'], $payload['data']['status'], $payload['status']);

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Missing or unknown SingaPay transaction status']);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_successful_webhook_without_amount_or_currency_is_rejected(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-MISSING-AMOUNT',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Missing Amount Test',
            'email_pembayar' => 'missing-amount@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'paid');
        unset(
            $payload['data']['transaction']['amount'],
            $payload['data']['payment']['additional_info']['payment_link']['total_amount']
        );

        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error', 'message' => 'Successful transaction is missing amount or currency']);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_singapay_webhook_cannot_resolve_a_payment_owned_by_another_provider(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 100000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'provider'       => 'midtrans',
            'order_id'       => 'BKG-WRONG-PROVIDER',
            'external_id'    => 'MIDTRANS-EXTERNAL-001',
            'expired_at'     => now()->addMinutes(15),
            'nama_pembayar'  => 'Wrong Provider Test',
            'email_pembayar' => 'wrong-provider@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $payload = $this->makeWebhookPayload($payment->order_id, 100000, 'paid');
        $response = $this->postSingaPayWebhook('/api/webhooks/singapay/transaction', $payload);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'error', 'message' => 'Payment not found']);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_webhook_currency_mismatch_rejected(): void
    {''',
        "SingaPay webhook regression tests",
    )

    # 2. Fail closed when SingaPay has no transaction status, require amount+currency
    # for successful callbacks, and narrow payment lookup to the SingaPay provider.
    path = "app/Actions/Payment/ProcessSingaPayWebhook.php"
    targets[path] = replace_exact(
        targets[path],
        '''        // 6. Validate Transaction Amount & Currency
        $incomingAmount   = $this->extractAmount($payload);
        $incomingCurrency = $this->extractCurrency($payload);

        if ($incomingCurrency !== null && strtoupper((string) $incomingCurrency) !== 'IDR') {''',
        '''        // 6. Validate transaction-specific status before any state mutation.
        // The root-level "status" can be an envelope status (e.g. HTTP 200); never
        // infer that a missing transaction status means payment success.
        $statusValue = $payload['data']['transaction']['status']
            ?? $payload['data']['status']
            ?? null;
        $rawStatus = is_string($statusValue) || is_numeric($statusValue)
            ? trim((string) $statusValue)
            : '';
        $domainStatus = PaymentState::mapSingaPayStatus($rawStatus);

        if ($domainStatus === 'unknown') {
            Log::warning('ProcessSingaPayWebhook: Missing or unknown transaction status', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
                'event'      => $payload['event'] ?? 'unknown',
                'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            ]);

            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Missing or unknown SingaPay transaction status',
            ];
        }

        // 7. Validate transaction amount and currency. Both are mandatory before
        // a callback may mark a payment successful.
        $incomingAmount   = $this->extractAmount($payload);
        $incomingCurrency = $this->extractCurrency($payload);

        if (
            $domainStatus === PaymentState::STATUS_SUKSES
            && ($incomingAmount === null || $incomingCurrency === null)
        ) {
            Log::warning('ProcessSingaPayWebhook: Successful transaction missing amount or currency', [
                'payment_id' => $payment->id,
                'order_id'   => $payment->order_id,
            ]);

            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Successful transaction is missing amount or currency',
            ];
        }

        if ($incomingCurrency !== null && strtoupper((string) $incomingCurrency) !== 'IDR') {''',
        "SingaPay transaction status and financial validation",
    )
    targets[path] = replace_exact(
        targets[path],
        '''        // 7. Idempotency Check''',
        '''        // 8. Idempotency Check''',
        "SingaPay idempotency comment",
    )
    targets[path] = replace_exact(
        targets[path],
        '''        // 8. Synchronize Payment Status
        app(TenantContext::class)->setTenantId($payment->idtenant);

        try {
            $rawStatus = (string) (
                $payload['data']['transaction']['status']
                ?? $payload['data']['status']
                ?? $payload['status']
                ?? 'paid'
            );

            $domainStatus = PaymentState::mapSingaPayStatus($rawStatus);

            if ($domainStatus === PaymentState::STATUS_SUKSES) {''',
        '''        // 9. Synchronize Payment Status
        app(TenantContext::class)->setTenantId($payment->idtenant);

        try {
            if ($domainStatus === PaymentState::STATUS_SUKSES) {''',
        "remove unsafe status fallback",
    )
    targets[path] = replace_exact(
        targets[path],
        '''            } else {
                Log::warning('ProcessSingaPayWebhook: Unknown SingaPay status encountered', [
                    'raw_status' => $rawStatus,
                    'order_id'   => $payment->order_id,
                ]);
            }''',
        '''            } else {
                // Defensive fallback: status was validated above, so this should be
                // unreachable unless PaymentState mappings change in the future.
                Log::error('ProcessSingaPayWebhook: Unhandled mapped transaction status', [
                    'payment_id' => $payment->id,
                    'order_id'   => $payment->order_id,
                    'raw_status' => $rawStatus,
                ]);

                return [
                    'success' => false,
                    'code'    => 422,
                    'message' => 'Unsupported SingaPay transaction status',
                ];
            }''',
        "reject unhandled SingaPay status",
    )
    for old, new, label in [
        ('''            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('order_id', (string) $primaryReff)
                ->first();''', '''            $payment = $this->findSingaPayPayment('order_id', (string) $primaryReff);''', "provider-scope primary order id"),
        ('''            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('external_id', (string) $primaryReff)
                ->first();''', '''            $payment = $this->findSingaPayPayment('external_id', (string) $primaryReff);''', "provider-scope primary external id"),
        ('''            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('order_id', (string) $ref)
                ->first();''', '''            $payment = $this->findSingaPayPayment('order_id', (string) $ref);''', "provider-scope fallback order id"),
        ('''            $payment = Payment::withoutGlobalScope(TenantScope::class)
                ->where('external_id', (string) $ref)
                ->first();''', '''            $payment = $this->findSingaPayPayment('external_id', (string) $ref);''', "provider-scope fallback external id"),
    ]:
        targets[path] = replace_exact(targets[path], old, new, label)
    targets[path] = replace_exact(
        targets[path],
        '''    /**
     * Resolve Payment using data.transaction.reff_no as primary identifier,''',
        '''    /**
     * Resolve only payments owned by SingaPay, with a narrow backward-compatible
     * fallback for legacy rows that have a null provider but explicitly use SingaPay.
     */
    protected function findSingaPayPayment(string $column, string $reference): ?Payment
    {
        return Payment::withoutGlobalScope(TenantScope::class)
            ->where($column, $reference)
            ->where(function ($query) {
                $query->where('provider', 'singapay')
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->whereNull('provider')
                            ->where('metode', PaymentState::METODE_SINGAPAY);
                    });
            })
            ->first();
    }

    /**
     * Resolve Payment using data.transaction.reff_no as primary identifier,''',
        "add SingaPay provider-scoped lookup",
    )
    targets[path] = replace_exact(
        targets[path],
        '''        return $val !== null ? (float) $val : null;''',
        '''        return is_numeric($val) ? (float) $val : null;''',
        "strict amount parsing",
    )
    targets[path] = replace_exact(
        targets[path],
        '''        return $curr ? (string) $curr : null;''',
        '''        return is_string($curr) && trim($curr) !== '' ? trim($curr) : null;''',
        "strict currency parsing",
    )

    # Add a regression test: a valid provider signature does not waive the
    # requirement that the provider-reported amount match BookQu's payment.
    path = "tests/Feature/Payment/PaymentActionTest.php"
    targets[path] = replace_exact(
        targets[path],
        '''    public function test_process_webhook_validates_signature(): void
    {''',
        '''    public function test_process_webhook_rejects_gross_amount_mismatch(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_MIDTRANS,
            'order_id'       => 'BKG-MIDTRANS-AMOUNT-MISMATCH',
            'nama_pembayar'  => 'Amount Mismatch Test',
            'email_pembayar' => 'amount-mismatch@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $reportedAmount = '10000.00';
        $serverKey = config('midtrans.server_key');
        $signature = hash('sha512', $payment->order_id . '200' . $reportedAmount . $serverKey);

        $result = app(ProcessPaymentWebhook::class)->execute([
            'order_id'           => $payment->order_id,
            'status_code'        => '200',
            'gross_amount'       => $reportedAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['code']);
        $this->assertSame('Transaction amount mismatch', $result['message']);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_process_webhook_validates_signature(): void
    {''',
        "Midtrans amount mismatch regression test",
    )

    # Test that a Midtrans callback cannot resolve a SingaPay-owned payment.
    path = "tests/Feature/Payment/PaymentActionTest.php"
    targets[path] = replace_exact(
        targets[path],
        '''    public function test_process_webhook_rejects_gross_amount_mismatch(): void
    {''',
        '''    public function test_midtrans_webhook_cannot_resolve_singapay_payment(): void
    {
        $payment = Payment::create([
            'idtenant'       => $this->tenant->id,
            'tipe'           => PaymentState::TIPE_BOOKING,
            'jumlah'         => 50000,
            'status'         => PaymentState::STATUS_PENDING,
            'metode'         => PaymentState::METODE_SINGAPAY,
            'provider'       => 'singapay',
            'order_id'       => 'BKG-WRONG-PROVIDER-MIDTRANS',
            'nama_pembayar'  => 'Provider Isolation Test',
            'email_pembayar' => 'provider-isolation@test.com',
            'hp_pembayar'    => '08123456789',
        ]);

        $reportedAmount = '50000.00';
        $serverKey = config('midtrans.server_key');
        $signature = hash('sha512', $payment->order_id . '200' . $reportedAmount . $serverKey);
        $result = app(ProcessPaymentWebhook::class)->execute([
            'order_id'           => $payment->order_id,
            'status_code'        => '200',
            'gross_amount'       => $reportedAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['code']);
        $this->assertSame('Payment not found', $result['message']);
        $this->assertSame(PaymentState::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_process_webhook_rejects_gross_amount_mismatch(): void
    {''',
        "Midtrans provider isolation regression test",
    )

    # 3. Prevent cross-provider callbacks from resolving the wrong payment.
    path = "app/Actions/Payment/ProcessPaymentWebhook.php"
    targets[path] = replace_exact(
        targets[path],
        r'''use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;''',
        r'''use App\Domain\Payment\PaymentState;
use App\Infrastructure\Payments\Midtrans\MidtransPaymentGateway;''',
        "import payment state constants",
    )
    targets[path] = replace_exact(
        targets[path],
        '''        if (!$payment) {
            Log::warning('ProcessPaymentWebhook: Payment not found', ['order_id' => $orderId]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        // Validate webhook signature''',
        '''        if (!$payment) {
            Log::warning('ProcessPaymentWebhook: Payment not found', ['order_id' => $orderId]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        // Do not let a Midtrans callback operate on a record explicitly owned by
        // SingaPay. Keep legacy Midtrans rows resolvable even if their `metode`
        // was replaced by Midtrans's payment-type value after settlement.
        if ($payment->provider === 'singapay' || $payment->metode === PaymentState::METODE_SINGAPAY) {
            Log::warning('ProcessPaymentWebhook: Provider mismatch', [
                'payment_id' => $payment->id,
                'order_id'   => $orderId,
                'provider'   => $payment->provider,
            ]);

            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Payment not found',
            ];
        }

        // Validate webhook signature''',
        "reject Midtrans callbacks for SingaPay payment rows",
    )

    # 4. Prevent raw Midtrans payload/signature from being persisted to logs.
    path = "app/Actions/Payment/ProcessPaymentWebhook.php"
    targets[path] = replace_exact(
        targets[path],
        '''        Log::info('ProcessPaymentWebhook Received:', $payload);

        $orderId      = $payload['order_id'] ?? null;
        $statusCode   = $payload['status_code'] ?? null;
        $grossAmount  = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('ProcessPaymentWebhook: Missing required fields', $payload);''',
        '''        $orderId      = $payload['order_id'] ?? null;
        $statusCode   = $payload['status_code'] ?? null;
        $grossAmount  = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        // Keep operational context but avoid persisting the raw webhook payload,
        // which may contain customer data and the provider signature value.
        $safeLogContext = array_filter([
            'order_id'           => is_scalar($orderId) ? (string) $orderId : null,
            'status_code'        => is_scalar($statusCode) ? (string) $statusCode : null,
            'gross_amount'       => is_scalar($grossAmount) ? (string) $grossAmount : null,
            'transaction_status' => is_scalar($payload['transaction_status'] ?? null)
                ? (string) ($payload['transaction_status'] ?? null)
                : null,
            'payment_type'       => is_scalar($payload['payment_type'] ?? null)
                ? (string) ($payload['payment_type'] ?? null)
                : null,
        ], static fn ($value) => $value !== null && $value !== '');

        Log::info('ProcessPaymentWebhook Received', $safeLogContext);

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('ProcessPaymentWebhook: Missing required fields', $safeLogContext);''',
        "sanitize Midtrans webhook logging",
    )

    # After signature verification, reject any provider-reported gross amount
    # that differs from the amount saved in BookQu before updating payment state.
    path = "app/Actions/Payment/ProcessPaymentWebhook.php"
    targets[path] = replace_exact(
        targets[path],
        '''        // Validate webhook signature
        if (!$this->gateway->verifyWebhookSignature($payload, $payment)) {
            Log::warning('ProcessPaymentWebhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Invalid signature',
            ];
        }

        app(TenantContext::class)->setTenantId($payment->idtenant);''',
        '''        // Validate webhook signature
        if (!$this->gateway->verifyWebhookSignature($payload, $payment)) {
            Log::warning('ProcessPaymentWebhook: Signature mismatch', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Invalid signature',
            ];
        }

        // A valid signature proves the notification origin, not that BookQu's
        // expected payment amount matches the reported transaction amount.
        if (!is_numeric($grossAmount) || abs((float) $grossAmount - (float) $payment->jumlah) > 0.001) {
            Log::warning('ProcessPaymentWebhook: Gross amount mismatch', [
                'payment_id' => $payment->id,
                'order_id'   => $orderId,
                'expected'   => (string) $payment->jumlah,
                'received'   => is_scalar($grossAmount) ? (string) $grossAmount : null,
            ]);

            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Transaction amount mismatch',
            ];
        }

        app(TenantContext::class)->setTenantId($payment->idtenant);''',
        "Midtrans expected amount validation",
    )

    # Record explicit Midtrans provider identity when creating new Midtrans payments.
    path = "app/Actions/Payment/CreateSubscriptionPayment.php"
    targets[path] = replace_exact(
        targets[path],
        '''            'metode'         => PaymentState::METODE_MIDTRANS,''',
        '''            'metode'         => PaymentState::METODE_MIDTRANS,
            'provider'       => 'midtrans',''',
        "store Midtrans provider for subscription payments",
    )

    path = "app/Actions/Payment/CreateBookingPayment.php"
    targets[path] = replace_exact(
        targets[path],
        '''        $payment->update(['snap_token' => $snapToken]);''',
        '''        $payment->update([
            'snap_token' => $snapToken,
            'provider'   => 'midtrans',
            'metode'     => PaymentState::METODE_MIDTRANS,
        ]);''',
        "store Midtrans provider for Snap booking payments",
    )

    # 5. Make payout debit and payout creation atomic, with a tenant row lock.
    path = "app/Actions/Owner/RequestOwnerPayout.php"
    targets[path] = replace_exact(
        targets[path],
        r'''use App\Support\TenantContext;''',
        r'''use Illuminate\Support\Facades\DB;''',
        "replace payout action context import with database transaction support",
    )
    targets[path] = replace_exact(
        targets[path],
        '''    public function execute(Tenant $tenant, float $amount): OwnerPayout
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        if ($tenant->payment_mode !== 'platform') {
            throw ValidationException::withMessages([
                'jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.',
            ]);
        }

        if ((float) $tenant->saldo_platform < $amount) {
            throw ValidationException::withMessages([
                'jumlah' => 'Saldo tidak mencukupi.',
            ]);
        }

        $tenant->saldo_platform = (float) $tenant->saldo_platform - $amount;
        $tenant->save();

        return OwnerPayout::create([
            'idtenant'     => $tenant->id,
            'jumlah'       => $amount,
            'status'       => 'pending',
            'requested_at' => now(),
        ]);
    }''',
        '''    public function execute(Tenant $tenant, float $amount): OwnerPayout
    {
        if (!is_finite($amount) || $amount < 10000 || abs($amount - round($amount, 2)) > 0.00001) {
            throw ValidationException::withMessages([
                'jumlah' => 'Nominal withdraw minimal Rp 10.000 dan maksimal dua angka desimal.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $amount): OwnerPayout {
            // Serialize withdrawals for this tenant so parallel requests cannot
            // both spend the same previously-read balance.
            $lockedTenant = Tenant::query()->lockForUpdate()->find($tenant->id);
            if (!$lockedTenant) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Tenant tidak ditemukan.',
                ]);
            }

            if ($lockedTenant->payment_mode !== 'platform') {
                throw ValidationException::withMessages([
                    'jumlah' => 'Withdraw hanya tersedia untuk mode pembayaran platform.',
                ]);
            }

            $balance = (float) $lockedTenant->saldo_platform;
            $amount = round($amount, 2);
            if ($balance < $amount) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo tidak mencukupi.',
                ]);
            }

            $lockedTenant->saldo_platform = round($balance - $amount, 2);
            $lockedTenant->save();

            return OwnerPayout::create([
                'idtenant'     => $lockedTenant->id,
                'jumlah'       => $amount,
                'status'       => 'pending',
                'requested_at' => now(),
            ]);
        });
    }''',
        "make payout debit and creation atomic with a tenant row lock",
    )

    # Reject payout inputs with more than two decimal places, to avoid debiting a
    # different amount from the value stored in the decimal(14,2) payout record.
    path = "app/Http/Requests/Owner/RequestPayoutRequest.php"
    targets[path] = replace_exact(
        targets[path],
        '''            'jumlah' => ['required', 'numeric', 'min:10000'],''',
        '''            'jumlah' => ['required', 'numeric', 'min:10000', 'decimal:0,2'],''',
        "payout precision validation",
    )

    # Regression tests for payout overspend and invalid precision.
    path = "tests/Feature/Owner/SettingsTest.php"
    targets[path] = replace_exact(
        targets[path],
        '''    public function test_owner_cannot_delete_account_without_matching_confirmation(): void
    {''',
        '''    public function test_owner_cannot_request_payout_above_available_balance(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/payouts', [
            'jumlah' => 600000,
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertDatabaseMissing('owner_payouts', ['idtenant' => $this->tenant->id]);
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'saldo_platform' => 500000,
        ]);
    }

    public function test_owner_cannot_request_payout_with_more_than_two_decimal_places(): void
    {
        $response = $this->actingAs($this->user)->post('/owner/payouts', [
            'jumlah' => 10000.001,
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertDatabaseMissing('owner_payouts', ['idtenant' => $this->tenant->id]);
        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'saldo_platform' => 500000,
        ]);
    }

    public function test_owner_cannot_delete_account_without_matching_confirmation(): void
    {''',
        "payout regression tests",
    )

    # 6. Make accidental SVG uploads impossible unless the project adopts an
    # explicit sanitizer/serving policy for active SVG content.
    path = "app/Http/Controllers/Owner/OwnerAssetController.php"
    targets[path] = replace_exact(
        targets[path],
        '''            'file'     => 'required|file|image|mimes:jpg,jpeg,png,webp,svg,gif|max:10240',''',
        '''            'file'     => 'required|file|image|mimes:jpg,jpeg,png,webp,gif|max:10240',''',
        "disallow unsanitized SVG asset uploads",
    )

    # 7. Ensure this development branch runs the repository's CI workflow.
    path = ".github/workflows/tests.yml"
    targets[path] = replace_exact(
        targets[path],
        '''    branches:
      - Refactor
      - main
      - payment
  pull_request:
    branches:
      - Refactor
      - main
      - payment''',
        '''    branches:
      - agathan
      - Refactor
      - main
      - payment
  pull_request:
    branches:
      - agathan
      - Refactor
      - main
      - payment''',
        "CI branch filters",
    )

    audit_report = '''# BookQu Technical Audit — 2026-10-17

## Scope

- Repository: `BagusAth/bookqu`
- Working branch intended for this patch: `agathan`
- Audit areas: payment webhook correctness, provider isolation, sensitive logging, payout atomicity, asset upload policy, CI trigger coverage, and regression tests.
- This is a source-code review, not a penetration test or verification of live production configuration.

## Code fixes in this patch

### P0 — Fail-closed SingaPay webhook status handling
- Removed the fallback that treated a missing transaction status as `paid`.
- Root-level `status` is no longer treated as payment status; it can be an HTTP/envelope status.
- Missing or unmapped transaction status returns HTTP 422 without changing payment state.
- A successful payment status requires amount and currency, and the existing IDR/amount mismatch checks remain in force.
- Webhook amount/currency values are type-checked before use.

### P0 — Provider identity and callback isolation
- SingaPay callback resolution only matches `provider=singapay`, plus legacy rows with null `provider` and explicit `metode=singapay`.
- The Midtrans webhook rejects records explicitly marked as SingaPay, while retaining compatibility for old Midtrans payment rows whose `metode` becomes a payment-instrument value after settlement.
- New subscription and Snap booking payments record `provider=midtrans` explicitly.
- Regression tests cover reference collisions in both directions.

### P1 — Midtrans amount integrity and logging
- Midtrans webhook logs keep operational fields but no longer log the entire raw request payload and signature value.
- Midtrans callbacks with a valid signature are rejected if `gross_amount` differs from BookQu's stored payment amount.

### P1 — Payout consistency and upload safety
- Payout balance debit and payout record creation now occur in one transaction with a tenant row lock, and amounts are validated to two decimal places.
- Unsanitized SVG uploads are excluded until an explicit SVG sanitization and serving policy exists.

### P1 — Test and CI coverage
- The OAuth contract test now checks `X-CLIENT-ID`, `X-PARTNER-ID`, and the HMAC-SHA512 `X-Signature` expected by SingaPay v1.1 rather than expecting Basic Authorization.
- Regression tests cover missing SingaPay status, missing amount/currency on success, cross-provider callback collisions in both directions, Midtrans gross-amount mismatch, and payout balance/precision failures.
- Payout debit and payout-row creation now occur in one transaction using a per-tenant row lock; withdrawal amounts are bounded to valid positive values with at most two decimal places.
- Unsanitized SVG asset uploads are excluded until the application has an explicit SVG sanitization and serving policy.
- GitHub Actions test workflow now triggers for pushes and pull requests targeting `agathan`.

## Evidence

- The last recorded CI run on the equivalent `Refactor` implementation failed only in `SingaPayIntegrationTest::test_oauth_token_request_contract`: 347 tests passed, 1 failed, 1,660 assertions.
- Official SingaPay Payment Gateway v1.1 Access Token documentation: https://docs.singapay.id/api-reference/security/access-token-v11
- The tracked `.env.testing` inspected during the audit contains a testing APP_KEY and in-memory SQLite configuration only; no production payment secret was found there.

## Verification status

The patch must be applied and then tested in the repository environment. Release confidence is not established until the complete GitHub Actions run is green and the logs are reviewed. This source-only audit does not prove that production secrets, schedulers, queue workers, backups/restores, monitoring, or rollback work correctly in the live hosting environment.

## Remaining audit work

1. Review every remaining `withoutGlobalScope(s)` usage and prove that the call has a correct authorization/ownership check.
2. Verify concurrent booking and payout behavior against the production database engine, not only in-memory SQLite.
3. Run dependency vulnerability/static-analysis checks.
4. Verify production scheduler, queue worker, monitoring, backup restore, and deployment rollback procedures with authorized operational access.
5. Review all public uploads and ensure SVG/HTML-like active content cannot execute in the application origin.
6. Refresh the implementation tracker only after CI and required operational checks produce evidence.
'''

    # Add audit report as an in-memory change; do not write any file until all guards pass.
    audit_path = ROOT / "docs" / "TECHNICAL-AUDIT-2026-10-17.md"
    if audit_path.exists():
        fail("docs/TECHNICAL-AUDIT-2026-10-17.md already exists; review it manually before applying this patch.")

    # Syntax-level postconditions before disk writes.
    if "?? 'paid'" in targets["app/Actions/Payment/ProcessSingaPayWebhook.php"]:
        fail("Unsafe default-to-paid expression remains after patch preparation.")
    if "Log::info('ProcessPaymentWebhook Received:', $payload)" in targets["app/Actions/Payment/ProcessPaymentWebhook.php"]:
        fail("Raw Midtrans payload logging remains after patch preparation.")
    if "Transaction amount mismatch" not in targets["app/Actions/Payment/ProcessPaymentWebhook.php"]:
        fail("Midtrans amount-mismatch guard is missing after patch preparation.")
    if targets[".github/workflows/tests.yml"].count("      - agathan") != 2:
        fail("Expected agathan in both push and pull_request CI branch filters.")

    # Write only after all file guards succeeded. Originals are retained by Git for easy rollback.
    for relative, content in targets.items():
        (ROOT / relative).write_text(content, encoding="utf-8", newline="\n")
    audit_path.write_text(audit_report, encoding="utf-8", newline="\n")

    print("Audit patch applied to working tree on branch agathan. No commit or push was performed.")
    print("Changed files:")
    for relative in list(targets) + ["docs/TECHNICAL-AUDIT-2026-10-17.md"]:
        print(f"  - {relative}")
    print("Next: inspect `git diff`, run `git diff --check`, run SingaPayIntegrationTest, then the full `php artisan test` suite.")


if __name__ == "__main__":
    main()
