<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmergencyController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Emergency/Index', [
            'types'  => EmergencyAlert::TYPES,
            'alerts' => EmergencyAlert::where('user_id', $request->user()->id)->latest()->take(5)->get()
                ->map(fn ($a) => [
                    'id' => $a->id, 'type' => $a->alert_type, 'status' => $a->status,
                    'at' => $a->created_at->diffForHumans(),
                    'located' => $a->latitude !== null,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'alert_type' => ['required', Rule::in(EmergencyAlert::TYPES)],
            'latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $alert = EmergencyAlert::where('user_id', $user->id)
            ->whereIn('status', ['active', 'dispatched'])
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest()->first();

        if ($alert) {
            // Repeat press: refresh the open alert, but never overwrite a good fix with an empty one
            $alert->update(array_filter(
                Arr::only($data, ['latitude', 'longitude', 'accuracy_m', 'note']),
                fn ($v) => $v !== null && $v !== ''
            ));
        } else {
            $alert = EmergencyAlert::create($data + [
                'user_id'     => $user->id,
                'barangay_id' => $user->barangay_id,
            ]);
        }

        $alert->announce();

        return back();
    }
}