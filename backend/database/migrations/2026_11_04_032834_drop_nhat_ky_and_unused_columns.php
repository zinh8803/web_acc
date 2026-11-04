<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Xóa bảng nhat_ky_hoat_dong
        Schema::dropIfExists('nhat_ky_hoat_dong');

        // 2. Xóa các trường SEO ở các bảng
        if (Schema::hasTable('danh_muc')) {
            Schema::table('danh_muc', function (Blueprint $table) {
                if (Schema::hasColumn('danh_muc', 'tieu_de_seo')) $table->dropColumn('tieu_de_seo');
                if (Schema::hasColumn('danh_muc', 'mo_ta_seo')) $table->dropColumn('mo_ta_seo');
                // Xóa ảnh danh mục và đường dẫn cây
                if (Schema::hasColumn('danh_muc', 'anh_danh_muc')) $table->dropColumn('anh_danh_muc');
                if (Schema::hasColumn('danh_muc', 'duong_dan_cay')) $table->dropColumn('duong_dan_cay');
            });
        }

        if (Schema::hasTable('san_pham')) {
            Schema::table('san_pham', function (Blueprint $table) {
                if (Schema::hasColumn('san_pham', 'tieu_de_seo')) $table->dropColumn('tieu_de_seo');
                if (Schema::hasColumn('san_pham', 'mo_ta_seo')) $table->dropColumn('mo_ta_seo');
            });
        }

        // 3. Xóa các trường không cần thiết ở bảng nguoi_dung
        // ma_bi_mat_2fa, anh_dai_dien, ngay_sinh, so_lan_dang_nhap_sai, khoa_den_thoi_gian
        if (Schema::hasTable('nguoi_dung')) {
            Schema::table('nguoi_dung', function (Blueprint $table) {
                if (Schema::hasColumn('nguoi_dung', 'ma_bi_mat_2fa')) $table->dropColumn('ma_bi_mat_2fa');
                if (Schema::hasColumn('nguoi_dung', 'anh_dai_dien')) $table->dropColumn('anh_dai_dien');
                if (Schema::hasColumn('nguoi_dung', 'ngay_sinh')) $table->dropColumn('ngay_sinh');
                if (Schema::hasColumn('nguoi_dung', 'so_lan_dang_nhap_sai')) $table->dropColumn('so_lan_dang_nhap_sai');
                if (Schema::hasColumn('nguoi_dung', 'khoa_den_thoi_gian')) $table->dropColumn('khoa_den_thoi_gian');
            });
        }

        // 4. Cho phép đánh giá không bắt buộc chi_tiet_don_hang_id
        if (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'chi_tiet_don_hang_id')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->unsignedBigInteger('chi_tiet_don_hang_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Phục hồi bảng nhat_ky_hoat_dong
        if (!Schema::hasTable('nhat_ky_hoat_dong')) {
            Schema::create('nhat_ky_hoat_dong', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quan_tri_vien_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
                $table->string('hanh_dong', 100);
                $table->string('loai_doi_tuong', 100)->nullable();
                $table->unsignedBigInteger('doi_tuong_id')->nullable();
                $table->json('du_lieu_cu')->nullable();
                $table->json('du_lieu_moi')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('ngay_tao')->useCurrent();
            });
        }

        if (Schema::hasTable('danh_muc')) {
            Schema::table('danh_muc', function (Blueprint $table) {
                $table->string('anh_danh_muc', 500)->nullable();
                $table->string('duong_dan_cay', 255)->nullable();
                $table->string('tieu_de_seo', 255)->nullable();
                $table->text('mo_ta_seo')->nullable();
            });
        }

        if (Schema::hasTable('san_pham')) {
            Schema::table('san_pham', function (Blueprint $table) {
                $table->string('tieu_de_seo', 255)->nullable();
                $table->text('mo_ta_seo')->nullable();
            });
        }

        if (Schema::hasTable('nguoi_dung')) {
            Schema::table('nguoi_dung', function (Blueprint $table) {
                $table->string('ma_bi_mat_2fa', 255)->nullable();
                $table->string('anh_dai_dien', 500)->nullable();
                $table->date('ngay_sinh')->nullable();
                $table->tinyInteger('so_lan_dang_nhap_sai')->default(0);
                $table->timestamp('khoa_den_thoi_gian')->nullable();
            });
        }
    }
};
