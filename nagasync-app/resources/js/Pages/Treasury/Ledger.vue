<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, Clock, Receipt, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ summary: Object, byType: Array, payments: Object });

const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const tone = {
    pending: 'bg-amber-100 text-amber-800', paid: 'bg-emerald-100 text-emerald-800',
    failed: 'bg-red-100 text-red-800', expired: 'bg-gray-200 text-gray-700', refunded: 'bg-sky-100 text-sky-800',
};
const meaning = {
    pending: 'Started but not completed', paid: 'Payment received', failed: 'Did not go through',
    expired: 'Session ended without payment', refunded: 'Money returned',
};

const maxType = computed(() => Math.max(1, ...props.byType.map((r) => r.amount)));

// Filters work on the rows loaded on this page
const tabs = ['all', 'paid', 'pending', 'failed', 'expired', 'refunded'];
const tab = ref('all');
const q = ref('');
const rows = computed(() => props.payments.data.filter((p) => {
    if (tab.value !== 'all' && p.status !== tab.value) return false;
    const s = q.value.trim().toLowerCase();
    return !s || [p.reference_no, p.citizen, p.label, p.barangay].some((v) => String(v ?? '').toLowerCase().includes(s));
}));
</script>

<template>
    <Head title="Treasury Ledger" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Treasury Ledger</h2></template>

        <div class="mx-auto max-w-7xl space-y-8 p-4 sm:p-6">
            <!-- Money in -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">Money collected</h3>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Gross collected</p>
                        <p class="mt-1 text-2xl font-extrabold text-[#1B1340]">{{ php(summary.gross) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Municipal collections</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ php(summary.collected) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Convenience fees</p>
                        <p class="mt-1 text-2xl font-extrabold text-[#1B1340]">{{ php(summary.fees) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                        <p class="text-xs font-semibold text-gray-500">Collected today</p>
                        <p class="mt-1 text-2xl font-extrabold text-[#1B1340]">{{ php(summary.today) }}</p>
                    </div>
                </div>
            </section>

            <!-- Needs follow-up -->
            <section class="grid gap-4 sm:grid-cols-2">
                <div class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><Clock class="h-5 w-5" /></span>
                    <div>
                        <p class="text-2xl font-extrabold text-amber-600">{{ summary.pending }}</p>
                        <p class="text-xs text-gray-500">Pending: payments started but not completed</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700"><AlertTriangle class="h-5 w-5" /></span>
                    <div>
                        <p class="text-2xl font-extrabold text-red-600">{{ summary.failed }}</p>
                        <p class="text-xs text-gray-500">Failed: payments that did not go through</p>
                    </div>
                </div>
            </section>

            <!-- By type -->
            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <h3 class="font-bold text-[#3D2470]">Collections by type</h3>
                <p class="mb-4 text-xs text-gray-500">Paid payments only.</p>
                <div v-for="r in byType" :key="r.label" class="mb-4 last:mb-0">
                    <div class="flex justify-between gap-4 text-sm">
                        <span class="font-medium">{{ r.label }} <span class="text-gray-400">· {{ r.count }} payment{{ r.count === 1 ? '' : 's' }}</span></span>
                        <span class="font-bold">{{ php(r.amount) }}</span>
                    </div>
                    <div class="mt-1 h-2.5 rounded-full bg-gray-100">
                        <div class="h-2.5 rounded-full bg-[#5BC4C9]" :style="{ width: (r.amount / maxType) * 100 + '%' }"></div>
                    </div>
                </div>
                <p v-if="!byType.length" class="text-sm text-gray-500">No paid transactions yet.</p>
            </section>

            <!-- Transactions -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">All transactions</h3>

                <div class="space-y-3">
                    <label class="relative block">
                        <span class="sr-only">Search transactions</span>
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="q" placeholder="Search by reference, name, type or barangay"
                               class="w-full rounded-2xl border-gray-300 pl-10 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                    </label>
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter by status">
                        <button v-for="t in tabs" :key="t" type="button" role="tab" :aria-selected="tab === t" @click="tab = t"
                                class="rounded-full px-4 py-1.5 text-sm font-semibold capitalize ring-1 transition"
                                :class="tab === t ? 'bg-[#3D2470] text-white ring-[#3D2470]' : 'bg-white text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#F1E8F6]'">
                            {{ t }}
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">Search and filters apply to the transactions shown on this page.</p>
                </div>

                <div v-if="rows.length" class="mt-4 overflow-x-auto rounded-3xl bg-white shadow-sm ring-1 ring-black/5">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#F1E8F6] text-left text-[#3D2470]">
                            <tr>
                                <th class="p-3 font-bold">Reference</th><th class="p-3 font-bold">Resident</th><th class="p-3 font-bold">Type</th>
                                <th class="p-3 font-bold">Barangay</th><th class="p-3 text-right font-bold">Total</th>
                                <th class="p-3 font-bold">Status</th><th class="p-3 font-bold">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in rows" :key="p.id" class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="select-all p-3 font-mono text-xs">{{ p.reference_no }}</td>
                                <td class="p-3 font-medium">{{ p.citizen }}</td>
                                <td class="p-3">{{ p.label }}</td>
                                <td class="p-3">{{ p.barangay }}</td>
                                <td class="p-3 text-right font-bold">{{ php(p.total) }}</td>
                                <td class="p-3">
                                    <span :class="tone[p.status]" :title="meaning[p.status]" class="rounded-full px-3 py-1 text-xs font-bold capitalize">{{ p.status }}</span>
                                </td>
                                <td class="whitespace-nowrap p-3 text-xs text-gray-500">{{ p.when }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="mt-4 rounded-3xl border-2 border-dashed border-[#3D2470]/20 p-10 text-center">
                    <Receipt class="mx-auto h-8 w-8 text-[#3D2470]/50" />
                    <p class="mt-3 font-semibold text-[#3D2470]">{{ payments.data.length ? 'No transactions match your search' : 'No transactions yet' }}</p>
                    <p class="text-sm text-gray-500">{{ payments.data.length ? 'Try a different filter or search term.' : 'Payments made by residents will appear here.' }}</p>
                </div>

                <Pagination :links="payments.links" />
            </section>
        </div>
    </AuthenticatedLayout>
</template>