<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== GROUP C: CATALOG ====================
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->string('logo_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'HIDDEN'])->default('ACTIVE');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->enum('type', ['ACCOUNT', 'SERVICE']);
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->tinyInteger('level')->default(1);
            $table->string('path', 255)->nullable(); // /1/5/9/ for branch queries
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['ACTIVE', 'HIDDEN'])->default('ACTIVE');
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['type', 'status']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique(); // rank, hero_count
            $table->string('name', 120); // Rank, Số tướng
            $table->enum('data_type', ['TEXT', 'NUMBER', 'BOOLEAN', 'SELECT', 'MULTI_SELECT']);
            $table->string('unit', 20)->nullable(); // tướng, skin
            $table->boolean('is_filterable')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('value', 100);
            $table->string('label', 150);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unique(['category_id', 'attribute_id']);
        });

        Schema::create('warranty_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->enum('product_type', ['ACCOUNT', 'SERVICE']);
            $table->integer('duration_days')->nullable(); // null if lifetime
            $table->boolean('is_lifetime')->default(false);
            $table->json('coverage')->nullable(); // ["LOSS_ACCESS","RECOVERED_BY_OLD_OWNER"]
            $table->text('exclusions')->nullable();
            $table->integer('max_claims')->default(1);
            $table->json('resolution_options')->nullable(); // ["REPLACE_ACCOUNT","REFUND"]
            $table->integer('claim_sla_hours')->default(24);
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->enum('type', ['ACCOUNT', 'SERVICE']);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 280)->unique();
            $table->string('short_description', 500)->nullable();
            $table->mediumText('description')->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->foreignId('warranty_policy_id')->nullable()->constrained('warranty_policies')->nullOnDelete();
            $table->decimal('min_price', 15, 0)->default(0); // cache for sorting/filtering
            $table->enum('status', ['DRAFT', 'ACTIVE', 'HIDDEN', 'SOLD_OUT', 'ARCHIVED'])->default('DRAFT');
            $table->boolean('is_featured')->default(false);
            $table->json('service_form_schema')->nullable();
            $table->integer('sold_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['type', 'status', 'min_price']);
            $table->fullText('name');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 60)->unique();
            $table->string('name', 150);
            $table->decimal('price', 15, 0);
            $table->decimal('compare_at_price', 15, 0)->nullable();
            $table->decimal('sale_price', 15, 0)->nullable();
            $table->timestamp('sale_start_at')->nullable();
            $table->timestamp('sale_end_at')->nullable();
            $table->decimal('cost_price', 15, 0)->nullable(); // Admin only
            $table->integer('stock_quantity')->default(0);
            $table->integer('estimated_hours')->nullable(); // for service SLA
            $table->enum('status', ['ACTIVE', 'HIDDEN'])->default('ACTIVE');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('alt_text', 200)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->restrictOnDelete();
            $table->string('value_text', 500)->nullable();
            $table->decimal('value_number', 18, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->foreignId('option_id')->nullable()->constrained('attribute_options')->nullOnDelete();

            $table->index(['product_id', 'attribute_id']);
            $table->index(['attribute_id', 'option_id']);
            $table->index(['attribute_id', 'value_number']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warranty_policies');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('games');
    }
};
