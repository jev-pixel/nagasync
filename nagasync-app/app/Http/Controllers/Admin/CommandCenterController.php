<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DocumentRequest;
use App\Models\EmergencyAlert;
use App\Models\MunicipalPayment;
use App\Services\Analytics\MarketAnalytics;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommandCenterController extends Controller
{
    public function index(MarketAnalytics $market)
    {
        $counts = DocumentRequest::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $statuses = collect(array_keys(DocumentRequest::TRANSITIONS))
            ->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)]);

        $avgMinutes = DocumentRequest::whereNotNull('issued_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, issued_at)'));

        $byBarangay = DB::table('document_requests')
            ->join('barangays', 'barangays.id', '=', 'document_requests.barangay_id')
            ->select('barangays.name', DB::raw('count(*) as total'))
            ->groupBy('barangays.name')->orderByDesc('total')->limit(10)->get();

        $from = now()->subDays(13)->startOfDay();
        $perDay = DocumentRequest::where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')->pluck('total', 'day');

        $trend = collect(range(0, 13))->map(function ($i) use ($from, $perDay) {
            $d = $from->copy()->addDays($i);

            return ['day' => $d->format('M j'), 'total' => (int) ($perDay[$d->toDateString()] ?? 0)];
        });

        $activity = AuditLog::with('user:id,name')->latest()->take(10)->get()->map(fn ($a) => [
            'id'     => $a->id,
            'action' => $a->action,
            'what'   => class_basename($a->auditable_type) . ' #' . $a->auditable_id,
            'by'     => $a->user?->name ?? 'System',
            'at'     => $a->created_at->diffForHumans(),
        ]);

        // --- Operations overview (aggregates only: no names, no locations) ---
        $paidToday = MunicipalPayment::where('status', 'paid')->whereDate('paid_at', today());
        $ack = EmergencyAlert::whereNotNull('acknowledged_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)'));
        $trends = $market->trends();

        $operations = [
            'emergencies' => [
                'active'        => EmergencyAlert::where('status', 'active')->count(),
                'open'          => EmergencyAlert::whereIn('status', ['active', 'dispatched'])->count(),
                'today'         => EmergencyAlert::whereDate('created_at', today())->count(),
                'avgAckMinutes' => $ack !== null ? round((float) $ack, 1) : null,
            ],
            'payments' => [
                'paidToday'  => (clone $paidToday)->count(),
                'grossToday' => (int) (clone $paidToday)->sum('total'), // centavos
                'pending'    => MunicipalPayment::where('status', 'pending')->count(),
            ],
            'market' => [
                'available' => $trends['available'],
                'flagged'   => collect($trends['data'])
                    ->whereIn('status', ['alert_high', 'over_srp'])->values()
                    ->map(fn ($t) => [
                        'commodity' => $t['commodity'], 'unit' => $t['unit'],
                        'price' => $t['latest_price'], 'status' => $t['status'],
                    ]),
            ],
        ];

        return Inertia::render('Admin/CommandCenter', [
            'statuses'   => $statuses,
            'total'      => $statuses->sum(),
            'avgHours'   => $avgMinutes ? round($avgMinutes / 60, 1) : null,
            'byBarangay' => $byBarangay,
            'trend'      => $trend,
            'activity'   => $activity,
            'operations' => $operations,
        ]);
    }
}