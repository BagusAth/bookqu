<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Team\ManageTeam;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

final class TeamInvitationController extends Controller
{
    public function show(Request $request, ManageTeam $team): Response
    {
        abort_if(app()->isProduction() && ! $request->isSecure(), 404);
        $id = $request->session()->get('team_invitation_id');
        $invite = is_int($id) ? $team->pending()->find($id, ['email', 'role']) : null;

        return response()->view('auth.team-invitation', ['invite' => $invite])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function exchange(Request $request, ManageTeam $team): RedirectResponse
    {
        abort_if(app()->isProduction() && ! $request->isSecure(), 404);
        $request->session()->forget('team_invitation_id');
        $invite = $team->resolve((string) $request->input('credential'));
        $request->session()->regenerate();
        $request->session()->put('team_invitation_id', (int) $invite->id);

        return redirect()->route('team-invitation.show', status: 303);
    }

    public function accept(Request $request, ManageTeam $team): RedirectResponse
    {
        abort_if(app()->isProduction() && ! $request->isSecure(), 404);
        $id = $request->session()->get('team_invitation_id');
        abort_unless(is_int($id), 404);
        $user = $request->user();
        $data = $user instanceof User ? [] : $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nomorhp' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,20}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $member = $team->accept($id, $user instanceof User ? $user : null, $data);
        Auth::login($member);
        $request->session()->regenerate();
        $request->session()->forget(['team_invitation_id', 'current_tenant_id']);

        return redirect()->route('owner.dashboard');
    }

    public function login(Request $request): RedirectResponse
    {
        abort_if(app()->isProduction() && ! $request->isSecure(), 404);
        abort_unless(is_int($request->session()->get('team_invitation_id')), 404);
        $request->session()->put('url.intended', route('team-invitation.show'));

        return redirect()->route('login');
    }
}
