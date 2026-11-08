<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\MaGiamGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class XacThucController extends Controller
{
    /**
     * Đăng ký tài khoản người dùng mới.
     */
    public function dangKy(Request $request): JsonResponse
    {
        $request->merge([
            'ho_ten' => $request->ho_ten ?? $request->full_name ?? $request->name,
            'so_dien_thoai' => $request->so_dien_thoai ?? $request->phone,
            'mat_khau' => $request->mat_khau ?? $request->password,
            'mat_khau_confirmation' => $request->mat_khau_confirmation ?? $request->password_confirmation,
        ]);

        $validated = $request->validate([
            'email' => 'required|email|max:191|unique:nguoi_dung,email',
            'so_dien_thoai' => ['required', 'regex:/^0[0-9]{9}$/', 'unique:nguoi_dung,so_dien_thoai'],
            'ho_ten' => 'required|string|max:120',
            'mat_khau' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.unique' => 'Email này đã được sử dụng.',
            'so_dien_thoai.required' => 'Số điện thoại là bắt buộc.',
            'so_dien_thoai.regex' => 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng số 0.',
            'so_dien_thoai.unique' => 'Số điện thoại này đã được sử dụng.',
            'ho_ten.required' => 'Họ tên là bắt buộc.',
            'mat_khau.required' => 'Mật khẩu là bắt buộc.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
        ]);

        $email = strtolower($validated['email']);
        $otp = (string) random_int(100000, 999999);

        DB::table('ma_xac_thuc_email')->updateOrInsert(
            ['email' => $email],
            [
                'ma_otp_ma_hoa' => Hash::make($otp),
                'ho_ten' => $validated['ho_ten'],
                'so_dien_thoai' => $validated['so_dien_thoai'] ?? null,
                'mat_khau_ma_hoa' => Hash::make($validated['mat_khau']),
                'het_han_luc' => now()->addMinutes(10),
                'so_lan_thu' => 0,
                'ngay_tao' => now(),
                'ngay_cap_nhat' => now(),
            ]
        );

        try {
            Mail::raw("Mã OTP xác thực đăng ký tài khoản của bạn là: {$otp}\nMã có hiệu lực trong 10 phút. Không chia sẻ mã này cho bất kỳ ai.", function ($message) use ($email) {
                $message->to($email)->subject('Mã xác thực đăng ký tài khoản');
            });
        } catch (\Throwable $exception) {
            DB::table('ma_xac_thuc_email')->where('email', $email)->delete();
            throw $exception;
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Mã OTP đã được gửi đến email của bạn.',
            'message' => 'Mã OTP đã được gửi đến email của bạn.',
        ]);
    }

    /**
     * Xác thực mã OTP và khởi tạo tài khoản.
     */
    public function xacThucDangKy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:191',
            'ma_otp' => 'required|digits:6',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'ma_otp.required' => 'Mã OTP là bắt buộc.',
            'ma_otp.digits' => 'Mã OTP phải có 6 chữ số.',
        ]);

        $email = strtolower($validated['email']);
        $banGhi = DB::table('ma_xac_thuc_email')->where('email', $email)->first();

        if (!$banGhi || now()->greaterThan($banGhi->het_han_luc)) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mã OTP không tồn tại hoặc đã hết hạn. Vui lòng đăng ký lại.',
                'error' => ['code' => 'OTP_EXPIRED', 'message' => 'Mã OTP không tồn tại hoặc đã hết hạn.']
            ], 422);
        }

        if ($banGhi->so_lan_thu >= 5) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Bạn đã nhập sai OTP quá 5 lần. Vui lòng đăng ký lại.',
                'error' => ['code' => 'OTP_ATTEMPTS_EXCEEDED', 'message' => 'Bạn đã nhập sai OTP quá số lần cho phép.']
            ], 429);
        }

        if (!Hash::check($validated['ma_otp'], $banGhi->ma_otp_ma_hoa)) {
            DB::table('ma_xac_thuc_email')->where('email', $email)->increment('so_lan_thu');
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mã OTP không chính xác.',
                'error' => ['code' => 'INVALID_OTP', 'message' => 'Mã OTP không chính xác.']
            ], 422);
        }

        $nguoiDung = DB::transaction(function () use ($banGhi, $email) {
            $user = NguoiDung::create([
                'email' => $email,
                'so_dien_thoai' => $banGhi->so_dien_thoai,
                'ho_ten' => $banGhi->ho_ten,
                'mat_khau_ma_hoa' => $banGhi->mat_khau_ma_hoa,
                'trang_thai' => 'ACTIVE',
                'thoi_gian_xac_thuc_email' => now(),
            ]);
            DB::table('ma_xac_thuc_email')->where('email', $email)->delete();
            return $user;
        });

        // Tự động tặng mã giảm giá CHAOBANMOI
        try {
            $maChaoMung = MaGiamGia::firstOrCreate(
                ['ma_khuyen_mai' => 'CHAOBANMOI'],
                [
                    'ten_ma_giam_gia' => 'Ưu đãi chào mừng khách hàng mới',
                    'mo_ta' => 'Giảm 10% cho khách hàng mới gia nhập hệ thống',
                    'loai_giam_gia' => 'PERCENT',
                    'gia_tri_giam' => 10.00,
                    'gioi_han_moi_nguoi_dung' => 1,
                    'trang_thai' => 'ACTIVE',
                ]
            );

            Mail::to($nguoiDung->email)->send(new \App\Mail\WelcomeCouponMail($nguoiDung, $maChaoMung));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Lỗi gửi email chào mừng: ' . $e->getMessage());
        }

        $token = $nguoiDung->createToken('access-token')->plainTextToken;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Xác thực email và đăng ký thành công.',
            'message' => 'Xác thực email và đăng ký thành công.',
            'du_lieu' => [
                'token_truy_cap' => $token,
                'access_token' => $token,
                'token' => $token,
                'nguoi_dung' => $this->dinhDangNguoiDung($nguoiDung),
                'user' => $this->dinhDangNguoiDung($nguoiDung),
            ],
            'data' => [
                'token_truy_cap' => $token,
                'access_token' => $token,
                'token' => $token,
                'nguoi_dung' => $this->dinhDangNguoiDung($nguoiDung),
                'user' => $this->dinhDangNguoiDung($nguoiDung),
            ],
        ], 201);
    }

    /**
     * Đăng nhập.
     */
    public function dangNhap(Request $request): JsonResponse
    {
        $request->merge([
            'mat_khau' => $request->mat_khau ?? $request->password,
        ]);

        $validated = $request->validate([
            'email' => 'required|email',
            'mat_khau' => 'required|string',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'mat_khau.required' => 'Mật khẩu là bắt buộc.',
        ]);

        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $giay = RateLimiter::availableIn($key);
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => "Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau {$giay} giây.",
                'error' => ['code' => 'TOO_MANY_ATTEMPTS', 'message' => "Quá nhiều lần thử. Vui lòng đợi {$giay} giây."]
            ], 429);
        }

        $nguoiDung = NguoiDung::where('email', $validated['email'])->first();

        if (!$nguoiDung || !Hash::check($validated['mat_khau'], $nguoiDung->mat_khau_ma_hoa)) {
            RateLimiter::hit($key, 900);
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Email hoặc mật khẩu không chính xác.',
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Email hoặc mật khẩu không đúng.']
            ], 401);
        }

        if ($nguoiDung->isBanned()) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Tài khoản của bạn đã bị khóa.',
                'error' => ['code' => 'ACCOUNT_BANNED', 'message' => 'Tài khoản đã bị khóa.']
            ], 403);
        }

        $nguoiDung->update([
            'dang_nhap_cuoi_luc' => now(),
            'ip_dang_nhap_cuoi' => $request->ip(),
        ]);

        RateLimiter::clear($key);

        $token = $nguoiDung->createToken('access-token')->plainTextToken;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => [
                'token_truy_cap' => $token,
                'access_token' => $token,
                'token' => $token,
                'nguoi_dung' => $this->dinhDangNguoiDung($nguoiDung),
                'user' => $this->dinhDangNguoiDung($nguoiDung),
            ],
            'data' => [
                'token_truy_cap' => $token,
                'access_token' => $token,
                'token' => $token,
                'nguoi_dung' => $this->dinhDangNguoiDung($nguoiDung),
                'user' => $this->dinhDangNguoiDung($nguoiDung),
            ],
        ]);
    }

    /**
     * Đăng xuất.
     */
    public function dangXuat(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã đăng xuất.',
            'message' => 'Đã đăng xuất.',
        ]);
    }

    /**
     * Lấy thông tin tài khoản hiện tại.
     */
    public function thongTinCaNhan(Request $request): JsonResponse
    {
        $user = $this->dinhDangNguoiDung($request->user());
        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $user,
            'data' => $user,
        ]);
    }

    /**
     * Cập nhật thông tin cá nhân.
     */
    public function capNhatThongTinCaNhan(Request $request): JsonResponse
    {
        $nguoiDung = $request->user();

        $validated = $request->validate([
            'ho_ten' => 'sometimes|string|max:120',
            'so_dien_thoai' => "sometimes|nullable|string|max:20|unique:nguoi_dung,so_dien_thoai,{$nguoiDung->id}",
        ]);

        $nguoiDung->update($validated);

        $user = $this->dinhDangNguoiDung($nguoiDung->fresh());
        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $user,
            'data' => $user,
        ]);
    }

    /**
     * Đổi mật khẩu.
     */
    public function doiMatKhau(Request $request): JsonResponse
    {
        $request->merge([
            'mat_khau_hien_tai' => $request->mat_khau_hien_tai ?? $request->current_password,
            'mat_khau_moi' => $request->mat_khau_moi ?? $request->new_password ?? $request->password,
            'mat_khau_moi_confirmation' => $request->mat_khau_moi_confirmation ?? $request->new_password_confirmation ?? $request->password_confirmation,
        ]);

        $nguoiDung = $request->user();

        $request->validate([
            'mat_khau_hien_tai' => 'required|string',
            'mat_khau_moi' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers(), 'different:mat_khau_hien_tai'],
        ], [
            'mat_khau_hien_tai.required' => 'Mật khẩu hiện tại là bắt buộc.',
            'mat_khau_moi.required' => 'Mật khẩu mới là bắt buộc.',
            'mat_khau_moi.confirmed' => 'Xác nhận mật khẩu mới không trùng khớp.',
            'mat_khau_moi.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);

        if (!Hash::check($request->mat_khau_hien_tai, $nguoiDung->mat_khau_ma_hoa)) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mật khẩu hiện tại không đúng.',
                'error' => ['code' => 'WRONG_CURRENT_PASSWORD', 'message' => 'Mật khẩu hiện tại không đúng.']
            ], 422);
        }

        $nguoiDung->update(['mat_khau_ma_hoa' => Hash::make($request->mat_khau_moi)]);
        $nguoiDung->tokens()->where('id', '!=', $nguoiDung->currentAccessToken()->id)->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đổi mật khẩu thành công.',
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    /**
     * Quên mật khẩu.
     */
    public function quenMatKhau(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Nếu email tồn tại, chúng tôi đã gửi đường dẫn đặt lại mật khẩu.',
            'message' => 'Nếu email tồn tại, chúng tôi đã gửi link đặt lại mật khẩu.',
        ]);
    }

    /**
     * Định dạng dữ liệu người dùng.
     */
    private function dinhDangNguoiDung(NguoiDung $nguoiDung): array
    {
        return [
            'id' => $nguoiDung->id,
            'email' => $nguoiDung->email,
            'so_dien_thoai' => $nguoiDung->so_dien_thoai,
            'ho_ten' => $nguoiDung->ho_ten,
            'vai_tro' => $nguoiDung->vai_tro,
            'trang_thai' => $nguoiDung->trang_thai,
            'thoi_gian_xac_thuc_email' => $nguoiDung->thoi_gian_xac_thuc_email,
            'ngay_tao' => $nguoiDung->ngay_tao,
            // Aliases cho frontend
            'phone' => $nguoiDung->so_dien_thoai,
            'full_name' => $nguoiDung->ho_ten,
            'role' => $nguoiDung->vai_tro,
            'status' => $nguoiDung->trang_thai,
        ];
    }
}

