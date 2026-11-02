<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Xóa các bảng hoàn toàn không sử dụng trong codebase.
     */
    public function up(): void
    {
        // 1. Xóa bảng category_attributes (liên kết categories và attributes) - không có Model và không sử dụng trong code
        Schema::dropIfExists('category_attributes');

        // 2. Xóa bảng email_verification_tokens - tính năng OTP đã dùng bảng otp_mail, bảng này không sử dụng
        Schema::dropIfExists('email_verification_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('category_attributes')) {
            Schema::create('category_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_filterable')->default(true);
                $table->integer('sort_order')->default(0);
                $table->unique(['category_id', 'attribute_id']);
            });
        }

        if (!Schema::hasTable('email_verification_tokens')) {
            Schema::create('email_verification_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('new_email', 191)->nullable();
                $table->char('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }
};
