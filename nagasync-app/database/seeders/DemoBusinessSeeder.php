<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $owner = User::where('email', 'citizen@nagasync.test')->firstOrFail();
        $barangayIds = Barangay::pluck('id');

        // name, category, tagline, tags, price level, featured, status
        $rows = [
            ['Demo Kapehan sa Kanto', 'cafe', 'Strong coffee, slow mornings', ['wifi', 'air-conditioned'], 1, true, 'approved'],
            ['Demo Beans & Pages', 'cafe', 'Books, brews and a quiet corner', ['wifi', 'pwd-accessible'], 2, false, 'approved'],
            ['Demo Lutong Bahay', 'restaurant', 'Home-style meals, served fast', ['family-friendly', 'delivery'], 1, true, 'approved'],
            ['Demo Hapag Grill', 'restaurant', 'Charcoal-grilled favourites', ['parking', 'accepts-gcash'], 2, false, 'approved'],
            ['Demo Pasalubong Corner', 'shop', 'Local snacks to take home', ['local-products', 'accepts-gcash'], 1, true, 'approved'],
            ['Demo Hardware Hub', 'shop', 'Tools and supplies', ['parking'], 2, false, 'approved'],
            ['Demo Tailor & Alterations', 'services', 'Fitting, hemming, repairs', ['pwd-accessible'], 1, false, 'approved'],
            ['Demo Pension House', 'accommodation', 'Clean rooms near the plaza', ['wifi', 'air-conditioned', 'parking'], 2, true, 'approved'],
            ['Demo Heritage Walk', 'tours', 'Guided walking tours of the old town', ['family-friendly'], 2, false, 'approved'],
            ['Demo Riverside Park', 'attraction', 'A green space for the whole family', ['family-friendly', 'pet-friendly', 'pwd-accessible'], null, false, 'approved'],
            ['Demo Bakeshop Pending', 'shop', 'Awaiting review', [], 1, false, 'pending'],
            ['Demo Print & Copy Pending', 'services', 'Awaiting review', [], 1, false, 'pending'],
        ];

        foreach ($rows as $i => [$name, $category, $tagline, $tags, $price, $featured, $status]) {
            $slug = Str::slug($name);
            $business = Business::where('slug', $slug)->first() ?? new Business();

            $business->fill([
                'name' => $name, 'category' => $category, 'tagline' => $tagline,
                'description' => "DEMO listing, not a real business. {$tagline}.",
                'barangay_id' => $barangayIds->random(), 'address' => 'Demo Street ' . ($i + 1),
                'latitude' => round(13.6218 + mt_rand(-80, 80) / 10000, 6),
                'longitude' => round(123.1948 + mt_rand(-80, 80) / 10000, 6),
                'phone' => '0917 000 ' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'hours' => 'Mon–Sat 8am–8pm', 'price_range' => $price, 'tags' => $tags,
            ])->forceFill([
                'slug' => $slug, 'owner_id' => $owner->id, 'status' => $status, 'is_featured' => $featured,
                'reviewed_at' => $status === 'approved' ? now() : null,
            ])->save();
        }
    }
}