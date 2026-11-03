<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Việt hóa toàn bộ tên bảng và tên cột, loại bỏ Soft Delete, loại bỏ bảng không sử dụng.
     */
    public function up(): void
    {
        // 1. Xóa bảng user_sessions không còn sử dụng (thay thế bởi personal_access_tokens của Sanctum)
        Schema::dropIfExists('user_sessions');

        // 2. Loại bỏ cột Soft Delete (deleted_at) trước khi đổi tên bảng/cột
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'deleted_at')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'deleted_at')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // 3. Đổi tên bảng sang Tiếng Việt (snake_case)
        $renameTables = [
            'users' => 'nguoi_dung',
            'categories' => 'danh_muc',
            'products' => 'san_pham',
            'product_variants' => 'bien_the_san_pham',
            'product_images' => 'hinh_anh_san_pham',
            'game_accounts' => 'tai_khoan_game',
            'carts' => 'gio_hang',
            'cart_items' => 'chi_tiet_gio_hang',
            'coupons' => 'ma_giam_gia',
            'coupon_usages' => 'lich_su_dung_ma_giam_gia',
            'orders' => 'don_hang',
            'order_items' => 'chi_tiet_don_hang',
            'service_requests' => 'yeu_cau_dich_vu',
            'warranty_policies' => 'chinh_sach_bao_hanh',
            'warranty_claims' => 'yeu_cau_bao_hanh',
            'reviews' => 'danh_gia',
            'audit_logs' => 'nhat_ky_hoat_dong',
            'settings' => 'cai_dat',
            'banners' => 'banner_quang_cao',
            'otp_mail' => 'ma_xac_thuc_email',
        ];

        foreach ($renameTables as $oldTable => $newTable) {
            if (Schema::hasTable($oldTable) && !Schema::hasTable($newTable)) {
                Schema::rename($oldTable, $newTable);
            }
        }

        // 4. Đổi tên cột trong từng bảng sang Tiếng Việt
        // 4.1. Bảng nguoi_dung
        if (Schema::hasTable('nguoi_dung')) {
            Schema::table('nguoi_dung', function (Blueprint $table) {
                if (Schema::hasColumn('nguoi_dung', 'phone')) $table->renameColumn('phone', 'so_dien_thoai');
                if (Schema::hasColumn('nguoi_dung', 'password_hash')) $table->renameColumn('password_hash', 'mat_khau_ma_hoa');
                if (Schema::hasColumn('nguoi_dung', 'full_name')) $table->renameColumn('full_name', 'ho_ten');
                if (Schema::hasColumn('nguoi_dung', 'role')) $table->renameColumn('role', 'vai_tro');
                if (Schema::hasColumn('nguoi_dung', 'two_factor_secret_enc')) $table->renameColumn('two_factor_secret_enc', 'ma_bi_mat_2fa');
                if (Schema::hasColumn('nguoi_dung', 'avatar_url')) $table->renameColumn('avatar_url', 'anh_dai_dien');
                if (Schema::hasColumn('nguoi_dung', 'birth_date')) $table->renameColumn('birth_date', 'ngay_sinh');
                if (Schema::hasColumn('nguoi_dung', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('nguoi_dung', 'email_verified_at')) $table->renameColumn('email_verified_at', 'thoi_gian_xac_thuc_email');
                if (Schema::hasColumn('nguoi_dung', 'failed_login_count')) $table->renameColumn('failed_login_count', 'so_lan_dang_nhap_sai');
                if (Schema::hasColumn('nguoi_dung', 'locked_until')) $table->renameColumn('locked_until', 'khoa_den_thoi_gian');
                if (Schema::hasColumn('nguoi_dung', 'last_login_at')) $table->renameColumn('last_login_at', 'dang_nhap_cuoi_luc');
                if (Schema::hasColumn('nguoi_dung', 'last_login_ip')) $table->renameColumn('last_login_ip', 'ip_dang_nhap_cuoi');
                if (Schema::hasColumn('nguoi_dung', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('nguoi_dung', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.2. Bảng danh_muc
        if (Schema::hasTable('danh_muc')) {
            Schema::table('danh_muc', function (Blueprint $table) {
                if (Schema::hasColumn('danh_muc', 'parent_id')) $table->renameColumn('parent_id', 'danh_muc_cha_id');
                if (Schema::hasColumn('danh_muc', 'type')) $table->renameColumn('type', 'loai_danh_muc');
                if (Schema::hasColumn('danh_muc', 'name')) $table->renameColumn('name', 'ten_danh_muc');
                if (Schema::hasColumn('danh_muc', 'slug')) $table->renameColumn('slug', 'duong_dan_tinh');
                if (Schema::hasColumn('danh_muc', 'description')) $table->renameColumn('description', 'mo_ta');
                if (Schema::hasColumn('danh_muc', 'image_url')) $table->renameColumn('image_url', 'anh_danh_muc');
                if (Schema::hasColumn('danh_muc', 'level')) $table->renameColumn('level', 'cap_do');
                if (Schema::hasColumn('danh_muc', 'path')) $table->renameColumn('path', 'duong_dan_cay');
                if (Schema::hasColumn('danh_muc', 'sort_order')) $table->renameColumn('sort_order', 'thu_tu_hien_thi');
                if (Schema::hasColumn('danh_muc', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('danh_muc', 'seo_title')) $table->renameColumn('seo_title', 'tieu_de_seo');
                if (Schema::hasColumn('danh_muc', 'seo_description')) $table->renameColumn('seo_description', 'mo_ta_seo');
                if (Schema::hasColumn('danh_muc', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('danh_muc', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.3. Bảng chinh_sach_bao_hanh
        if (Schema::hasTable('chinh_sach_bao_hanh')) {
            Schema::table('chinh_sach_bao_hanh', function (Blueprint $table) {
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'code')) $table->renameColumn('code', 'ma_chinh_sach');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'name')) $table->renameColumn('name', 'ten_chinh_sach');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'product_type')) $table->renameColumn('product_type', 'loai_san_pham');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'duration_days')) $table->renameColumn('duration_days', 'so_ngay_bao_hanh');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'is_lifetime')) $table->renameColumn('is_lifetime', 'bao_hanh_tron_doi');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'coverage')) $table->renameColumn('coverage', 'pham_vi_bao_hanh');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'exclusions')) $table->renameColumn('exclusions', 'dieu_khoan_loai_tru');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'max_claims')) $table->renameColumn('max_claims', 'so_lan_yeu_cau_toi_da');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'resolution_options')) $table->renameColumn('resolution_options', 'phuong_an_giai_quyet');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'claim_sla_hours')) $table->renameColumn('claim_sla_hours', 'thoi_gian_xu_ly_gio');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'description')) $table->renameColumn('description', 'mo_ta');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'created_by')) $table->renameColumn('created_by', 'nguoi_tao_id');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('chinh_sach_bao_hanh', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.4. Bảng san_pham
        if (Schema::hasTable('san_pham')) {
            Schema::table('san_pham', function (Blueprint $table) {
                if (Schema::hasColumn('san_pham', 'sku')) $table->renameColumn('sku', 'ma_san_pham');
                if (Schema::hasColumn('san_pham', 'type')) $table->renameColumn('type', 'loai_san_pham');
                if (Schema::hasColumn('san_pham', 'category_id')) $table->renameColumn('category_id', 'danh_muc_id');
                if (Schema::hasColumn('san_pham', 'name')) $table->renameColumn('name', 'ten_san_pham');
                if (Schema::hasColumn('san_pham', 'slug')) $table->renameColumn('slug', 'duong_dan_tinh');
                if (Schema::hasColumn('san_pham', 'short_description')) $table->renameColumn('short_description', 'mo_ta_ngan');
                if (Schema::hasColumn('san_pham', 'description')) $table->renameColumn('description', 'noi_dung_chi_tiet');
                if (Schema::hasColumn('san_pham', 'thumbnail_url')) $table->renameColumn('thumbnail_url', 'anh_dai_dien');
                if (Schema::hasColumn('san_pham', 'warranty_policy_id')) $table->renameColumn('warranty_policy_id', 'chinh_sach_bao_hanh_id');
                if (Schema::hasColumn('san_pham', 'min_price')) $table->renameColumn('min_price', 'gia_thap_nhat');
                if (Schema::hasColumn('san_pham', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('san_pham', 'is_featured')) $table->renameColumn('is_featured', 'noi_bat');
                if (Schema::hasColumn('san_pham', 'service_form_schema')) $table->renameColumn('service_form_schema', 'cau_hinh_bieu_mau_dich_vu');
                if (Schema::hasColumn('san_pham', 'sold_count')) $table->renameColumn('sold_count', 'so_luong_da_ban');
                if (Schema::hasColumn('san_pham', 'view_count')) $table->renameColumn('view_count', 'luot_xem');
                if (Schema::hasColumn('san_pham', 'rating_avg')) $table->renameColumn('rating_avg', 'diem_danh_gia_tb');
                if (Schema::hasColumn('san_pham', 'rating_count')) $table->renameColumn('rating_count', 'so_luot_danh_gia');
                if (Schema::hasColumn('san_pham', 'seo_title')) $table->renameColumn('seo_title', 'tieu_de_seo');
                if (Schema::hasColumn('san_pham', 'seo_description')) $table->renameColumn('seo_description', 'mo_ta_seo');
                if (Schema::hasColumn('san_pham', 'created_by')) $table->renameColumn('created_by', 'nguoi_tao_id');
                if (Schema::hasColumn('san_pham', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('san_pham', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.5. Bảng bien_the_san_pham
        if (Schema::hasTable('bien_the_san_pham')) {
            Schema::table('bien_the_san_pham', function (Blueprint $table) {
                if (Schema::hasColumn('bien_the_san_pham', 'product_id')) $table->renameColumn('product_id', 'san_pham_id');
                if (Schema::hasColumn('bien_the_san_pham', 'sku')) $table->renameColumn('sku', 'ma_bien_the');
                if (Schema::hasColumn('bien_the_san_pham', 'name')) $table->renameColumn('name', 'ten_bien_the');
                if (Schema::hasColumn('bien_the_san_pham', 'price')) $table->renameColumn('price', 'gia_goc');
                if (Schema::hasColumn('bien_the_san_pham', 'compare_at_price')) $table->renameColumn('compare_at_price', 'gia_so_sanh');
                if (Schema::hasColumn('bien_the_san_pham', 'sale_price')) $table->renameColumn('sale_price', 'gia_khuyen_mai');
                if (Schema::hasColumn('bien_the_san_pham', 'sale_start_at')) $table->renameColumn('sale_start_at', 'ngay_bat_dau_km');
                if (Schema::hasColumn('bien_the_san_pham', 'sale_end_at')) $table->renameColumn('sale_end_at', 'ngay_ket_thuc_km');
                if (Schema::hasColumn('bien_the_san_pham', 'cost_price')) $table->renameColumn('cost_price', 'gia_von');
                if (Schema::hasColumn('bien_the_san_pham', 'stock_quantity')) $table->renameColumn('stock_quantity', 'so_luong_ton_kho');
                if (Schema::hasColumn('bien_the_san_pham', 'estimated_hours')) $table->renameColumn('estimated_hours', 'thoi_gian_uoc_tinh_gio');
                if (Schema::hasColumn('bien_the_san_pham', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('bien_the_san_pham', 'sort_order')) $table->renameColumn('sort_order', 'thu_tu_hien_thi');
                if (Schema::hasColumn('bien_the_san_pham', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('bien_the_san_pham', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.6. Bảng hinh_anh_san_pham
        if (Schema::hasTable('hinh_anh_san_pham')) {
            Schema::table('hinh_anh_san_pham', function (Blueprint $table) {
                if (Schema::hasColumn('hinh_anh_san_pham', 'product_id')) $table->renameColumn('product_id', 'san_pham_id');
                if (Schema::hasColumn('hinh_anh_san_pham', 'variant_id')) $table->renameColumn('variant_id', 'bien_the_id');
                if (Schema::hasColumn('hinh_anh_san_pham', 'url')) $table->renameColumn('url', 'duong_dan_anh');
                if (Schema::hasColumn('hinh_anh_san_pham', 'thumbnail_url')) $table->renameColumn('thumbnail_url', 'duong_dan_anh_nho');
                if (Schema::hasColumn('hinh_anh_san_pham', 'alt_text')) $table->renameColumn('alt_text', 'chu_thich_anh');
                if (Schema::hasColumn('hinh_anh_san_pham', 'sort_order')) $table->renameColumn('sort_order', 'thu_tu_hien_thi');
                if (Schema::hasColumn('hinh_anh_san_pham', 'is_primary')) $table->renameColumn('is_primary', 'anh_chinh');
                if (Schema::hasColumn('hinh_anh_san_pham', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
            });
        }

        // 4.7. Bảng tai_khoan_game
        if (Schema::hasTable('tai_khoan_game')) {
            Schema::table('tai_khoan_game', function (Blueprint $table) {
                if (Schema::hasColumn('tai_khoan_game', 'product_id')) $table->renameColumn('product_id', 'san_pham_id');
                if (Schema::hasColumn('tai_khoan_game', 'variant_id')) $table->renameColumn('variant_id', 'bien_the_id');
                if (Schema::hasColumn('tai_khoan_game', 'account_username_enc')) $table->renameColumn('account_username_enc', 'ten_dang_nhap_ma_hoa');
                if (Schema::hasColumn('tai_khoan_game', 'account_password_enc')) $table->renameColumn('account_password_enc', 'mat_khau_ma_hoa');
                if (Schema::hasColumn('tai_khoan_game', 'extra_info_enc')) $table->renameColumn('extra_info_enc', 'thong_tin_phu_ma_hoa');
                if (Schema::hasColumn('tai_khoan_game', 'original_owner_info_enc')) $table->renameColumn('original_owner_info_enc', 'thong_tin_chu_cu_ma_hoa');
                if (Schema::hasColumn('tai_khoan_game', 'image_url')) $table->renameColumn('image_url', 'anh_minh_hoa');
                if (Schema::hasColumn('tai_khoan_game', 'public_details')) $table->renameColumn('public_details', 'thong_tin_cong_khai');
                if (Schema::hasColumn('tai_khoan_game', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('tai_khoan_game', 'reserved_by_order_id')) $table->renameColumn('reserved_by_order_id', 'giu_cho_boi_don_hang_id');
                if (Schema::hasColumn('tai_khoan_game', 'reserved_until')) $table->renameColumn('reserved_until', 'giu_cho_den_khi');
                if (Schema::hasColumn('tai_khoan_game', 'sold_order_item_id')) $table->renameColumn('sold_order_item_id', 'chi_tiet_don_hang_da_ban_id');
                if (Schema::hasColumn('tai_khoan_game', 'sold_at')) $table->renameColumn('sold_at', 'thoi_gian_ban');
                if (Schema::hasColumn('tai_khoan_game', 'cost_price')) $table->renameColumn('cost_price', 'gia_nhap');
                if (Schema::hasColumn('tai_khoan_game', 'verified_at')) $table->renameColumn('verified_at', 'xac_minh_luc');
                if (Schema::hasColumn('tai_khoan_game', 'verified_by')) $table->renameColumn('verified_by', 'nguoi_xac_minh_id');
                if (Schema::hasColumn('tai_khoan_game', 'note')) $table->renameColumn('note', 'ghi_chu_noi_bo');
                if (Schema::hasColumn('tai_khoan_game', 'created_by')) $table->renameColumn('created_by', 'nguoi_tao_id');
                if (Schema::hasColumn('tai_khoan_game', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('tai_khoan_game', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.8. Bảng gio_hang
        if (Schema::hasTable('gio_hang')) {
            Schema::table('gio_hang', function (Blueprint $table) {
                if (Schema::hasColumn('gio_hang', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('gio_hang', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('gio_hang', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.9. Bảng chi_tiet_gio_hang
        if (Schema::hasTable('chi_tiet_gio_hang')) {
            Schema::table('chi_tiet_gio_hang', function (Blueprint $table) {
                if (Schema::hasColumn('chi_tiet_gio_hang', 'cart_id')) $table->renameColumn('cart_id', 'gio_hang_id');
                if (Schema::hasColumn('chi_tiet_gio_hang', 'variant_id')) $table->renameColumn('variant_id', 'bien_the_id');
                if (Schema::hasColumn('chi_tiet_gio_hang', 'game_account_id')) $table->renameColumn('game_account_id', 'tai_khoan_game_id');
                if (Schema::hasColumn('chi_tiet_gio_hang', 'quantity')) $table->renameColumn('quantity', 'so_luong');
                if (Schema::hasColumn('chi_tiet_gio_hang', 'added_at')) $table->renameColumn('added_at', 'ngay_them');
            });
        }

        // 4.10. Bảng ma_giam_gia
        if (Schema::hasTable('ma_giam_gia')) {
            Schema::table('ma_giam_gia', function (Blueprint $table) {
                if (Schema::hasColumn('ma_giam_gia', 'code')) $table->renameColumn('code', 'ma_khuyen_mai');
                if (Schema::hasColumn('ma_giam_gia', 'name')) $table->renameColumn('name', 'ten_ma_giam_gia');
                if (Schema::hasColumn('ma_giam_gia', 'description')) $table->renameColumn('description', 'mo_ta');
                if (Schema::hasColumn('ma_giam_gia', 'discount_type')) $table->renameColumn('discount_type', 'loai_giam_gia');
                if (Schema::hasColumn('ma_giam_gia', 'discount_value')) $table->renameColumn('discount_value', 'gia_tri_giam');
                if (Schema::hasColumn('ma_giam_gia', 'max_discount_amount')) $table->renameColumn('max_discount_amount', 'giam_toi_da');
                if (Schema::hasColumn('ma_giam_gia', 'min_order_amount')) $table->renameColumn('min_order_amount', 'don_hang_toi_thieu');
                if (Schema::hasColumn('ma_giam_gia', 'applies_to')) $table->renameColumn('applies_to', 'ap_dung_cho');
                if (Schema::hasColumn('ma_giam_gia', 'applies_product_type')) $table->renameColumn('applies_product_type', 'ap_dung_loai_san_pham');
                if (Schema::hasColumn('ma_giam_gia', 'usage_limit')) $table->renameColumn('usage_limit', 'gioi_han_su_dung');
                if (Schema::hasColumn('ma_giam_gia', 'usage_per_user')) $table->renameColumn('usage_per_user', 'gioi_han_moi_nguoi_dung');
                if (Schema::hasColumn('ma_giam_gia', 'used_count')) $table->renameColumn('used_count', 'so_lan_da_dung');
                if (Schema::hasColumn('ma_giam_gia', 'starts_at')) $table->renameColumn('starts_at', 'ngay_bat_dau');
                if (Schema::hasColumn('ma_giam_gia', 'ends_at')) $table->renameColumn('ends_at', 'ngay_ket_thuc');
                if (Schema::hasColumn('ma_giam_gia', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('ma_giam_gia', 'created_by')) $table->renameColumn('created_by', 'nguoi_tao_id');
                if (Schema::hasColumn('ma_giam_gia', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('ma_giam_gia', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.11. Bảng lich_su_dung_ma_giam_gia
        if (Schema::hasTable('lich_su_dung_ma_giam_gia')) {
            Schema::table('lich_su_dung_ma_giam_gia', function (Blueprint $table) {
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'coupon_id')) $table->renameColumn('coupon_id', 'ma_giam_gia_id');
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'order_id')) $table->renameColumn('order_id', 'don_hang_id');
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'discount_amount')) $table->renameColumn('discount_amount', 'so_tien_giam');
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('lich_su_dung_ma_giam_gia', 'used_at')) $table->renameColumn('used_at', 'thoi_gian_dung');
            });
        }

        // 4.12. Bảng don_hang
        if (Schema::hasTable('don_hang')) {
            Schema::table('don_hang', function (Blueprint $table) {
                if (Schema::hasColumn('don_hang', 'order_code')) $table->renameColumn('order_code', 'ma_don_hang');
                if (Schema::hasColumn('don_hang', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('don_hang', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('don_hang', 'subtotal')) $table->renameColumn('subtotal', 'tam_tinh');
                if (Schema::hasColumn('don_hang', 'discount_total')) $table->renameColumn('discount_total', 'tong_giam_gia');
                if (Schema::hasColumn('don_hang', 'total')) $table->renameColumn('total', 'tong_tien');
                if (Schema::hasColumn('don_hang', 'coupon_id')) $table->renameColumn('coupon_id', 'ma_giam_gia_id');
                if (Schema::hasColumn('don_hang', 'coupon_code')) $table->renameColumn('coupon_code', 'ma_giam_gia_luu_lai');
                if (Schema::hasColumn('don_hang', 'payment_method_code')) $table->renameColumn('payment_method_code', 'phuong_thuc_thanh_toan');
                if (Schema::hasColumn('don_hang', 'payment_reference')) $table->renameColumn('payment_reference', 'ma_giao_dich_thanh_toan');
                if (Schema::hasColumn('don_hang', 'customer_note')) $table->renameColumn('customer_note', 'ghi_chu_khach_hang');
                if (Schema::hasColumn('don_hang', 'expires_at')) $table->renameColumn('expires_at', 'het_han_thanh_toan_luc');
                if (Schema::hasColumn('don_hang', 'paid_at')) $table->renameColumn('paid_at', 'thanh_toan_luc');
                if (Schema::hasColumn('don_hang', 'completed_at')) $table->renameColumn('completed_at', 'hoan_thanh_luc');
                if (Schema::hasColumn('don_hang', 'cancelled_at')) $table->renameColumn('cancelled_at', 'huy_luc');
                if (Schema::hasColumn('don_hang', 'cancel_reason')) $table->renameColumn('cancel_reason', 'ly_do_huy');
                if (Schema::hasColumn('don_hang', 'ip_address')) $table->renameColumn('ip_address', 'dia_chi_ip');
                if (Schema::hasColumn('don_hang', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('don_hang', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.13. Bảng chi_tiet_don_hang
        if (Schema::hasTable('chi_tiet_don_hang')) {
            Schema::table('chi_tiet_don_hang', function (Blueprint $table) {
                if (Schema::hasColumn('chi_tiet_don_hang', 'order_id')) $table->renameColumn('order_id', 'don_hang_id');
                if (Schema::hasColumn('chi_tiet_don_hang', 'item_type')) $table->renameColumn('item_type', 'loai_muc');
                if (Schema::hasColumn('chi_tiet_don_hang', 'product_id')) $table->renameColumn('product_id', 'san_pham_id');
                if (Schema::hasColumn('chi_tiet_don_hang', 'variant_id')) $table->renameColumn('variant_id', 'bien_the_id');
                if (Schema::hasColumn('chi_tiet_don_hang', 'product_name')) $table->renameColumn('product_name', 'ten_san_pham_luu_lai');
                if (Schema::hasColumn('chi_tiet_don_hang', 'variant_name')) $table->renameColumn('variant_name', 'ten_bien_the_luu_lai');
                if (Schema::hasColumn('chi_tiet_don_hang', 'thumbnail_url')) $table->renameColumn('thumbnail_url', 'anh_san_pham_luu_lai');
                if (Schema::hasColumn('chi_tiet_don_hang', 'unit_price')) $table->renameColumn('unit_price', 'don_gia');
                if (Schema::hasColumn('chi_tiet_don_hang', 'quantity')) $table->renameColumn('quantity', 'so_luong');
                if (Schema::hasColumn('chi_tiet_don_hang', 'line_total')) $table->renameColumn('line_total', 'thanh_tien');
                if (Schema::hasColumn('chi_tiet_don_hang', 'game_account_id')) $table->renameColumn('game_account_id', 'tai_khoan_game_id');
                if (Schema::hasColumn('chi_tiet_don_hang', 'warranty_policy_id')) $table->renameColumn('warranty_policy_id', 'chinh_sach_bao_hanh_id');
                if (Schema::hasColumn('chi_tiet_don_hang', 'warranty_snapshot')) $table->renameColumn('warranty_snapshot', 'thong_tin_bao_hanh_luu_lai');
                if (Schema::hasColumn('chi_tiet_don_hang', 'warranty_expires_at')) $table->renameColumn('warranty_expires_at', 'het_han_bao_hanh_luc');
                if (Schema::hasColumn('chi_tiet_don_hang', 'fulfillment_status')) $table->renameColumn('fulfillment_status', 'trang_thai_giao_hang');
                if (Schema::hasColumn('chi_tiet_don_hang', 'delivered_at')) $table->renameColumn('delivered_at', 'giao_hang_luc');
                if (Schema::hasColumn('chi_tiet_don_hang', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
            });
        }

        // 4.14. Bảng yeu_cau_dich_vu
        if (Schema::hasTable('yeu_cau_dich_vu')) {
            Schema::table('yeu_cau_dich_vu', function (Blueprint $table) {
                if (Schema::hasColumn('yeu_cau_dich_vu', 'request_code')) $table->renameColumn('request_code', 'ma_yeu_cau');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'order_item_id')) $table->renameColumn('order_item_id', 'chi_tiet_don_hang_id');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'form_data')) $table->renameColumn('form_data', 'du_lieu_bieu_mau');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'game_login_enc')) $table->renameColumn('game_login_enc', 'thong_tin_dang_nhap_ma_hoa');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'customer_note')) $table->renameColumn('customer_note', 'ghi_chu_khach_hang');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'assigned_to')) $table->renameColumn('assigned_to', 'nhan_vien_phu_trach_id');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'priority')) $table->renameColumn('priority', 'do_uu_tien');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'due_at')) $table->renameColumn('due_at', 'han_hoan_thanh');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'started_at')) $table->renameColumn('started_at', 'bat_dau_luc');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'completed_at')) $table->renameColumn('completed_at', 'hoan_thanh_luc');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'auto_confirm_at')) $table->renameColumn('auto_confirm_at', 'tu_dong_xac_nhan_luc');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'confirmed_at')) $table->renameColumn('confirmed_at', 'khach_xac_nhan_luc');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'cancel_reason')) $table->renameColumn('cancel_reason', 'ly_do_huy');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('yeu_cau_dich_vu', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.15. Bảng yeu_cau_bao_hanh
        if (Schema::hasTable('yeu_cau_bao_hanh')) {
            Schema::table('yeu_cau_bao_hanh', function (Blueprint $table) {
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'claim_code')) $table->renameColumn('claim_code', 'ma_yeu_cau_bao_hanh');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'order_item_id')) $table->renameColumn('order_item_id', 'chi_tiet_don_hang_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'game_account_id')) $table->renameColumn('game_account_id', 'tai_khoan_game_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'policy_id')) $table->renameColumn('policy_id', 'chinh_sach_bao_hanh_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'claim_type')) $table->renameColumn('claim_type', 'loai_yeu_cau');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'description')) $table->renameColumn('description', 'mo_ta_su_co');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'resolution_type')) $table->renameColumn('resolution_type', 'hinh_thuc_giai_quyet');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'resolution_note')) $table->renameColumn('resolution_note', 'ghi_chu_giai_quyet');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'reject_reason')) $table->renameColumn('reject_reason', 'ly_do_tu_choi');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'replacement_order_item_id')) $table->renameColumn('replacement_order_item_id', 'chi_tiet_don_hang_thay_the_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'assigned_to')) $table->renameColumn('assigned_to', 'nhan_vien_phu_trach_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'reviewed_by')) $table->renameColumn('reviewed_by', 'nguoi_duyet_id');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'due_at')) $table->renameColumn('due_at', 'han_xu_ly');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'submitted_at')) $table->renameColumn('submitted_at', 'ngay_gui_yeu_cau');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'resolved_at')) $table->renameColumn('resolved_at', 'ngay_giai_quyet');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('yeu_cau_bao_hanh', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.16. Bảng danh_gia
        if (Schema::hasTable('danh_gia')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                if (Schema::hasColumn('danh_gia', 'user_id')) $table->renameColumn('user_id', 'nguoi_dung_id');
                if (Schema::hasColumn('danh_gia', 'product_id')) $table->renameColumn('product_id', 'san_pham_id');
                if (Schema::hasColumn('danh_gia', 'order_item_id')) $table->renameColumn('order_item_id', 'chi_tiet_don_hang_id');
                if (Schema::hasColumn('danh_gia', 'rating')) $table->renameColumn('rating', 'so_sao');
                if (Schema::hasColumn('danh_gia', 'comment')) $table->renameColumn('comment', 'noi_dung_danh_gia');
                if (Schema::hasColumn('danh_gia', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('danh_gia', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
            });
        }

        // 4.17. Bảng nhat_ky_hoat_dong
        if (Schema::hasTable('nhat_ky_hoat_dong')) {
            Schema::table('nhat_ky_hoat_dong', function (Blueprint $table) {
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'actor_type')) $table->renameColumn('actor_type', 'loai_tac_nhan');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'actor_id')) $table->renameColumn('actor_id', 'tac_nhan_id');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'action')) $table->renameColumn('action', 'hanh_dong');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'entity_type')) $table->renameColumn('entity_type', 'loai_doi_tuong');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'entity_id')) $table->renameColumn('entity_id', 'doi_tuong_id');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'before_data')) $table->renameColumn('before_data', 'du_lieu_truoc');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'after_data')) $table->renameColumn('after_data', 'du_lieu_sau');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'ip_address')) $table->renameColumn('ip_address', 'dia_chi_ip');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'user_agent')) $table->renameColumn('user_agent', 'thiet_bi_trinh_duyet');
                if (Schema::hasColumn('nhat_ky_hoat_dong', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
            });
        }

        // 4.18. Bảng cai_dat
        if (Schema::hasTable('cai_dat')) {
            Schema::table('cai_dat', function (Blueprint $table) {
                if (Schema::hasColumn('cai_dat', 'key')) $table->renameColumn('key', 'khoa_cai_dat');
                if (Schema::hasColumn('cai_dat', 'value')) $table->renameColumn('value', 'gia_tri_cai_dat');
                if (Schema::hasColumn('cai_dat', 'updated_by')) $table->renameColumn('updated_by', 'nguoi_cap_nhat_id');
                if (Schema::hasColumn('cai_dat', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.19. Bảng banner_quang_cao
        if (Schema::hasTable('banner_quang_cao')) {
            Schema::table('banner_quang_cao', function (Blueprint $table) {
                if (Schema::hasColumn('banner_quang_cao', 'image_url')) $table->renameColumn('image_url', 'duong_dan_anh');
                if (Schema::hasColumn('banner_quang_cao', 'link_url')) $table->renameColumn('link_url', 'duong_dan_lien_ket');
                if (Schema::hasColumn('banner_quang_cao', 'title')) $table->renameColumn('title', 'tieu_de');
                if (Schema::hasColumn('banner_quang_cao', 'sort_order')) $table->renameColumn('sort_order', 'thu_tu_hien_thi');
                if (Schema::hasColumn('banner_quang_cao', 'status')) $table->renameColumn('status', 'trang_thai');
                if (Schema::hasColumn('banner_quang_cao', 'starts_at')) $table->renameColumn('starts_at', 'ngay_bat_dau');
                if (Schema::hasColumn('banner_quang_cao', 'ends_at')) $table->renameColumn('ends_at', 'ngay_ket_thuc');
                if (Schema::hasColumn('banner_quang_cao', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('banner_quang_cao', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }

        // 4.20. Bảng ma_xac_thuc_email
        if (Schema::hasTable('ma_xac_thuc_email')) {
            Schema::table('ma_xac_thuc_email', function (Blueprint $table) {
                if (Schema::hasColumn('ma_xac_thuc_email', 'otp_hash')) $table->renameColumn('otp_hash', 'ma_otp_ma_hoa');
                if (Schema::hasColumn('ma_xac_thuc_email', 'full_name')) $table->renameColumn('full_name', 'ho_ten');
                if (Schema::hasColumn('ma_xac_thuc_email', 'phone')) $table->renameColumn('phone', 'so_dien_thoai');
                if (Schema::hasColumn('ma_xac_thuc_email', 'password_hash')) $table->renameColumn('password_hash', 'mat_khau_ma_hoa');
                if (Schema::hasColumn('ma_xac_thuc_email', 'expires_at')) $table->renameColumn('expires_at', 'het_han_luc');
                if (Schema::hasColumn('ma_xac_thuc_email', 'attempts')) $table->renameColumn('attempts', 'so_lan_thu');
                if (Schema::hasColumn('ma_xac_thuc_email', 'created_at')) $table->renameColumn('created_at', 'ngay_tao');
                if (Schema::hasColumn('ma_xac_thuc_email', 'updated_at')) $table->renameColumn('updated_at', 'ngay_cap_nhat');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Khôi phục lại tên bảng và tên cột cũ tiếng Anh (Hỗ trợ Rollback hoàn toàn).
     */
    public function down(): void
    {
        // 1. Đổi lại tên cột tiếng Anh trước khi đổi tên bảng
        if (Schema::hasTable('nguoi_dung')) {
            Schema::table('nguoi_dung', function (Blueprint $table) {
                if (Schema::hasColumn('nguoi_dung', 'so_dien_thoai')) $table->renameColumn('so_dien_thoai', 'phone');
                if (Schema::hasColumn('nguoi_dung', 'mat_khau_ma_hoa')) $table->renameColumn('mat_khau_ma_hoa', 'password_hash');
                if (Schema::hasColumn('nguoi_dung', 'ho_ten')) $table->renameColumn('ho_ten', 'full_name');
                if (Schema::hasColumn('nguoi_dung', 'vai_tro')) $table->renameColumn('vai_tro', 'role');
                if (Schema::hasColumn('nguoi_dung', 'ma_bi_mat_2fa')) $table->renameColumn('ma_bi_mat_2fa', 'two_factor_secret_enc');
                if (Schema::hasColumn('nguoi_dung', 'anh_dai_dien')) $table->renameColumn('anh_dai_dien', 'avatar_url');
                if (Schema::hasColumn('nguoi_dung', 'ngay_sinh')) $table->renameColumn('ngay_sinh', 'birth_date');
                if (Schema::hasColumn('nguoi_dung', 'trang_thai')) $table->renameColumn('trang_thai', 'status');
                if (Schema::hasColumn('nguoi_dung', 'thoi_gian_xac_thuc_email')) $table->renameColumn('thoi_gian_xac_thuc_email', 'email_verified_at');
                if (Schema::hasColumn('nguoi_dung', 'so_lan_dang_nhap_sai')) $table->renameColumn('so_lan_dang_nhap_sai', 'failed_login_count');
                if (Schema::hasColumn('nguoi_dung', 'khoa_den_thoi_gian')) $table->renameColumn('khoa_den_thoi_gian', 'locked_until');
                if (Schema::hasColumn('nguoi_dung', 'dang_nhap_cuoi_luc')) $table->renameColumn('dang_nhap_cuoi_luc', 'last_login_at');
                if (Schema::hasColumn('nguoi_dung', 'ip_dang_nhap_cuoi')) $table->renameColumn('ip_dang_nhap_cuoi', 'last_login_ip');
                if (Schema::hasColumn('nguoi_dung', 'ngay_tao')) $table->renameColumn('ngay_tao', 'created_at');
                if (Schema::hasColumn('nguoi_dung', 'ngay_cap_nhat')) $table->renameColumn('ngay_cap_nhat', 'updated_at');
                $table->softDeletes();
            });
        }

        $rollbackTables = [
            'nguoi_dung' => 'users',
            'danh_muc' => 'categories',
            'san_pham' => 'products',
            'bien_the_san_pham' => 'product_variants',
            'hinh_anh_san_pham' => 'product_images',
            'tai_khoan_game' => 'game_accounts',
            'gio_hang' => 'carts',
            'chi_tiet_gio_hang' => 'cart_items',
            'ma_giam_gia' => 'coupons',
            'lich_su_dung_ma_giam_gia' => 'coupon_usages',
            'don_hang' => 'orders',
            'chi_tiet_don_hang' => 'order_items',
            'yeu_cau_dich_vu' => 'service_requests',
            'chinh_sach_bao_hanh' => 'warranty_policies',
            'yeu_cau_bao_hanh' => 'warranty_claims',
            'danh_gia' => 'reviews',
            'nhat_ky_hoat_dong' => 'audit_logs',
            'cai_dat' => 'settings',
            'banner_quang_cao' => 'banners',
            'ma_xac_thuc_email' => 'otp_mail',
        ];

        foreach ($rollbackTables as $newTable => $oldTable) {
            if (Schema::hasTable($newTable) && !Schema::hasTable($oldTable)) {
                Schema::rename($newTable, $oldTable);
            }
        }
    }
};

