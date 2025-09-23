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

        // Сохраняем старый хеш для отладки
        $oldHash = $user->password;

        // Хешируем пароль вручную (без каста)
        $user->password = Hash::make($newPassword);
        $user->save();

        // Проверяем, что пароль действительно изменился
        $user->refresh(); // Перезагружаем из БД
        $newHash = $user->password;

        // Логируем для отладки
        Log::info('Password Reset Debug', [
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'new_password' => $newPassword,
            'old_hash' => $oldHash,
            'new_hash' => $newHash,
            'hash_check' => Hash::check($newPassword, $newHash)
        ]);

        // Проверяем, что новый пароль работает
        if (!Hash::check($newPassword, $newHash)) {
            Log::error('Password hash verification failed after reset');
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при создании нового пароля. Попробуйте еще раз.'
            ], 500);
        }

        // Отправляем новый пароль через SMS/Telegram
        $smsService = app(SmsService::class);
        $smsService->sendSms($user->phone_number, "Ваш новый пароль: $newPassword", 'password_reset', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Новый пароль отправлен на ваш номер.',
            // Временно для отладки - уберите в продакшене!
            'debug' => [
                'password' => $newPassword,
                'user_id' => $user->id
            ]
        ]);
    }

    // Добавим метод для тестирования аутентификации
    public function testLogin(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $passwordCheck = Hash::check($request->password, $user->password);

        Log::info('Login Test', [
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'input_password' => $request->password,
            'stored_hash' => $user->password,
            'hash_check_result' => $passwordCheck
        ]);

        return response()->json([
            'user_found' => true,
            'password_correct' => $passwordCheck,
            'user_active' => $user->is_active ?? true,
            'debug' => [
                'input_password' => $request->password,
                'stored_hash' => $user->password
            ]
        ]);
    }
}