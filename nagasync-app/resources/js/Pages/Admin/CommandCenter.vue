<script setup>
/**
 * Admin Mission Control: what is happening now, what needs attention, and what can I do about it.
 * Runs inside the command frame (CommandLayout), so live status lives in the bottom status bar
 * and details open in the right context panel instead of sending the admin to another page.
 * Only numbers the system really tracks are shown.
 */
import ContextPanel from '@/Components/ContextPanel.vue';
import KpiCard from '@/Components/KpiCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { pesoWhole } from '@/utils/money';
import { useLiveData } from '@/utils/useLiveData';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, AlertTriangle, ArrowRight, CheckCircle2, ChevronRight, FileText, Siren, Store, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    statuses: Object, total: Number, avgHours: Number,
    byBarangay: Array, trend: Array, activity: Array, operations: Object,
});

// Refresh only what changes quickly; the status bar shows how fresh it is
useLiveData({ only: ['statuses', 'total', 'avgHours', 'activity', 'operations'], interval: 10000 });

const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
const ops = computed(() => props.operations);
const paidGross = computed(() => pesoWhole(ops.value.payments.grossToday / 100)); // stored in centavos

const maxTrend = computed(() => Math.max(1, ...props.trend.map((t) => t.total)));
const maxBgy = computed(() => Math.max(1, ...props.byBarangay.map((b) => b.total)));

// Every KPI leads somewhere. The `status` filter is ignored harmlessly if the queue does not read it yet.
const kpis = computed(() => [
    { label: 'Pending requests', value: props.statuses.pending ?? 0, tone: (props.statuses.pending ?? 0) > 0 ? 'warning' : 'normal',
      hint: `${props.statuses.processing ?? 0} being reviewed`, href: route('staff.documents.index', { status: 'pending' }), action: 'Review queue' },
    { label: 'Emergencies awaiting', value: ops.value.emergencies.active, tone: ops.value.emergencies.active > 0 ? 'critical' : 'normal',
      hint: `${ops.value.emergencies.open} open including dispatched`, href: route('dispatch.index'), action: 'Open dispatch' },
    { label: 'Payments not completed', value: ops.value.payments.pending, tone: ops.value.payments.pending > 0 ? 'warning' : 'normal',
      hint: `${ops.value.payments.paidToday} paid today`, href: route('treasury.payments'), action: 'View payments' },
    { label: 'Collected today', value: paidGross.value, tone: 'normal',
      hint: plural(ops.value.payments.paidToday, 'paid transaction'), href: route('treasury.payments'), action: 'See transactions' },
    { label: 'Market flags', value: ops.value.market.available ? ops.value.market.flagged.length : '-',
      tone: ops.value.market.available && ops.value.market.flagged.length ? 'warning' : 'normal',
      hint: ops.value.market.available ? 'commodities with unusual prices' : 'analysis unavailable', href: route('market.index'), action: 'See prices' },
    { label: 'Avg. time to issue', value: props.avgHours ?? '-', tone: 'normal',
      hint: 'hours, lower is faster' },
]);

const STATUS_ORDER = ['pending', 'processing', 'approved', 'rejected', 'revoked'];

// Priority queue ("Needs your attention"), most urgent first
const attention = computed(() => {
    const items = [];
    if (ops.value.emergencies.active > 0) {
        items.push({ key: 'emg', urgent: true, icon: Siren, text: `${plural(ops.value.emergencies.active, 'emergency alert')} awaiting response` });
    }
    if ((props.statuses.pending ?? 0) > 0) {
        items.push({ key: 'docs', urgent: false, icon: FileText, text: `${plural(props.statuses.pending, 'document request')} waiting for review` });
    }
    if (ops.value.payments.pending > 0) {
        items.push({ key: 'pay', urgent: false, icon: Wallet, text: `${plural(ops.value.payments.pending, 'payment')} not yet completed` });
    }
    if (ops.value.market.available && ops.value.market.flagged.length > 0) {
        items.push({ key: 'mkt', urgent: false, icon: Store, text: `${plural(ops.value.market.flagged.length, 'commodity')} flagged for unusual prices` });
    }
    return items;
});

