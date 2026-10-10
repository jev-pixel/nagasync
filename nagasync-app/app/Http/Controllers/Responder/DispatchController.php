<?php

namespace App\Http\Controllers\Responder;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DispatchController extends Controller
{
    public function index()
    {
        $with = ['user:id,name,phone', 'barangay:id,name', 'acknowledger:id,name'];
        $map = fn ($a) => [
            'id' => $a->id, 'type' => $a->alert_type, 'status' => $a->status, 'note' => $a->note,
            'citizen' => $a->user?->name, 'phone' => $a->user?->phone, 'barangay' => $a->barangay?->name,
            'lat' => $a->latitude, 'lng' => $a->longitude, 'accuracy' => $a->accuracy_m,
            'at' => $a->created_at->diffForHumans(), 'ack_by' => $a->acknowledger?->name,
        ];

        return Inertia::render('Responder/Dispatch/Index', [
            'open' => EmergencyAlert::with($with)->whereIn('status', ['active', 'dispatched'])
                ->orderByRaw("status = 'active' desc")->oldest()->get()->map($map),
            'recent' => EmergencyAlert::with($with)->whereIn('status', ['resolved', 'false_alarm'])
                ->latest('updated_at')->take(10)->get()->map($map),
            'transitions' => EmergencyAlert::TRANSITIONS,
        ]);
    }

    public function update(Request $request, EmergencyAlert $emergencyAlert)
    {
        $status = $request->validate([
            'status' => ['required', Rule::in(['dispatched', 'resolved', 'false_alarm'])],
        ])['status'];

        $alert = DB::transaction(function () use ($emergencyAlert, $status, $request) {
            $a = EmergencyAlert::whereKey($emergencyAlert->id)->lockForUpdate()->firstOrFail();

            abort_unless(in_array($status, EmergencyAlert::TRANSITIONS[$a->status], true), 422, 'Invalid status change.');

            $a->update([
                'status'          => $status,
                'acknowledged_by' => $a->acknowledged_by ?? $request->user()->id,
                'acknowledged_at' => $a->acknowledged_at ?? now(),
                'resolved_at'     => in_array($status, ['resolved', 'false_alarm'], true) ? now() : null,
            ]);

            return $a;
        });

        $alert->announce();

        return back();
    }
}