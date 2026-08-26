<?php

namespace App\Console\Commands;

use App\Models\MemberDue;
use App\Notifications\MemberDueReminder;
use Illuminate\Console\Command;

class SendUnpaidDueReminders extends Command
{
    protected $signature = 'dues:remind-unpaid';

    protected $description = 'Notify members whose dues have reached their period and are still outstanding';

    public function handle(): int
    {
        $sent = 0;

        MemberDue::query()
            ->whereDate('period', '<=', now())
            ->with(['payments', 'membership.user'])
            ->chunkById(100, function ($dues) use (&$sent) {
                foreach ($dues as $due) {
                    if ($due->isPaid()) {
                        continue;
                    }

                    $due->membership->user->notify(new MemberDueReminder($due));
                    $sent++;
                }
            });

        $this->info("Sent {$sent} due reminder(s).");

        return self::SUCCESS;
    }
}
