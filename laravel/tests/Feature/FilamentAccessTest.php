<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_active_admin_role_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));

        $this->assertTrue($user->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_unverified_or_non_admin_users_cannot_access_the_admin_panel(): void
    {
        $unverifiedAdmin = User::factory()->unverified()->create(['status' => 'active']);
        $unverifiedAdmin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));

        $regularUser = User::factory()->create(['status' => 'active']);
        $regularUser->assignRole(Role::findOrCreate('USER', 'web'));

        $panel = Panel::make()->id('admin');

        $this->assertFalse($unverifiedAdmin->canAccessPanel($panel));
        $this->assertFalse($regularUser->canAccessPanel($panel));
    }
}
