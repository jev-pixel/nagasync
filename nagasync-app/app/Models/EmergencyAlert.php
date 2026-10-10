<?php

namespace App\Models;

use App\Events\EmergencyAlertChanged;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class EmergencyAlert extends Model
{
    use Auditable;

    public const TYPES = ['medical', 'fire', 'crime', 'flood', 'other'];

    public const TRANSITIONS = [
        'active'      => ['dispatched', 'resolved', 'false_alarm'],
        'dispatched'  => ['resolved', 'false_alarm'],
        'resolved'    => [],
        'false_alarm' => [],
    ];

    protected $fillable = [
        'user_id', 'barangay_id', 'alert_type', 'latitude', 'longitude', 'accuracy_m', 'note',
        'status', 'acknowledged_by', 'acknowledged_at', 'resolved_at',
    ];

    // Location and free text never go into the audit log
public array $auditHidden = ['latitude', 'longitude', 'accuracy_m', 'note'];
// Mirror the database defaults so a fresh model has them without a refresh
protected $attributes = ['status' => 'active', 'alert_type' => 'other'];
    protected $casts = [
        'latitude' => 'float', 'longitude' => 'float',
        'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function acknowledger() { return $this->belongsTo(User::class, 'acknowledged_by'); }

    /** Notify live listeners. A broadcast failure must never block an emergency report. */
    public function announce(): void
    {
        try {
            EmergencyAlertChanged::dispatch($this->id, $this->user_id, $this->status);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}