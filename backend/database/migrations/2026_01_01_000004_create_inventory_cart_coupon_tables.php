<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== GROUP D: GAME ACCOUNT INVENTORY ====================
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('contact_info', 500)->nullable();
            $table->tinyInteger('trust_level')->default(3); // 1-5
            $table->enum('status', ['ACTIVE', 'BLOCKED'])->default('ACTIVE');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->binary('account_username_enc'); // AES-256-GCM encrypted
            $table->binary('account_password_enc'); // AES-256-GCM encrypted
            $table->text('extra_info_enc')->nullable(); // JSON encrypted: email, 2FA, etc.
            $table->text('original_owner_info_enc')->nullable(); // encrypted for warranty
            $table->enum('status', ['AVAILABLE', 'RESERVED', 'SOLD', 'LOCKED', 'RECOVERED', 'RETIRED'])
                ->default('AVAILABLE');
            $table->unsignedBigInteger('reserved_by_order_id')->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->unsignedBigInteger('sold_order_item_id')->nullable()->unique();
            $table->timestamp('sold_at')->nullable();
            $table->decimal('cost_price', 15, 0)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'reserved_until']);
        });

        Schema::create('game_account_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_account_id')->constrained('game_accounts')->restrictOnDelete();
            $table->enum('action', [
                'IMPORTED', 'RESERVED', 'RELEASED', 'SOLD',
                'LOCKED', 'RECOVERED', 'REPLACED', 'CREDENTIAL_CHANGED'
            ]);
            $table->enum('actor_type', ['SYSTEM', 'USER', 'ADMIN']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('claim_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('credential_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_account_id')->constrained('game_accounts')->restrictOnDelete();
            $table->enum('actor_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->enum('action', ['VIEW', 'COPY'])->default('VIEW');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ==================== GROUP E: CART & COUPONS ====================
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamp('added_at')->useCurrent();
            $table->unique(['cart_id', 'variant_id']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('discount_type', ['PERCENT', 'FIXED']);
            $table->decimal('discount_value', 15, 2);
            $table->decimal('max_discount_amount', 15, 0)->nullable();
            $table->decimal('min_order_amount', 15, 0)->default(0);
            $table->enum('applies_to', ['ALL', 'CATEGORY', 'PRODUCT', 'PRODUCT_TYPE'])->default('ALL');
            $table->enum('applies_product_type', ['ACCOUNT', 'SERVICE'])->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_user')->default(1);
            $table->integer('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coupon_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('category_id');
            $table->primary(['coupon_id', 'category_id']);
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        Schema::create('coupon_products', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('product_id');
            $table->primary(['coupon_id', 'product_id']);
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_products');
        Schema::dropIfExists('coupon_categories');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('credential_access_logs');
        Schema::dropIfExists('game_account_logs');
        Schema::dropIfExists('game_accounts');
        Schema::dropIfExists('suppliers');
    }
};
