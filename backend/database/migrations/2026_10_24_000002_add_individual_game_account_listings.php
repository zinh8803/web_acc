<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('game_accounts', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('extra_info_enc');
            }
            if (!Schema::hasColumn('game_accounts', 'public_details')) {
                $table->json('public_details')->nullable()->after('image_url');
            }
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'game_account_id')) {
                $table->foreignId('game_account_id')->nullable()->after('variant_id')->constrained('game_accounts')->restrictOnDelete();
                $table->unique(['cart_id', 'game_account_id']);
            }
            // The old unique index also supports cart_id's FK, so replace it only after the new index exists.
            $table->dropUnique('cart_items_cart_id_variant_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'game_account_id']);
            $table->dropConstrainedForeignId('game_account_id');
            $table->unique(['cart_id', 'variant_id']);
        });

        Schema::table('game_accounts', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'public_details']);
        });
    }
};
