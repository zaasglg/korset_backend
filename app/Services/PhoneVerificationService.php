<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    private $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send verification code to phone
     */
    public function sendVerificationCode(string $phone, array $registrationData = []): array
    {
        // Clean expired verifications
        PhoneVerification::cleanExpired();

        // Check if there's an active verification for this phone
        $existingVerification = PhoneVerification::where('phone_number', $phone)
            ->active()
            ->first();

        if ($existingVerification) {
            $remainingTime = $existingVerification->expires_at->diffInSeconds(now());
            
            if ($remainingTime > 240) { // 4 minutes remaining
                return [
                    'success' => false,
                    'error' => 'Код уже отправлен. Попробуйте через ' . ceil($remainingTime / 60) . ' минут.',
                    'remaining_time' => $remainingTime
                ];
            }
        }

        // Generate new code
        $code = PhoneVerification::generateCode();
        $expiresAt = Carbon::now()->addMinutes(5);

        // Create or update verification record
        $verification = PhoneVerification::updateOrCreate(
            ['phone_number' => $phone],
            [
                'code' => $code,
                'is_verified' => false,
                'expires_at' => $expiresAt,
                'registration_data' => $registrationData
            ]
        );

        // Send SMS
        $smsResult = $this->smsService->sendVerificationCode($phone, $code, null);

        if (!$smsResult['success']) {
            Log::error('Failed to send verification SMS', [
                'phone' => $phone,
                'error' => $smsResult['error'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => 'Не удалось отправить SMS. Попробуйте позже.',
                'sms_error' => $smsResult['error'] ?? 'Unknown error'
            ];
        }

        Log::info('Verification code sent', [
            'phone' => $phone,
            'expires_at' => $expiresAt->toDateTimeString()
        ]);

        return [
            'success' => true,
            'message' => 'Код подтверждения отправлен на номер ' . $phone,
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_in_seconds' => 300
        ];
    }

    /**
     * Verify phone code
     */
    public function verifyCode(string $phone, string $code): array
    {
        $verification = PhoneVerification::where('phone_number', $phone)
            ->where('code', $code)
            ->first();

        if (!$verification) {
            return [
                'success' => false,
                'error' => 'Неверный код подтверждения'
            ];
        }

        if ($verification->is_verified) {
            return [
                'success' => false,
                'error' => 'Код уже использован'
            ];
        }

        if ($verification->isExpired()) {
            return [
                'success' => false,
                'error' => 'Код истек. Запросите новый код.'
            ];
        }

        // Mark as verified
        $verification->markAsVerified();

        Log::info('Phone verification successful', [
            'phone' => $phone
        ]);

        return [
            'success' => true,
            'message' => 'Номер телефона подтвержден',
            'verification_id' => $verification->id,
            'registration_data' => $verification->registration_data
        ];
    }

    /**
     * Check if phone is verified
     */
    public function isPhoneVerified(string $phone): bool
    {
        return PhoneVerification::where('phone_number', $phone)
            ->where('is_verified', true)
            ->where('created_at', '>', now()->subHour()) // Valid for 1 hour
            ->exists();
    }

    /**
     * Get verification status
     */
    public function getVerificationStatus(string $phone): array
    {
        $verification = PhoneVerification::where('phone_number', $phone)
            ->latest()
            ->first();

        if (!$verification) {
            return [
                'status' => 'not_requested',
                'message' => 'Код не запрашивался'
            ];
        }

        if ($verification->is_verified) {
            return [
                'status' => 'verified',
                'message' => 'Номер подтвержден',
                'verified_at' => $verification->updated_at->toDateTimeString()
            ];
        }

        if ($verification->isExpired()) {
            return [
                'status' => 'expired',
                'message' => 'Код истек'
            ];
        }

        $remainingTime = $verification->expires_at->diffInSeconds(now());
        
        return [
            'status' => 'pending',
            'message' => 'Ожидается подтверждение',
            'expires_at' => $verification->expires_at->toDateTimeString(),
            'remaining_seconds' => $remainingTime
        ];
    }
}