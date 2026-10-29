<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\WarrantyPolicy;
use App\Models\Product;
use App\Models\OrderItem;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Policy bảo hành tài khoản trọn đời
        $accLifetime = WarrantyPolicy::firstOrCreate(
            ['code' => 'WAR-ACC-LIFETIME'],
            [
                'name' => 'Bảo Hành Tài Khoản Trọn Đời (1 Đổi 1 / Hoàn Tiền)',
                'product_type' => 'ACCOUNT',
                'duration_days' => null,
                'is_lifetime' => true,
                'coverage' => ['LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS', 'OTHER'],
                'exclusions' => 'Khách hàng chia sẻ mật khẩu cho bên thứ 3 hoặc vi phạm điều khoản game dẫn đến bị khóa.',
                'max_claims' => 2,
                'resolution_options' => ['REPLACE_ACCOUNT', 'REFUND', 'RECOVER_ACCOUNT'],
                'claim_sla_hours' => 24,
                'description' => 'Cam kết bảo hành vĩnh viễn không bị chủ cũ back nick. Nếu xảy ra sự cố được hỗ trợ lấy lại, đổi acc mới tương đương hoặc hoàn tiền 100%.',
                'status' => 'ACTIVE',
            ]
        );

        // 2. Policy bảo hành tài khoản 30 ngày
        $acc30d = WarrantyPolicy::firstOrCreate(
            ['code' => 'WAR-ACC-30D'],
            [
                'name' => 'Bảo Hành Tài Khoản 30 Ngày',
                'product_type' => 'ACCOUNT',
                'duration_days' => 30,
                'is_lifetime' => false,
                'coverage' => ['LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS'],
                'exclusions' => 'Sử dụng phần mềm can thiệp thứ ba / hack cheat.',
                'max_claims' => 1,
                'resolution_options' => ['REPLACE_ACCOUNT', 'REFUND'],
                'claim_sla_hours' => 24,
                'description' => 'Bảo hành an toàn trong vòng 30 ngày kể từ ngày bàn giao tài khoản.',
                'status' => 'ACTIVE',
            ]
        );

        // 3. Policy bảo hành dịch vụ cày thuê
        $srvPolicy = WarrantyPolicy::firstOrCreate(
            ['code' => 'WAR-SRV-COMPLETION'],
            [
                'name' => 'Bảo Hành Dịch Vụ Cày Thuê Chuẩn Rank & Bảo Mật',
                'product_type' => 'SERVICE',
                'duration_days' => 7,
                'is_lifetime' => false,
                'coverage' => ['SERVICE_NOT_DONE', 'OTHER'],
                'exclusions' => 'Khách hàng tự ý đăng nhập làm tụt rank trong thời gian cày thuê.',
                'max_claims' => 1,
                'resolution_options' => ['REFUND', 'PARTIAL_REFUND'],
                'claim_sla_hours' => 12,
                'description' => 'Cam kết đạt mục tiêu rank đúng thời hạn, bảo mật 100% tài sản trong game.',
                'status' => 'ACTIVE',
            ]
        );

        // Link products
        Product::where('type', 'ACCOUNT')->whereNull('warranty_policy_id')->update(['warranty_policy_id' => $accLifetime->id]);
        Product::where('type', 'SERVICE')->whereNull('warranty_policy_id')->update(['warranty_policy_id' => $srvPolicy->id]);

        // Link order items
        OrderItem::where('item_type', 'ACCOUNT')->whereNull('warranty_policy_id')->update(['warranty_policy_id' => $accLifetime->id]);
        OrderItem::where('item_type', 'SERVICE')->whereNull('warranty_policy_id')->update(['warranty_policy_id' => $srvPolicy->id]);
    }

    public function down(): void
    {
    }
};

