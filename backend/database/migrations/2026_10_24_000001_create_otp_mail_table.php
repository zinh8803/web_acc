<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_mail', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->string('otp_hash');
            $table->string('full_name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('password_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_mail');
    }
};
