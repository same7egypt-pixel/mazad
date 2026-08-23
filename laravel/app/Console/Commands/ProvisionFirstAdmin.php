<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProvisionFirstAdmin extends Command
{
    protected $signature = 'marketplace:provision-first-admin';

    protected $description = 'Provision the Render trial global administrator from environment credentials when configured.';

    public function handle(): int
    {
        if (! config('marketplace.first_admin.enabled')) {
            $this->info('First administrator provisioning is disabled; skipping.');

            return self::SUCCESS;
        }

        $name = config('marketplace.first_admin.name');
        $email = config('marketplace.first_admin.email');
        $password = config('marketplace.first_admin.password');

        if (! is_string($name) || ! is_string($email) || ! is_string($password) || $name === '' || $email === '' || $password === '') {
            $this->info('First administrator credentials are not configured; skipping provisioning.');

            return self::SUCCESS;
        }

        $email = mb_strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->warn('First administrator email is invalid; skipping provisioning.');

            return self::SUCCESS;
        }

        $role = Role::findByName('GLOBAL_SUPER_ADMIN', 'web');
        $administrator = User::query()->firstOrNew(['email' => $email]);

        $administrator->fill([
            'name' => $name,
            'status' => 'active',
            'verification_status' => 'verified',
        ]);
        $administrator->email_verified_at = now();

        if (! $administrator->exists) {
            $administrator->password = Hash::make($password);
        }

        $administrator->save();
        $administrator->syncRoles([$role]);

        $this->info("First administrator {$administrator->email} is ready.");

        return self::SUCCESS;
    }
}
