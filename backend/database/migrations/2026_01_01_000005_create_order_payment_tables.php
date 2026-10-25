<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== GROUP F: ORDERS & PAYMENTS ====================
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 20)->unique(); // public code = transfer content
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', [
                'PENDING_PAYMENT', 'PAID', 'PROCESSING', 'COMPLETED',
                'CANCELLED', 'EXPIRED', 'REFUNDED', 'PARTIALLY_REFUNDED'
            ])->default('PENDING_PAYMENT');
            $table->decimal('subtotal', 15, 0);
            $table->decimal('discount_total', 15, 0)->default(0);
            $table->decimal('total', 15, 0);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 40)->nullable(); // snapshot
            $table->string('payment_method_code', 30);
            $table->string('payment_reference', 100)->nullable();
            $table->string('customer_note', 500)->nullable();
            $table->timestamp('expires_at'); // payment deadline
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->enum('item_type', ['ACCOUNT', 'SERVICE']);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->string('product_name', 255); // snapshot
            $table->string('variant_name', 150); // snapshot
            $table->string('thumbnail_url', 500)->nullable(); // snapshot
            $table->decimal('unit_price', 15, 0);
            $table->integer('quantity')->default(1);
            $table->decimal('line_total', 15, 0);
            $table->foreignId('game_account_id')->nullable()->constrained('game_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('warranty_policy_id')->nullable();
            $table->json('warranty_snapshot')->nullable(); // policy snapshot at purchase time
            $table->timestamp('warranty_expires_at')->nullable();
            $table->enum('fulfillment_status', [
                'PENDING', 'DELIVERED', 'IN_PROGRESS', 'COMPLETED', 'FAILED', 'CANCELLED'
            ])->default('PENDING');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->decimal('discount_amount', 15, 0);
            $table->enum('status', ['USED', 'REVERTED'])->default('USED');
            $table->timestamp('used_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
