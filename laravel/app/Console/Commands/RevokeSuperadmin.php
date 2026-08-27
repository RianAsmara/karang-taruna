<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RevokeSuperadmin extends Command
{
    protected $signature = 'superadmin:revoke {email}';

    protected $description = 'Revoke platform-level superadmin access from a user';

    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $user->is_superadmin) {
            $this->info("{$user->email} is not a superadmin.");

            return self::SUCCESS;
        }

        $user->forceFill(['is_superadmin' => false])->save();
        $this->info("Revoked superadmin from {$user->email}.");

        return self::SUCCESS;
    }
}
