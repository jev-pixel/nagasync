<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Notifications\BusinessReviewed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessDirectoryTest extends TestCase
{
    private function business(array $attrs = []): Business
    {
        return Business::unguarded(fn () => Business::create($attrs + [
            'name' => 'Demo Café ' . Str::random(4), 'slug' => 'demo-' . Str::lower(Str::random(8)),
            'category' => 'cafe', 'address' => '1 Demo Street', 'status' => 'approved',
            'barangay_id' => $this->barangay()->id, 'owner_id' => $this->userWithRole('citizen')->id,
        ]));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sampaguita Café', 'category' => 'cafe', 'address' => '12 Rizal Street',
            'barangay_id' => $this->barangay()->id,
        ], $overrides);
    }

    // --- Public pages ---

    public function test_the_public_directory_lists_only_approved_listings(): void
    {
        $this->business(['name' => 'Visible Café']);
        foreach (['pending', 'rejected', 'suspended'] as $status) {
            $this->business(['status' => $status, 'name' => "Hidden {$status}"]);
        }

        $this->get('/explore')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Explore/Index')->has('businesses.data', 1)->where('businesses.data.0.name', 'Visible Café'));
    }

    public function test_guests_can_open_approved_listings_but_not_others(): void
    {
        $live = $this->business();
        $hidden = $this->business(['status' => 'pending']);

        $this->get("/explore/{$live->slug}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Explore/Show')->where('business.name', $live->name));
        $this->get("/explore/{$hidden->slug}")->assertNotFound();
    }

    public function test_private_fields_are_never_sent_to_the_public(): void
    {
        $b = $this->business(['permit_no' => 'BP-12345']);

        $this->get("/explore/{$b->slug}")->assertInertia(fn (Assert $p) => $p->missing('business.permit_no')->missing('business.owner'));
        $this->get('/explore')->assertInertia(fn (Assert $p) => $p->missing('businesses.data.0.permit_no'));
    }

    public function test_search_and_filters_narrow_the_results_and_bad_filters_are_ignored(): void
    {
        $this->business(['name' => 'Sampaguita Café', 'category' => 'cafe']);
        $this->business(['name' => 'Mabolo Hardware', 'category' => 'shop']);

        $this->get('/explore?q=Sampaguita')->assertInertia(fn (Assert $p) => $p->has('businesses.data', 1));
        $this->get('/explore?category=shop')->assertInertia(fn (Assert $p) => $p->where('businesses.data.0.name', 'Mabolo Hardware'));
        $this->get('/explore?category=bogus')->assertOk()->assertInertia(fn (Assert $p) => $p->has('businesses.data', 2));
    }

    // --- Submitting and editing ---

    public function test_a_resident_can_submit_a_listing_and_it_starts_pending(): void
    {
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload())->assertRedirect(route('businesses.index'));

        $b = Business::firstOrFail();
        $this->assertSame('pending', $b->status);
        $this->assertEquals($user->id, $b->owner_id);
        $this->assertSame('sampaguita-cafe', $b->slug);
    }

    public function test_privileged_fields_in_the_request_are_ignored(): void
    {
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload([
            'status' => 'approved', 'is_featured' => true, 'owner_id' => 999, 'slug' => 'hijack',
        ]));

        $b = Business::firstOrFail();
        $this->assertSame('pending', $b->status);
        $this->assertFalse($b->is_featured);
        $this->assertEquals($user->id, $b->owner_id);
        $this->assertSame('sampaguita-cafe', $b->slug);
    }

    public function test_duplicate_names_get_unique_slugs(): void
    {
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload());
        $this->actingAs($user)->post('/my-businesses', $this->payload());

        $this->assertEqualsCanonicalizing(['sampaguita-cafe', 'sampaguita-cafe-2'], Business::pluck('slug')->all());
    }

    public function test_listings_per_resident_are_capped(): void
    {
        config(['nagasync.directory.max_listings_per_user' => 1]);
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload());
        $this->actingAs($user)->post('/my-businesses', $this->payload(['name' => 'Second']))->assertSessionHasErrors('name');

        $this->assertSame(1, Business::count());
    }

    public function test_only_web_links_are_accepted(): void
    {
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload(['website' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('website');
        $this->actingAs($user)->post('/my-businesses', $this->payload(['website' => 'https://example.com']))
            ->assertSessionHasNoErrors();
    }

    public function test_cover_photos_are_stored_replaced_and_validated(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('citizen');

        $this->actingAs($user)->post('/my-businesses', $this->payload(['cover' => UploadedFile::fake()->image('c.jpg', 800, 600)]));
        $b = Business::firstOrFail();
        $old = $b->cover_path;
        Storage::disk('public')->assertExists($old);

        $this->actingAs($user)->post("/my-businesses/{$b->id}", $this->payload([
            '_method' => 'put', 'cover' => UploadedFile::fake()->image('d.jpg', 800, 600),
        ]));
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($b->fresh()->cover_path);

        $this->actingAs($user)->post('/my-businesses', $this->payload(['name' => 'Bad', 'cover' => UploadedFile::fake()->create('x.pdf', 50, 'application/pdf')]))
            ->assertSessionHasErrors('cover');
        $this->actingAs($user)->post('/my-businesses', $this->payload(['name' => 'Tiny', 'cover' => UploadedFile::fake()->image('t.jpg', 100, 100)]))
            ->assertSessionHasErrors('cover');
    }

    public function test_only_the_owner_can_edit_or_delete(): void
    {
        $b = $this->business();
        $stranger = $this->userWithRole('citizen');

        $this->actingAs($stranger)->put("/my-businesses/{$b->id}", $this->payload())->assertForbidden();
        $this->actingAs($stranger)->delete("/my-businesses/{$b->id}")->assertForbidden();
        $this->assertDatabaseHas('businesses', ['id' => $b->id]);
    }

    public function test_substantive_edits_go_back_to_review_but_small_ones_do_not(): void
    {
        $owner = $this->userWithRole('citizen');
        $b = $this->business(['owner_id' => $owner->id, 'name' => 'Old Name']);
        $base = ['name' => 'Old Name', 'category' => 'cafe', 'address' => '1 Demo Street', 'barangay_id' => $b->barangay_id];

        $this->actingAs($owner)->put("/my-businesses/{$b->id}", $base + ['hours' => 'Daily 8-5']);
        $this->assertSame('approved', $b->fresh()->status);

        $this->actingAs($owner)->put("/my-businesses/{$b->id}", array_merge($base, ['name' => 'New Name']));
        $this->assertSame('pending', $b->fresh()->status);
    }

    public function test_a_suspended_listing_cannot_be_released_by_editing(): void
    {
        $owner = $this->userWithRole('citizen');
        $b = $this->business(['owner_id' => $owner->id, 'status' => 'suspended', 'name' => 'Old Name']);

        $this->actingAs($owner)->put("/my-businesses/{$b->id}", [
            'name' => 'New Name', 'category' => 'cafe', 'address' => '1 Demo Street', 'barangay_id' => $b->barangay_id,
        ]);

        $this->assertSame('suspended', $b->fresh()->status);
    }

    // --- Review ---

    public function test_staff_of_the_same_barangay_can_approve_and_the_owner_is_notified(): void
    {
        Notification::fake();
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $owner = $this->userWithRole('citizen', $barangay);
        $b = $this->business(['status' => 'pending', 'owner_id' => $owner->id, 'barangay_id' => $barangay->id]);

        $this->actingAs($staff)->patch("/staff/businesses/{$b->id}", ['status' => 'approved'])->assertRedirect();

        $fresh = $b->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertEquals($staff->id, $fresh->reviewed_by);
        Notification::assertSentTo($owner, BusinessReviewed::class);
    }

    public function test_rejection_needs_a_reason(): void
    {
        $barangay = $this->barangay('A');
        $staff = $this->userWithRole('barangay_staff', $barangay);
        $b = $this->business(['status' => 'pending', 'barangay_id' => $barangay->id]);

        $this->actingAs($staff)->patch("/staff/businesses/{$b->id}", ['status' => 'rejected'])->assertSessionHasErrors('note');
        $this->assertSame('pending', $b->fresh()->status);
    }

    public function test_staff_of_another_barangay_cannot_review(): void
    {
        $b = $this->business(['status' => 'pending', 'barangay_id' => $this->barangay('A')->id]);
        $outsider = $this->userWithRole('barangay_staff', $this->barangay('B'));

        $this->actingAs($outsider)->patch("/staff/businesses/{$b->id}", ['status' => 'approved'])->assertForbidden();
    }

    public function test_invalid_status_changes_are_refused(): void
    {
        $admin = $this->userWithRole('admin');
        $b = $this->business(['status' => 'rejected']);

        $this->actingAs($admin)->patch("/staff/businesses/{$b->id}", ['status' => 'approved'])->assertStatus(422);
    }

    public function test_only_administrators_can_feature_a_listing(): void
    {
        $barangay = $this->barangay('A');
        $b = $this->business(['barangay_id' => $barangay->id]);

        $this->actingAs($this->userWithRole('barangay_staff', $barangay))
            ->patch("/staff/businesses/{$b->id}", ['is_featured' => true])->assertForbidden();
        $this->assertFalse($b->fresh()->is_featured);

        $this->actingAs($this->userWithRole('admin'))->patch("/staff/businesses/{$b->id}", ['is_featured' => true]);
        $this->assertTrue($b->fresh()->is_featured);
    }
}