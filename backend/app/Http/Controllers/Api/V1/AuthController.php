<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:191|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'full_name' => 'required|string|max:120',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'full_name' => $validated['full_name'],
            'password_hash' => Hash::make($validated['password']),
            'status' => 'ACTIVE',
        ]);

        // Send email verification
        // $user->sendEmailVerificationNotification();

        return $this->success(['message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.'], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limit: 5 attempts per 15 minutes per IP
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->error("Quá nhiều lần đăng nhập. Vui lòng thử lại sau {$seconds} giây.", 429, 'TOO_MANY_ATTEMPTS');
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            RateLimiter::hit($key, 900); // 15 min window

            if ($user) {
                $user->increment('failed_login_count');
                if ($user->failed_login_count >= 5) {
                    $user->update(['locked_until' => now()->addMinutes(15)]);
                }
            }

            return $this->error('Email hoặc mật khẩu không đúng.', 401, 'INVALID_CREDENTIALS');
        }

        if ($user->isBanned()) {
            return $this->error('Tài khoản của bạn đã bị khóa.', 403, 'ACCOUNT_BANNED');
        }

        if ($user->isLocked()) {
            return $this->error('Tài khoản tạm thời bị khóa. Vui lòng thử lại sau.', 403, 'ACCOUNT_LOCKED');
        }

        // Reset failed attempts
        $user->update([
            'failed_login_count' => 0,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        RateLimiter::clear($key);

        // Create access token
        $token = $user->createToken('access-token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Logout current session.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(['message' => 'Đã đăng xuất.']);
    }

    /**
     * Get current user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($this->formatUser($request->user()));
    }

    /**
     * Update profile.
     */
    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:120',
            'phone' => "sometimes|nullable|string|max:20|unique:users,phone,{$user->id}",
            'birth_date' => 'sometimes|nullable|date',
            'avatar_url' => 'sometimes|nullable|url|max:500',
        ]);

        $user->update($validated);

        return $this->success($this->formatUser($user->fresh()));
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers(), 'different:current_password'],
        ]);

        if (!Hash::check($request->current_password, $user->password_hash)) {
            return $this->error('Mật khẩu hiện tại không đúng.', 422, 'WRONG_CURRENT_PASSWORD');
        }

        $user->update(['password_hash' => Hash::make($request->password)]);

        // Revoke all other tokens
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return $this->success(['message' => 'Đổi mật khẩu thành công.']);
    }

    /**
     * Forgot password - send reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        // Always return success to not reveal if email exists
        $user = User::where('email', $request->email)->first();
        if ($user) {
            // TODO: Send password reset email
        }

        return $this->success(['message' => 'Nếu email tồn tại, chúng tôi đã gửi link đặt lại mật khẩu.']);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        // TODO: Validate token from password_reset_tokens
        // For now, return success placeholder
        return $this->success(['message' => 'Đặt lại mật khẩu thành công.']);
    }

    // Helper: format user response
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'full_name' => $user->full_name,
            'avatar_url' => $user->avatar_url,
            'birth_date' => $user->birth_date,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];
    }

    // Standardized response helpers
    private function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function error(string $message, int $status = 400, string $code = 'ERROR'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }
}
