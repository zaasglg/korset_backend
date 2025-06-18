<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Referral System Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the referral system settings
    |
    */

    // Default reward amount for successful referrals
    'reward_amount' => env('REFERRAL_REWARD_AMOUNT', 10.00),

    // Minimum amount required for withdrawal
    'minimum_withdrawal' => env('REFERRAL_MINIMUM_WITHDRAWAL', 50.00),

    // Referral code length
    'code_length' => env('REFERRAL_CODE_LENGTH', 8),

    // Number of days after which referral reward becomes available
    'reward_delay_days' => env('REFERRAL_REWARD_DELAY_DAYS', 30),
];
