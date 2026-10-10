<script setup>
import Card from '@/Components/Card.vue';
import StatCard from '@/Components/StatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, defineProps, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    statuses: Object, total: Number, avgHours: Number,
    byBarangay: Array, trend: Array, activity: Array, operations: Object,
});

const cards = [
    ['pending', 'Pending', 'warning'],
    ['processing', 'Processing', 'brand'],
    ['approved', 'Approved', 'success'],
    ['rejected', 'Rejected', 'danger'],
    ['revoked', 'Revoked', 'default'],
];

const lastUpdated = ref(new Date());
// Bars start at zero and grow once on first paint; later polls just ease to new values.
const ready = ref(false);
let timer;
onMounted(() => {
    requestAnimationFrame(() => (ready.value = true));
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
</script>

<template>
    <Head title="Command Center" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Command Center</h2>
                <span class="flex shrink-0 items-center text-xs text-gray-500">
                    <span class="mr-2 h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                    <span class="hidden sm:inline">Live · updated&nbsp;</span>{{ lastUpdated.toLocaleTimeString() }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Document requests -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                <StatCard label="Total requests" :value="total" icon="document" />
                <StatCard v-for="[key, label, tone] in cards" :key="key" :label="label" :value="statuses[key]" :tone="tone" />
                <StatCard label="Avg. time to issue" :value="avgHours ?? '-'" hint="hours" />
            </div>

            <!-- Operations overview -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <h3 class="font-semibold text-gray-900">Emergencies</h3>
                    <p :class="operations.emergencies.active ? 'text-red-600' : 'text-green-600'" class="mt-1 text-3xl font-bold tabular-nums">
                        {{ operations.emergencies.active }}
                    </p>
                    <p class="text-xs text-gray-500">awaiting response</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Open (incl. dispatched)</dt><dd>{{ operations.emergencies.open }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Raised today</dt><dd>{{ operations.emergencies.today }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Avg. response</dt>
                            <dd>{{ operations.emergencies.avgAckMinutes ?? '-' }} min</dd></div>
                    </dl>
                </Card>

                <Card>
                    <h3 class="font-semibold text-gray-900">Payments today</h3>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ php(operations.payments.grossToday) }}</p>
                    <p class="text-xs text-gray-500">{{ operations.payments.paidToday }} paid transactions</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Pending</dt><dd>{{ operations.payments.pending }}</dd></div>
                    </dl>
                </Card>

                <Card>
                    <h3 class="font-semibold text-gray-900">Market alerts</h3>
                    <p :class="operations.market.flagged.length ? 'text-red-600' : 'text-green-600'" class="mt-1 text-3xl font-bold tabular-nums">
                        {{ operations.market.available ? operations.market.flagged.length : '-' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ operations.market.available ? 'commodities flagged' : 'analysis unavailable' }}
                    </p>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        <li v-for="f in operations.market.flagged" :key="f.commodity + f.unit" class="flex justify-between gap-2">
                            <span class="truncate">{{ f.commodity }}</span>
                            <span class="shrink-0 text-red-600">{{ f.status === 'over_srp' ? 'Above SRP' : 'Unusually high' }}</span>
                        </li>
                    </ul>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- 14-day trend -->
                <Card>
                    <h3 class="mb-4 font-semibold text-gray-900">Requests, last 14 days</h3>
                    <div class="flex h-40 items-end gap-1">
                        <div v-for="t in trend" :key="t.day" class="flex h-full flex-1 flex-col items-center justify-end">
                            <span class="mb-1 text-[10px] text-gray-500">{{ t.total || '' }}</span>
                            <div class="w-full rounded-t bg-brand-500 transition-[height] duration-700 ease-out"
                                 :style="{ height: ready ? (t.total / maxTrend) * 80 + '%' : '0%', minHeight: ready && t.total ? '4px' : '1px' }"></div>
                        </div>
                    </div>
                    <div class="mt-1 flex justify-between text-[10px] text-gray-400">
                        <span>{{ trend[0].day }}</span><span>{{ trend[trend.length - 1].day }}</span>
                    </div>
                </Card>

                <!-- By barangay -->
                <Card>
                    <h3 class="mb-4 font-semibold text-gray-900">Top barangays by requests</h3>
                    <div v-for="b in byBarangay" :key="b.name" class="mb-3">
                        <div class="flex justify-between text-xs"><span>{{ b.name }}</span><span class="tabular-nums">{{ b.total }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-brand-50">
                            <div class="h-2 rounded-full bg-brand-500 transition-[width] duration-700 ease-out"
                                 :style="{ width: ready ? (b.total / maxBgy) * 100 + '%' : '0%' }"></div>
                        </div>
                    </div>
                    <p v-if="!byBarangay.length" class="text-sm text-gray-500">No data yet.</p>
                </Card>
            </div>

            <!-- Activity feed -->
            <Card>
                <h3 class="mb-1 font-semibold text-gray-900">Recent activity</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    <li v-for="a in activity" :key="a.id" class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between">
                        <span><span class="font-medium capitalize">{{ a.action }}</span> {{ a.what }} <span class="text-gray-500">by {{ a.by }}</span></span>
                        <span class="text-xs text-gray-400 sm:text-sm">{{ a.at }}</span>
                    </li>
                </ul>
                <p v-if="!activity.length" class="text-sm text-gray-500">No activity yet.</p>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>