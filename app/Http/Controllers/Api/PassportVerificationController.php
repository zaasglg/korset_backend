<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PassportVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PassportVerificationController extends Controller
{
    /**
     * Submit passport verification
     */
    public function store(Request $request)
    {
        $request->validate([
            'passport_number' => 'required|string|max:20',
            'passport_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // Check if user already has a verification
        if (Auth::user()->passportVerification) {
            return response()->json([
                'message' => 'You already have a verification request'
            ], 400);
        }

        // Store passport photo
        $passportPhoto = $request->file('passport_photo');
        $passportPath = $passportPhoto->store('passports', 'public');

        // Store selfie photo
        $selfiePhoto = $request->file('selfie_photo');
        $selfiePath = $selfiePhoto->store('selfies', 'public');

        $verification = PassportVerification::create([
            'user_id' => Auth::id(),
            'passport_number' => $request->passport_number,
            'passport_photo' => $passportPath,
            'selfie_photo' => $selfiePath,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Passport verification submitted successfully',
            'verification' => $verification
        ], 201);
    }

    /**
     * Get user's verification status
     */
    public function show()
    {
        $verification = Auth::user()->passportVerification;

        if (!$verification) {
            return response()->json([
                'message' => 'No verification request found'
            ], 404);
        }

        return response()->json([
            'verification' => $verification
        ]);
    }

    /**
     * Get all verification requests (admin only)
     */
    public function index()
    {
        $this->authorize('viewAny', PassportVerification::class);

        $verifications = PassportVerification::with('user')->get();

        return response()->json([
            'verifications' => $verifications
        ]);
    }

    /**
     * Update verification status (admin only)
     */
    public function updateStatus(Request $request, PassportVerification $verification)
    {
        $this->authorize('update', $verification);

        $request->validate([
            'status' => 'required|in:verified,rejected',
            'admin_comment' => 'nullable|string'
        ]);

        $verification->update([
            'status' => $request->status,
            'admin_comment' => $request->admin_comment,
            'verified_at' => $request->status === 'verified' ? now() : null
        ]);

        return response()->json([
            'message' => 'Verification status updated successfully',
            'verification' => $verification->load('user')
        ]);
    }
}
