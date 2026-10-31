<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Game;
use App\Models\WarrantyPolicy;

return new class extends Migration
{
    public function up(): void
    {
        $game = Game::where('slug', 'lien-quan-mobile')->first();
        if (!$game) {
            $game = Game::first();
        }

        $category = Category::where('slug', 'dich-vu-cay-thue-lq')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Dịch Vụ Cày Thuê Rank & Chiến Tướng',
                'slug' => 'dich-vu-cay-thue-lq',
                'game_id' => $game?->id,
                'type' => 'SERVICE',
                'description' => 'Dịch vụ cày rank, cày top tướng Liên Quân Mobile chuyên nghiệp.',
                'status' => 'ACTIVE',
            ]);
        }

        $warrantyPolicy = WarrantyPolicy::where('code', 'WAR-SRV-COMPLETION')->first();

        // 1. DỊCH VỤ CÀY RANK LIÊN QUÂN MOBILE
        $rankProduct = Product::updateOrCreate(
            ['slug' => 'cay-thue-rank-lien-quan-mobile'],
            [
                'sku' => 'SRV-LQ-RANK',
                'type' => 'SERVICE',
                'category_id' => $category->id,
                'game_id' => $game?->id,
                'name' => 'Dịch Vụ Cày Thuê Rank Liên Quân Mobile',
                'slug' => 'cay-thue-rank-lien-quan-mobile',
                'min_price' => 50000,
                'short_description' => 'Dịch vụ cày thuê rank Liên Quân Mobile chuyên nghiệp, cam kết tỷ lệ thắng > 85%, hoàn thành siêu tốc.',
                'description' => "Dịch vụ cày rank Liên Quân Mobile chuyên nghiệp, uy tín hàng đầu.\n- Tuyển thủ Top Thách Đấu trực tiếp cày, tỷ lệ thắng cực cao (>85%).\n- Cam kết bảo mật tài khoản 100%, bảo toàn trang phục, ngọc và vàng.\n- Tuyệt đối không chat trong game, không toxic, không sử dụng phần mềm thứ ba.\n- Hoàn thành đúng tiến độ cam kết hoặc hoàn tiền 100%.",
                'thumbnail_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'warranty_policy_id' => $warrantyPolicy?->id,
            ]
        );

        $rankVariants = [
            [
                'name' => 'Gói Đồng / Bạc lên Vàng',
                'sku' => 'SRV-LQ-RANK-VANG',
                'price' => 60000,
                'sale_price' => 50000,
                'estimated_hours' => 4,
                'sort_order' => 1,
            ],
            [
                'name' => 'Gói Vàng lên Bạch Kim',
                'sku' => 'SRV-LQ-RANK-BK',
                'price' => 100000,
                'sale_price' => 80000,
                'estimated_hours' => 6,
                'sort_order' => 2,
            ],
            [
                'name' => 'Gói Bạch Kim lên Kim Cương',
                'sku' => 'SRV-LQ-RANK-KC',
                'price' => 150000,
                'sale_price' => 120000,
                'estimated_hours' => 8,
                'sort_order' => 3,
            ],
            [
                'name' => 'Gói Kim Cương lên Tinh Anh',
                'sku' => 'SRV-LQ-RANK-TA',
                'price' => 180000,
                'sale_price' => 150000,
                'estimated_hours' => 12,
                'sort_order' => 4,
            ],
            [
                'name' => 'Gói Tinh Anh lên Cao Thủ',
                'sku' => 'SRV-LQ-RANK-CT',
                'price' => 220000,
                'sale_price' => 180000,
                'estimated_hours' => 18,
                'sort_order' => 5,
            ],
            [
                'name' => 'Gói Cao Thủ lên Chiến Tướng (50 Sao)',
                'sku' => 'SRV-LQ-RANK-CHIENTUONG',
                'price' => 380000,
                'sale_price' => 320000,
                'estimated_hours' => 24,
                'sort_order' => 6,
            ],
            [
                'name' => 'Gói Chiến Tướng lên Thách Đấu',
                'sku' => 'SRV-LQ-RANK-THACHDAU',
                'price' => 650000,
                'sale_price' => 550000,
                'estimated_hours' => 36,
                'sort_order' => 7,
            ],
        ];

        foreach ($rankVariants as $v) {
            ProductVariant::updateOrCreate(
                ['product_id' => $rankProduct->id, 'sku' => $v['sku']],
                [
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'sale_price' => $v['sale_price'],
                    'compare_at_price' => $v['price'],
                    'stock_quantity' => 999,
                    'estimated_hours' => $v['estimated_hours'],
                    'status' => 'ACTIVE',
                    'sort_order' => $v['sort_order'],
                ]
            );
        }

        // 2. DỊCH VỤ CÀY TOP TƯỚNG LIÊN QUÂN MOBILE
        $topProduct = Product::updateOrCreate(
            ['slug' => 'cay-thue-top-tuong-lien-quan'],
            [
                'sku' => 'SRV-LQ-TOP',
                'type' => 'SERVICE',
                'category_id' => $category->id,
                'game_id' => $game?->id,
                'name' => 'Dịch Vụ Cày Top Tướng Liên Quân Mobile',
                'slug' => 'cay-thue-top-tuong-lien-quan',
                'min_price' => 130000,
                'short_description' => 'Dịch vụ leo lực chiến top tướng Liên Quân Mobile (Top Tỉnh, Top Miền, Top Việt Nam, Top Server).',
                'description' => "Dịch vụ leo Top Tướng Liên Quân Mobile uy tín nhất:\n- Nhận cày mọi vị tướng bạn yêu cầu: Florentino, Nakroth, Raz, Elsu, Điêu Thuyền, Murad, Hayate,...\n- Đội ngũ tuyển thủ tay to cày điểm lực chiến chuẩn xác, đạt Top theo đúng mục tiêu đã chọn.\n- Lựa chọn linh hoạt khu vực: Top Tỉnh (63 tỉnh thành), Top Miền (Bắc - Trung - Nam), Top Việt Nam hoặc Top Server.\n- Bảo mật 100% tài khoản, không tiêu vàng/quân huy.",
                'thumbnail_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&auto=format&fit=crop&q=80',
                'status' => 'ACTIVE',
                'is_featured' => true,
                'warranty_policy_id' => $warrantyPolicy?->id,
            ]
        );

        $topVariants = [
            [
                'name' => 'Gói Cày Top Tỉnh',
                'sku' => 'SRV-LQ-TOP-TINH',
                'price' => 160000,
                'sale_price' => 130000,
                'estimated_hours' => 12,
                'sort_order' => 1,
            ],
            [
                'name' => 'Gói Cày Top Miền',
                'sku' => 'SRV-LQ-TOP-MIEN',
                'price' => 280000,
                'sale_price' => 240000,
                'estimated_hours' => 24,
                'sort_order' => 2,
            ],
            [
                'name' => 'Gói Cày Top Việt Nam',
                'sku' => 'SRV-LQ-TOP-VN',
                'price' => 500000,
                'sale_price' => 420000,
                'estimated_hours' => 48,
                'sort_order' => 3,
            ],
            [
                'name' => 'Gói Cày Top Server',
                'sku' => 'SRV-LQ-TOP-SERVER',
                'price' => 850000,
                'sale_price' => 750000,
                'estimated_hours' => 72,
                'sort_order' => 4,
            ],
        ];

        foreach ($topVariants as $v) {
            ProductVariant::updateOrCreate(
                ['product_id' => $topProduct->id, 'sku' => $v['sku']],
                [
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'sale_price' => $v['sale_price'],
                    'compare_at_price' => $v['price'],
                    'stock_quantity' => 999,
                    'estimated_hours' => $v['estimated_hours'],
                    'status' => 'ACTIVE',
                    'sort_order' => $v['sort_order'],
                ]
            );
        }
    }

    public function down(): void
    {
    }
};

