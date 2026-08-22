<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class MarketplaceReferenceSeeder extends Seeder
{
    /**
     * Seed only catalog reference values needed to operate the empty trial database.
     * This intentionally never creates Marketplace users, products, auctions, bids, or reviews.
     */
    public function run(): void
    {
        $sar = Currency::query()->updateOrCreate(
            ['code' => 'SAR'],
            [
                'name' => 'الريال السعودي',
                'symbol' => 'ر.س',
                'decimal_places' => 2,
                'is_active' => true,
            ],
        );

        $saudiArabia = Country::query()->updateOrCreate(
            ['code' => 'SA'],
            [
                'name' => 'المملكة العربية السعودية',
                'timezone' => 'Asia/Riyadh',
                'currency_id' => $sar->id,
                'is_active' => true,
            ],
        );

        foreach (['الرياض', 'جدة', 'الدمام'] as $cityName) {
            City::query()->updateOrCreate(
                ['country_id' => $saudiArabia->id, 'name' => $cityName],
                ['is_active' => true],
            );
        }

        $categories = [
            ['name' => 'ساعات ومجوهرات', 'slug' => 'watches-jewellery', 'description' => 'ساعات ومجوهرات قابلة للمزايدة.', 'sort_order' => 10],
            ['name' => 'فن واقتناء', 'slug' => 'art-collectibles', 'description' => 'أعمال فنية وقطع قابلة للاقتناء.', 'sort_order' => 20],
            ['name' => 'تحف وأنتيكات', 'slug' => 'antiques', 'description' => 'تحف وقطع أنتيكات.', 'sort_order' => 30],
            ['name' => 'تصميم وديكور', 'slug' => 'design-decor', 'description' => 'قطع تصميم وديكور.', 'sort_order' => 40],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['country_id' => $saudiArabia->id, 'slug' => $category['slug']],
                [...$category, 'parent_id' => null, 'is_active' => true],
            );
        }
    }
}
