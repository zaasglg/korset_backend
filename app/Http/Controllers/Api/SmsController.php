<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    /**
     * Get SMS statistics
     */
    public function statistics()
    {
        $stats = [
            'total_sent' => SmsLog::count(),
            'successful' => SmsLog::successful()->count(),
            'failed' => SmsLog::failed()->count(),
            'by_type' => [
                'welcome' => SmsLog::byType('welcome')->count(),
                'verification' => SmsLog::byType('verification')->count(),
                'general' => SmsLog::byType('general')->count(),
            ],
            'recent_logs' => SmsLog::with('user:id,name,phone_number')
                ->latest()
                ->take(10)
                ->get()
        ];

        return response()->json($stats);
    }

    /**
     * Get SMS balance
     */
    public function balance()
    {
        $smsService = app(SmsService::class);
        $result = $smsService->getBalance();

        return response()->json($result);
    }

    /**
     * Send test SMS (admin only)
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:160'
        ]);

        $smsService = app(SmsService::class);
        $result = $smsService->sendSms(
            $request->phone, 
            $request->message, 
            'test'
        );

        return response()->json($result);
    }
}