<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admins and customers share one users table. Role values are enforced in code.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 60)->default('CUSTOMER')->after('full_name');
            $table->string('two_factor_secret_enc', 255)->nullable()->after('role');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'two_factor_secret_enc']);
        });
    }
};
