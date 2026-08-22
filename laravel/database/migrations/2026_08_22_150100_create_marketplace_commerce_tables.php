<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->string('condition', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['country_id', 'city_id', 'category_id', 'status']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 64)->default('s3');
            $table->string('path', 1024);
            $table->string('media_type', 16);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'media_type']);
        });

        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('starting_price', 18, 2);
            $table->decimal('current_price', 18, 2);
            $table->decimal('reserve_price', 18, 2)->nullable();
            $table->decimal('minimum_increment', 18, 2);
            $table->timestampTz('start_time')->index();
            $table->timestampTz('end_time')->index();
            $table->string('status', 32)->default('upcoming')->index();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('bid_count')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->index(['country_id', 'status', 'end_time']);
        });

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['auction_id', 'amount']);
            $table->index(['auction_id', 'created_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->decimal('seller_amount', 18, 2)->default(0);
            $table->string('status', 32)->default('waiting_payment')->index();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
            $table->index(['country_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 64);
            $table->string('transaction_id', 191)->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('status', 32)->index();
            $table->jsonb('payload')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'transaction_id']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('pending_balance', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'currency_id']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->decimal('amount', 18, 2);
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('products');
    }
};
