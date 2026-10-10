<?php

namespace App\Services\Analytics;

use App\Models\MarketPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketAnalytics
{
    private const CACHE_KEY = 'market:trends';
    private const DOWN_KEY = 'market:trends:down';

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::DOWN_KEY);
    }

    /** @return array{available: bool, data: array<int, array<string, mixed>>} */
    public function trends(): array
    {
        if ($cached = Cache::get(self::CACHE_KEY)) {
            return $cached;
        }

        $rows = MarketPrice::where('recorded_on', '>=', now()->subDays(60)->toDateString())
            ->orderBy('recorded_on')->get();

        // Circuit breaker: after a failure, skip the HTTP call for 30 seconds
        if (Cache::has(self::DOWN_KEY)) {
            return ['available' => false, 'data' => $this->latestOnly($rows)];
        }

        try {
            $response = Http::withHeaders(['X-Internal-Key' => (string) config('nagasync.analytics.key')])
                ->timeout(5)->acceptJson()
                ->post(rtrim(config('nagasync.analytics.url'), '/') . '/api/analytics/price-trends', [
                    'points' => $rows->map(fn ($r) => [
                        'commodity'   => $r->commodity,
                        'unit'        => $r->unit,
                        'recorded_on' => $r->recorded_on->toDateString(),
                        'price'       => $r->price / 100,
                        'srp'         => $r->srp !== null ? $r->srp / 100 : null,
                    ])->values()->all(),
                ])->throw();

            $result = ['available' => true, 'data' => $response->json('data')];
            Cache::put(self::CACHE_KEY, $result, now()->addMinutes(5));

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Market analytics unavailable: ' . $e->getMessage());
            Cache::put(self::DOWN_KEY, true, now()->addSeconds(30));

            return ['available' => false, 'data' => $this->latestOnly($rows)];
        }
    }

    private function latestOnly($rows): array
    {
        return $rows->groupBy(fn ($r) => $r->commodity . '|' . $r->unit)->map(function ($g) {
            $r = $g->last();

            return [
                'commodity' => $r->commodity, 'unit' => $r->unit,
                'latest_price' => $r->price / 100, 'latest_date' => $r->recorded_on->toDateString(),
                'avg_7d' => null, 'prev_avg_7d' => null, 'change_pct' => null,
                'status' => 'unknown', 'sparkline' => [],
            ];
        })->values()->all();
    }
}