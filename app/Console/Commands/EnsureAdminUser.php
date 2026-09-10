<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminUser extends Command
{
    protected $signature = 'app:ensure-admin';

    protected $description = 'Create the admin user from ADMIN_EMAIL / ADMIN_PASSWORD if it does not exist yet';

    public function handle(): int
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set, skipping.');

            return self::SUCCESS;
        }

        User::firstOrCreate(['email' => $email], ['name' => 'Admin', 'password' => Hash::make($password)]);
        $this->info("Admin user {$email} ready.");

        return self::SUCCESS;
    }
}
