<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReferralRewards;
use Illuminate\Console\Command;

class ProcessReferralRewardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referrals:process-rewards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process referral rewards that are eligible for payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting referral rewards processing...');
        
        ProcessReferralRewards::dispatch();
        
        $this->info('Referral rewards processing job has been dispatched.');
        
        return Command::SUCCESS;
    }
}
