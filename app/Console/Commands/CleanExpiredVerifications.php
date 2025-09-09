<?php

namespace App\Console\Commands;

use App\Models\PhoneVerification;
use Illuminate\Console\Command;

class CleanExpiredVerifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired phone verifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = PhoneVerification::where('expires_at', '<', now())->count();
        PhoneVerification::cleanExpired();

        $this->info("Cleaned {$deletedCount} expired verifications");
    }
}
