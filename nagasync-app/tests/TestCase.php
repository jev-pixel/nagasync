<?php

namespace Tests;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests check backend behaviour; they must not depend on the state of the Vite build
        $this->withoutVite();
    }

    // Belt and braces: refuse to run against anything that isn't a *_test database
    protected function setUpTraits()
    {
        $db = config('database.connections.' . config('database.default') . '.database');

        if (! is_string($db) || ! str_ends_with($db, '_test')) {
            throw new \RuntimeException("Refusing to run tests against '{$db}': the database name must end in _test.");
        }

        return parent::setUpTraits();
    }

    protected function barangay(string $name = 'Test Barangay'): Barangay
    {
        return Barangay::firstOrCreate(['name' => $name]);
    }

    protected function userWithRole(string $role, ?Barangay $barangay = null, array $attrs = []): User
    {
        return User::factory()->create(
            ['role' => $role, 'barangay_id' => ($barangay ?? $this->barangay())->id] + $attrs
        );
    }
}