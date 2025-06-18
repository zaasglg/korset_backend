<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    /**
     * Get user's referral information
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get or create user's referral code
        $myReferral = Referral::firstOrCreate(
            ['referrer_id' => $user->id, 'referred_id' => null],
            ['referral_code' => Referral::generateUniqueCode()]
        );

        $referrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('referred_id')
            ->with('referred:id,name,surname,email,created_at')
            ->latest()
            ->get();

        $statistics = [
            'my_referral_code' => $myReferral->referral_code,
            'total_referrals' => $referrals->count(),
            'total_earnings' => $referrals->sum('reward_amount'),
            'pending_earnings' => $referrals->where('is_paid', false)->sum('reward_amount'),
            'paid_earnings' => $referrals->where('is_paid', true)->sum('reward_amount'),
            'referrals' => $referrals->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'referred_user' => [
                        'name' => $referral->referred->name,
                        'surname' => $referral->referred->surname,
                        'email' => $referral->referred->email,
                    ],
                    'reward_amount' => $referral->reward_amount,
                    'is_paid' => $referral->is_paid,
                    'created_at' => $referral->created_at,
                    'paid_at' => $referral->paid_at,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Generate a new referral code for the user
     */
    public function generateCode()
    {
        $user = Auth::user();
        
        // Check if user already has a referral code
        $existingReferral = Referral::where('referrer_id', $user->id)
            ->whereNull('referred_id')
            ->first();

        if ($existingReferral) {
            return response()->json([
                'success' => true,
                'data' => [
                    'referral_code' => $existingReferral->referral_code,
                    'message' => 'Your existing referral code'
                ]
            ]);
        }

        // Generate new referral code
        $referralCode = Referral::generateUniqueCode();
        
        Referral::create([
            'referrer_id' => $user->id,
            'referral_code' => $referralCode,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $referralCode,
                'message' => 'New referral code generated successfully'
            ]
        ]);
    }

    /**
     * Apply a referral code
     */
    public function applyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|min:6|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code format',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        // Check if user already used a referral code
        $existingUsage = Referral::where('referred_id', $user->id)->first();
        if ($existingUsage) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used a referral code'
            ], 400);
        }

        // Find the referral code
        $referral = Referral::where('referral_code', strtoupper($request->referral_code))
            ->whereNull('referred_id')
            ->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used referral code'
            ], 404);
        }

        // Check if user is trying to use their own referral code
        if ($referral->referrer_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot use your own referral code'
            ], 400);
        }

        // Apply the referral code
        $referral->update([
            'referred_id' => $user->id,
            'reward_amount' => config('referral.reward_amount', 10.00)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Referral code applied successfully! Your referrer will receive a reward.',
            'data' => [
                'reward_amount' => $referral->reward_amount,
                'referrer_name' => $referral->referrer->name . ' ' . $referral->referrer->surname
            ]
        ]);
    }

    /**
     * Get referral statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        
        $referrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('referred_id')
            ->get();

        $thisMonth = $referrals->filter(function ($referral) {
            return $referral->created_at->isCurrentMonth();
        });

        $thisWeek = $referrals->filter(function ($referral) {
            return $referral->created_at->isCurrentWeek();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_referrals' => $referrals->count(),
                'total_earnings' => $referrals->sum('reward_amount'),
                'pending_earnings' => $referrals->where('is_paid', false)->sum('reward_amount'),
                'paid_earnings' => $referrals->where('is_paid', true)->sum('reward_amount'),
                'this_month_referrals' => $thisMonth->count(),
                'this_month_earnings' => $thisMonth->sum('reward_amount'),
                'this_week_referrals' => $thisWeek->count(),
                'this_week_earnings' => $thisWeek->sum('reward_amount'),
                'recent_referrals' => $referrals->take(5)->map(function ($referral) {
                    return [
                        'referred_user_name' => $referral->referred->name . ' ' . $referral->referred->surname,
                        'reward_amount' => $referral->reward_amount,
                        'is_paid' => $referral->is_paid,
                        'created_at' => $referral->created_at->format('Y-m-d H:i:s'),
                    ];
                })
            ]
        ]);
    }

    /**
     * Validate referral code (for registration)
     */
    public function validateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|min:6|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code format',
                'errors' => $validator->errors()
            ], 422);
        }

        $referral = Referral::where('referral_code', strtoupper($request->referral_code))
            ->whereNull('referred_id')
            ->with('referrer:id,name,surname')
            ->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used referral code'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Valid referral code',
            'data' => [
                'referrer_name' => $referral->referrer->name . ' ' . $referral->referrer->surname,
                'reward_amount' => config('referral.reward_amount', 10.00)
            ]
        ]);
    }
}
