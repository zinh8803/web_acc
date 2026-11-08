<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('danh_muc')) {
            // 1. Chuyển kiểu cột loai_danh_muc sang VARCHAR(50) và cập nhật dữ liệu sang tiếng Việt (TAI_KHOAN / DICH_VU)
            if (Schema::hasColumn('danh_muc', 'loai_danh_muc')) {
                DB::statement("ALTER TABLE `danh_muc` MODIFY `loai_danh_muc` VARCHAR(50) NOT NULL DEFAULT 'TAI_KHOAN'");
                DB::statement("UPDATE `danh_muc` SET `loai_danh_muc` = 'TAI_KHOAN' WHERE `loai_danh_muc` = 'ACCOUNT'");
                DB::statement("UPDATE `danh_muc` SET `loai_danh_muc` = 'DICH_VU' WHERE `loai_danh_muc` = 'SERVICE'");
            }

            // 2. Xóa khóa ngoại và cột danh_muc_cha_id
            if (Schema::hasColumn('danh_muc', 'danh_muc_cha_id')) {
                try {
                    Schema::table('danh_muc', function (Blueprint $table) {
                        $table->dropForeign('categories_parent_id_foreign');
                    });
                } catch (\Throwable $e) {
                    // Bỏ qua nếu constraint không tồn tại hoặc đã bị xóa
                }

                Schema::table('danh_muc', function (Blueprint $table) {
                    $table->dropColumn('danh_muc_cha_id');
                });
            }

            // 3. Xóa cột cap_do và duong_dan_cay nếu còn (do không còn phân cấp cha-con)
            Schema::table('danh_muc', function (Blueprint $table) {
                if (Schema::hasColumn('danh_muc', 'cap_do')) {
                    $table->dropColumn('cap_do');
                }
                if (Schema::hasColumn('danh_muc', 'duong_dan_cay')) {
                    $table->dropColumn('duong_dan_cay');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('danh_muc')) {
            Schema::table('danh_muc', function (Blueprint $table) {
                if (!Schema::hasColumn('danh_muc', 'danh_muc_cha_id')) {
                    $table->unsignedBigInteger('danh_muc_cha_id')->nullable()->after('id');
                    $table->foreign('danh_muc_cha_id', 'categories_parent_id_foreign')->references('id')->on('danh_muc')->nullOnDelete();
                }
                if (!Schema::hasColumn('danh_muc', 'cap_do')) {
                    $table->tinyInteger('cap_do')->default(1)->after('mo_ta');
                }
            });

            if (Schema::hasColumn('danh_muc', 'loai_danh_muc')) {
                DB::statement("UPDATE `danh_muc` SET `loai_danh_muc` = 'ACCOUNT' WHERE `loai_danh_muc` = 'TAI_KHOAN'");
                DB::statement("UPDATE `danh_muc` SET `loai_danh_muc` = 'SERVICE' WHERE `loai_danh_muc` = 'DICH_VU'");
                DB::statement("ALTER TABLE `danh_muc` MODIFY `loai_danh_muc` ENUM('ACCOUNT', 'SERVICE') NOT NULL DEFAULT 'ACCOUNT'");
            }
        }
    }
};

