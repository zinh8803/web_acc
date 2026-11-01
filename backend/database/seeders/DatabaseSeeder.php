<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User (role is stored directly on the account)
        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@gamehub.vn'],
            [
                'password_hash' => Hash::make('Admin@123456'),
                'full_name' => 'Hệ Thống Quản Trị Viên',
                'role' => 'SUPER_ADMIN',
                'status' => 'ACTIVE',
            ]
        );

        // 2. Customer User
        $customer = User::firstOrCreate(
            ['email' => 'customer@gamehub.vn'],
            [
                'password_hash' => Hash::make('Customer@123456'),
                'full_name' => 'Nguyễn Văn Khách',
                'phone' => '0912345678',
                'status' => 'ACTIVE',
            ]
        );

        // 5. Categories
        $lqCatVip = Category::updateOrCreate(['slug' => 'nick-lien-quan-vip'], [
            'type' => 'ACCOUNT',
            'name' => 'Nick Liên Quân VIP Rank Thách Đấu',
            'slug' => 'nick-lien-quan-vip',
            'description' => 'Acc full tướng, hàng trăm skin SSS Tuyệt Sắc, ngọc 90 chuẩn.',
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        $lqCatGiaRe = Category::updateOrCreate(['slug' => 'nick-lien-quan-gia-re'], [
            'type' => 'ACCOUNT',
            'name' => 'Nick Liên Quân Giá Rẻ Tân Thủ',
            'slug' => 'nick-lien-quan-gia-re',
            'description' => 'Acc trắng thông tin 100%, 30-50 tướng, thích hợp leo rank.',
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        $serviceCat = Category::updateOrCreate(['slug' => 'dich-vu-cay-thue-lq'], [
            'type' => 'SERVICE',
            'name' => 'Dịch Vụ Cày Thuê Rank & Chiến Tướng',
            'slug' => 'dich-vu-cay-thue-lq',
            'description' => 'Cày thuê uy tín từ Cao Thủ lên Thách Đấu, bảo mật tài khoản tuyệt đối.',
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        // 6. Products & Variants & Accounts
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

            $product = Product::updateOrCreate(['slug' => $p['slug']], [
                'sku' => $p['sku'],
                'type' => $p['type'],
                'category_id' => $p['category_id'],
                'name' => $p['name'],
                'slug' => $p['slug'],
                'min_price' => $p['min_price'],
                'short_description' => $p['short_description'],
                'description' => $p['description'],
                'thumbnail_url' => $p['thumbnail_url'],
                'status' => $p['status'],
                'is_featured' => $p['is_featured'],
            ]);

            if (!empty($p['variants_data'])) {
                foreach ($p['variants_data'] as $vData) {
                    ProductVariant::updateOrCreate(['sku' => $vData['sku']], [
                        'product_id' => $product->id,
                        'name' => $vData['name'],
                        'price' => $vData['price'],
                        'sale_price' => $vData['sale_price'],
                        'compare_at_price' => $vData['price'],
                        'stock_quantity' => 999,
                        'estimated_hours' => $vData['estimated_hours'] ?? null,
                        'status' => 'ACTIVE',
                        'sort_order' => $vData['sort_order'] ?? 1,
                    ]);
                }
            } else {
                $variant = ProductVariant::updateOrCreate(['sku' => $p['sku'] . '-V1'], [
                    'product_id' => $product->id,
                    'name' => 'Mặc định',
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'compare_at_price' => $price,
                    'stock_quantity' => 1,
                    'status' => 'ACTIVE',
                ]);

                if ($product->type === 'ACCOUNT' && $accUser && $accPass) {
                    if (!GameAccount::where('product_id', $product->id)->exists()) {
                        GameAccount::createWithEncryption(
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

        // 7. Coupons
        Coupon::updateOrCreate(['code' => 'CHAOBANMOI'], [
            'name' => 'Chào bạn mới giảm 10%',
            'description' => 'Giảm 10% cho khách hàng mới gia nhập hệ thống',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'min_order_amount' => 100000,
            'max_discount_amount' => 200000,
            'usage_limit' => 1000,
            'status' => 'ACTIVE',
        ]);

        Coupon::updateOrCreate(['code' => 'GAMEVIP'], [
            'name' => 'VIP giảm 50K',
            'description' => 'Giảm ngay 50.000đ cho đơn hàng từ 300.000đ',
            'discount_type' => 'FIXED',
            'discount_value' => 50000,
            'min_order_amount' => 300000,
            'usage_limit' => 500,
            'status' => 'ACTIVE',
        ]);

        // 8. Banners
        Banner::updateOrCreate(['title' => 'Mừng Đại Lễ - Xả Kho Acc Game VIP Giảm Đến 50%'], [
            'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
            'link_url' => '/catalog',
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Banner::updateOrCreate(['title' => 'Bảo Hành Trọn Đời - Đổi Trả Miễn Phí Trong 7 Ngày'], [
            'image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1600&auto=format&fit=crop&q=80',
            'link_url' => '/warranty',
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        // 9. Settings
        Setting::updateOrCreate(['key' => 'general_settings'], [
            'value' => [
                'site_name' => 'GAMEHUB2003',
                'hotline' => '0987.654.321',
                'zalo' => '0987654321',
                'telegram' => 'https://t.me/gamehub_support',
                'default_warranty_days' => 7,
            ]
        ]);
    }
}
