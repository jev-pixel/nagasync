<script setup>
/**
 * Clerk / counter home: what is waiting at the counter, find any request by tracking number or QR,
 * and release it from the right-hand panel without leaving the page.
 */
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ContextPanel from '@/Components/ContextPanel.vue';
import KpiCard from '@/Components/KpiCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { peso } from '@/utils/money';
import { useLiveData } from '@/utils/useLiveData';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronRight, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ q: String, doc: Object, error: String, stats: Object, ready: Array });

// Refresh only the numbers and the waiting list. The open document and the form stay as they are.
useLiveData({ only: ['stats', 'ready'], interval: 15000 });

const search = useForm({ q: props.q ?? '' });
const find = (q = search.q) => {
    search.q = q;
    router.get(route('staff.index'), { q }, { preserveState: true, preserveScroll: true });
};
const closePanel = () => {
    search.q = '';
    router.get(route('staff.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const release = useForm({ official_receipt_no: '', amount_paid: props.doc?.fee?.total ?? 0, id_checked: false });
watch(() => props.doc?.id, () => {
    release.reset();
    release.clearErrors();
    release.amount_paid = props.doc?.fee?.total ?? 0;
});

const isFree = computed(() => Number(props.doc?.fee?.total ?? 0) <= 0);

// Releasing cannot be undone, so ask first
const confirming = ref(false);
const askRelease = () => { if (release.id_checked) confirming.value = true; };
const submitRelease = () =>
    release.post(route('staff.release', props.doc.id), {
        preserveScroll: true,
        onFinish: () => { confirming.value = false; },
    });

const confirmMessage = computed(() => isFree.value
    ? 'You are confirming the citizen\'s ID was checked. The document becomes valid immediately.'
    : `You are confirming ${peso(release.amount_paid)} was collected (OR ${release.official_receipt_no || 'no receipt number'}). The document becomes valid immediately.`);

// Document state -> badge (the label always says it in words too)
const STATE_BADGE = {
    ready: ['ready', 'Approved: ready to release'],
    already_released: ['failed', 'Already released'],
    rejected: ['rejected', 'Rejected'],
    revoked: ['revoked', 'Revoked'],
    not_ready: ['pending', 'Not yet approved'],
};
const badge = computed(() => STATE_BADGE[props.doc?.state] ?? ['pending', props.doc?.status ?? '']);
</script>

<template>
    <Head title="Counter" />
    <AuthenticatedLayout>
        <template #header>Counter</template>

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Key numbers: each one leads to the matching list -->
            <section aria-label="Key numbers" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="Ready to release" :value="stats.ready" :tone="stats.ready > 0 ? 'warning' : 'normal'"
                         hint="approved, waiting at the counter" />
                <KpiCard label="Awaiting review" :value="stats.awaiting" hint="pending or being reviewed"
                         :href="route('staff.documents.index', { status: 'open' })" action="Review queue" />
                <KpiCard label="Fees to assess" :value="stats.toAssess" :tone="stats.toAssess > 0 ? 'warning' : 'normal'"
                         hint="City Hall requests that cannot be approved yet"
                         :href="route('staff.documents.index', { status: 'open' })" action="Assess fees" />
                <KpiCard label="Released today" :value="stats.releasedToday" hint="handed over at the counter" />
            </section>

            <!-- Find any request -->
            <section aria-label="Find a request">
                <form class="flex gap-2" @submit.prevent="find()">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input v-model="search.q" autofocus aria-label="Tracking number or QR code"
                               placeholder="Scan the QR code or type the tracking number"
                               class="w-full rounded-xl border border-ns-mist py-2.5 pl-10 pr-3 text-sm focus:border-ns-violet focus:ring-ns-violet" />
                    </div>
                    <button class="rounded-xl bg-ns-violet px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet focus-visible:ring-offset-2">Find</button>
                </form>
                <p v-if="error" role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
            </section>

            <!-- Waiting at the counter -->
            <section aria-label="Ready to release" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-bold text-ns-ink">Ready to release</h2>
                    <p class="text-xs text-slate-500">Longest waiting first. Open one to release it.</p>
                </div>
                <ul v-if="ready.length" class="mt-3 divide-y">
                    <li v-for="r in ready" :key="r.id">
                        <button type="button" :aria-pressed="doc?.tracking_number === r.tracking_number" @click="find(r.tracking_number)"
                                :class="['flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                                         doc?.tracking_number === r.tracking_number ? 'bg-ns-mist' : 'hover:bg-ns-canvas']">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ns-ink">{{ r.document_type }}</span>
                                <span class="block truncate text-xs text-slate-500">
                                    <span class="font-mono">{{ r.tracking_number }}</span> · {{ r.citizen }}<template v-if="r.barangay"> · {{ r.barangay }}</template>
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3 text-right">
                                <span>
                                    <span class="block text-sm font-semibold tabular-nums text-ns-ink">{{ r.fee_total ? peso(r.fee_total) : 'Free' }}</span>
                                    <span class="block text-xs text-slate-500">waiting {{ r.waiting }}</span>
                                </span>
                                <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-slate-500">Nothing is waiting at the counter. Scan a QR code or type a tracking number above for anything else.</p>
            </section>
        </div>

        <!-- Release from the panel -->
        <template #context>
            <ContextPanel v-if="doc" :title="doc.document_type" :subtitle="doc.tracking_number" @close="closePanel">
                <div class="space-y-1 text-sm">
                    <p class="font-semibold text-ns-ink">{{ doc.citizen.name }}<span v-if="doc.citizen.barangay" class="font-normal text-slate-500"> · {{ doc.citizen.barangay }}</span></p>
                    <p class="text-slate-600">{{ doc.purpose }}</p>
                    <div class="pt-1"><StatusBadge :status="badge[0]" :label="badge[1]" /></div>
                </div>

                <p v-if="doc.state === 'already_released'" role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">
                    Released {{ doc.released_at }} · OR {{ doc.receipt_no }}. Do not release again.
                </p>

                <div>
                    <h3 class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Requirements</h3>
                    <ul class="divide-y text-sm">
                        <li v-for="r in doc.requirements" :key="r.name" class="flex justify-between gap-3 py-1.5">
                            <span>{{ r.name }}<em v-if="r.linked" class="text-slate-500"> (linked {{ r.linked_tracking }}, {{ r.linked_status }})</em></span>
                            <span :class="r.review_status === 'verified' ? 'text-emerald-700' : 'text-amber-700'">{{ r.review_status }}</span>
                        </li>
                    </ul>
                </div>

                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="l in doc.fee.lines" :key="l.label">
                            <td class="py-1">{{ l.label }}</td><td class="py-1 text-right tabular-nums">{{ peso(l.amount) }}</td>
                        </tr>
                        <tr class="border-t font-semibold">
                            <td class="py-1.5">Total due</td><td class="py-1.5 text-right tabular-nums">{{ peso(doc.fee.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <form v-if="doc.state === 'ready'" id="release-form" class="space-y-3 border-t pt-3" @submit.prevent="askRelease">
                    <label class="flex items-start gap-2 text-sm">
                        <input v-model="release.id_checked" type="checkbox" class="mt-0.5 rounded border-slate-300 text-ns-violet focus:ring-ns-violet" />
                        I checked the citizen's valid ID against the name above.
                    </label>
                    <p v-if="isFree" class="rounded-xl bg-emerald-50 p-2 text-sm text-emerald-800">No fee for this document. Just confirm the ID and release.</p>
                    <div v-else class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="or-no" class="sr-only">Official receipt number</label>
                            <input id="or-no" v-model="release.official_receipt_no" placeholder="Official receipt no."
                                   class="w-full rounded-xl border border-ns-mist px-3 py-2 text-sm focus:border-ns-violet focus:ring-ns-violet" />
                            <p v-if="release.errors.official_receipt_no" class="mt-1 text-xs text-red-600">{{ release.errors.official_receipt_no }}</p>
                        </div>
                        <div>
                            <label for="amount" class="sr-only">Amount paid</label>
                            <input id="amount" v-model="release.amount_paid" type="number" step="0.01"
                                   class="w-full rounded-xl border border-ns-mist px-3 py-2 text-sm focus:border-ns-violet focus:ring-ns-violet" />
                            <p v-if="release.errors.amount_paid" class="mt-1 text-xs text-red-600">{{ release.errors.amount_paid }}</p>
                        </div>
                    </div>
                    <p v-if="release.errors.id_checked" class="text-xs text-red-600">Confirm that you checked the ID.</p>
                </form>

                <template v-if="doc.state === 'ready'" #actions>
                    <button type="submit" form="release-form" :disabled="release.processing || !release.id_checked"
                            class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 disabled:opacity-50">
                        {{ isFree ? 'Release document' : 'Collect payment and release' }}
                    </button>
                </template>
            </ContextPanel>
        </template>

        <ConfirmDialog :open="confirming" title="Release this document?" :message="confirmMessage"
                       confirm-label="Release" :busy="release.processing"
                       @confirm="submitRelease" @cancel="confirming = false" />
    </AuthenticatedLayout>
</template>