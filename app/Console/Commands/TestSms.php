<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test {phone} {--message=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test SMS to specified phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->option('message') ?: 'Тестовое сообщение от Korset';

        $this->info("Sending SMS to: {$phone}");
        $this->info("Message: {$message}");

        $smsService = app(SmsService::class);
        $result = $smsService->sendSms($phone, $message);

        if ($result['success']) {
            $this->info("SMS sent successfully!");
            $this->info("ID: {$result['id']}");
            $this->info("Count: {$result['cnt']}");
        } else {
            $this->error("Failed to send SMS: {$result['error']}");
            if (isset($result['error_code'])) {
                $this->error("Error code: {$result['error_code']}");
            }
        }
    }
}
