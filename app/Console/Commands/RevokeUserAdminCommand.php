<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RevokeUserAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:revoke-admin {email : The email of the user to revoke admin from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke administrator privileges from a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return self::FAILURE;
        }

        if (! $user->isAdmin()) {
            $this->info("User '{$user->name}' ({$email}) is not an administrator.");

            return self::SUCCESS;
        }

        $user->update(['is_admin' => false]);

        $this->info("Administrator privileges revoked from '{$user->name}' ({$email}).");

        return self::SUCCESS;
    }
}