// Clicking a row opens its details here instead of navigating away
const selectedKey = ref(null);
const selected = computed(() => attention.value.find((a) => a.key === selectedKey.value) ?? null);

const detail = computed(() => {
    const e = ops.value.emergencies, p = ops.value.payments, m = ops.value.market, s = props.statuses;
    switch (selectedKey.value) {
        case 'emg': return {
            title: 'Emergency alerts', subtitle: plural(e.active, 'alert') + ' awaiting response',
            rows: [['Open (including dispatched)', e.open], ['Raised today', e.today], ['Average response', `${e.avgAckMinutes ?? '-'} min`]],
            href: route('dispatch.index'), label: 'Open dispatch',
        };
        case 'docs': return {
            title: 'Document requests', subtitle: plural(s.pending ?? 0, 'request') + ' waiting for review',
            rows: [['Being reviewed', s.processing ?? 0], ['Approved', s.approved ?? 0], ['Rejected', s.rejected ?? 0], ['Average time to issue', `${props.avgHours ?? '-'} hrs`]],
            href: route('staff.documents.index', { status: 'pending' }), label: 'Review requests',
        };
        case 'pay': return {
            title: 'Payments', subtitle: plural(p.pending, 'payment') + ' not yet completed',
            rows: [['Paid today', p.paidToday], ['Collected today', paidGross.value]],
            href: route('treasury.payments'), label: 'View payments',
        };
        case 'mkt': return {
            title: 'Market alerts', subtitle: plural(m.flagged.length, 'commodity') + ' flagged',
            rows: m.flagged.map((f) => [f.commodity, f.status === 'over_srp' ? 'Above SRP' : 'Unusually high']),
            href: route('market.index'), label: 'See prices',
        };
        default: return null;
    }
});
</script>

