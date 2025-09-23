<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckPhoneNumberRequest;
use App\Models\User;
use App\Models\Referral;
use App\Models\PhoneVerification;
use App\Services\VideoService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * @tags Authentication
 */
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|min:6|max:10',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        // Handle referral code if provided
        if ($request->referral_code) {
            $referral = Referral::where('referral_code', strtoupper($request->referral_code))
                ->whereNull('referred_id')
                ->first();

            if ($referral && $referral->referrer_id !== $user->id) {
                $referral->update([
                    'referred_id' => $user->id,
                    'reward_amount' => config('referral.reward_amount', 10.00)
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required',
        ]);

        $phoneNumber = $request->phone_number;
        
        // Нормализуем номер телефона для поиска
        $normalizedPhone = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);
        
        // Ищем пользователя по разным вариантам номера
        $user = User::where('phone_number', $phoneNumber)
            ->orWhere('phone_number', $normalizedPhone)
            ->orWhere('phone_number', '+' . $normalizedPhone)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $avatar = $request->file('avatar');
        $avatarPath = $avatar->store('avatars', 'public');

        // Update user avatar
        $user->update(['avatar' => $avatarPath]);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|max:20|unique:users,phone_number,' . $user->id,
        ]);

        $updateData = $request->only(['name', 'email', 'phone_number']);

        // Only update fields that were provided
        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make(value: $request->password)
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify password before deletion
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный пароль'
            ], 401);
        }

        try {
            // Delete user's products and associated files
            $products = $user->products;
            if ($products) {
                foreach ($products as $product) {
                    // Delete product videos if exists
                    if ($product->video) {
                        $videoService = app(VideoService::class);
                        $videoService->deleteVideo($product->video);
                    }
                    
                    // Delete product photos if exists
                    if ($product->main_photo) {
                        Storage::disk('public')->delete($product->main_photo);
                    }
                    
                    // Delete product parameters values
                    if (method_exists($product, 'parameterValues')) {
                        $product->parameterValues()->delete();
                    }
                    
                    // Delete product from favorites
                    if (method_exists($product, 'favoritedBy')) {
                        $product->favoritedBy()->detach();
                    }
                    
                    // Delete product
                    $product->delete();
                }
            }

            // Delete user's referrals
            if (method_exists($user, 'referralsMade')) {
                $user->referralsMade()->delete();
            }
            if (method_exists($user, 'referralReceived')) {
                $user->referralReceived()->delete();
            }

            // Delete user's favorites
            if (method_exists($user, 'favorites')) {
                $user->favorites()->delete();
            }

            // Delete user's chat messages (if relation exists)
            if (method_exists($user, 'chatMessages')) {
                $user->chatMessages()->delete();
            }

            // Delete user's chats (if relation exists)
            if (method_exists($user, 'chats')) {
                $user->chats()->delete();
            }

            // Delete user's passport verifications
            if (method_exists($user, 'passportVerification')) {
                $user->passportVerification()->delete();
            }

            // Delete user's avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Finally delete the user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Аккаунт успешно удален'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении аккаунта: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка кода верификации
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|min:6|max:10',
        ]);

        // Проверяем, не зарегистрирован ли уже номер
        if (User::where('phone_number', $request->phone_number)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Номер телефона уже зарегистрирован'
            ], 422);
        }

        // Проверяем, не зарегистрирован ли уже email
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Email уже зарегистрирован'
            ], 422);
        }

        // Генерируем код
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Сохраняем данные верификации
        PhoneVerification::updateOrCreate(
            ['phone_number' => $request->phone_number],
            [
                'code' => $code,
                'expires_at' => $expiresAt,
                'registration_data' => [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'referral_code' => $request->referral_code,
                ],
                'is_verified' => false,
            ]
        );

        // Отправляем SMS
        try {
            $smsService = app(SmsService::class);
            $smsService->sendVerificationCode($request->phone_number, $code);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка отправки SMS: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Код подтверждения отправлен на номер {$request->phone_number}",
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'expires_in_seconds' => 300
        ]);
    }

    /**
     * Подтверждение кода и завершение регистрации
     */
    public function verifyAndRegister(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $verification = PhoneVerification::where('phone_number', $request->phone_number)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_verified', false)
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'error' => 'Неверный код подтверждения'
            ], 422);
        }

        // Получаем данные регистрации
        $registrationData = $verification->registration_data;

        // Создаем пользователя
        $user = User::create([
            'name' => $registrationData['name'],
            'email' => $registrationData['email'],
            'phone_number' => $request->phone_number,
            'password' => Hash::make($registrationData['password']),
        ]);

        // Обрабатываем реферальный код
        if (!empty($registrationData['referral_code'])) {
            $referral = Referral::where('referral_code', strtoupper($registrationData['referral_code']))
                ->whereNull('referred_id')
                ->first();

            if ($referral && $referral->referrer_id !== $user->id) {
                $referral->update([
                    'referred_id' => $user->id,
                    'reward_amount' => config('referral.reward_amount', 10.00)
                ]);
            }
        }

        // Отмечаем верификацию как завершенную
        $verification->update(['is_verified' => true]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешно завершена',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Получение статуса верификации
     */
    public function getVerificationStatus(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $verification = PhoneVerification::where('phone_number', $request->phone_number)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => 'not_requested',
                'message' => 'Код не запрашивался'
            ]);
        }

        if ($verification->is_verified) {
            return response()->json([
                'status' => 'verified',
                'message' => 'Номер подтвержден',
                'verified_at' => $verification->updated_at->format('Y-m-d H:i:s')
            ]);
        }

        if ($verification->expires_at < Carbon::now()) {
            return response()->json([
                'status' => 'expired',
                'message' => 'Код истек'
            ]);
        }

        $remainingSeconds = $verification->expires_at->diffInSeconds(Carbon::now());

        return response()->json([
            'status' => 'pending',
            'message' => 'Ожидается подтверждение',
            'expires_at' => $verification->expires_at->format('Y-m-d H:i:s'),
            'remaining_seconds' => $remainingSeconds
        ]);
    }

    /**
     * Проверить, зарегистрирован ли номер телефона
     */
    public function checkPhoneNumber(CheckPhoneNumberRequest $request)
    {
        $phoneNumber = $request->validated()['phone_number'];
        
        $normalizedPhone = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);
        
        $userExists = User::where('phone_number', $phoneNumber)
            ->orWhere('phone_number', $normalizedPhone)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'phone_number' => $phoneNumber,
                'is_registered' => $userExists,
                'message' => $userExists 
                    ? 'Номер телефона уже зарегистрирован' 
                    : 'Номер телефона доступен для регистрации'
            ]
        ]);
    }
}
