<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. RBAC & Admin User
        $superAdminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            [
                'name' => 'Super Administrator',
                'description' => 'Quản trị viên toàn quyền hệ thống',
                'is_system' => true,
            ]
        );

        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@gamehub.vn'],
            [
                'password_hash' => Hash::make('Admin@123456'),
                'full_name' => 'Hệ Thống Quản Trị Viên',
                'role_id' => $superAdminRole->id,
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

        // 3. Payment Methods
        PaymentMethod::create([
            'code' => 'vietqr',
            'name' => 'Chuyển Khoản Ngân Hàng 24/7 (VietQR)',
            'provider' => 'SEPAY',
            'icon_url' => 'https://img.vietqr.io/image/MB-0987654321-compact2.png',
            'fee_percent' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PaymentMethod::create([
            'code' => 'momo',
            'name' => 'Ví Điện Tử MoMo',
            'provider' => 'MOMO',
            'icon_url' => 'https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png',
            'fee_percent' => 0,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        PaymentMethod::create([
            'code' => 'vnpay',
            'name' => 'Cổng Thanh Toán VNPAY-QR',
            'provider' => 'VNPAY',
            'icon_url' => 'https://vnpay.vn/assets/images/logo.svg',
            'fee_percent' => 0,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // 4. Games
        $gamesData = [
            [
                'name' => 'Liên Quân Mobile',
                'slug' => 'lien-quan-mobile',
                'logo_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=100&auto=format&fit=crop&q=80',
                'description' => 'MOBA di động số 1 Việt Nam với hàng triệu game thủ thi đấu kịch tính.',
                'status' => 'ACTIVE',
                'sort_order' => 1,
            ],
            [
                'name' => 'Genshin Impact',
                'slug' => 'genshin-impact',
                'logo_url' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=100&auto=format&fit=crop&q=80',
                'description' => 'Siêu phẩm nhập vai thế giới mở hành động thế hệ mới Teyvat.',
                'status' => 'ACTIVE',
                'sort_order' => 2,
            ],
            [
                'name' => 'Free Fire',
                'slug' => 'free-fire',
                'logo_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=100&auto=format&fit=crop&q=80',
                'description' => 'Game bắn súng sinh tồn di động tốc độ cao hàng đầu thế giới.',
                'status' => 'ACTIVE',
                'sort_order' => 3,
            ],
            [
                'name' => 'Valorant',
                'slug' => 'valorant',
                'logo_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=100&auto=format&fit=crop&q=80',
                'description' => 'FPS chiến thuật 5v5 nhân vật đặc vụ thi đấu đỉnh cao toàn cầu.',
                'status' => 'ACTIVE',
                'sort_order' => 4,
            ],
            [
                'name' => 'FC Online',
                'slug' => 'fc-online',
                'logo_url' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=100&auto=format&fit=crop&q=80',
                'description' => 'Game bóng đá trực tuyến chân thực nhất dành cho fan túc cầu giáo.',
                'status' => 'ACTIVE',
                'sort_order' => 5,
            ],
        ];

        $games = [];
        foreach ($gamesData as $g) {
            $games[$g['slug']] = Game::create($g);
        }

        // 5. Categories
        $lqCatVip = Category::create([
            'game_id' => $games['lien-quan-mobile']->id,
            'type' => 'ACCOUNT',
            'name' => 'Nick Liên Quân VIP Rank Thách Đấu',
            'slug' => 'nick-lien-quan-vip',
            'description' => 'Acc full tướng, hàng trăm skin SSS Tuyệt Sắc, ngọc 90 chuẩn.',
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        $lqCatGiaRe = Category::create([
            'game_id' => $games['lien-quan-mobile']->id,
            'type' => 'ACCOUNT',
            'name' => 'Nick Liên Quân Giá Rẻ Tân Thủ',
            'slug' => 'nick-lien-quan-gia-re',
            'description' => 'Acc trắng thông tin 100%, 30-50 tướng, thích hợp leo rank.',
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        $genshinCat = Category::create([
            'game_id' => $games['genshin-impact']->id,
            'type' => 'ACCOUNT',
            'name' => 'Nick Genshin Impact AR 55+ VIP',
            'slug' => 'nick-genshin-ar55-vip',
            'description' => 'Acc nhiều nhân vật 5 sao giới hạn, vũ khí trấn, C6 R5.',
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        $valCat = Category::create([
            'game_id' => $games['valorant']->id,
            'type' => 'ACCOUNT',
            'name' => 'Nick Valorant Skin VIP',
            'slug' => 'nick-valorant-skin-vip',
            'description' => 'Acc Kuronami, Reaver, Prime, rank Kim Cương - Immortal.',
            'sort_order' => 4,
            'status' => 'ACTIVE',
        ]);

        $serviceCat = Category::create([
            'game_id' => $games['lien-quan-mobile']->id,
            'type' => 'SERVICE',
            'name' => 'Dịch Vụ Cày Thuê Rank & Chiến Tướng',
            'slug' => 'dich-vu-cay-thue-lq',
            'description' => 'Cày thuê uy tín từ Cao Thủ lên Thách Đấu, bảo mật tài khoản tuyệt đối.',
            'sort_order' => 5,
            'status' => 'ACTIVE',
        ]);

        // 6. Products & Variants & Accounts
        $productsSeed = [
            [
                'game_id' => $games['lien-quan-mobile']->id,
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
                'game_id' => $games['lien-quan-mobile']->id,
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
                'game_id' => $games['genshin-impact']->id,
                'category_id' => $genshinCat->id,
                'type' => 'ACCOUNT',
                'name' => 'Acc Genshin Impact #3045: AR 58, Raiden Shogun C2 R1, Furina, Hu Tao, Yelan',
                'slug' => 'acc-genshin-3045-raiden-furina',
                'sku' => 'GI-3045',
                'min_price' => 1950000,
                'price' => 2500000,
                'sale_price' => 1950000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Server Asia, AR 58, 18 Nhân Vật 5 Sao, 8 Vũ Khí 5 Sao, 12.000 Nguyên Thạch.',
                'description' => 'Tài khoản Genshin Impact Asia cực mạnh la hoàn 36 sao nhàn tênh. Đổi mail HoYoverse sạch sẽ không liên kết bên thứ 3.',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => 'hoyoverse_genshin_3045',
                'acc_pass' => 'Genshin!Safe2026',
            ],
            [
                'game_id' => $games['valorant']->id,
                'category_id' => $valCat->id,
                'type' => 'ACCOUNT',
                'name' => 'Acc Valorant #4012: Vandal Kuronami + Reaver Karambit + Phantom Prime',
                'slug' => 'acc-valorant-4012-kuronami-reaver',
                'sku' => 'VAL-4012',
                'min_price' => 1250000,
                'price' => 1500000,
                'sale_price' => 1250000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Server Việt Nam (VNG/Riot), Rank Ascendant 2, Full hiệu ứng nâng cấp.',
                'description' => 'Tài khoản Riot VNG có mail gốc bàn giao, bảo hành tài khoản 30 ngày.',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'acc_user' => 'riot_val_pro4012',
                'acc_pass' => 'Valorant@Rank99',
            ],
            [
                'game_id' => $games['lien-quan-mobile']->id,
                'category_id' => $serviceCat->id,
                'type' => 'SERVICE',
                'name' => 'Cày Thuê Rank Liên Quân Mobile - Từ Tinh Anh Lên Cao Thủ',
                'slug' => 'cay-thue-tinh-anh-len-cao-thu',
                'sku' => 'SRV-LQ-01',
                'min_price' => 150000,
                'price' => 200000,
                'sale_price' => 150000,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Cày siêu tốc trong 24h, bảo mật thông tin, tuyển thủ Thách Đấu trực tiếp cày.',
                'description' => 'Dịch vụ cày thuê rank Liên Quân chuyên nghiệp. Đảm bảo tỷ lệ thắng >85%, không dùng tool hack, không chat trong game.',
                'status' => 'ACTIVE',
                'is_featured' => false,
                'acc_user' => null,
                'acc_pass' => null,
            ],
        ];

        foreach ($productsSeed as $p) {
            $accUser = $p['acc_user'];
            $accPass = $p['acc_pass'];
            $price = $p['price'];
            $salePrice = $p['sale_price'];

            $product = Product::create([
                'sku' => $p['sku'],
                'type' => $p['type'],
                'category_id' => $p['category_id'],
                'game_id' => $p['game_id'],
                'name' => $p['name'],
                'slug' => $p['slug'],
                'min_price' => $p['min_price'],
                'short_description' => $p['short_description'],
                'description' => $p['description'],
                'thumbnail_url' => $p['thumbnail_url'],
                'status' => $p['status'],
                'is_featured' => $p['is_featured'],
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $p['sku'] . '-V1',
                'name' => 'Mặc định',
                'price' => $price,
                'sale_price' => $salePrice,
                'compare_at_price' => $price,
                'stock_quantity' => 1,
                'status' => 'ACTIVE',
            ]);

            if ($product->type === 'ACCOUNT' && $accUser && $accPass) {
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

        // 7. Coupons
        Coupon::create([
            'code' => 'CHAOBANMOI',
            'name' => 'Chào bạn mới giảm 10%',
            'description' => 'Giảm 10% cho khách hàng mới gia nhập hệ thống',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'min_order_amount' => 100000,
            'max_discount_amount' => 200000,
            'usage_limit' => 1000,
            'status' => 'ACTIVE',
        ]);

        Coupon::create([
            'code' => 'GAMEVIP',
            'name' => 'VIP giảm 50K',
            'description' => 'Giảm ngay 50.000đ cho đơn hàng từ 300.000đ',
            'discount_type' => 'FIXED',
            'discount_value' => 50000,
            'min_order_amount' => 300000,
            'usage_limit' => 500,
            'status' => 'ACTIVE',
        ]);

        // 8. Banners
        Banner::create([
            'title' => 'Mừng Đại Lễ - Xả Kho Acc Game VIP Giảm Đến 50%',
            'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
            'link_url' => '/catalog',
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Banner::create([
            'title' => 'Bảo Hành Trọn Đời - Đổi Trả Miễn Phí Trong 7 Ngày',
            'image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1600&auto=format&fit=crop&q=80',
            'link_url' => '/warranty',
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        // 9. Settings
        Setting::create([
            'key' => 'general_settings',
            'value' => [
                'site_name' => 'GAMEHUB VN - Sàn Giao Dịch Acc Game & Dịch Vụ',
                'hotline' => '0987.654.321',
                'zalo' => '0987654321',
                'telegram' => 'https://t.me/gamehub_support',
                'default_warranty_days' => 7,
            ]
        ]);
    }
}
