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
use Tests\TestCase;

class ProductModerationMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_requires_private_media_before_submission_for_review(): void
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
        $user = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $user->givePermissionTo(Permission::findOrCreate('products.update', 'web'));
        $product = Product::query()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Vintage watch',
            'description' => 'A detailed description for a product waiting for review.',
            'condition' => 'good',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/submit-for-review", [], ['X-Marketplace-Country' => (string) $country->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'At least one product media item is required before review.');

        ProductMedia::query()->create([
            'product_id' => $product->id,
            'disk' => 's3',
            'path' => "products/{$product->id}/watch.webp",
            'media_type' => 'image',
            'mime_type' => 'image/webp',
            'size_bytes' => 1024,
            'sort_order' => 0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/submit-for-review", [], ['X-Marketplace-Country' => (string) $country->id])
            ->assertOk()
            ->assertJsonPath('product.status', 'pending_review');
    }
}
