<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({ available: Boolean, trends: Array, market: String });

const php = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const statusInfo = {
    stable: ['Stable', 'bg-green-100 text-green-800'],
    rising: ['Rising', 'bg-amber-100 text-amber-800'],
    falling: ['Falling', 'bg-blue-100 text-blue-800'],
    alert_high: ['Unusually high', 'bg-red-100 text-red-800'],
    over_srp: ['Above SRP', 'bg-red-100 text-red-800'],
    unknown: ['No analysis', 'bg-gray-100 text-gray-600'],
};

const spark = (vals) => {
    if (!vals || vals.length < 2) return '';
    const min = Math.min(...vals), max = Math.max(...vals), span = max - min || 1;
    return vals.map((v, i) => `${(i / (vals.length - 1)) * 100},${27 - ((v - min) / span) * 25}`).join(' ');
};
</script>

<template>
    <Head title="Market Watch" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Market Watch · {{ market }}</h2></template>

        <div class="mx-auto max-w-5xl space-y-4 p-6">
            <div v-if="!available" class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                Trend analysis is temporarily unavailable. Showing the latest recorded prices only.
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr><th class="p-3">Commodity</th><th class="p-3 text-right">Latest price</th>
                            <th class="p-3 text-right">7-day avg</th><th class="p-3 text-right">Week change</th>
                            <th class="p-3">Trend</th><th class="p-3">Status</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in trends" :key="t.commodity + t.unit" class="border-t">
                            <td class="p-3 font-medium">{{ t.commodity }}<span class="text-gray-400"> / {{ t.unit }}</span>
                                <div class="text-xs text-gray-400">as of {{ t.latest_date }}</div></td>
                            <td class="p-3 text-right">{{ php(t.latest_price) }}</td>
                            <td class="p-3 text-right">{{ t.avg_7d !== null ? php(t.avg_7d) : '-' }}</td>
                            <td class="p-3 text-right">
                                <span v-if="t.change_pct !== null" :class="t.change_pct > 0 ? 'text-red-600' : t.change_pct < 0 ? 'text-green-600' : ''">
                                    {{ t.change_pct > 0 ? '▲' : t.change_pct < 0 ? '▼' : '' }} {{ Math.abs(t.change_pct) }}%
                                </span>
                                <span v-else>-</span>
                            </td>
                            <td class="p-3 text-gray-500">
                                <svg v-if="t.sparkline.length > 1" viewBox="0 0 100 28" class="h-7 w-24">
                                    <polyline :points="spark(t.sparkline)" fill="none" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </td>
                            <td class="p-3">
                                <span :class="statusInfo[t.status]?.[1]" class="rounded px-2 py-0.5 text-xs font-medium">
                                    {{ statusInfo[t.status]?.[0] ?? t.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!trends.length" class="p-6 text-center text-gray-500">No prices recorded yet.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>