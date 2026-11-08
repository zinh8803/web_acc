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
        // 1. Xóa bảng hinh_anh_san_pham
        if (Schema::hasTable('hinh_anh_san_pham')) {
            try {
                Schema::table('hinh_anh_san_pham', function (Blueprint $table) {
                    $table->dropForeign('product_images_product_id_foreign');
                });
            } catch (\Throwable $e) {}

            Schema::dropIfExists('hinh_anh_san_pham');
        }

        // 2. Xóa khóa ngoại và cột nguoi_tao_id ở các bảng
        if (Schema::hasTable('san_pham') && Schema::hasColumn('san_pham', 'nguoi_tao_id')) {
            try {
                Schema::table('san_pham', function (Blueprint $table) {
                    $table->dropForeign('products_created_by_foreign');
                });
            } catch (\Throwable $e) {}
            Schema::table('san_pham', function (Blueprint $table) {
                $table->dropColumn('nguoi_tao_id');
            });
        }

        if (Schema::hasTable('tai_khoan_game') && Schema::hasColumn('tai_khoan_game', 'nguoi_tao_id')) {
            try {
                Schema::table('tai_khoan_game', function (Blueprint $table) {
                    $table->dropForeign('game_accounts_created_by_foreign');
                });
            } catch (\Throwable $e) {}
            Schema::table('tai_khoan_game', function (Blueprint $table) {
                $table->dropColumn('nguoi_tao_id');
            });
        }

        if (Schema::hasTable('ma_giam_gia') && Schema::hasColumn('ma_giam_gia', 'nguoi_tao_id')) {
            try {
                Schema::table('ma_giam_gia', function (Blueprint $table) {
                    $table->dropForeign('coupons_created_by_foreign');
                });
            } catch (\Throwable $e) {}
            Schema::table('ma_giam_gia', function (Blueprint $table) {
                $table->dropColumn('nguoi_tao_id');
            });
        }

        if (Schema::hasTable('chinh_sach_bao_hanh') && Schema::hasColumn('chinh_sach_bao_hanh', 'nguoi_tao_id')) {
            try {
                Schema::table('chinh_sach_bao_hanh', function (Blueprint $table) {
                    $table->dropForeign('warranty_policies_created_by_foreign');
                });
            } catch (\Throwable $e) {}
            Schema::table('chinh_sach_bao_hanh', function (Blueprint $table) {
                $table->dropColumn('nguoi_tao_id');
            });
        }

        // 3. Chuẩn hóa loai_san_pham và loai_muc sang VARCHAR(50), chuyển đổi giá trị ACCOUNT -> TAI_KHOAN, SERVICE -> DICH_VU
        if (Schema::hasTable('san_pham') && Schema::hasColumn('san_pham', 'loai_san_pham')) {
            DB::statement("ALTER TABLE `san_pham` MODIFY `loai_san_pham` VARCHAR(50) NOT NULL DEFAULT 'TAI_KHOAN'");
            DB::statement("UPDATE `san_pham` SET `loai_san_pham` = 'TAI_KHOAN' WHERE `loai_san_pham` = 'ACCOUNT'");
            DB::statement("UPDATE `san_pham` SET `loai_san_pham` = 'DICH_VU' WHERE `loai_san_pham` = 'SERVICE'");
        }

        if (Schema::hasTable('chinh_sach_bao_hanh') && Schema::hasColumn('chinh_sach_bao_hanh', 'loai_san_pham')) {
            DB::statement("ALTER TABLE `chinh_sach_bao_hanh` MODIFY `loai_san_pham` VARCHAR(50) NOT NULL DEFAULT 'TAI_KHOAN'");
            DB::statement("UPDATE `chinh_sach_bao_hanh` SET `loai_san_pham` = 'TAI_KHOAN' WHERE `loai_san_pham` = 'ACCOUNT'");
            DB::statement("UPDATE `chinh_sach_bao_hanh` SET `loai_san_pham` = 'DICH_VU' WHERE `loai_san_pham` = 'SERVICE'");
        }

        if (Schema::hasTable('chi_tiet_don_hang') && Schema::hasColumn('chi_tiet_don_hang', 'loai_muc')) {
            DB::statement("ALTER TABLE `chi_tiet_don_hang` MODIFY `loai_muc` VARCHAR(50) NOT NULL DEFAULT 'TAI_KHOAN'");
            DB::statement("UPDATE `chi_tiet_don_hang` SET `loai_muc` = 'TAI_KHOAN' WHERE `loai_muc` = 'ACCOUNT'");
            DB::statement("UPDATE `chi_tiet_don_hang` SET `loai_muc` = 'DICH_VU' WHERE `loai_muc` = 'SERVICE'");
        }

        if (Schema::hasTable('ma_giam_gia') && Schema::hasColumn('ma_giam_gia', 'ap_dung_loai_san_pham')) {
            DB::statement("ALTER TABLE `ma_giam_gia` MODIFY `ap_dung_loai_san_pham` VARCHAR(50) NULL");
            DB::statement("UPDATE `ma_giam_gia` SET `ap_dung_loai_san_pham` = 'TAI_KHOAN' WHERE `ap_dung_loai_san_pham` = 'ACCOUNT'");
            DB::statement("UPDATE `ma_giam_gia` SET `ap_dung_loai_san_pham` = 'DICH_VU' WHERE `ap_dung_loai_san_pham` = 'SERVICE'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
