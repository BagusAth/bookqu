<?php

namespace App\Jobs;

use App\Actions\Team\ManageTeam;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SendTeamInvitation implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(private int $invitationId, private string $credential) {}

    public function handle(ManageTeam $team): void
    {
        try {
            $invite = $team->resolve($this->credential);
        } catch (NotFoundHttpException) {
            return;
        }
        if ((int) $invite->id !== $this->invitationId) {
            return;
        }

        $mailer = (string) config('mail.default');
        if (in_array(config('mail.mailers.'.$mailer.'.transport'), ['log', 'failover', 'roundrobin'], true)) {
            throw new \RuntimeException('Team invitations require a non-logging mail transport.');
        }

        $link = rtrim((string) config('app.url'), '/').route('team-invitation.show', absolute: false).'#'.$this->credential;
        Mail::raw("Anda diundang bergabung dengan tim BookQu. Buka tautan ini dalam 24 jam:\n".$link, function ($message) use ($invite): void {
            $message->to((string) $invite->email)->subject('Undangan tim BookQu');
        });
    }
}
