<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Arr;

trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                // A model can list attributes that must never be copied into the audit log
                $skip = array_merge(['password', 'remember_token'], $model->auditHidden ?? []);

                $new = match ($event) {
                    'created' => $model->getAttributes(),
                    'updated' => $model->getChanges(),
                    default   => null,
                };
                $old = match ($event) {
                    'updated' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                    'deleted' => $model->getOriginal(),
                    default   => null,
                };

                $new = $new ? Arr::except($new, $skip) : null;
                $old = $old ? Arr::except($old, $skip) : null;

                // An update that only touched hidden fields has nothing worth recording
                if ($event === 'updated' && empty(Arr::except($new ?? [], ['updated_at']))) {
                    return;
                }

                AuditLog::create([
                    'user_id'        => auth()->id(),
                    'action'         => $event,
                    'auditable_type' => $model->getMorphClass(),
                    'auditable_id'   => $model->getKey(),
                    'old_values'     => $old,
                    'new_values'     => $new,
                    'ip_address'     => request()?->ip(),
                    'user_agent'     => request()?->userAgent(),
                ]);
            });
        }
    }
}