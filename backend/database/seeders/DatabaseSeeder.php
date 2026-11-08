<?php

namespace Database\Seeders;

use App\Models\QuanTriVien;
use App\Models\BannerQuangCao;
use App\Models\DanhMuc;
use App\Models\MaGiamGia;
use App\Models\TaiKhoanGame;
use App\Models\SanPham;
use App\Models\BienTheSanPham;
use App\Models\CaiDat;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = QuanTriVien::firstOrCreate(
            ['email' => 'admin@gamehub.vn'],
            [
                'mat_khau_ma_hoa' => Hash::make('Admin@123456'),
                'ho_ten' => 'Hệ Thống Quản Trị Viên',
                'vai_tro' => 'SUPER_ADMIN',
                'trang_thai' => 'ACTIVE',
            ]
        );

        // 2. Customer User
        $customer = NguoiDung::firstOrCreate(
            ['email' => 'customer@gamehub.vn'],
            [
                'mat_khau_ma_hoa' => Hash::make('Customer@123456'),
                'ho_ten' => 'Nguyễn Văn Khách',
                'so_dien_thoai' => '0912345678',
                'trang_thai' => 'ACTIVE',
            ]
        );

        // 3. Categories
        $lqCatVip = DanhMuc::updateOrCreate(['duong_dan_tinh' => 'nick-lien-quan-vip'], [
            'loai_danh_muc' => 'TAI_KHOAN',
            'ten_danh_muc' => 'Nick Liên Quân VIP Rank Thách Đấu',
            'duong_dan_tinh' => 'nick-lien-quan-vip',
            'mo_ta' => 'Acc full tướng, hàng trăm skin SSS Tuyệt Sắc, ngọc 90 chuẩn.',
            'thu_tu_hien_thi' => 1,
            'trang_thai' => 'ACTIVE',
        ]);

        $lqCatGiaRe = DanhMuc::updateOrCreate(['duong_dan_tinh' => 'nick-lien-quan-gia-re'], [
            'loai_danh_muc' => 'TAI_KHOAN',
            'ten_danh_muc' => 'Nick Liên Quân Giá Rẻ Tân Thủ',
            'duong_dan_tinh' => 'nick-lien-quan-gia-re',
            'mo_ta' => 'Acc trắng thông tin 100%, 30-50 tướng, thích hợp leo rank.',
            'thu_tu_hien_thi' => 2,
            'trang_thai' => 'ACTIVE',
        ]);

        $serviceCat = DanhMuc::updateOrCreate(['duong_dan_tinh' => 'dich-vu-cay-thue-lq'], [
            'loai_danh_muc' => 'DICH_VU',
            'ten_danh_muc' => 'Dịch Vụ Cày Thuê Rank & Chiến Tướng',
            'duong_dan_tinh' => 'dich-vu-cay-thue-lq',
            'mo_ta' => 'Cày thuê uy tín từ Cao Thủ lên Thách Đấu, bảo mật tài khoản tuyệt đối.',
            'thu_tu_hien_thi' => 3,
            'trang_thai' => 'ACTIVE',
        ]);

        // 4. Products & Variants & Accounts
        $productsSeed = [
            [
                'category_id' => $lqCatVip->id,
                'type' => 'ACCOUNT',
                'name' => 'Acc Liên Quân #1029: Thách Đấu 50 Sao, Nakroth Thứ Nguyên, Raz Muay Thái',
                'slug' => 'acc-lien-quan-1029-thach-dau',
                'sku' => 'LQ-1029',
                'min_price' => 890000,
                'price' => 1200000,
                'sale_price' => 890000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Rank Thách Đấu 50 Sao, 118 Tướng, 280 Trang Phục. Full bảng ngọc.',
                'description' => 'Tài khoản Liên Quân cực phẩm chính chủ. Trắng thông tin, đổi pass và sđt ngay sau khi nhận. Bảo hành vĩnh viễn không back.',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => 'nakroth_vip_99',
                'acc_pass' => 'GarenaPass!2026',
            ],
            [
                'category_id' => $lqCatGiaRe->id,
                'type' => 'ACCOUNT',
                'name' => 'Acc Liên Quân #2081: Trắng TT 100%, 65 Tướng, Ngộ Không Nhóc Tì',
                'slug' => 'acc-lien-quan-2081-trang-tt',
                'sku' => 'LQ-2081',
                'min_price' => 180000,
                'price' => 250000,
                'sale_price' => 180000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Trắng thông tin, 65 Tướng, 90 Trang Phục, Rank Kim Cương.',
                'description' => 'Acc giá sinh viên cực rẻ, trắng email & sđt garena, đăng nhập đổi mật khẩu thoải mái.',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => 'tanthu_lq_01',
                'acc_pass' => 'LienQuan#2026',
            ],
            [
                'category_id' => $serviceCat->id,
                'type' => 'SERVICE',
                'name' => 'Dịch Vụ Cày Thuê Rank Liên Quân Mobile',
                'slug' => 'cay-thue-rank-lien-quan-mobile',
                'sku' => 'SRV-LQ-RANK',
                'min_price' => 50000,
                'price' => 60000,
                'sale_price' => 50000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
                'short_description' => 'Dịch vụ cày thuê rank Liên Quân Mobile chuyên nghiệp, cam kết tỷ lệ thắng > 85%, hoàn thành siêu tốc.',
                'description' => "Dịch vụ cày rank Liên Quân Mobile chuyên nghiệp, uy tín hàng đầu.\n- Tuyển thủ Top Thách Đấu trực tiếp cày, tỷ lệ thắng cực cao (>85%).\n- Cam kết bảo mật tài khoản 100%, bảo toàn trang phục, ngọc và vàng.\n- Tuyệt đối không chat trong game, không toxic, không sử dụng phần mềm thứ ba.\n- Hoàn thành đúng tiến độ cam kết hoặc hoàn tiền 100%.",
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => null,
                'acc_pass' => null,
                'variants_data' => [
                    ['name' => 'Gói Đồng / Bạc lên Vàng', 'sku' => 'SRV-LQ-RANK-VANG', 'price' => 60000, 'sale_price' => 50000, 'estimated_hours' => 4, 'sort_order' => 1],
                    ['name' => 'Gói Vàng lên Bạch Kim', 'sku' => 'SRV-LQ-RANK-BK', 'price' => 100000, 'sale_price' => 80000, 'estimated_hours' => 6, 'sort_order' => 2],
                    ['name' => 'Gói Bạch Kim lên Kim Cương', 'sku' => 'SRV-LQ-RANK-KC', 'price' => 150000, 'sale_price' => 120000, 'estimated_hours' => 8, 'sort_order' => 3],
                    ['name' => 'Gói Kim Cương lên Tinh Anh', 'sku' => 'SRV-LQ-RANK-TA', 'price' => 180000, 'sale_price' => 150000, 'estimated_hours' => 12, 'sort_order' => 4],
                    ['name' => 'Gói Tinh Anh lên Cao Thủ', 'sku' => 'SRV-LQ-RANK-CT', 'price' => 220000, 'sale_price' => 180000, 'estimated_hours' => 18, 'sort_order' => 5],
                    ['name' => 'Gói Cao Thủ lên Chiến Tướng (50 Sao)', 'sku' => 'SRV-LQ-RANK-CHIENTUONG', 'price' => 380000, 'sale_price' => 320000, 'estimated_hours' => 24, 'sort_order' => 6],
                    ['name' => 'Gói Chiến Tướng lên Thách Đấu', 'sku' => 'SRV-LQ-RANK-THACHDAU', 'price' => 650000, 'sale_price' => 550000, 'estimated_hours' => 36, 'sort_order' => 7],
                ],
            ],
            [
                'category_id' => $serviceCat->id,
                'type' => 'SERVICE',
                'name' => 'Dịch Vụ Cày Top Tướng Liên Quân Mobile',
                'slug' => 'cay-thue-top-tuong-lien-quan',
                'sku' => 'SRV-LQ-TOP',
                'min_price' => 130000,
                'price' => 160000,
                'sale_price' => 130000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&auto=format&fit=crop&q=80',
                'short_description' => 'Dịch vụ leo lực chiến top tướng Liên Quân Mobile (Top Tỉnh, Top Miền, Top Việt Nam, Top Server).',
                'description' => "Dịch vụ leo Top Tướng Liên Quân Mobile uy tín nhất:\n- Nhận cày mọi vị tướng bạn yêu cầu: Florentino, Nakroth, Raz, Elsu, Điêu Thuyền, Murad, Hayate,...\n- Đội ngũ tuyển thủ tay to cày điểm lực chiến chuẩn xác, đạt Top theo đúng mục tiêu đã chọn.\n- Lựa chọn linh hoạt khu vực: Top Tỉnh (63 tỉnh thành), Top Miền (Bắc - Trung - Nam), Top Việt Nam hoặc Top Server.\n- Bảo mật 100% tài khoản, không tiêu vàng/quân huy.",
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => null,
                'acc_pass' => null,
                'variants_data' => [
                    ['name' => 'Gói Cày Top Tỉnh', 'sku' => 'SRV-LQ-TOP-TINH', 'price' => 160000, 'sale_price' => 130000, 'estimated_hours' => 12, 'sort_order' => 1],
                    ['name' => 'Gói Cày Top Miền', 'sku' => 'SRV-LQ-TOP-MIEN', 'price' => 280000, 'sale_price' => 240000, 'estimated_hours' => 24, 'sort_order' => 2],
                    ['name' => 'Gói Cày Top Việt Nam', 'sku' => 'SRV-LQ-TOP-VN', 'price' => 500000, 'sale_price' => 420000, 'estimated_hours' => 48, 'sort_order' => 3],
                    ['name' => 'Gói Cày Top Server', 'sku' => 'SRV-LQ-TOP-SERVER', 'price' => 850000, 'sale_price' => 750000, 'estimated_hours' => 72, 'sort_order' => 4],
                ],
            ],
        ];

        foreach ($productsSeed as $p) {
            $accUser = $p['acc_user'];
            $accPass = $p['acc_pass'];
            $price = $p['price'];
            $salePrice = $p['sale_price'];

            $product = SanPham::updateOrCreate(['duong_dan_tinh' => $p['slug']], [
                'ma_san_pham' => $p['sku'],
                'loai_san_pham' => $p['type'],
                'danh_muc_id' => $p['category_id'],
                'ten_san_pham' => $p['name'],
                'duong_dan_tinh' => $p['slug'],
                'gia_thap_nhat' => $p['min_price'],
                'mo_ta_ngan' => $p['short_description'],
                'noi_dung_chi_tiet' => $p['description'],
                'anh_dai_dien' => $p['thumbnail_url'],
                'trang_thai' => $p['status'],
                'noi_bat' => $p['is_featured'],
            ]);

            if (!empty($p['variants_data'])) {
                foreach ($p['variants_data'] as $vData) {
                    BienTheSanPham::updateOrCreate(['ma_bien_the' => $vData['sku']], [
                        'san_pham_id' => $product->id,
                        'ten_bien_the' => $vData['name'],
                        'gia_goc' => $vData['price'],
                        'gia_khuyen_mai' => $vData['sale_price'],
                        'gia_so_sanh' => $vData['price'],
                        'so_luong_kho' => 999,
                        'thoi_gian_du_kien_gio' => $vData['estimated_hours'] ?? null,
                        'trang_thai' => 'ACTIVE',
                        'thu_tu_hien_thi' => $vData['sort_order'] ?? 1,
                    ]);
                }
            } else {
                $variant = BienTheSanPham::updateOrCreate(['ma_bien_the' => $p['sku'] . '-V1'], [
                    'san_pham_id' => $product->id,
                    'ten_bien_the' => 'Mặc định',
                    'gia_goc' => $price,
                    'gia_khuyen_mai' => $salePrice,
                    'gia_so_sanh' => $price,
                    'so_luong_kho' => 1,
                    'trang_thai' => 'ACTIVE',
                ]);

                if ($product->loai_san_pham === 'ACCOUNT' && $accUser && $accPass) {
                    if (!TaiKhoanGame::where('san_pham_id', $product->id)->exists()) {
                        TaiKhoanGame::createWithEncryption(
                            productId: $product->id,
                            variantId: $variant->id,
                            username: $accUser,
                            password: $accPass,
                            extra: [
                                'sec_email' => $accUser . '@gmail.com',
                                'sec_phone' => '0988776655',
                                'additional_info' => 'Mã PIN cấp 2: 123456. Vui lòng đổi mật khẩu ngay sau khi đăng nhập!',
                                'cost_price' => 50000,
                            ]
                        );
                    }
                }
            }
        }

        // 5. Coupons
        MaGiamGia::updateOrCreate(['ma_giam_gia' => 'CHAOBANMOI'], [
            'ten_ma_giam' => 'Chào bạn mới giảm 10%',
            'mo_ta' => 'Giảm 10% cho khách hàng mới gia nhập hệ thống',
            'loai_giam_gia' => 'PERCENT',
            'gia_tri_giam' => 10,
            'gia_tri_don_toi_thieu' => 100000,
            'giam_toi_da' => 200000,
            'gioi_han_tong_luot_dung' => 1000,
            'trang_thai' => 'ACTIVE',
        ]);

        MaGiamGia::updateOrCreate(['ma_giam_gia' => 'GAMEVIP'], [
            'ten_ma_giam' => 'VIP giảm 50K',
            'mo_ta' => 'Giảm ngay 50.000đ cho đơn hàng từ 300.000đ',
            'loai_giam_gia' => 'FIXED',
            'gia_tri_giam' => 50000,
            'gia_tri_don_toi_thieu' => 300000,
            'gioi_han_tong_luot_dung' => 500,
            'trang_thai' => 'ACTIVE',
        ]);

        // 6. Banners
        BannerQuangCao::updateOrCreate(['tieu_de' => 'Mừng Đại Lễ - Xả Kho Acc Game VIP Giảm Đến 50%'], [
            'hinh_anh_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
            'duong_dan_lien_ket' => '/catalog',
            'thu_tu_hien_thi' => 1,
            'trang_thai' => 'ACTIVE',
        ]);

        BannerQuangCao::updateOrCreate(['tieu_de' => 'Bảo Hành Trọn Đời - Đổi Trả Miễn Phí Trong 7 Ngày'], [
            'hinh_anh_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1600&auto=format&fit=crop&q=80',
            'duong_dan_lien_ket' => '/warranty',
            'thu_tu_hien_thi' => 2,
            'trang_thai' => 'ACTIVE',
        ]);

        // 7. Settings
        CaiDat::updateOrCreate(['khoa_cai_dat' => 'general_settings'], [
            'gia_tri_cai_dat' => [
                'site_name' => 'GAMEHUB2003',
                'hotline' => '0987.654.321',
                'zalo' => '0987654321',
                'telegram' => 'https://t.me/gamehub_support',
                'default_warranty_days' => 7,
            ]
        ]);
    }
}
