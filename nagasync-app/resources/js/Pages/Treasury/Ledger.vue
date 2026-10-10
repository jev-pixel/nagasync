<script setup>
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({ summary: Object, byType: Array, payments: Object });

const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Treasury Ledger" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Treasury Ledger</h2></template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                <StatCard label="Gross collected" :value="php(summary.gross)" />
                <StatCard label="Municipal collections" :value="php(summary.collected)" tone="success" />
                <StatCard label="Convenience fees" :value="php(summary.fees)" />
                <StatCard label="Collected today" :value="php(summary.today)" tone="brand" />
                <StatCard label="Pending" :value="summary.pending" tone="warning" />
                <StatCard label="Failed" :value="summary.failed" tone="danger" />
            </div>

            <Card>
                <h3 class="mb-2 font-semibold text-gray-900">Collections by type (paid only)</h3>
                <div v-for="r in byType" :key="r.label" class="flex items-center justify-between gap-3 border-b border-gray-100 py-2.5 text-sm last:border-0">
                    <span>{{ r.label }} <span class="text-gray-400">· {{ r.count }} payments</span></span>
                    <span class="font-medium tabular-nums">{{ php(r.amount) }}</span>
                </div>
                <p v-if="!byType.length" class="text-sm text-gray-500">No paid transactions yet.</p>
            </Card>

            <Card :padded="false" class="overflow-hidden">
                <!-- Phones: one card per payment, no sideways scrolling -->
                <ul class="divide-y divide-gray-100 md:hidden">
                    <li v-for="p in payments.data" :key="p.id" class="space-y-1 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900">{{ p.citizen }}</p>
                                <p class="text-sm text-gray-500">{{ p.label }} · {{ p.barangay }}</p>
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums">{{ php(p.total) }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate font-mono text-xs text-gray-500">{{ p.reference_no }}</span>
                            <StatusBadge :status="p.status" />
                        </div>
                        <p class="text-xs text-gray-400">{{ p.when }}</p>
                    </li>
                </ul>

                <!-- Tablets and desktops: full table -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-brand-50/60 text-left text-gray-600">
                            <tr>
                                <th class="p-3 font-medium">Reference</th><th class="p-3 font-medium">Citizen</th><th class="p-3 font-medium">Type</th>
                                <th class="p-3 font-medium">Barangay</th><th class="p-3 text-right font-medium">Total</th>
                                <th class="p-3 font-medium">Status</th><th class="p-3 font-medium">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in payments.data" :key="p.id">
                                <td class="p-3 font-mono text-xs">{{ p.reference_no }}</td>
                                <td class="p-3">{{ p.citizen }}</td>
                                <td class="p-3">{{ p.label }}</td>
                                <td class="p-3">{{ p.barangay }}</td>
                                <td class="p-3 text-right tabular-nums">{{ php(p.total) }}</td>
                                <td class="p-3"><StatusBadge :status="p.status" /></td>
                                <td class="p-3 text-xs text-gray-500">{{ p.when }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="!payments.data.length" title="No payments yet" text="Payments will appear here as residents pay fees." icon="card" />

                <div v-if="payments.prev_page_url || payments.next_page_url" class="flex items-center justify-between border-t border-gray-100 p-3 text-sm text-gray-600">
                    <span>{{ payments.from ?? 0 }}–{{ payments.to ?? 0 }} of {{ payments.total }}</span>
                    <div class="space-x-2">
                        <Link v-if="payments.prev_page_url" :href="payments.prev_page_url" class="rounded-tile border border-gray-200 px-3 py-1.5">Previous</Link>
                        <Link v-if="payments.next_page_url" :href="payments.next_page_url" class="rounded-tile border border-gray-200 px-3 py-1.5">Next</Link>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>