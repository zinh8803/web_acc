<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Http\Controllers\Api\V1\WarrantyClaimController;
use App\Http\Controllers\Api\V1\ReviewController;
// Admin controllers
use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminGameAccountController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminServiceRequestController;
use App\Http\Controllers\Api\V1\Admin\AdminWarrantyController;
use App\Http\Controllers\Api\V1\Admin\AdminCouponController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminStaffController;
use App\Http\Controllers\Api\V1\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\AdminReportController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Web Acc Game & Game Services
|--------------------------------------------------------------------------
| Prefix: /api/v1
| User routes: /api/v1/*
| Admin routes: /api/v1/admin/*
*/

Route::prefix('v1')->group(function () {

    // ==================== PUBLIC AUTH ====================
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('register/verify-otp', [AuthController::class, 'verifyRegistration']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('signed')
            ->name('verification.verify');
    });

    // ==================== PUBLIC CATALOG ====================
    Route::get('games', [CatalogController::class, 'games']);
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('products', [CatalogController::class, 'index']);
    Route::get('products/{slug}/accounts', [CatalogController::class, 'accounts']);
    Route::get('products/{slug}', [CatalogController::class, 'show']);
    Route::get('products/{product}/reviews', [ReviewController::class, 'index']);
    Route::get('payment-methods', [PaymentController::class, 'methods']);
    Route::get('payments/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payments.vnpay.return');
    Route::post('payments/zalopay/callback', [PaymentController::class, 'zaloPayCallback'])->name('payments.zalopay.callback');
    Route::get('payments/zalopay/return', [PaymentController::class, 'zaloPayReturn'])->name('payments.zalopay.return');
    Route::get('banners', [CatalogController::class, 'banners']);
    Route::get('settings/public', [CatalogController::class, 'publicSettings']);


    // ==================== AUTHENTICATED USER ====================
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/email/resend', [AuthController::class, 'resendVerification']);

        // Profile
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateMe']);
        Route::patch('me/password', [AuthController::class, 'changePassword']);
        Route::get('me/sessions', [AuthController::class, 'sessions']);
        Route::delete('me/sessions/{id}', [AuthController::class, 'revokeSession']);

        // Cart (acc only)
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::patch('cart/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);

        // Coupons
        Route::post('coupons/validate', [CartController::class, 'validateCoupon']);

        // Orders
        Route::post('orders', [OrderController::class, 'store']); // checkout
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{code}', [OrderController::class, 'show']);
        Route::post('orders/{code}/cancel', [OrderController::class, 'cancel']);
        Route::post('orders/{code}/items/{item}/credentials', [OrderController::class, 'viewCredentials']);

        // Payments
        Route::post('payments/vnpay/create-url', [PaymentController::class, 'createVnPayCheckoutUrl']);
        Route::get('orders/{code}/payments', [PaymentController::class, 'initiate']);
        Route::get('orders/{code}/payments/status', [PaymentController::class, 'checkStatus']);
        Route::post('orders/{code}/payments/vnpay', [PaymentController::class, 'createVnPayPayment'])->name('payments.vnpay.create');
        Route::post('orders/{code}/payments/zalopay', [PaymentController::class, 'initiateZaloPayPayment'])->name('payments.zalopay.create');
        Route::post('orders/{code}/mock-pay', [PaymentController::class, 'mockPay']);

        // Service Requests
        Route::get('service-requests', [ServiceRequestController::class, 'index']);
        Route::post('service-requests', [ServiceRequestController::class, 'store']);
        Route::get('service-requests/{code}', [ServiceRequestController::class, 'show']);
        Route::post('service-requests/{code}/confirm', [ServiceRequestController::class, 'confirm']);

        // Warranty Claims
        Route::post('warranty-claims', [WarrantyClaimController::class, 'store']);
        Route::get('warranty-claims', [WarrantyClaimController::class, 'index']);
        Route::get('warranty-claims/{code}', [WarrantyClaimController::class, 'show']);

        // Reviews
        Route::post('reviews', [ReviewController::class, 'store']);
    });

    // ==================== ADMIN ROUTES ====================
    Route::prefix('admin')->group(function () {
        // Admin Auth (separate token audience)
        Route::post('auth/login', [AdminAuthController::class, 'login']);
        Route::post('auth/logout', [AdminAuthController::class, 'logout'])
            ->middleware(['auth:admin-sanctum', \App\Http\Middleware\EnsureAdminRole::class]);
        Route::get('auth/me', [AdminAuthController::class, 'me'])
            ->middleware(['auth:admin-sanctum', \App\Http\Middleware\EnsureAdminRole::class]);

        Route::middleware(['auth:admin-sanctum', \App\Http\Middleware\EnsureAdminRole::class])->group(function () {
            // Categories & Attributes
            Route::apiResource('categories', AdminCategoryController::class);
            Route::get('categories/{category}/attributes', [AdminCategoryController::class, 'attributes']);
            Route::post('categories/{category}/attributes', [AdminCategoryController::class, 'attachAttribute']);
            Route::apiResource('attributes', \App\Http\Controllers\Api\V1\Admin\AdminAttributeController::class);

            // Games
            Route::apiResource('games', \App\Http\Controllers\Api\V1\Admin\AdminGameController::class);

            // Products
            Route::apiResource('products', AdminProductController::class);
            Route::post('products/{product}/images', [AdminProductController::class, 'uploadImage']);
            Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'deleteImage']);
            Route::patch('products/{product}/images/reorder', [AdminProductController::class, 'reorderImages']);
            Route::apiResource('products.variants', \App\Http\Controllers\Api\V1\Admin\AdminVariantController::class)
                ->shallow();
            Route::post('products/{product}/attributes', [AdminProductController::class, 'setAttributes']);

            // Inventory
            Route::get('game-accounts', [AdminGameAccountController::class, 'index']);
            Route::post('game-accounts', [AdminGameAccountController::class, 'store']);
            Route::patch('game-accounts/{account}', [AdminGameAccountController::class, 'update']);
            Route::patch('game-accounts/{account}/listing', [AdminGameAccountController::class, 'updateListing']);
            Route::post('game-accounts/import', [AdminGameAccountController::class, 'import']);
            Route::get('game-accounts/{account}', [AdminGameAccountController::class, 'show']);
            Route::patch('game-accounts/{account}/status', [AdminGameAccountController::class, 'updateStatus']);
            Route::post('game-accounts/{account}/verify', [AdminGameAccountController::class, 'verify']);
            Route::post('game-accounts/{account}/lock', [AdminGameAccountController::class, 'lock']);
            Route::get('game-accounts/{account}/credentials', [AdminGameAccountController::class, 'viewCredentials']);
            Route::apiResource('suppliers', \App\Http\Controllers\Api\V1\Admin\AdminSupplierController::class);

            // Orders
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{code}', [AdminOrderController::class, 'show']);
            Route::patch('orders/{code}/status', [AdminOrderController::class, 'updateStatus']);


            // Service Requests
            Route::get('service-requests', [AdminServiceRequestController::class, 'index']);
            Route::get('service-requests/{request}', [AdminServiceRequestController::class, 'show']);
            Route::get('service-requests/{id}/credentials', [AdminServiceRequestController::class, 'credentials']);
            Route::post('service-requests/{id}/assign', [AdminServiceRequestController::class, 'assign']);
            Route::post('service-requests/{id}/status', [AdminServiceRequestController::class, 'updateStatus']);

            // Warranty
            Route::apiResource('warranty-policies', AdminWarrantyController::class)->except(['show']);
            Route::get('warranty-claims', [AdminWarrantyController::class, 'claims']);
            Route::get('warranty-claims/{claim}', [AdminWarrantyController::class, 'showClaim']);
            Route::post('warranty-claims/{claim}/decide', [AdminWarrantyController::class, 'decide']);

            // Coupons
            Route::apiResource('coupons', AdminCouponController::class);

            // Users
            Route::get('users', [AdminUserController::class, 'index']);
            Route::get('users/{user}', [AdminUserController::class, 'show']);
            Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);

            // Staff & RBAC
            Route::apiResource('admin-users', AdminStaffController::class)->except(['destroy']);

            // Audit Logs
            Route::get('audit-logs', [AdminAuditLogController::class, 'index']);

            // Reports
            Route::get('reports/revenue', [AdminReportController::class, 'revenue']);
            Route::get('reports/products', [AdminReportController::class, 'products']);
            Route::get('reports/warranty', [AdminReportController::class, 'warranty']);

            // Settings
            Route::get('settings', [AdminSettingController::class, 'index']);
            Route::put('settings', [AdminSettingController::class, 'update']);

            // Banners
            Route::apiResource('banners', \App\Http\Controllers\Api\V1\Admin\AdminBannerController::class);
        });
    });
});
