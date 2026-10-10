<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmergencyAlert;
use Tests\TestCase;

class RetentionTest extends TestCase
{
    private function closedAlert(int $resolvedDaysAgo): EmergencyAlert
    {
        $citizen = $this->userWithRole('citizen');

        return EmergencyAlert::create([
            'user_id' => $citizen->id, 'alert_type' => 'fire', 'status' => 'resolved',
            'latitude' => 13.62, 'longitude' => 123.19, 'accuracy_m' => 10, 'note' => 'near the market',
            'resolved_at' => now()->subDays($resolvedDaysAgo),
        ]);
    }

    public function test_old_closed_alerts_lose_their_location_but_keep_the_record(): void
    {
        $alert = $this->closedAlert((int) config('nagasync.retention.emergency_location_days') + 10);

        $this->artisan('nagasync:prune')->assertSuccessful();

        $fresh = $alert->fresh();
        $this->assertNull($fresh->latitude);
        $this->assertNull($fresh->note);
        $this->assertSame('resolved', $fresh->status);
    }

    public function test_recent_and_open_alerts_keep_their_location(): void
    {
        $recent = $this->closedAlert(5);
        $open = EmergencyAlert::create([
            'user_id' => $this->userWithRole('citizen')->id, 'alert_type' => 'medical',
            'latitude' => 13.6, 'longitude' => 123.1,
        ]);

        $this->artisan('nagasync:prune')->assertSuccessful();

        $this->assertNotNull($recent->fresh()->latitude);
        $this->assertNotNull($open->fresh()->latitude);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $alert = $this->closedAlert((int) config('nagasync.retention.emergency_location_days') + 10);

        $this->artisan('nagasync:prune --dry-run')->assertSuccessful();

        $this->assertNotNull($alert->fresh()->latitude);
    }

    public function test_locations_are_not_copied_into_the_audit_log(): void
    {
        $alert = $this->closedAlert(1);

        $log = AuditLog::where('auditable_type', EmergencyAlert::class)->where('auditable_id', $alert->id)->firstOrFail();

        foreach (['latitude', 'longitude', 'accuracy_m', 'note'] as $key) {
            $this->assertArrayNotHasKey($key, $log->new_values);
        }
        $this->assertArrayHasKey('alert_type', $log->new_values);
    }

    public function test_historical_audit_rows_are_scrubbed(): void
    {
        AuditLog::create([
            'action' => 'created', 'auditable_type' => EmergencyAlert::class, 'auditable_id' => 999,
            'new_values' => ['status' => 'active', 'latitude' => 13.6, 'longitude' => 123.1],
        ]);

        $this->artisan('nagasync:prune')->assertSuccessful();

        $row = AuditLog::where('auditable_id', 999)->firstOrFail();
        $this->assertArrayNotHasKey('latitude', $row->new_values);
        $this->assertSame('active', $row->new_values['status']);
    }

    public function test_expired_audit_rows_are_deleted_and_recent_ones_kept(): void
    {
        $old = new AuditLog(['action' => 'created', 'auditable_type' => 'X', 'auditable_id' => 1]);
        $old->forceFill(['created_at' => now()->subDays((int) config('nagasync.retention.audit_log_days') + 5)])->save();
        $recent = AuditLog::create(['action' => 'created', 'auditable_type' => 'X', 'auditable_id' => 2]);

        $this->artisan('nagasync:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }
}