<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GlobalAdminMarketplaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_super_admin_without_a_home_country_can_load_its_account_and_manage_its_own_product_in_the_selected_marketplace(): void
    {
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches']);
        $administrator = User::factory()->create(['country_id' => null, 'city_id' => null, 'status' => 'active']);
        $administrator->assignRole(Role::findOrCreate('GLOBAL_SUPER_ADMIN', 'web'));
        $administrator->givePermissionTo(Permission::findOrCreate('products.update', 'web'));
        $product = Product::query()->create([
            'user_id' => $administrator->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Administrator watch',
            'description' => 'A product owned by the global administrator.',
            'condition' => 'good',
            'status' => 'draft',
        ]);
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'disk' => 's3',
            'path' => "products/{$product->id}/watch.webp",
            'media_type' => 'image',
            'mime_type' => 'image/webp',
            'size_bytes' => 1024,
            'sort_order' => 0,
        ]);
        $headers = ['X-Marketplace-Country' => (string) $country->id];

        $this->actingAs($administrator, 'sanctum')
            ->getJson('/api/my/products', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->actingAs($administrator, 'sanctum')->getJson('/api/wallets', $headers)->assertOk();
        $this->actingAs($administrator, 'sanctum')->getJson('/api/orders', $headers)->assertOk();
        $this->actingAs($administrator, 'sanctum')
            ->postJson("/api/products/{$product->id}/submit-for-review", [], $headers)
            ->assertOk()
            ->assertJsonPath('product.status', 'pending_review');

        $this->assertNull($administrator->fresh()->country_id);
    }
}
