<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        $user = User::where('phone_number', $request->phone_number)->first();

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
            'password' => Hash::make($request->password)
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
}
