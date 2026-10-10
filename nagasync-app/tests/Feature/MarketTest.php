<?php

namespace Tests\Feature;

use App\Models\MarketPrice;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketTest extends TestCase
{
    public function test_board_survives_an_analytics_outage_without_hammering_the_service(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);
        $citizen = $this->userWithRole('citizen');

        foreach (range(1, 2) as $_) {
            $this->actingAs($citizen)->get('/market')->assertOk()
                ->assertInertia(fn (Assert $p) => $p->component('Market/Index')->where('available', false));
        }

        Http::assertSentCount(1); // the circuit breaker skipped the second call
    }

    public function test_price_entry_converts_pesos_to_centavos_and_corrects_instead_of_duplicating(): void
    {
        Http::fake();
        $staff = $this->userWithRole('barangay_staff');
        $entry = ['commodity' => 'Rice', 'category' => 'rice', 'unit' => 'kg', 'recorded_on' => now()->toDateString()];

        $this->actingAs($staff)->post('/staff/market-prices', $entry + ['price' => 52.25]);
        $this->assertSame(5225, MarketPrice::firstOrFail()->price);

        $this->actingAs($staff)->post('/staff/market-prices', $entry + ['price' => 53]);
        $this->assertSame(1, MarketPrice::count());
        $this->assertSame(5300, MarketPrice::first()->price);
    }
}