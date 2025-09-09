<?php

namespace App\Console\Commands;

use App\Services\PhoneVerificationService;
use Illuminate\Console\Command;

class TestVerificationCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:test {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending verification code to phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');

        $this->info("Sending verification code to: {$phone}");

        $verificationService = app(PhoneVerificationService::class);
        
        $testData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $verificationService->sendVerificationCode($phone, $testData);

        if ($result['success']) {
            $this->info("✅ Verification code sent successfully!");
            $this->info("Message: {$result['message']}");
            $this->info("Expires at: {$result['expires_at']}");
            
            // Show the code from database for testing
            $verification = \App\Models\PhoneVerification::where('phone_number', $phone)
                ->latest()
                ->first();
                
            if ($verification) {
                $this->warn("🔐 Code for testing: {$verification->code}");
            }
        } else {
            $this->error("❌ Failed to send verification code");
            $this->error("Error: {$result['error']}");
            if (isset($result['sms_error'])) {
                $this->error("SMS Error: {$result['sms_error']}");
            }
        }
    }
}
