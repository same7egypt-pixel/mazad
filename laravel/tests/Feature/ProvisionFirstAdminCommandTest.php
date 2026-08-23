<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionFirstAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_the_configured_first_admin_with_global_access(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('marketplace.first_admin', [
            'enabled' => true,
            'name' => 'Trial Administrator',
            'email' => 'admin@example.test',
            'password' => 'A-safe-temporary-password-123!',
        ]);

        $this->artisan('marketplace:provision-first-admin')->assertSuccessful();
        $this->artisan('marketplace:provision-first-admin')->assertSuccessful();

        $administrator = User::query()->where('email', 'admin@example.test')->sole();

        $this->assertSame('active', $administrator->status);
        $this->assertSame('verified', $administrator->verification_status);
        $this->assertNotNull($administrator->email_verified_at);
        $this->assertTrue($administrator->hasRole('GLOBAL_SUPER_ADMIN'));
        $this->assertTrue($administrator->canAccessPanel(Filament::getPanel('admin')));
        $this->assertSame(1, User::query()->where('email', 'admin@example.test')->count());
    }

    public function test_it_does_not_create_an_admin_without_explicit_enablement(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('marketplace.first_admin', [
            'enabled' => false,
            'name' => 'Trial Administrator',
            'email' => 'admin@example.test',
            'password' => 'A-safe-temporary-password-123!',
        ]);

        $this->artisan('marketplace:provision-first-admin')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_it_does_not_create_an_admin_with_an_invalid_email_address(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('marketplace.first_admin', [
            'enabled' => true,
            'name' => 'Trial Administrator',
            'email' => 'admin-without-a-domain',
            'password' => 'A-safe-temporary-password-123!',
        ]);

        $this->artisan('marketplace:provision-first-admin')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'admin-without-a-domain']);
    }
}
