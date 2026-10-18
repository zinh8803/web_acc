<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== GROUP G: SERVICE REQUESTS ====================
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 30)->unique();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->enum('status', [
                'PENDING', 'ASSIGNED', 'IN_PROGRESS', 'NEED_INFO', 'DONE', 'CANCELLED', 'REFUNDED'
            ])->default('PENDING');
            $table->json('form_data'); // game, server, rank info (non-sensitive)
            $table->text('game_login_enc')->nullable(); // AES encrypted game credentials
            $table->string('customer_note', 500)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->tinyInteger('priority')->default(3);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('auto_confirm_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index(['status', 'due_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('service_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->enum('actor_type', ['SYSTEM', 'USER', 'ADMIN']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('progress_percent')->nullable();
            $table->boolean('is_visible_to_user')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('service_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('log_id')->nullable();
            $table->enum('uploaded_by_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('uploaded_by_id');
            $table->string('url', 500);
            $table->enum('file_type', ['IMAGE', 'VIDEO']);
            $table->timestamp('created_at')->useCurrent();
        });

        // ==================== GROUP H: WARRANTY ====================
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_code', 30)->unique();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('game_account_id')->nullable()->constrained('game_accounts')->nullOnDelete();
            $table->foreignId('policy_id')->constrained('warranty_policies')->restrictOnDelete();
            $table->enum('claim_type', [
                'LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS', 'SERVICE_NOT_DONE', 'OTHER'
            ]);
            $table->text('description');
            $table->enum('status', [
                'SUBMITTED', 'IN_REVIEW', 'NEED_INFO', 'APPROVED', 'REJECTED', 'RESOLVED', 'CANCELLED'
            ])->default('SUBMITTED');
            $table->string('resolution_type', 30)->nullable(); // REPLACE_ACCOUNT, RECOVER_ACCOUNT, REFUND, PARTIAL_REFUND
            $table->string('resolution_note', 500)->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->foreignId('replacement_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index(['order_item_id']);
        });

        Schema::create('warranty_claim_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('warranty_claims')->cascadeOnDelete();
            $table->enum('sender_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->boolean('is_internal')->default(false); // staff internal notes
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('warranty_claim_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('warranty_claims')->cascadeOnDelete();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->enum('uploaded_by_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('uploaded_by_id');
            $table->string('url', 500);
            $table->enum('file_type', ['IMAGE', 'VIDEO']);
            $table->timestamp('created_at')->useCurrent();
        });

        // ==================== GROUP I: MISC ====================
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->tinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'HIDDEN'])->default('PENDING');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ['USER', 'ADMIN']);
            $table->unsignedBigInteger('recipient_id');
            $table->string('type', 100);
            $table->string('title', 255);
            $table->text('content');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['recipient_type', 'recipient_id', 'read_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['ADMIN', 'USER', 'SYSTEM']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_url', 500);
            $table->string('link_url', 500)->nullable();
            $table->string('title', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['ACTIVE', 'HIDDEN'])->default('ACTIVE');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('warranty_claim_attachments');
        Schema::dropIfExists('warranty_claim_messages');
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('service_request_attachments');
        Schema::dropIfExists('service_request_logs');
        Schema::dropIfExists('service_requests');
    }
};
