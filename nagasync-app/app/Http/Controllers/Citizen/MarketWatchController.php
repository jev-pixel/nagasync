<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Services\Analytics\MarketAnalytics;
use Inertia\Inertia;

class MarketWatchController extends Controller
{
    public function index(MarketAnalytics $analytics)
    {
        $result = $analytics->trends();

        return Inertia::render('Market/Index', [
            'available' => $result['available'],
            'trends'    => $result['data'],
            'market'    => config('nagasync.market_name'),
        ]);
    }
}