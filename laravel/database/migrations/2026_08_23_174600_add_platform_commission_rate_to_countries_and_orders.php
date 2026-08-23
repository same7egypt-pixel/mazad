<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->decimal('platform_commission_rate', 5, 2)->default(10)->after('currency_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('commission_rate');
        });

        Schema::table('countries', function (Blueprint $table): void {
            $table->dropColumn('platform_commission_rate');
        });
    }
};
