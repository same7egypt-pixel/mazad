<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductMediaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_cannot_upload_private_media_to_another_sellers_product_in_the_same_country(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);
        $currency = Currency::query()->create(['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => 'ر.س']);
        $country = Country::query()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'timezone' => 'Asia/Riyadh',
            'currency_id' => $currency->id,
        ]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
        $category = Category::query()->create(['country_id' => $country->id, 'name' => 'Watches', 'slug' => 'watches']);
        $owner = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $intruder = User::factory()->create(['country_id' => $country->id, 'city_id' => $city->id]);
        $intruder->givePermissionTo(Permission::findOrCreate('products.update', 'web'));
        $product = Product::query()->create([
            'user_id' => $owner->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'title' => 'Owner watch',
            'description' => 'A product whose private media can only be uploaded by the owner.',
            'condition' => 'good',
            'status' => 'draft',
        ]);

        $this->actingAs($intruder, 'sanctum')
            ->post("/api/products/{$product->id}/media", [
                'file' => UploadedFile::fake()->create('watch.jpg', 12, 'image/jpeg'),
            ], ['X-Marketplace-Country' => (string) $country->id])
            ->assertForbidden();

        $this->assertDatabaseCount('product_media', 0);
    }
}
