<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Xóa các bảng attributes, attribute_options, product_attribute_values và suppliers
     * do hệ thống không sử dụng (FE không dùng, Controller chỉ trả về mảng rỗng []).
     */
    public function up(): void
    {
        // 1. Xóa khóa ngoại supplier_id trong game_accounts trước khi xóa bảng suppliers
        if (Schema::hasTable('game_accounts')) {
            Schema::table('game_accounts', function (Blueprint $table) {
                // Kiểm tra và drop foreign key supplier_id
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }

        // 2. Xóa bảng liên kết thuộc tính sản phẩm nếu còn tồn tại
        Schema::dropIfExists('product_attribute_values');

        // 3. Xóa bảng tùy chọn thuộc tính
        Schema::dropIfExists('attribute_options');

        // 4. Xóa bảng thuộc tính
        Schema::dropIfExists('attributes');

        // 5. Xóa bảng nhà cung cấp
        Schema::dropIfExists('suppliers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('contact_person', 100)->nullable();
                $table->string('email', 191)->nullable();
                $table->string('phone', 20)->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attributes')) {
            Schema::create('attributes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name', 120);
                $table->enum('data_type', ['TEXT', 'NUMBER', 'BOOLEAN', 'SELECT', 'MULTI_SELECT']);
                $table->string('unit', 20)->nullable();
                $table->boolean('is_filterable')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attribute_options')) {
            Schema::create('attribute_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->string('value', 100);
                $table->string('label', 150);
                $table->integer('sort_order')->default(0);
            });
        }

        if (!Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->text('value_text')->nullable();
                $table->decimal('value_number', 12, 2)->nullable();
                $table->boolean('value_boolean')->nullable();
                $table->foreignId('option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
                $table->unique(['product_id', 'attribute_id']);
            });
        }

        if (Schema::hasTable('game_accounts') && !Schema::hasColumn('game_accounts', 'supplier_id')) {
            Schema::table('game_accounts', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('variant_id')->constrained('suppliers')->nullOnDelete();
            });
        }
    }
};
