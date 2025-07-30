<?php

namespace App\Jobs;

use App\Models\Referral;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessReferralRewards implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $delayDays = config('referral.reward_delay_days', 30);
        $cutoffDate = Carbon::now()->subDays($delayDays);

        // Find referrals that are eligible for payment
        $eligibleReferrals = Referral::whereNotNull('referred_id')
            ->where('is_paid', false)
            ->where('created_at', '<=', $cutoffDate)
            ->get();

        foreach ($eligibleReferrals as $referral) {
            try {
                // Here you would integrate with your payment system
                // For now, we'll just mark as paid
                $referral->markAsPaid();
                
                Log::info('Referral reward processed', [
                    'referral_id' => $referral->id,
                    'referrer_id' => $referral->referrer_id,
                    'reward_amount' => $referral->reward_amount
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to process referral reward', [
                    'referral_id' => $referral->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Referral rewards processing completed', [
            'processed_count' => $eligibleReferrals->count()
        ]);
    }
}
