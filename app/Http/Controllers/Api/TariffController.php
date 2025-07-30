<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    /**
     * Get all tariffs
     */
    public function index()
    {
        $tariffs = Tariff::all();

        return response()->json([
            'tariffs' => $tariffs
        ]);
    }

    /**
     * Get a specific tariff
     */
    public function show(Tariff $tariff)
    {
        return response()->json([
            'tariff' => $tariff
        ]);
    }
}
