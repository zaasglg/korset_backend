<?php

namespace App\Http\Controllers;

use App\Services\FreedomPayService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testFreedomPaySignature(FreedomPayService $freedomPayService)
    {
        try {
            $result = $freedomPayService->testSignatureGeneration();
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}