<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResolvesOwnerTenant;

class OwnerPortalController extends Controller
{
    use ResolvesOwnerTenant;

    public function calendar()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-calendar', compact('tenant'));
    }

    public function scheduleReport()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-schedule-report', compact('tenant'));
    }

    public function categories()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-categories', compact('tenant'));
    }

    public function staffResources()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-staff-resources', compact('tenant'));
    }

    public function additionalItems()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-additional-items', compact('tenant'));
    }

    public function vouchers()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-vouchers', compact('tenant'));
    }

    public function reviews()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-reviews', compact('tenant'));
    }

    public function customers()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-customers', compact('tenant'));
    }

    public function appearance()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-appearance', compact('tenant'));
    }

    public function paymentSettings()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-payment-settings', compact('tenant'));
    }

    public function assets()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-assets', compact('tenant'));
    }

    public function balance()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-balance', compact('tenant'));
    }

    public function integrations()
    {
        $tenant = $this->resolveTenant();
        return view('owner.owner-integrations', compact('tenant'));
    }
}
