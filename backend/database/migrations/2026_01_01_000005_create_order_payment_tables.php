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

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->enum('actor_type', ['SYSTEM', 'USER', 'ADMIN']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // BANK_TRANSFER, MOMO, VNPAY
            $table->string('name', 100);
            $table->string('provider', 30)->nullable();
            $table->string('icon_url', 500)->nullable();
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 30)->unique();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('method_code', 30);
            $table->string('provider', 30)->nullable(); // VNPAY, MOMO, SEPAY
            $table->decimal('amount', 15, 0);
            $table->decimal('paid_amount', 15, 0)->nullable();
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED', 'NEED_REVIEW'])
                ->default('PENDING');
            $table->string('provider_txn_id', 100)->nullable();
            $table->string('transfer_content', 100)->nullable();
            $table->text('pay_url')->nullable();
            $table->text('qr_payload')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_txn_id']); // idempotency
            $table->index(['order_id']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('provider_txn_id', 100)->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->enum('process_status', ['RECEIVED', 'PROCESSED', 'IGNORED', 'FAILED'])
                ->default('RECEIVED');
            $table->string('error_message')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_code', 30)->unique();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('claim_id')->nullable();
            $table->decimal('amount', 15, 0);
            $table->string('reason', 500)->nullable();
            $table->enum('status', ['REQUESTED', 'APPROVED', 'REJECTED', 'COMPLETED'])
                ->default('REQUESTED');
            $table->enum('refund_method', ['ORIGINAL', 'BANK_MANUAL'])->default('ORIGINAL');
            $table->text('bank_info_enc')->nullable();
            $table->enum('requested_by_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('requested_by_id');
            $table->foreignId('approved_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('provider_refund_id', 100)->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
