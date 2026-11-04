<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, AlertTriangle, ArrowRight, CheckCircle2, FileText, Siren, Store, Wallet } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    statuses: Object, total: Number, avgHours: Number,
    byBarangay: Array, trend: Array, activity: Array, operations: Object,
});

// [key, label, plain-language hint, number colour]
const cards = [
    ['pending', 'Pending', 'Waiting for staff', 'text-amber-600'],
    ['processing', 'Processing', 'Being reviewed', 'text-sky-600'],
    ['approved', 'Approved', 'Issued to residents', 'text-emerald-600'],
    ['rejected', 'Rejected', 'Not approved', 'text-red-600'],
    ['revoked', 'Revoked', 'No longer valid', 'text-gray-600'],
];

// Auto-refresh every 10 seconds while the tab is visible
const lastUpdated = ref(new Date());
let timer;
onMounted(() => {
    timer = setInterval(() => {
        if (document.hidden) return; // don't poll in background tabs
        router.reload({
            preserveScroll: true, preserveState: true,
            onSuccess: () => (lastUpdated.value = new Date()),
        });
    }, 10000);
});
onUnmounted(() => clearInterval(timer));

const maxTrend = computed(() => Math.max(1, ...props.trend.map((t) => t.total)));
const maxBgy = computed(() => Math.max(1, ...props.byBarangay.map((b) => b.total)));
const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;

// "Needs attention" list: the few things an admin should act on first
const attention = computed(() => {
    const ops = props.operations;
    const items = [];
    if (ops.emergencies.active > 0) {
        items.push({ key: 'emg', urgent: true, icon: Siren, text: `${plural(ops.emergencies.active, 'emergency alert')} awaiting response`, label: 'Open dispatch', href: route('dispatch.index') });
    }
    if (props.statuses.pending > 0) {
        items.push({ key: 'docs', urgent: false, icon: FileText, text: `${plural(props.statuses.pending, 'document request')} waiting for review`, label: 'Review requests', href: route('staff.documents.index') });
    }
    if (ops.payments.pending > 0) {
        items.push({ key: 'pay', urgent: false, icon: Wallet, text: `${plural(ops.payments.pending, 'payment')} not yet completed`, label: 'View payments', href: route('treasury.payments') });
    }
    if (ops.market.available && ops.market.flagged.length > 0) {
        items.push({ key: 'mkt', urgent: false, icon: Store, text: `${plural(ops.market.flagged.length, 'commodity')} flagged for unusual prices`, label: 'See prices', href: route('market.index') });
    }
    return items;
});
</script>

