<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Deliberately the ONLY way to grant this — no HTTP endpoint exists to
 * set `is_superadmin`, and it's absent from User::$fillable so it can
 * never be set via mass assignment either. A human runs this directly
 * on the server; it is never a client-facing action.
 */
class GrantSuperadmin extends Command
{
    protected $signature = 'superadmin:grant {email}';

    protected $description = 'Grant platform-level superadmin (read-only, cross-organization) access to a user';

    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if ($user->is_superadmin) {
            $this->info("{$user->email} is already a superadmin.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Grant superadmin (read-only, all organizations) to {$user->name} <{$user->email}>?")) {
            return self::FAILURE;
        }

        $user->forceFill(['is_superadmin' => true])->save();
        $this->info("Granted superadmin to {$user->email}.");

        return self::SUCCESS;
    }
}
