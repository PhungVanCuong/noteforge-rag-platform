<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailOtp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register new account and send 6-digit OTP email.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $existingUser->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được đăng ký và kích hoạt trong hệ thống.'
            ], 422);
        }

        // Create or update pending user
        if (!$existingUser) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Casts automatically hashes
            ]);
        } else {
            $existingUser->update([
                'name' => $validated['name'],
                'password' => $validated['password'],
            ]);
        }

        // Generate 6-digit OTP
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Remove old OTPs for this email
        EmailOtp::where('email', $validated['email'])->delete();

        EmailOtp::create([
            'email' => $validated['email'],
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
        ]);

        // Send Email via SMTP Gmail
        try {
            Mail::to($validated['email'])->send(new OtpMail($otpCode, $validated['name']));
        } catch (\Exception $e) {
            Log::error('OTP Mail sending failed: ' . $e->getMessage());
            // In dev mode, return OTP in response if mail fails
            if (config('app.debug')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mã OTP đã được tạo (Debug OTP: ' . $otpCode . ').',
                    'email' => $validated['email'],
                    'debug_otp' => $otpCode,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mã xác thực OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư trong 5 phút.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Verify 6-digit OTP and activate account.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $otpRecord = EmailOtp::where('email', $validated['email'])
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy yêu cầu xác thực cho email này.'
            ], 404);
        }

        if ($otpRecord->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn sau 5 phút. Vui lòng bấm gửi lại mã mới.'
            ], 422);
        }

        if ($otpRecord->otp_code !== $validated['otp_code']) {
            $otpRecord->increment('attempts');
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác. Vui lòng kiểm tra lại.'
            ], 422);
        }

        // Activate user
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại.'], 404);
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        // Clear OTP record
        $otpRecord->delete();

        // Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Xác thực tài khoản thành công! Chào mừng bạn đến với NoteForge AI.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email chưa được đăng ký.'], 404);
        }

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailOtp::where('email', $validated['email'])->delete();
        EmailOtp::create([
            'email' => $validated['email'],
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
        ]);

        try {
            Mail::to($validated['email'])->send(new OtpMail($otpCode, $user->name));
        } catch (\Exception $e) {
            Log::error('Resend OTP Mail failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Mã xác thực OTP mới đã được gửi.',
        ]);
    }

    /**
     * Standard email/password login.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác.'
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'needs_verification' => true,
                'message' => 'Tài khoản chưa được kích hoạt OTP. Vui lòng xác thực email của bạn.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Google OAuth 2.0 Redirect via Socialite.
     */
    public function googleRedirect()
    {
        $redirectUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $redirectUrl]);
    }

    /**
     * Google OAuth 2.0 Callback.
     */
    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => Carbon::now(),
                ]);
            }

            $token = $user->createToken('spa')->plainTextToken;
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return redirect("{$frontendUrl}/auth/callback?token={$token}&name=" . urlencode($user->name) . "&avatar=" . urlencode($user->avatar ?? ''));
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect("{$frontendUrl}/login?error=" . urlencode('Đăng nhập Google thất bại'));
        }
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'has_google' => $user->has_google,
                'has_custom_key' => $user->has_custom_key,
                'masked_key' => $user->masked_key,
                'has_password' => !empty($user->password),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update user profile (name, avatar file or URL).
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|max:10240',
            'avatar_url' => 'nullable|string|url|max:1000',
        ]);

        $user = $request->user();
        $user->name = $validated['name'];

        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $avatarName = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $destPath = storage_path('app/public/avatars');
            if (!file_exists($destPath)) {
                mkdir($destPath, 0755, true);
            }
            $avatarFile->move($destPath, $avatarName);
            $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
            $user->avatar = "{$baseUrl}/storage/avatars/{$avatarName}";
        } elseif (!empty($validated['avatar_url'])) {
            $user->avatar = $validated['avatar_url'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ảnh đại diện và thông tin hồ sơ thành công!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'has_google' => $user->has_google,
                'has_custom_key' => $user->has_custom_key,
                'masked_key' => $user->masked_key,
                'has_password' => !empty($user->password),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $rules = [
            'new_password' => 'required|string|min:6|confirmed',
        ];

        // If user already has a password, current password is required
        if (!empty($user->password)) {
            $rules['current_password'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if (!empty($user->password) && !Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác.'
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công! Vui lòng sử dụng mật khẩu mới cho lần đăng nhập tiếp theo.'
        ]);
    }

    /**
     * Update user's personal encrypted Gemini API Key.
     */
    public function updateGeminiKey(Request $request)
    {
        $request->validate([
            'gemini_key' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->custom_gemini_key = $request->gemini_key ? trim($request->gemini_key) : null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $request->gemini_key ? 'Đã lưu API Key Gemini cá nhân thành công (mã hóa AES-256 an toàn).' : 'Đã xóa API Key cá nhân. Hệ thống sẽ sử dụng key mặc định.',
            'has_custom_key' => $user->has_custom_key,
            'masked_key' => $user->masked_key,
        ]);
    }

    /**
     * Test a Gemini API Key against AI service.
     */
    public function testGeminiKey(Request $request)
    {
        $request->validate([
            'gemini_key' => 'nullable|string',
        ]);

        $keyToTest = $request->gemini_key ? trim($request->gemini_key) : $request->user()->getDecryptedGeminiKey();
        if (empty($keyToTest)) {
            $keyToTest = env('GEMINI_API_KEY', '');
        }

        if (empty($keyToTest)) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có API Key nào được nhập hoặc cấu hình trong hệ thống.'
            ], 422);
        }

        try {
            $aiUrl = rtrim(env('AI_SERVICE_URL', 'http://127.0.0.1:8001'), '/');
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post("{$aiUrl}/api/gemini/test-key", [
                'api_key' => $keyToTest,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kết nối thành công! API Key hoạt động bình thường với Gemini Model.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kiểm tra thất bại: ' . ($response->json('detail') ?? $response->body())
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể kết nối tới AI Microservice để kiểm tra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất an toàn.'
        ]);
    }
}
