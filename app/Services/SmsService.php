<?php

namespace App\Services;

use App\Models\SmsLog;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Проверяет, является ли номер Билайн Казахстан
     */
    public function isBeelineKzNumber(string $phone): bool
    {
        $phone = preg_replace('/\D/', '', $phone);
        // Привести к формату 7XXXXXXXXXX
        if (strlen($phone) === 11 && ($phone[0] === '8' || $phone[0] === '7')) {
            $phone = '7' . substr($phone, 1);
        }
        // Проверка по шаблонам
        $prefixes = [
            '7705', '7771', '7776', '7777'
        ];
        foreach ($prefixes as $prefix) {
            if (strpos($phone, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }
    private $client;
    private $login;
    private $password;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->login = config('services.smsc.login');
        $this->password = config('services.smsc.password');
        $this->baseUrl = 'https://smsc.kz/sys/send.php';
    }

    /**
     * Send SMS message
     */
    public function sendSms(string $phone, string $message, string $type = 'general', ?int $userId = null): array
    {
        $formattedPhone = $this->formatPhone($phone);
        $formParams = [
            'login' => $this->login,
            'psw' => $this->password,
            'phones' => $formattedPhone,
            'mes' => $message,
            'fmt' => 3, // JSON response
            'charset' => 'utf-8'
        ];
        // Если номер Билайн, добавить tg=1
        if ($this->isBeelineKzNumber($phone)) {
            $formParams['tg'] = 1;
        }
        try {
            $response = $this->client->post($this->baseUrl, [
                'form_params' => $formParams,
                'timeout' => 30
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['error'])) {
                // Log failed SMS
                SmsLog::create([
                    'phone_number' => $formattedPhone,
                    'message' => $message,
                    'type' => $type,
                    'success' => false,
                    'error_message' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'user_id' => $userId
                ]);

                Log::error('SMS sending failed', [
                    'phone' => $phone,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null
                ];
            }

            // Log successful SMS
            SmsLog::create([
                'phone_number' => $formattedPhone,
                'message' => $message,
                'type' => $type,
                'success' => true,
                'sms_id' => $result['id'] ?? null,
                'sms_count' => $result['cnt'] ?? null,
                'user_id' => $userId
            ]);

            Log::info('SMS sent successfully', [
                'phone' => $phone,
                'id' => $result['id'] ?? null,
                'cnt' => $result['cnt'] ?? null
            ]);

            return [
                'success' => true,
                'id' => $result['id'] ?? null,
                'cnt' => $result['cnt'] ?? null
            ];

        } catch (\Exception $e) {
            // Log exception
            SmsLog::create([
                'phone_number' => $formattedPhone,
                'message' => $message,
                'type' => $type,
                'success' => false,
                'error_message' => 'Service unavailable: ' . $e->getMessage(),
                'user_id' => $userId
            ]);

            Log::error('SMS service error', [
                'phone' => $phone,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send welcome SMS after registration
     */
    public function sendWelcomeSms(string $phone, string $name, ?int $userId = null): array
    {
        $message = "Добро пожаловать в Korset, {$name}! Спасибо за регистрацию. Начните продавать и покупать уже сегодня!";
        
        return $this->sendSms($phone, $message, 'welcome', $userId);
    }

    /**
     * Send verification code SMS
     */
    public function sendVerificationCode(string $phone, string $code, ?int $userId = null): array
    {
        $message = "Ваш код подтверждения: {$code}. Никому не сообщайте этот код.";
        
        return $this->sendSms($phone, $message, 'verification', $userId);
    }

    /**
     * Format phone number for SMSC
     */
    private function formatPhone(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Add +7 if phone starts with 8 or 7
        if (strlen($phone) === 11 && ($phone[0] === '8' || $phone[0] === '7')) {
            $phone = '7' . substr($phone, 1);
        }
        
        // Add + if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Get SMS balance
     */
    public function getBalance(): array
    {
        try {
            $response = $this->client->post('https://smsc.kz/sys/balance.php', [
                'form_params' => [
                    'login' => $this->login,
                    'psw' => $this->password,
                    'fmt' => 3
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'balance' => $result['balance'] ?? 0
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}