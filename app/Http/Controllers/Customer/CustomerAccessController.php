<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\CustomerAccess\CustomerAccessSession;
use App\Actions\CustomerAccess\ResolveCustomerAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerAccessController extends Controller
{
    public function show(Request $request): View
    {
        $this->canonical($request);

        return view('customer.access');
    }

    public function store(Request $request, ResolveCustomerAccess $resolve, CustomerAccessSession $session, CustomerAccessLinks $links): JsonResponse
    {
        $this->canonical($request);
        $credential = $request->input('credential');
        abort_unless(is_string($credential) && strlen($credential) === 97, 404);
        $context = $resolve->credential($credential);
        $session->establish($request, $context);

        return response()->json(['redirect' => $links->destination($context)]);
    }

    private function canonical(Request $request): void
    {
        abort_unless($request->getHost() === parse_url(config('app.url'), PHP_URL_HOST), 404);
        abort_if(! app()->environment('local', 'testing') && ! $request->isSecure(), 404);
    }
}
