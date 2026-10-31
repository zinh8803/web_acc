<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop foreign key and column game_id from categories
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'game_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign(['game_id']);
                $table->dropColumn('game_id');
            });
        }

        // 2. Drop foreign key and column game_id from products
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'game_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['game_id']);
                $table->dropColumn('game_id');
            });
        }

        // 3. Drop foreign key and column game_id from service_requests
        if (Schema::hasTable('service_requests') && Schema::hasColumn('service_requests', 'game_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropForeign(['game_id']);
                $table->dropColumn('game_id');
            });
        }

        // 4. Drop games table
        Schema::dropIfExists('games');
    }

    public function down(): void
    {
        if (!Schema::hasTable('games')) {
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
        }

        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'game_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'game_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            });
        }

        if (Schema::hasTable('service_requests') && !Schema::hasColumn('service_requests', 'game_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            });
        }
    }
};

