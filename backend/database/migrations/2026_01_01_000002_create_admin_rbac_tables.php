<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== GROUP B: ADMIN, STAFF & RBAC ====================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique(); // SUPER_ADMIN, STAFF_SERVICE, STAFF_SUPPORT
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique(); // product.create, order.view, inventory.view_credentials
            $table->string('module', 60);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->string('password_hash', 255);
            $table->string('full_name', 120);
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->enum('status', ['ACTIVE', 'DISABLED'])->default('ACTIVE');
            $table->string('two_factor_secret_enc', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