<template>
    <Head title="Mission Control" />
    <AuthenticatedLayout>
        <template #header>Mission Control</template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- What needs attention -->
            <section aria-label="Needs attention">
                <div v-if="attention.length" class="rounded-3xl bg-[#F1E8F6] p-5">
                    <h2 class="flex items-center gap-2 text-lg font-extrabold text-ns-ink">
                        <AlertTriangle class="h-5 w-5" aria-hidden="true" /> Needs your attention
                    </h2>
                    <ul class="mt-4 grid gap-3 md:grid-cols-2">
                        <li v-for="i in attention" :key="i.key">
                            <button type="button" :aria-pressed="selectedKey === i.key" @click="selectedKey = i.key"
                                    :class="['flex w-full items-center justify-between gap-3 rounded-2xl bg-white p-4 text-left ring-1 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                                             selectedKey === i.key ? 'ring-2 ring-ns-violet' : i.urgent ? 'ring-red-300 hover:ring-red-400' : 'ring-black/5 hover:ring-ns-violet/40']">
                                <span class="flex items-center gap-3">
                                    <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', i.urgent ? 'bg-red-100 text-red-600' : 'bg-[#F1E8F6] text-ns-ink']">
                                        <component :is="i.icon" class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <span class="text-sm font-semibold text-ns-ink">{{ i.text }}</span>
                                </span>
                                <ChevronRight class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                            </button>
                        </li>
                    </ul>
                </div>
                <div v-else class="flex items-center gap-3 rounded-3xl bg-emerald-50 p-5 text-emerald-800 ring-1 ring-emerald-200">
                    <CheckCircle2 class="h-6 w-6 shrink-0" aria-hidden="true" />
                    <p class="font-bold">All clear. Nothing needs your attention right now.</p>
                </div>
            </section>

            <!-- KPI row: each card links to the filtered list -->
            <section aria-label="Key numbers" class="grid grid-cols-2 gap-4 lg:grid-cols-3 2xl:grid-cols-6">
                <KpiCard v-for="k in kpis" :key="k.label" v-bind="k" />
            </section>

            <!-- Document pipeline -->
            <section aria-label="Document requests" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-bold text-ns-ink">Document requests</h2>
                    <p class="text-xs text-slate-500">{{ total }} in total</p>
                </div>
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <li v-for="s in STATUS_ORDER" :key="s" class="rounded-2xl bg-ns-canvas p-4">
                        <StatusBadge :status="s" />
                        <p class="mt-2 text-3xl font-extrabold tabular-nums text-ns-ink">{{ statuses[s] ?? 0 }}</p>
                    </li>
                </ul>
            </section>

            <!-- Charts -->
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h2 class="font-bold text-ns-ink">Requests, last 14 days</h2>
                    <p class="mb-4 text-xs text-slate-500">Number of document requests received each day.</p>
                    <div v-if="trend.length" class="flex h-40 items-end gap-1">
                        <div v-for="t in trend" :key="t.day" :title="`${t.day}: ${t.total}`" class="flex h-full flex-1 flex-col items-center justify-end">
                            <span class="mb-1 text-[10px] tabular-nums text-slate-500">{{ t.total || '' }}</span>
                            <div class="w-full rounded-t bg-ns-violet"
                                 :style="{ height: (t.total / maxTrend) * 80 + '%', minHeight: t.total ? '4px' : '1px' }"></div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No data yet.</p>
                    <div v-if="trend.length" class="mt-1 flex justify-between text-[10px] text-slate-400">
                        <span>{{ trend[0].day }}</span><span>{{ trend[trend.length - 1].day }}</span>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h2 class="font-bold text-ns-ink">Top barangays by requests</h2>
                    <p class="mb-4 text-xs text-slate-500">Where most document requests come from.</p>
                    <div v-for="b in byBarangay" :key="b.name" class="mb-3">
                        <div class="flex justify-between text-sm"><span class="font-medium">{{ b.name }}</span><span class="font-semibold tabular-nums">{{ b.total }}</span></div>
                        <div class="mt-1 h-2.5 rounded-full bg-ns-mist">
                            <div class="h-2.5 rounded-full bg-[#5BC4C9]" :style="{ width: (b.total / maxBgy) * 100 + '%' }"></div>
                        </div>
                    </div>
                    <p v-if="!byBarangay.length" class="text-sm text-slate-500">No data yet.</p>
                </div>
            </section>

            <!-- Activity feed -->
            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <h2 class="mb-3 flex items-center gap-2 font-bold text-ns-ink"><Activity class="h-4 w-4" aria-hidden="true" /> Recent activity</h2>
                <ul class="divide-y text-sm">
                    <li v-for="a in activity" :key="a.id" class="flex flex-wrap justify-between gap-x-4 py-2.5">
                        <span><span class="font-semibold capitalize">{{ a.action }}</span> {{ a.what }} <span class="text-slate-500">by {{ a.by }}</span></span>
                        <span class="text-slate-400">{{ a.at }}</span>
                    </li>
                </ul>
                <p v-if="!activity.length" class="text-sm text-slate-500">No activity yet.</p>
            </section>
        </div>

        <!-- Details of the selected priority item, without leaving the page -->
        <template #context>
            <ContextPanel v-if="selected && detail" :title="detail.title" :subtitle="detail.subtitle" @close="selectedKey = null">
                <dl class="divide-y text-sm">
                    <div v-for="[label, value] in detail.rows" :key="label" class="flex justify-between gap-4 py-2.5">
                        <dt class="text-slate-500">{{ label }}</dt>
                        <dd class="font-semibold tabular-nums text-ns-ink">{{ value }}</dd>
                    </div>
                </dl>
                <p v-if="!detail.rows.length" class="text-sm text-slate-500">No details to show.</p>
                <template #actions>
                    <Link :href="detail.href"
                          class="inline-flex items-center gap-1.5 rounded-xl bg-ns-violet px-4 py-2 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet focus-visible:ring-offset-2">
                        {{ detail.label }} <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </template>
            </ContextPanel>
        </template>
    </AuthenticatedLayout>
</template>