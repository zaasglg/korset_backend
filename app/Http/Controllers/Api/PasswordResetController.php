<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function reset(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Пользователь с таким номером не найден.'
            ], 404);
        }


        // Генерируем сложный пароль (цифры + буквы)
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $newPassword = '';
        $length = 8;
        for ($i = 0; $i < $length; $i++) {
            $newPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

    // Меняем пароль через fill+save (Laravel сам хеширует через cast)
    $user->fill(['password' => $newPassword]);
    $user->save();

        // Отправляем новый пароль через SMS/Telegram
        $smsService = app(SmsService::class);
        $smsService->sendSms($user->phone_number, "Ваш новый пароль: $newPassword", 'password_reset', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Новый пароль отправлен на ваш номер.'
        ]);
    }
}
