<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class CheckSmsBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check SMS balance on SMSC.kz';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $smsService = app(SmsService::class);
        $result = $smsService->getBalance();

        if ($result['success']) {
            $this->info("SMS Balance: {$result['balance']} KZT");
        } else {
            $this->error("Failed to get balance: {$result['error']}");
        }
    }
}
