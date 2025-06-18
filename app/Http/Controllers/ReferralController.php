<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function index()
    {
        $referrals = Referral::where('referrer_id', Auth::id())
            ->with('referred')
            ->latest()
            ->get();

        $totalEarnings = $referrals->sum('reward_amount');
        $totalReferrals = $referrals->count();
        $pendingReferrals = $referrals->where('is_paid', false)->count();

        return view('referrals.index', compact('referrals', 'totalEarnings', 'totalReferrals', 'pendingReferrals'));
    }

    public function generateCode()
    {
        $user = Auth::user();
        $referralCode = Referral::generateUniqueCode();

        return response()->json([
            'referral_code' => $referralCode,
            'referral_link' => route('register', ['ref' => $referralCode])
        ]);
    }

    public function applyReferral(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|exists:referrals,referral_code'
        ]);

        $referral = Referral::where('referral_code', $request->referral_code)->first();

        if ($referral->referred_id) {
            return response()->json([
                'message' => 'This referral code has already been used'
            ], 400);
        }

        $referral->update([
            'referred_id' => Auth::id(),
            'reward_amount' => config('referral.reward_amount', 10.00)
        ]);

        return response()->json([
            'message' => 'Referral code applied successfully'
        ]);
    }

    public function statistics()
    {
        $referrals = Referral::where('referrer_id', Auth::id())
            ->with('referred')
            ->latest()
            ->get();

        $statistics = [
            'total_earnings' => $referrals->sum('reward_amount'),
            'total_referrals' => $referrals->count(),
            'pending_referrals' => $referrals->where('is_paid', false)->count(),
            'paid_referrals' => $referrals->where('is_paid', true)->count(),
            'recent_referrals' => $referrals->take(5)
        ];

        return response()->json($statistics);
    }
}
