<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminAccess\AdminAccessResource;
use App\Filament\Resources\CashOnDeliveryOrders\CashOnDeliveryOrderResource;
use App\Filament\Resources\CashOnDeliveryOrders\Widgets\CashOnDeliveryOverview;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
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

    public function test_only_global_super_admin_can_manage_administrative_roles(): void
    {
        $globalAdmin = User::factory()->create(['status' => 'active']);
        $globalAdmin->assignRole(Role::findOrCreate('GLOBAL_SUPER_ADMIN', 'web'));
        $globalAdmin->givePermissionTo(Permission::findOrCreate('roles.manage', 'web'));
        $targetUser = User::factory()->create(['status' => 'active']);

        $this->actingAs($globalAdmin);

        $this->assertTrue(AdminAccessResource::canViewAny());
        $this->assertTrue(AdminAccessResource::canEdit($targetUser));

        $countryAdmin = User::factory()->create(['status' => 'active']);
        $countryAdmin->assignRole(Role::findOrCreate('COUNTRY_ADMIN', 'web'));
        $countryAdmin->givePermissionTo(Permission::findOrCreate('roles.manage', 'web'));

        $this->actingAs($countryAdmin);

        $this->assertFalse(AdminAccessResource::canViewAny());
        $this->assertFalse(AdminAccessResource::canEdit($targetUser));
    }

    public function test_authorized_admin_can_open_the_country_edit_form_with_the_commission_setting(): void
    {
        $currency = Currency::query()->create([
            'name' => 'Saudi Riyal',
            'code' => 'SAR',
            'symbol' => 'ر.س',
        ]);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
            'cash_on_delivery_enabled' => true,
        ]);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole(Role::findOrCreate('GLOBAL_SUPER_ADMIN', 'web'));
        $admin->givePermissionTo(Permission::findOrCreate('countries.manage', 'web'));

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.countries.edit', ['record' => $country]))
            ->assertOk()
            ->assertSee('نسبة عمولة المنصة')
            ->assertSee('تفعيل الدفع عند الاستلام')
            ->assertSee('مهلة تأكيد الفائز');
    }

    public function test_authorized_admin_can_open_the_cash_on_delivery_operations_queue(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole(Role::findOrCreate('GLOBAL_SUPER_ADMIN', 'web'));
        $admin->givePermissionTo(Permission::findOrCreate('orders.view', 'web'));

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.cash-on-delivery-orders.index'))
            ->assertOk()
            ->assertSee('طلبات الدفع عند الاستلام')
            ->assertSee('لا توجد طلبات دفع عند الاستلام في نطاقك حالياً');

        Livewire::test(CashOnDeliveryOverview::class)
            ->assertSee('تأكيدات الفائزين')
            ->assertSee('بانتظار التحصيل')
            ->assertSee('تعذر تحصيلها')
            ->assertSee('مستحقات محررة');

        $this->assertTrue(CashOnDeliveryOrderResource::canViewAny());
    }
}
