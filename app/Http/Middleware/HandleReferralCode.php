<?php

namespace App\Http\Middleware;

use App\Models\Referral;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleReferralCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if there's a referral code in the request
        if ($request->has('ref') && $request->get('ref')) {
            $referralCode = strtoupper($request->get('ref'));
            
            // Validate the referral code
            $referral = Referral::where('referral_code', $referralCode)
                ->whereNull('referred_id')
                ->first();
                
            if ($referral) {
                // Store the referral code in session for later use during registration
                session(['pending_referral_code' => $referralCode]);
            }
        }

        return $next($request);
    }
}
