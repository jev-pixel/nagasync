<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\EmergencyAlert;
use App\Models\MunicipalPayment;
use App\Models\Poll;
use App\Models\PollParticipation;
use App\Models\User;
use App\Services\Analytics\MarketAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MarketAnalytics $market)
    {
        $user = $request->user();
        $role = $user->role;
        $url = fn (string $name) => route($name, absolute: false);
        $peso = fn (int $centavos) => '₱' . number_format($centavos / 100, 2);
        $ratio = fn (int $part, int $whole) => $whole > 0 ? (int) round($part / $whole * 100) : 0;

        // Citizens see their own records, barangay staff their barangay (none if unassigned), everyone else all
        $scope = fn ($query) => match ($role) {
            'citizen' => $query->where('user_id', $user->id),
            'barangay_staff' => $user->barangay_id ? $query->where('barangay_id', $user->barangay_id) : $query->whereRaw('1 = 0'),
            default => $query,
        };
        $pays = $scope(MunicipalPayment::query());

        // Documents are also limited by office: barangay staff never see City Hall requests
        // (even from their own barangay), and City Hall staff only see City Hall requests.
        $docs = $scope(DocumentRequest::query());
        $docs = in_array($role, ReviewAccess::ROLES, true) ? ReviewAccess::scopeQueue($user, $docs) : $docs;

        // City Hall clerks work from the Counter (what is waiting, find a request, release it)
        if ($role === 'city_staff') {
            return redirect()->route('staff.index');
        }

        // Barangay staff get their own desk inside the command frame
        if ($role === 'barangay_staff') {
            return $this->barangayHome($request, $docs);
        }

        $openStatuses = ['pending', 'processing', 'needs_resubmission'];
        $docCount = fn (array $statuses) => (clone $docs)->whereIn('status', $statuses)->count();
        $paidToday = (clone $pays)->where('status', 'paid')->whereDate('paid_at', today());

        $openPolls = Poll::where('is_published', true)
            ->where('opens_at', '<=', now())->where('closes_at', '>=', now())
            ->where(fn ($q) => $q->whereNull('barangay_id')->orWhere('barangay_id', $user->barangay_id))
            ->whereNotIn('id', PollParticipation::where('user_id', $user->id)->select('poll_id'))
            ->orderBy('closes_at')->get(['id', 'title', 'closes_at'])
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'closes_at' => $p->closes_at->toDayDateTimeString()]);

        $trends = $role === 'admin' ? $market->trends() : null;
        $metric = fn (string $label, $value, string $tone, string $icon, string $href) => compact('label', 'value', 'tone', 'icon', 'href');

        $metrics = match ($role) {
            'barangay_staff' => [
                $metric('Pending', $docCount(['pending']), 'orange', 'list', $url('staff.documents.index')),
                $metric('Processing', $docCount(['processing']), 'teal', 'file', $url('staff.documents.index')),
                $metric('Approved today', (clone $docs)->where('status', 'approved')->whereDate('issued_at', today())->count(), 'violet', 'check', $url('staff.documents.index')),
                $metric('Ready for the counter', $docCount(['approved']), 'coral', 'wallet', $url('staff.index')),
            ],
            'city_staff' => [
                $metric('Awaiting review', $docCount($openStatuses), 'orange', 'list', $url('staff.documents.index')),
                $metric('Fee not assessed', (clone $docs)->whereIn('status', $openStatuses)->whereNull('fee_total')->count(), 'coral', 'file', $url('staff.documents.index')),
                $metric('Ready for the counter', $docCount(['approved']), 'violet', 'check', $url('staff.documents.index')),
                $metric('Released today', (clone $docs)->where('status', 'released')->whereDate('released_at', today())->count(), 'teal', 'check', $url('staff.documents.index')),
            ],
            'mdrrmo' => [
                $metric('Active alerts', EmergencyAlert::where('status', 'active')->count(), 'coral', 'siren', $url('dispatch.index')),
                $metric('Dispatched', EmergencyAlert::where('status', 'dispatched')->count(), 'orange', 'siren', $url('dispatch.index')),
                $metric('Resolved today', EmergencyAlert::where('status', 'resolved')->whereDate('resolved_at', today())->count(), 'teal', 'check', $url('dispatch.index')),
                $metric('Avg. response', ($ack = EmergencyAlert::whereNotNull('acknowledged_at')
                    ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)'))) !== null ? round((float) $ack, 1) . ' min' : '—', 'violet', 'gauge', $url('dispatch.index')),
            ],
            'treasury' => [
                $metric('Collected today', $peso((int) (clone $paidToday)->sum('total')), 'orange', 'wallet', $url('treasury.payments')),
                $metric('Paid today', (clone $paidToday)->count(), 'teal', 'check', $url('treasury.payments')),
                $metric('Pending', (clone $pays)->where('status', 'pending')->count(), 'violet', 'list', $url('treasury.payments')),
                $metric('Failed', (clone $pays)->where('status', 'failed')->count(), 'coral', 'wallet', $url('treasury.payments')),
            ],
            'admin' => [
                // Straight to the document queue, where requests are opened, verified and approved
                $metric('Awaiting review', $docCount($openStatuses), 'violet', 'file', $url('staff.documents.index')),
                $metric('Open emergencies', EmergencyAlert::whereIn('status', ['active', 'dispatched'])->count(), 'coral', 'siren', $url('dispatch.index')),
                $metric('Paid today', $peso((int) (clone $paidToday)->sum('total')), 'orange', 'wallet', $url('treasury.payments')),
                $metric('Market alerts', collect($trends['data'])->whereIn('status', ['alert_high', 'over_srp'])->count(), 'teal', 'tag', $url('market.index')),
            ],
            default => [ // citizen
                $metric('Open requests', $docCount($openStatuses), 'orange', 'file', $url('documents.index')),
                $metric('Needs your action', $docCount(['needs_resubmission']), 'coral', 'list', $url('documents.index')),
                $metric('Approved documents', $docCount(['approved', 'released']), 'teal', 'check', $url('documents.index')),
                $metric('Polls open', $openPolls->count(), 'violet', 'vote', $url('polls.index')),
            ],
        };

        // Gauges
        if ($role === 'mdrrmo') {
            $alerts = EmergencyAlert::count();
            $gauges = [
                ['label' => 'Alerts closed', 'sub' => 'Resolved or false alarm', 'tone' => 'teal',
                    'percent' => $ratio(EmergencyAlert::whereIn('status', ['resolved', 'false_alarm'])->count(), $alerts)],
                ['label' => 'Alerts responded to', 'sub' => 'Acknowledged by a responder', 'tone' => 'orange',
                    'percent' => $ratio(EmergencyAlert::whereNotNull('acknowledged_at')->count(), $alerts)],
            ];
        } else {
            $gauges = [
                ['label' => 'Requests completed', 'sub' => 'Approved, released, rejected or revoked', 'tone' => 'coral',
                    'percent' => $ratio($docCount(['approved', 'released', 'rejected', 'revoked']), (clone $docs)->count())],
                $role === 'citizen'
                    ? ['label' => 'Documents released', 'sub' => 'Released versus approved', 'tone' => 'orange',
                        'percent' => $ratio($docCount(['released']), $docCount(['approved', 'released']))]
                : ($role === 'city_staff'
                    ? ['label' => 'Fees assessed', 'sub' => 'Open requests with a fee set', 'tone' => 'orange',
                        'percent' => $ratio((clone $docs)->whereIn('status', $openStatuses)->whereNotNull('fee_total')->count(), $docCount($openStatuses))]
                    : ['label' => 'Payment success', 'sub' => 'Paid versus failed', 'tone' => 'orange',
                        'percent' => $ratio((clone $pays)->where('status', 'paid')->count(), (clone $pays)->whereIn('status', ['paid', 'failed'])->count())]),
            ];
        }

        // This week's activity (Monday to Sunday)
        [$activity, $activityLabel] = match ($role) {
            'mdrrmo' => [EmergencyAlert::query(), 'Alerts this week'],
            'treasury' => [MunicipalPayment::query(), 'Payments this week'],
            default => [clone $docs, 'Requests this week'],
        };
        $start = now()->startOfWeek();
        $perDay = $activity->where('created_at', '>=', $start)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')->pluck('total', 'day');
        $week = collect(range(0, 6))->map(function ($i) use ($start, $perDay) {
            $d = $start->copy()->addDays($i);

            return ['label' => $d->format('D'), 'date' => $d->day, 'count' => (int) ($perDay[$d->toDateString()] ?? 0), 'today' => $d->isToday()];
        })->values();

        // Hero
        $first = Str::before($user->name, ' ');
        $hero = match ($role) {
            'barangay_staff' => ['subtitle' => $docCount(['pending']) . ' request(s) are waiting for review.', 'label' => 'Open the queue', 'href' => $url('staff.documents.index')],
            'city_staff' => ['subtitle' => $docCount($openStatuses) . ' City Hall request(s) are waiting for review.', 'label' => 'Open the queue', 'href' => $url('staff.documents.index')],
            'mdrrmo' => ['subtitle' => 'Keep an eye on incoming alerts.', 'label' => 'Open dispatch', 'href' => $url('dispatch.index')],
            'treasury' => ['subtitle' => 'Collections and reconciliation at a glance.', 'label' => 'Open the ledger', 'href' => $url('treasury.payments')],
            'admin' => ['subtitle' => 'Everything across the municipality, live.', 'label' => 'Command center', 'href' => $url('admin.command-center')],
            default => ['subtitle' => 'Request documents, pay fees and stay informed.', 'label' => 'Request a document', 'href' => $url('documents.index')],
        };

        $props = [
            'hero' => ['title' => "Hello, {$first}!", 'subtitle' => $hero['subtitle'], 'cta' => ['label' => $hero['label'], 'href' => $hero['href']]],
            'metrics' => $metrics,
            'gauges' => $gauges,
            'week' => $week,
            'rangeLabel' => $start->format('M j') . ' – ' . $start->copy()->endOfWeek()->format('M j'),
            'activityLabel' => $activityLabel,
            'openPolls' => $openPolls,
            'canVote' => $user->resident_verified_at !== null && $user->barangay_id !== null,
        ];

        // System status is for administrators only
        if ($role === 'admin') {
            $waiting = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $safe = fn (string $value, string $bad) => ! app()->isProduction() || $value !== $bad;

            $props['system'] = [
                ['label' => 'Analytics service', 'ok' => $trends['available'], 'detail' => $trends['available'] ? 'Responding' : 'Unavailable'],
                ['label' => 'Job queue', 'ok' => $failed === 0, 'detail' => "{$waiting} waiting · {$failed} failed"],
                ['label' => 'SMS', 'ok' => $safe(config('nagasync.sms.driver'), 'log'), 'detail' => config('nagasync.sms.driver') . ' driver'],
                ['label' => 'Payments', 'ok' => $safe(config('nagasync.payments.gateway'), 'sandbox'), 'detail' => config('nagasync.payments.gateway') . ' gateway'],
            ];
        }

        return Inertia::render('Dashboard', $props);
    }

    /**
     * Barangay staff home: their barangay only. Residents waiting for verification come first
     * (verifying is what unlocks a citizen's account), then the requests waiting for review.
     */
    private function barangayHome(Request $request, $docs)
    {
        $user = $request->user();
        $barangayId = $user->barangay_id;

        // An account with no barangay must see nothing (never every resident without a barangay)
        $residents = fn () => $barangayId
            ? User::where('role', 'citizen')->where('barangay_id', $barangayId)
            : User::whereRaw('1 = 0');

        $waiting = fn () => (clone $docs)->whereIn('status', ['pending', 'processing']);

        return Inertia::render('Staff/BarangayHome', [
            'barangay' => $user->barangay?->name,
            'stats' => [
                'unverified'    => $residents()->whereNull('resident_verified_at')->count(),
                'awaiting'      => $waiting()->count(),
                'ready'         => (clone $docs)->where('status', 'approved')->count(),
                'releasedToday' => (clone $docs)->where('status', 'released')->whereDate('released_at', today())->count(),
            ],
            // Longest-waiting first, so nobody is left behind
            'unverifiedResidents' => $residents()->whereNull('resident_verified_at')
                ->oldest()->limit(8)->get(['id', 'name', 'email', 'phone', 'created_at'])
                ->map(fn ($u) => [
                    'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                    'registered' => $u->created_at->toFormattedDateString(),
                    'registered_human' => $u->created_at->diffForHumans(),
                ])->values(),
            'queue' => $waiting()->with('user:id,name')->oldest()->limit(8)->get()
                ->map(fn ($d) => [
                    'id' => $d->id, 'tracking_number' => $d->tracking_number,
                    'document_type' => $d->document_type, 'status' => $d->status,
                    'citizen' => $d->user?->name, 'created_human' => $d->created_at->diffForHumans(),
                ])->values(),
        ]);
    }
}