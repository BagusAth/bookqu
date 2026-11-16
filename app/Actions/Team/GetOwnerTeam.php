<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Domain\Subscription\EntitlementRules;
use App\Models\Tenant;
use App\Models\TenantMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

final class GetOwnerTeam
{
    /** @return array<string, mixed> */
    public function execute(Tenant $tenant, ManageTeam $team): array
    {
        $members = TenantMember::where('idtenant', $tenant->id)->with('user')
            ->whereNotNull('accepted_at')->whereNull('revoked_at')->orderBy('id')->get();
        $invitations = $team->pending()->where('idtenant', $tenant->id)->get(['id', 'email', 'role', 'expires_at']);

        return [
            'members' => $members,
            'invitations' => $invitations,
            'seatUsage' => $members->where('iduser', '!=', $tenant->iduser)->count() + $invitations->count(),
            'activity' => DB::table('tenant_activity_logs')->leftJoin('users', 'users.id', '=', 'tenant_activity_logs.iduser')
                ->where('tenant_activity_logs.idtenant', $tenant->id)->orderByDesc('tenant_activity_logs.id')
                ->paginate(20, ['tenant_activity_logs.*', 'users.namalengkap as actor_name'])
                ->through(function (object $entry): object {
                    $eventKey = 'tenant_activity.events.'.$entry->event;
                    [$subject, $verb] = array_pad(explode('.', $entry->event, 2), 2, '');
                    $subjectKey = 'tenant_activity.subjects.'.$subject;
                    $verbKey = 'tenant_activity.verbs.'.$verb;
                    $entry->label = match (true) {
                        Lang::has($eventKey) => __($eventKey),
                        Lang::has($subjectKey) && Lang::has($verbKey) => __($verbKey, ['subject' => __($subjectKey)]),
                        default => __('tenant_activity.unknown'),
                    };
                    $entry->local_time = Carbon::parse($entry->created_at, config('app.timezone'))->locale('id')->translatedFormat('d M Y H.i');

                    return $entry;
                }),
            'seatLimit' => EntitlementRules::teamLimit($tenant->subscription),
        ];
    }
}
