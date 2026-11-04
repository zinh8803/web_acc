<?php

use App\Http\Controllers\Api\V1\XacThucController;
use App\Http\Controllers\Api\V1\DanhMucVaSanPhamController;
use App\Http\Controllers\Api\V1\GioHangController;
use App\Http\Controllers\Api\V1\DonHangController;
use App\Http\Controllers\Api\V1\ThanhToanController;
use App\Http\Controllers\Api\V1\YeuCauDichVuController;
use App\Http\Controllers\Api\V1\YeuCauBaoHanhController;
use App\Http\Controllers\Api\V1\DanhGiaController;

// Admin controllers
use App\Http\Controllers\Api\V1\Admin\QuanTriXacThucController;
use App\Http\Controllers\Api\V1\Admin\QuanTriDanhMucController;
use App\Http\Controllers\Api\V1\Admin\QuanTriSanPhamController;
use App\Http\Controllers\Api\V1\Admin\QuanTriBienTheController;
use App\Http\Controllers\Api\V1\Admin\QuanTriTaiKhoanGameController;
use App\Http\Controllers\Api\V1\Admin\QuanTriDonHangController;
use App\Http\Controllers\Api\V1\Admin\QuanTriYeuCauDichVuController;
use App\Http\Controllers\Api\V1\Admin\QuanTriBaoHanhController;
use App\Http\Controllers\Api\V1\Admin\QuanTriMaGiamGiaController;
use App\Http\Controllers\Api\V1\Admin\QuanTriNguoiDungController;
use App\Http\Controllers\Api\V1\Admin\QuanTriNhanVienController;
use App\Http\Controllers\Api\V1\Admin\QuanTriBaoCaoController;
use App\Http\Controllers\Api\V1\Admin\QuanTriCaiDatController;
use App\Http\Controllers\Api\V1\Admin\QuanTriBannerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Web Acc Game & Dịch Vụ
|--------------------------------------------------------------------------
| Prefix: /api/v1
| User routes: /api/v1/*
| Admin routes: /api/v1/admin/*
*/

