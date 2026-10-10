<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MarketPrice;
use App\Services\Analytics\MarketAnalytics;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarketPriceController extends Controller
{
    public function index()
    {
        return Inertia::render('Staff/Market/Index', [
            'categories' => MarketPrice::CATEGORIES,
            'recent' => MarketPrice::latest('recorded_on')->latest('id')->take(20)->get()->map(fn ($p) => [
                'id' => $p->id, 'commodity' => $p->commodity, 'unit' => $p->unit,
                'price' => $p->price / 100, 'srp' => $p->srp !== null ? $p->srp / 100 : null,
                'recorded_on' => $p->recorded_on->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'commodity'   => ['required', 'string', 'max:100'],
            'category'    => ['required', Rule::in(MarketPrice::CATEGORIES)],
            'unit'        => ['required', 'string', 'max:20'],
            'price'       => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'srp'         => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
        ]);

        // One row per market/commodity/unit/day: re-entering a price corrects it
        MarketPrice::updateOrCreate(
            [
                'market' => config('nagasync.market_name'), 'commodity' => trim($data['commodity']),
                'unit' => trim($data['unit']), 'recorded_on' => $data['recorded_on'],
            ],
            [
                'category'    => $data['category'],
                'price'       => (int) round($data['price'] * 100),
                'srp'         => isset($data['srp']) ? (int) round($data['srp'] * 100) : null,
                'recorded_by' => $request->user()->id,
            ]
        );

        MarketAnalytics::forget();

        return back();
    }
}