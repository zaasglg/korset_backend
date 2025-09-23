<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function reset(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $phoneNumber = $request->phone_number;
        $normalizedPhone = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);
        
        $user = User::where('phone_number', $phoneNumber)
            ->orWhere('phone_number', $normalizedPhone)
            ->orWhere('phone_number', '+' . $normalizedPhone)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Пользователь с таким номером не найден.'
            ], 404);
        }

        // Генерируем простой пароль из цифр
        $newPassword = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Обновляем пароль
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        // Отправляем новый пароль через SMS
        try {
            $smsService = app(SmsService::class);
            $smsService->sendSms($user->phone_number, "Ваш новый пароль: $newPassword", 'password_reset', $user->id);
        } catch (\Exception $e) {
            // Продолжаем даже если SMS не отправилось
        }

        return response()->json([
            'success' => true,
            'message' => 'Новый пароль отправлен на ваш номер.',
            'password' => $newPassword // Временно для отладки
        ]);
    }


}