Route::prefix('v1')->group(function () {
    // ==================== XÁC THỰC NGƯỜI DÙNG ====================
    Route::prefix('auth')->group(function () {
        Route::post('register', [XacThucController::class, 'dangKy']);
        Route::post('dang-ky', [XacThucController::class, 'dangKy']);
        Route::post('register/verify-otp', [XacThucController::class, 'xacThucDangKy']);
        Route::post('xac-thuc-dang-ky', [XacThucController::class, 'xacThucDangKy']);
        Route::post('login', [XacThucController::class, 'dangNhap']);
        Route::post('dang-nhap', [XacThucController::class, 'dangNhap']);
        Route::post('forgot-password', [XacThucController::class, 'quenMatKhau']);
        Route::post('quen-mat-khau', [XacThucController::class, 'quenMatKhau']);
    });

    // ==================== DANH MỤC & SẢN PHẨM CÔNG KHAI ====================
    Route::get('games', [DanhMucVaSanPhamController::class, 'troChoi']);
    Route::get('categories', [DanhMucVaSanPhamController::class, 'danhMuc']);
    Route::get('danh-muc', [DanhMucVaSanPhamController::class, 'danhMuc']);
    Route::get('products', [DanhMucVaSanPhamController::class, 'sanPham']);
    Route::get('san-pham', [DanhMucVaSanPhamController::class, 'sanPham']);
    Route::get('products/{slug}/accounts', [DanhMucVaSanPhamController::class, 'danhSachTaiKhoan']);
    Route::get('san-pham/{slug}/tai-khoan', [DanhMucVaSanPhamController::class, 'danhSachTaiKhoan']);
    Route::get('products/{slug}', [DanhMucVaSanPhamController::class, 'chiTiet']);
    Route::get('san-pham/{slug}', [DanhMucVaSanPhamController::class, 'chiTiet']);
    Route::get('products/{product}/reviews', [DanhGiaController::class, 'danhSach']);
    Route::get('san-pham/{product}/danh-gia', [DanhGiaController::class, 'danhSach']);

    // ==================== THANH TOÁN ====================
    Route::get('payment-methods', [ThanhToanController::class, 'phuongThuc']);
    Route::get('phuong-thuc-thanh-toan', [ThanhToanController::class, 'phuongThuc']);
    Route::get('payments/vnpay/return', [ThanhToanController::class, 'vnpayKetQua'])->name('payments.vnpay.return');
    Route::get('banners', [DanhMucVaSanPhamController::class, 'banners']);
    Route::get('settings/public', [DanhMucVaSanPhamController::class, 'caiDatCongKhai']);
    Route::get('cai-dat/cong-khai', [DanhMucVaSanPhamController::class, 'caiDatCongKhai']);

    // ==================== USER ĐÃ ĐĂNG NHẬP (AUTH SANCTUM) ====================
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('auth/logout', [XacThucController::class, 'dangXuat']);
        Route::post('auth/dang-xuat', [XacThucController::class, 'dangXuat']);

        // Profile
        Route::get('me', [XacThucController::class, 'thongTinCaNhan']);
        Route::get('auth/me', [XacThucController::class, 'thongTinCaNhan']);
        Route::get('thong-tin-ca-nhan', [XacThucController::class, 'thongTinCaNhan']);
        Route::patch('me', [XacThucController::class, 'capNhatThongTinCaNhan']);
        Route::patch('thong-tin-ca-nhan', [XacThucController::class, 'capNhatThongTinCaNhan']);
        Route::patch('me/password', [XacThucController::class, 'doiMatKhau']);
        Route::patch('doi-mat-khau', [XacThucController::class, 'doiMatKhau']);

        // Giỏ hàng
        Route::get('cart', [GioHangController::class, 'xemGioHang']);
        Route::get('gio-hang', [GioHangController::class, 'xemGioHang']);
        Route::post('cart/items', [GioHangController::class, 'themSanPham']);
        Route::post('gio-hang/them', [GioHangController::class, 'themSanPham']);
        Route::patch('cart/items/{item}', [GioHangController::class, 'capNhatSoLuong']);
        Route::patch('gio-hang/muc/{item}', [GioHangController::class, 'capNhatSoLuong']);
        Route::delete('cart/items/{item}', [GioHangController::class, 'xoaMuc']);
        Route::delete('gio-hang/muc/{item}', [GioHangController::class, 'xoaMuc']);

        // Mã giảm giá
        Route::post('coupons/validate', [GioHangController::class, 'kiemTraMaGiamGia']);
        Route::post('ma-giam-gia/kiem-tra', [GioHangController::class, 'kiemTraMaGiamGia']);

        // Đơn hàng
        Route::post('orders', [DonHangController::class, 'taoDonHang']);
        Route::post('don-hang', [DonHangController::class, 'taoDonHang']);
        Route::get('orders', [DonHangController::class, 'danhSach']);
        Route::get('don-hang', [DonHangController::class, 'danhSach']);
        Route::get('orders/{code}', [DonHangController::class, 'chiTiet']);
        Route::get('don-hang/{code}', [DonHangController::class, 'chiTiet']);
        Route::post('orders/{code}/cancel', [DonHangController::class, 'huyDonHang']);
        Route::post('don-hang/{code}/huy', [DonHangController::class, 'huyDonHang']);
        Route::post('orders/{code}/items/{item}/credentials', [DonHangController::class, 'xemThongTinTaiKhoan']);
        Route::post('don-hang/{code}/chi-tiet/{item}/thong-tin-tai-khoan', [DonHangController::class, 'xemThongTinTaiKhoan']);

        // Thanh toán
        Route::post('payments/vnpay/create-url', [ThanhToanController::class, 'taoDuongDanThanhToanVnPay']);
        Route::post('thanh-toan/vnpay/tao-url', [ThanhToanController::class, 'taoDuongDanThanhToanVnPay']);
        Route::get('orders/{code}/payments', [ThanhToanController::class, 'khoiTaoThanhToan']);
        Route::get('don-hang/{code}/thanh-toan', [ThanhToanController::class, 'khoiTaoThanhToan']);
        Route::get('orders/{code}/payments/status', [ThanhToanController::class, 'kiemTraTrangThai']);
        Route::get('don-hang/{code}/thanh-toan/trang-thai', [ThanhToanController::class, 'kiemTraTrangThai']);
        Route::post('orders/{code}/payments/vnpay', [ThanhToanController::class, 'thanhToanVnPay'])->name('payments.vnpay.create');
        Route::post('don-hang/{code}/thanh-toan/vnpay', [ThanhToanController::class, 'thanhToanVnPay']);
        Route::post('orders/{code}/mock-pay', [ThanhToanController::class, 'giaLapThanhToan']);
        Route::post('don-hang/{code}/gia-lap-thanh-toan', [ThanhToanController::class, 'giaLapThanhToan']);

        // Yêu cầu dịch vụ
        Route::get('service-requests', [YeuCauDichVuController::class, 'danhSach']);
        Route::get('yeu-cau-dich-vu', [YeuCauDichVuController::class, 'danhSach']);
        Route::post('service-requests', [YeuCauDichVuController::class, 'taoYeuCau']);
        Route::post('yeu-cau-dich-vu', [YeuCauDichVuController::class, 'taoYeuCau']);
        Route::get('service-requests/{code}', [YeuCauDichVuController::class, 'chiTiet']);
        Route::get('yeu-cau-dich-vu/{code}', [YeuCauDichVuController::class, 'chiTiet']);
        Route::post('service-requests/{code}/confirm', [YeuCauDichVuController::class, 'xacNhanHoanThanh']);
        Route::post('yeu-cau-dich-vu/{code}/xac-nhan', [YeuCauDichVuController::class, 'xacNhanHoanThanh']);

        // Yêu cầu bảo hành
        Route::post('warranty-claims', [YeuCauBaoHanhController::class, 'guiYeuCau']);
        Route::post('yeu-cau-bao-hanh', [YeuCauBaoHanhController::class, 'guiYeuCau']);
        Route::get('warranty-claims', [YeuCauBaoHanhController::class, 'danhSach']);
        Route::get('yeu-cau-bao-hanh', [YeuCauBaoHanhController::class, 'danhSach']);
        Route::get('warranty-claims/{code}', [YeuCauBaoHanhController::class, 'chiTiet']);
        Route::get('yeu-cau-bao-hanh/{code}', [YeuCauBaoHanhController::class, 'chiTiet']);
        Route::post('warranties/claims', [YeuCauBaoHanhController::class, 'guiYeuCau']);
        Route::get('warranties/claims', [YeuCauBaoHanhController::class, 'danhSach']);
        Route::get('warranties/claims/{code}', [YeuCauBaoHanhController::class, 'chiTiet']);

        // Đánh giá
        Route::post('reviews', [DanhGiaController::class, 'guiDanhGia']);
        Route::post('danh-gia', [DanhGiaController::class, 'guiDanhGia']);
        Route::post('products/{product}/reviews', [DanhGiaController::class, 'guiDanhGia']);
        Route::post('san-pham/{product}/danh-gia', [DanhGiaController::class, 'guiDanhGia']);
    });

    // ==================== ADMIN ROUTES ====================
    Route::prefix('admin')->group(function () {
        Route::post('auth/login', [QuanTriXacThucController::class, 'dangNhap']);
        Route::post('auth/dang-nhap', [QuanTriXacThucController::class, 'dangNhap']);

        Route::middleware(['auth:admin-sanctum', \App\Http\Middleware\EnsureAdminRole::class])->group(function () {
            Route::post('auth/logout', [QuanTriXacThucController::class, 'dangXuat']);
            Route::get('auth/me', [QuanTriXacThucController::class, 'thongTinCaNhan']);

            // Categories
            Route::apiResource('categories', QuanTriDanhMucController::class);
            Route::apiResource('danh-muc', QuanTriDanhMucController::class);

            // Products & Variants
            Route::apiResource('products', QuanTriSanPhamController::class);
            Route::apiResource('san-pham', QuanTriSanPhamController::class);
            Route::post('products/{product}', [QuanTriSanPhamController::class, 'update']);
            Route::post('san-pham/{product}', [QuanTriSanPhamController::class, 'update']);
            Route::apiResource('products.variants', QuanTriBienTheController::class)->shallow();
            Route::apiResource('san-pham.bien-the', QuanTriBienTheController::class)->shallow();

            // Inventory / Game Accounts
            Route::get('game-accounts', [QuanTriTaiKhoanGameController::class, 'index']);
            Route::get('tai-khoan-game', [QuanTriTaiKhoanGameController::class, 'index']);
            Route::post('game-accounts', [QuanTriTaiKhoanGameController::class, 'store']);
            Route::post('tai-khoan-game', [QuanTriTaiKhoanGameController::class, 'store']);
            Route::post('game-accounts/{account}', [QuanTriTaiKhoanGameController::class, 'update']);
            Route::post('tai-khoan-game/{account}', [QuanTriTaiKhoanGameController::class, 'update']);
            Route::patch('game-accounts/{account}', [QuanTriTaiKhoanGameController::class, 'update']);
            Route::patch('tai-khoan-game/{account}', [QuanTriTaiKhoanGameController::class, 'update']);
            Route::patch('game-accounts/{account}/listing', [QuanTriTaiKhoanGameController::class, 'updateListing']);
            Route::patch('tai-khoan-game/{account}/listing', [QuanTriTaiKhoanGameController::class, 'updateListing']);
            Route::post('game-accounts/import', [QuanTriTaiKhoanGameController::class, 'import']);
            Route::post('tai-khoan-game/nhap-kho', [QuanTriTaiKhoanGameController::class, 'import']);
            Route::get('game-accounts/{account}', [QuanTriTaiKhoanGameController::class, 'show']);
            Route::get('tai-khoan-game/{account}', [QuanTriTaiKhoanGameController::class, 'show']);
            Route::patch('game-accounts/{account}/status', [QuanTriTaiKhoanGameController::class, 'updateStatus']);
            Route::patch('tai-khoan-game/{account}/trang-thai', [QuanTriTaiKhoanGameController::class, 'updateStatus']);
            Route::post('game-accounts/{account}/verify', [QuanTriTaiKhoanGameController::class, 'verify']);
            Route::post('game-accounts/{account}/lock', [QuanTriTaiKhoanGameController::class, 'lock']);
            Route::get('game-accounts/{account}/credentials', [QuanTriTaiKhoanGameController::class, 'viewCredentials']);
            Route::get('tai-khoan-game/{account}/thong-tin', [QuanTriTaiKhoanGameController::class, 'viewCredentials']);

            // Orders
            Route::get('orders', [QuanTriDonHangController::class, 'index']);
            Route::get('don-hang', [QuanTriDonHangController::class, 'index']);
            Route::get('orders/{code}', [QuanTriDonHangController::class, 'show']);
            Route::get('don-hang/{code}', [QuanTriDonHangController::class, 'show']);
            Route::patch('orders/{code}/status', [QuanTriDonHangController::class, 'updateStatus']);
            Route::patch('don-hang/{code}/trang-thai', [QuanTriDonHangController::class, 'updateStatus']);

            // Service Requests
            Route::get('service-requests', [QuanTriYeuCauDichVuController::class, 'index']);
            Route::get('yeu-cau-dich-vu', [QuanTriYeuCauDichVuController::class, 'index']);
            Route::get('service-requests/{request}', [QuanTriYeuCauDichVuController::class, 'show']);
            Route::get('yeu-cau-dich-vu/{request}', [QuanTriYeuCauDichVuController::class, 'show']);
            Route::get('service-requests/{id}/credentials', [QuanTriYeuCauDichVuController::class, 'credentials']);
            Route::post('service-requests/{id}/assign', [QuanTriYeuCauDichVuController::class, 'assign']);
            Route::post('service-requests/{id}/status', [QuanTriYeuCauDichVuController::class, 'updateStatus']);

            // Warranty
            Route::apiResource('warranty-policies', QuanTriBaoHanhController::class)->except(['show']);
            Route::apiResource('chinh-sach-bao-hanh', QuanTriBaoHanhController::class)->except(['show']);
            Route::get('warranty-claims', [QuanTriBaoHanhController::class, 'claims']);
            Route::get('yeu-cau-bao-hanh', [QuanTriBaoHanhController::class, 'claims']);
            Route::get('warranty-claims/{claim}', [QuanTriBaoHanhController::class, 'showClaim']);
            Route::get('yeu-cau-bao-hanh/{claim}', [QuanTriBaoHanhController::class, 'showClaim']);
            Route::post('warranty-claims/{claim}/decide', [QuanTriBaoHanhController::class, 'decide']);
            Route::post('yeu-cau-bao-hanh/{claim}/quyet-dinh', [QuanTriBaoHanhController::class, 'decide']);

            // Coupons
            Route::apiResource('coupons', QuanTriMaGiamGiaController::class);
            Route::apiResource('ma-giam-gia', QuanTriMaGiamGiaController::class);

            // Users
            Route::get('users', [QuanTriNguoiDungController::class, 'index']);
            Route::get('nguoi-dung', [QuanTriNguoiDungController::class, 'index']);
            Route::get('users/{user}', [QuanTriNguoiDungController::class, 'show']);
            Route::get('nguoi-dung/{user}', [QuanTriNguoiDungController::class, 'show']);
            Route::patch('users/{user}/status', [QuanTriNguoiDungController::class, 'updateStatus']);
            Route::patch('nguoi-dung/{user}/trang-thai', [QuanTriNguoiDungController::class, 'updateStatus']);

            // Staff & RBAC
            Route::apiResource('admin-users', QuanTriNhanVienController::class)->except(['destroy']);
            Route::apiResource('nhan-vien', QuanTriNhanVienController::class)->except(['destroy']);

            // Reports & Dashboard
            Route::get('dashboard', [QuanTriBaoCaoController::class, 'dashboard']);
            Route::get('tong-quan', [QuanTriBaoCaoController::class, 'dashboard']);
            Route::get('reports/revenue', [QuanTriBaoCaoController::class, 'revenue']);
            Route::get('bao-cao/doanh-thu', [QuanTriBaoCaoController::class, 'revenue']);
            Route::get('reports/products', [QuanTriBaoCaoController::class, 'products']);
            Route::get('bao-cao/san-pham', [QuanTriBaoCaoController::class, 'products']);
            Route::get('reports/warranty', [QuanTriBaoCaoController::class, 'warranty']);
            Route::get('bao-cao/bao-hanh', [QuanTriBaoCaoController::class, 'warranty']);

            // Settings
            Route::get('settings', [QuanTriCaiDatController::class, 'index']);
            Route::get('cai-dat', [QuanTriCaiDatController::class, 'index']);
            Route::put('settings', [QuanTriCaiDatController::class, 'update']);
            Route::put('cai-dat', [QuanTriCaiDatController::class, 'update']);

            // Banners
            Route::apiResource('banners', QuanTriBannerController::class);
            Route::apiResource('banner-quang-cao', QuanTriBannerController::class);
            Route::post('banners/{banner}', [QuanTriBannerController::class, 'update']);
            Route::post('banner-quang-cao/{banner}', [QuanTriBannerController::class, 'update']);
        });
    });
});
