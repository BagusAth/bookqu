<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Owner\GetHelpPage;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Bantuan (BQ-145, owner-portal.md §8.17). Every role (`help.view`), behind the `help` module until it
 * is released (ADR-0021).
 */
class OwnerHelpController extends Controller
{
    public function index(GetHelpPage $getHelpPage): View
    {
        return view('owner.help', $getHelpPage->execute());
    }
}
