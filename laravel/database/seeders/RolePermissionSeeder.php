<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'countries.manage', 'cities.manage', 'currencies.manage', 'categories.manage',
            'users.manage', 'roles.manage', 'products.create', 'products.update', 'products.approve',
            'auctions.create', 'auctions.manage', 'auctions.bid', 'orders.view', 'orders.fulfill',
            'payments.view', 'payments.manage', 'wallet.view', 'wallet.withdraw', 'shipping.manage',
            'reviews.create', 'reviews.moderate', 'notifications.manage', 'support.manage',
            'analytics.view', 'settings.manage', 'audit.view', 'fraud.review',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            'GLOBAL_SUPER_ADMIN' => $permissions,
            'COUNTRY_ADMIN' => ['cities.manage', 'currencies.manage', 'categories.manage', 'users.manage', 'products.approve', 'auctions.manage', 'orders.view', 'orders.fulfill', 'payments.view', 'shipping.manage', 'reviews.moderate', 'support.manage', 'analytics.view', 'audit.view', 'fraud.review'],
            'CITY_ADMIN' => ['products.approve', 'orders.view', 'orders.fulfill', 'shipping.manage', 'reviews.moderate'],
            'FINANCE_ADMIN' => ['orders.view', 'payments.view', 'payments.manage', 'wallet.view', 'analytics.view', 'audit.view'],
            'OPERATIONS_ADMIN' => ['products.approve', 'auctions.manage', 'orders.view', 'orders.fulfill', 'shipping.manage', 'support.manage'],
            'CONTENT_MODERATOR' => ['products.approve', 'reviews.moderate'],
            'SUPPORT_AGENT' => ['orders.view', 'support.manage'],
            'USER' => ['products.create', 'products.update', 'auctions.create', 'auctions.bid', 'orders.view', 'wallet.view', 'wallet.withdraw', 'reviews.create'],
        ];

        foreach ($rolePermissions as $name => $assignedPermissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($assignedPermissions);
        }
    }
}