<template>
    <Head title="Command Center" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-800">Command Center</h2>
                <span class="flex items-center text-xs text-gray-500">
                    <span class="mr-2 h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                    Live · updated {{ lastUpdated.toLocaleTimeString() }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 p-4 sm:p-6">
            <!-- Needs attention -->
            <section aria-label="Needs attention">
                <div v-if="attention.length" class="rounded-3xl bg-[#F1E8F6] p-5 sm:p-6">
                    <h3 class="flex items-center gap-2 text-lg font-extrabold text-[#3D2470]">
                        <AlertTriangle class="h-5 w-5" /> Needs your attention
                    </h3>
                    <ul class="mt-4 grid gap-3 md:grid-cols-2">
                        <li v-for="i in attention" :key="i.key"
                            class="flex items-center justify-between gap-3 rounded-2xl bg-white p-4 ring-1"
                            :class="i.urgent ? 'ring-red-300' : 'ring-black/5'">
                            <span class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                      :class="i.urgent ? 'bg-red-100 text-red-600' : 'bg-[#F1E8F6] text-[#3D2470]'">
                                    <component :is="i.icon" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-semibold text-[#1B1340]">{{ i.text }}</span>
                            </span>
                            <Link :href="i.href" class="inline-flex shrink-0 items-center gap-1 text-sm font-bold text-[#F04A2B] hover:underline">
                                {{ i.label }} <ArrowRight class="h-4 w-4" />
                            </Link>
                        </li>
                    </ul>
                </div>
                <div v-else class="flex items-center gap-3 rounded-3xl bg-emerald-50 p-5 text-emerald-800 ring-1 ring-emerald-200">
                    <CheckCircle2 class="h-6 w-6 shrink-0" />
                    <p class="font-bold">All clear. Nothing needs your attention right now.</p>
                </div>
            </section>

            <!-- Document requests -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">Document requests</h3>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Total requests</p>
                        <p class="mt-1 text-3xl font-extrabold text-[#1B1340]">{{ total }}</p>
                        <p class="text-xs text-gray-400">All time</p>
                    </div>
                    <div v-for="[key, label, hint, color] in cards" :key="key" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">{{ label }}</p>
                        <p :class="color" class="mt-1 text-3xl font-extrabold">{{ statuses[key] }}</p>
                        <p class="text-xs text-gray-400">{{ hint }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Avg. time to issue</p>
                        <p class="mt-1 text-3xl font-extrabold text-[#1B1340]">{{ avgHours ?? '-' }}<span class="text-sm font-medium text-gray-500"> hrs</span></p>
                        <p class="text-xs text-gray-400">Lower is faster</p>
                    </div>
                </div>
            </section>

            <!-- Operations overview -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">Today across the city</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <h4 class="flex items-center gap-2 font-bold text-[#3D2470]"><Siren class="h-4 w-4" /> Emergencies</h4>
                        <p :class="operations.emergencies.active ? 'text-red-600' : 'text-emerald-600'" class="mt-2 text-4xl font-extrabold">
                            {{ operations.emergencies.active }}
                        </p>
                        <p class="text-xs text-gray-500">awaiting response</p>
                        <dl class="mt-4 space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Open (incl. dispatched)</dt><dd class="font-semibold">{{ operations.emergencies.open }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Raised today</dt><dd class="font-semibold">{{ operations.emergencies.today }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Avg. response</dt>
                                <dd class="font-semibold">{{ operations.emergencies.avgAckMinutes ?? '-' }} min</dd></div>
                        </dl>
                    </div>

                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <h4 class="flex items-center gap-2 font-bold text-[#3D2470]"><Wallet class="h-4 w-4" /> Payments today</h4>
                        <p class="mt-2 text-4xl font-extrabold text-[#1B1340]">{{ php(operations.payments.grossToday) }}</p>
                        <p class="text-xs text-gray-500">{{ operations.payments.paidToday }} paid transactions</p>
                        <dl class="mt-4 space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Not yet completed</dt><dd class="font-semibold">{{ operations.payments.pending }}</dd></div>
                        </dl>
                    </div>

                    <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <h4 class="flex items-center gap-2 font-bold text-[#3D2470]"><Store class="h-4 w-4" /> Market alerts</h4>
                        <p :class="operations.market.flagged.length ? 'text-red-600' : 'text-emerald-600'" class="mt-2 text-4xl font-extrabold">
                            {{ operations.market.available ? operations.market.flagged.length : '-' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ operations.market.available ? 'commodities flagged' : 'analysis unavailable' }}
                        </p>
                        <ul class="mt-4 space-y-1.5 text-sm">
                            <li v-for="f in operations.market.flagged" :key="f.commodity + f.unit" class="flex justify-between">
                                <span class="font-medium">{{ f.commodity }}</span>
                                <span class="font-semibold text-red-600">{{ f.status === 'over_srp' ? 'Above SRP' : 'Unusually high' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Charts -->
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h3 class="font-bold text-[#3D2470]">Requests, last 14 days</h3>
                    <p class="mb-4 text-xs text-gray-500">Number of document requests received each day.</p>
                    <div v-if="trend.length" class="flex h-40 items-end gap-1">
                        <div v-for="t in trend" :key="t.day" :title="`${t.day}: ${t.total}`" class="flex h-full flex-1 flex-col items-center justify-end">
                            <span class="mb-1 text-[10px] text-gray-500">{{ t.total || '' }}</span>
                            <div class="w-full rounded-t bg-[#3D2470]"
                                 :style="{ height: (t.total / maxTrend) * 80 + '%', minHeight: t.total ? '4px' : '1px' }"></div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">No data yet.</p>
                    <div v-if="trend.length" class="mt-1 flex justify-between text-[10px] text-gray-400">
                        <span>{{ trend[0].day }}</span><span>{{ trend[trend.length - 1].day }}</span>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h3 class="font-bold text-[#3D2470]">Top barangays by requests</h3>
                    <p class="mb-4 text-xs text-gray-500">Where most document requests come from.</p>
                    <div v-for="b in byBarangay" :key="b.name" class="mb-3">
                        <div class="flex justify-between text-sm"><span class="font-medium">{{ b.name }}</span><span class="font-semibold">{{ b.total }}</span></div>
                        <div class="mt-1 h-2.5 rounded-full bg-gray-100">
                            <div class="h-2.5 rounded-full bg-[#5BC4C9]" :style="{ width: (b.total / maxBgy) * 100 + '%' }"></div>
                        </div>
                    </div>
                    <p v-if="!byBarangay.length" class="text-sm text-gray-500">No data yet.</p>
                </div>
            </section>

            <!-- Activity feed -->
            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <h3 class="mb-3 flex items-center gap-2 font-bold text-[#3D2470]"><Activity class="h-4 w-4" /> Recent activity</h3>
                <ul class="divide-y text-sm">
                    <li v-for="a in activity" :key="a.id" class="flex flex-wrap justify-between gap-x-4 py-2.5">
                        <span><span class="font-semibold capitalize">{{ a.action }}</span> {{ a.what }} <span class="text-gray-500">by {{ a.by }}</span></span>
                        <span class="text-gray-400">{{ a.at }}</span>
                    </li>
                </ul>
                <p v-if="!activity.length" class="text-sm text-gray-500">No activity yet.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>