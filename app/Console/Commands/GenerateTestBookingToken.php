<?php

namespace App\Console\Commands;

use App\Actions\CustomerAccess\CustomerAccessLinks;
use App\Actions\CustomerAccess\IssueCustomerAccess;
use App\Domain\CustomerAccess\AccessProfile;
use App\Models\Booking;
use Illuminate\Console\Command;

class GenerateTestBookingToken extends Command
{
    protected $signature = 'booking:test-token {--booking-id=} {--tenant-id=1} {--status=paid}';

    protected $description = 'Issue a new test Management link (local/testing only)';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Command hanya tersedia di local/testing.');

            return self::FAILURE;
        }
        // scope-bypass: explicit local diagnostic selection; no production access and issuance validates the subject.
        $query = Booking::withoutGlobalScopes()->where('idtenant', (int) $this->option('tenant-id'));
        $booking = $this->option('booking-id')
            ? $query->find($this->option('booking-id'))
            : $query->where('status', $this->option('status'))->latest()->first();
        if ($booking === null) {
            $this->error('Booking tidak ditemukan.');

            return self::FAILURE;
        }
        $booking->assignBookingCode();
        $issued = app(IssueCustomerAccess::class)->execute($booking, AccessProfile::Management);
        $this->line(app(CustomerAccessLinks::class)->entry($issued));

        return self::SUCCESS;
    }
}
