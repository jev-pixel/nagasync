<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EmergencyAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class PruneSensitiveData extends Command
{
    protected $signature = 'nagasync:prune {--dry-run : Show what would change without changing anything}';

    protected $description = 'Apply data retention: strip old emergency locations, scrub audit copies, delete expired audit logs';

    private const LOCATION_KEYS = ['latitude', 'longitude', 'accuracy_m', 'note'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $locationDays = (int) config('nagasync.retention.emergency_location_days');
        $auditDays = (int) config('nagasync.retention.audit_log_days');

        // 1. Location detail on closed alerts past retention (the alert row stays, for statistics)
        $alerts = EmergencyAlert::whereIn('status', ['resolved', 'false_alarm'])
            ->where('resolved_at', '<', now()->subDays($locationDays))
            ->where(fn ($q) => $q->whereNotNull('latitude')->orWhereNotNull('longitude')
                ->orWhereNotNull('accuracy_m')->orWhereNotNull('note'));
        $alertCount = (clone $alerts)->count();

        if (! $dry) {
            $alerts->update(['latitude' => null, 'longitude' => null, 'accuracy_m' => null, 'note' => null]);
        }

        // 2. Location copies left in historical audit rows (from before they were excluded)
        $scrubbed = 0;
        $keys = array_flip(self::LOCATION_KEYS);

        AuditLog::where('auditable_type', EmergencyAlert::class)->chunkById(500, function ($rows) use ($dry, $keys, &$scrubbed) {
            foreach ($rows as $row) {
                $old = $row->old_values ?? [];
                $new = $row->new_values ?? [];

                if (! array_intersect_key($old, $keys) && ! array_intersect_key($new, $keys)) {
                    continue;
                }

                $scrubbed++;

                if (! $dry) {
                    $row->old_values = Arr::except($old, self::LOCATION_KEYS) ?: null;
                    $row->new_values = Arr::except($new, self::LOCATION_KEYS) ?: null;
                    $row->save();
                }
            }
        });

        // 3. Audit entries past their retention period
        $expired = AuditLog::where('created_at', '<', now()->subDays($auditDays));
        $expiredCount = (clone $expired)->count();

        if (! $dry) {
            $expired->delete();
        }

        $this->components->info($dry ? 'DRY RUN: nothing was changed.' : 'Retention applied.');
        $this->table(['Action', 'Rows'], [
            ["Closed alerts: strip location/note (older than {$locationDays} days)", $alertCount],
            ['Audit rows: remove location copies', $scrubbed],
            ["Audit rows: delete (older than {$auditDays} days)", $expiredCount],
        ]);

        return self::SUCCESS;
    }
}