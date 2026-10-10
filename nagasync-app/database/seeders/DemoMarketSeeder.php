<?php

namespace Database\Seeders;

use App\Models\MarketPrice;
use Illuminate\Database\Seeder;

class DemoMarketSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        // [commodity, category, unit, demo base price in PHP]
        $items = [
            ['Rice (Well Milled)', 'rice', 'kg', 52],
            ['Pork Liempo', 'meat', 'kg', 380],
            ['Chicken (Dressed)', 'poultry', 'kg', 190],
            ['Tilapia', 'fish', 'kg', 140],
            ['Eggs (Medium)', 'poultry', 'piece', 9.5],
            ['Tomato', 'vegetables', 'kg', 60],
            ['Red Onion', 'vegetables', 'kg', 120],
            ['Cooking Oil', 'other', 'liter', 95],
        ];

        foreach ($items as [$name, $category, $unit, $base]) {
            foreach (range(29, 0) as $daysAgo) {
                $price = $base * (1 + mt_rand(-20, 20) / 1000);        // +/- 2% noise
                $srp = null;

                if ($name === 'Pork Liempo' && $daysAgo <= 1) {
                    $price = $base * 1.15;                              // injected spike
                }
                if ($name === 'Rice (Well Milled)') {
                    $srp = round($base * 1.03 * 100);
                    if ($daysAgo === 0) {
                        $price = $base * 1.06;                          // injected over-SRP
                    }
                }

                MarketPrice::updateOrCreate(
                    [
                        'market' => config('nagasync.market_name'), 'commodity' => $name,
                        'unit' => $unit, 'recorded_on' => now()->subDays($daysAgo)->toDateString(),
                    ],
                    ['category' => $category, 'price' => (int) round($price * 100), 'srp' => $srp]
                );
            }
        }
    }
}