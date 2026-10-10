<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\MunicipalPayment;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LedgerController extends Controller
{
    public function index()
    {
        $types = config('nagasync.payment_types');
        $paid = fn () => MunicipalPayment::where('status', 'paid');

        $summary = [
            'paidCount' => $paid()->count(),
            'collected' => (int) $paid()->sum('amount'),
            'fees'      => (int) $paid()->sum('convenience_fee'),
            'gross'     => (int) $paid()->sum('total'),
            'today'     => (int) $paid()->whereDate('paid_at', today())->sum('total'),
            'pending'   => MunicipalPayment::where('status', 'pending')->count(),
            'failed'    => MunicipalPayment::where('status', 'failed')->count(),
        ];

        $byType = $paid()
            ->select('payment_type', DB::raw('count(*) as count'), DB::raw('sum(amount) as amount'))
            ->groupBy('payment_type')->get()
            ->map(fn ($r) => [
                'label'  => $types[$r->payment_type]['label'] ?? $r->payment_type,
                'count'  => (int) $r->count,
                'amount' => (int) $r->amount,
            ]);

        $payments = MunicipalPayment::with(['user:id,name', 'barangay:id,name'])->latest()->paginate(20)
            ->through(fn ($p) => [
                'id' => $p->id, 'reference_no' => $p->reference_no,
                'label' => $types[$p->payment_type]['label'] ?? $p->payment_type,
                'citizen' => $p->user?->name, 'barangay' => $p->barangay?->name,
                'total' => $p->total, 'status' => $p->status,
                'when' => ($p->paid_at ?? $p->created_at)->toDayDateTimeString(),
            ]);

        return Inertia::render('Treasury/Ledger', compact('summary', 'byType', 'payments'));
    }
}