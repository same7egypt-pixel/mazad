<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->boolean('cash_on_delivery_enabled')->default(false);
            $table->unsignedSmallInteger('cod_confirmation_hours')->default(12);
            $table->unsignedSmallInteger('cod_dispute_hours')->default(48);
        });

        DB::table('countries')->where('code', 'SA')->update(['cash_on_delivery_enabled' => true]);

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_method', 32)->default('online')->index();
            $table->timestampTz('winner_confirmed_at')->nullable();
            $table->timestampTz('winner_confirmation_expires_at')->nullable()->index();
            $table->string('fulfilment_preference', 32)->nullable();
            $table->decimal('shipping_fee', 18, 2)->default(0);
            $table->string('collection_status', 32)->default('not_applicable')->index();
            $table->string('settlement_status', 32)->default('not_due')->index();
            $table->timestampTz('receipt_confirmed_at')->nullable();
            $table->timestampTz('settled_at')->nullable();
            $table->string('collection_failure_reason', 255)->nullable();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->timestampTz('collected_at')->nullable();
            $table->string('collection_reference', 191)->nullable();
            $table->string('collection_failure_reason', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['collected_at', 'collection_reference', 'collection_failure_reason']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['winner_confirmation_expires_at']);
            $table->dropIndex(['collection_status']);
            $table->dropIndex(['settlement_status']);
            $table->dropColumn([
                'payment_method', 'winner_confirmed_at', 'winner_confirmation_expires_at',
                'fulfilment_preference', 'shipping_fee', 'collection_status', 'settlement_status',
                'receipt_confirmed_at', 'settled_at', 'collection_failure_reason',
            ]);
        });

        Schema::table('countries', function (Blueprint $table): void {
            $table->dropColumn(['cash_on_delivery_enabled', 'cod_confirmation_hours', 'cod_dispute_hours']);
        });
    }
};
