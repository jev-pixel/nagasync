<script setup>
/**
 * Document queue inside the command frame: scan the list, open a request in the right-hand panel,
 * and approve / reject / send back without leaving the page. Filters and search run on the server,
 * so counts and results cover every page of the queue.
 */
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ContextPanel from '@/Components/ContextPanel.vue';
import FilterTabs from '@/Components/FilterTabs.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { peso } from '@/utils/money';
import { useLiveData } from '@/utils/useLiveData';
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Check, ChevronRight, Download, FileSearch, Inbox, Landmark, Play, RotateCcw, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ documents: Object, transitions: Object, counts: Object, filters: Object });

useLiveData({ only: ['documents', 'counts'], interval: 15000 });

const label = (s) => String(s ?? '').replace(/_/g, ' ');

// ---- Filters and search (server side) ----
const TAB_DEFS = [
    ['all', 'All'], ['open', 'Awaiting review'], ['pending', 'Pending'], ['processing', 'Processing'],
    ['needs_resubmission', 'Needs resubmission'], ['approved', 'Approved'], ['released', 'Released'],
    ['rejected', 'Rejected'], ['revoked', 'Revoked'],
];
const status = ref(props.filters.status || 'all');
const q = ref(props.filters.q ?? '');
const tabs = computed(() => TAB_DEFS.map(([key, text]) => ({ key, label: text, count: props.counts?.[key] ?? 0 })));

const apply = () => router.get(route('staff.documents.index'), {
    status: status.value === 'all' ? undefined : status.value,
    q: q.value.trim() || undefined,
}, { preserveState: true, preserveScroll: true, replace: true });
const setStatus = (key) => { status.value = key; apply(); };
const clearSearch = () => { q.value = ''; apply(); };

// ---- Selected request (opens the right-hand panel) ----
const selectedId = ref(null);
const selected = computed(() => props.documents.data.find((d) => d.id === selectedId.value) ?? null);
watch(() => props.documents.data, () => { if (selectedId.value && !selected.value) selectedId.value = null; });

const feeText = (d) => (d.fee_total == null ? 'Not assessed' : Number(d.fee_total) > 0 ? peso(d.fee_total) : 'Free');
// A City Hall request cannot be approved until its fee is assessed, so say so before the click
const needsAssessment = (d) => d.level === 'city' && d.fee_total == null;
const isOpen = (st) => ['pending', 'processing', 'needs_resubmission'].includes(st);

// ---- Actions ----
const actions = {
    processing: { label: 'Start processing', icon: Play, cls: 'bg-ns-violet text-white hover:opacity-90' },
    approved: { label: 'Approve', icon: Check, cls: 'bg-emerald-600 text-white hover:bg-emerald-700' },
    rejected: { label: 'Reject', icon: X, cls: 'bg-white text-red-600 ring-1 ring-red-300 hover:bg-red-50' },
    revoked: { label: 'Revoke', icon: Ban, cls: 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50' },
    needs_resubmission: { label: 'Ask to resubmit', icon: RotateCcw, cls: 'bg-white text-amber-700 ring-1 ring-amber-300 hover:bg-amber-50' },
};
// Safe lookup: an unknown status can never crash the page
const actionFor = (s) => actions[s] ?? { label: label(s), icon: null, cls: 'bg-white text-slate-700 ring-1 ring-slate-300' };
const needsReason = ['rejected', 'revoked', 'needs_resubmission'];
const confirmText = {
    approved: 'The resident will see this document as approved. A free document is issued immediately; a paid one is released at the counter.',
    rejected: 'The resident will see your reason. They can submit a new request.',
    revoked: 'The document will no longer be valid. The resident will see your reason.',
    needs_resubmission: 'The resident will see what to fix and can send the request again.',
};
const reasonChips = ['Incomplete information', 'Cannot verify residency', 'Duplicate request', 'Blurry or missing attachment'];

const dialog = ref(null); // { doc, status }
const remarks = ref('');
const busy = ref(false);
const error = ref('');

function send(doc, newStatus, text) {
    router.patch(route('staff.documents.update', doc.id), { status: newStatus, remarks: text }, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => (busy.value = false),
        onSuccess: () => { dialog.value = null; error.value = ''; },
        onError: (errs) => { error.value = errs.status ?? errs.remarks ?? 'Could not update this request.'; },
    });
}
function choose(doc, newStatus) {
    if (newStatus === 'processing') return send(doc, newStatus, null); // low-risk step, no confirmation needed
    remarks.value = '';
    error.value = '';
    dialog.value = { doc, status: newStatus };
}
const reasonMissing = computed(() => !!dialog.value && needsReason.includes(dialog.value.status) && !remarks.value.trim());
function confirm() {
    if (!dialog.value || reasonMissing.value) return;
    const { doc, status: s } = dialog.value;
    send(doc, s, needsReason.includes(s) ? remarks.value.trim() : null);
}
</script>

<template>
    <Head title="Document Queue" />
    <AuthenticatedLayout>
        <template #header>Document Queue</template>

        <div class="mx-auto max-w-5xl space-y-5">
            <p class="rounded-2xl bg-[#F1E8F6] p-4 text-sm text-ns-ink">
                Open a request to review it. <strong>Start processing</strong> tells the resident you are working on it.
                <strong>Approve</strong> issues a free document straight away; a paid one waits at the counter until the fee is collected.
            </p>

            <!-- Search + status filter -->
            <div class="space-y-3">
                <form class="flex gap-2" @submit.prevent="apply">
                    <label class="relative block flex-1">
                        <span class="sr-only">Search requests</span>
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input v-model="q" placeholder="Search by tracking number, name, document or barangay"
                               class="w-full rounded-xl border-ns-mist py-2.5 pl-10 text-sm focus:border-ns-violet focus:ring-ns-violet" />
                    </label>
                    <button class="rounded-xl bg-ns-violet px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet focus-visible:ring-offset-2">Search</button>
                    <button v-if="q" type="button" @click="clearSearch" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-ns-mist">Clear</button>
                </form>
                <FilterTabs :model-value="status" :tabs="tabs" label="Filter by status" @update:model-value="setStatus" />
            </div>

            <!-- Requests -->
            <section v-if="documents.data.length" aria-label="Requests" class="rounded-3xl bg-white p-2 shadow-sm ring-1 ring-black/5">
                <ul class="divide-y">
                    <li v-for="d in documents.data" :key="d.id">
                        <button type="button" :aria-pressed="selectedId === d.id" @click="selectedId = d.id"
                                :class="['flex w-full items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                                         selectedId === d.id ? 'bg-ns-mist' : 'hover:bg-ns-canvas']">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ns-ink">{{ d.document_type }}</span>
                                <span class="block truncate text-xs text-slate-500">
                                    {{ d.citizen }}<template v-if="d.barangay"> · {{ d.barangay }}</template> · <span class="font-mono">{{ d.tracking_number }}</span>
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span class="hidden text-right sm:block">
                                    <span class="block text-sm font-semibold tabular-nums text-ns-ink">{{ feeText(d) }}</span>
                                    <span class="block text-xs text-slate-500">{{ d.created_human }}</span>
                                </span>
                                <StatusBadge :status="d.status" />
                                <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <div v-else class="rounded-3xl border-2 border-dashed border-ns-violet/20 p-10 text-center">
                <Inbox class="mx-auto h-8 w-8 text-ns-violet/50" aria-hidden="true" />
                <p class="mt-3 font-semibold text-ns-ink">{{ (filters.status || filters.q) ? 'No requests match your filters' : 'The queue is empty' }}</p>
                <p class="text-sm text-slate-500">{{ (filters.status || filters.q) ? 'Try a different filter or search term.' : 'New requests from residents will appear here.' }}</p>
            </div>

            <Pagination :links="documents.links" />
        </div>

        <!-- Request details and actions, without leaving the list -->
        <template #context>
            <ContextPanel v-if="selected" :title="selected.document_type" :subtitle="selected.tracking_number" @close="selectedId = null">
                <div class="space-y-1">
                    <StatusBadge :status="selected.status" />
                    <p class="pt-1 text-sm text-slate-600">For: {{ selected.purpose }}</p>
                </div>

                <dl class="divide-y text-sm">
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Resident</dt><dd class="font-semibold text-ns-ink">{{ selected.citizen }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Barangay</dt><dd class="font-semibold text-ns-ink">{{ selected.barangay }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Office</dt><dd class="font-semibold text-ns-ink">{{ selected.level === 'city' ? 'City Hall' : 'Barangay' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Fee</dt><dd class="font-semibold tabular-nums text-ns-ink">{{ feeText(selected) }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Requested</dt><dd class="font-semibold text-ns-ink">{{ selected.created_human }}</dd></div>
                </dl>

                <p v-if="selected.remarks" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                    <span class="font-semibold">{{ selected.status === 'needs_resubmission' ? 'Sent back' : 'Note' }}:</span> {{ selected.remarks }}
                </p>

                <p v-if="needsAssessment(selected) && isOpen(selected.status)" class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">
                    This City Hall request cannot be approved until its fee is assessed. Open the file review to assess it.
                </p>

                <div class="flex flex-wrap gap-2">
                    <Link :href="route('staff.documents.review', selected.id)"
                          class="inline-flex items-center gap-1.5 rounded-xl bg-[#F1E8F6] px-4 py-2 text-sm font-semibold text-ns-ink ring-1 ring-ns-violet/30 hover:bg-[#E5D6EE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        <FileSearch class="h-4 w-4" aria-hidden="true" /> {{ isOpen(selected.status) ? 'Review files' : 'View files' }}
                    </Link>
                    <!-- A released document is valid, so staff can print a copy for a resident at the barangay hall -->
                    <a v-if="selected.status === 'released' && selected.level !== 'city'" :href="route('documents.pdf', selected.id)" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <Download class="h-4 w-4" aria-hidden="true" /> Download PDF certificate
                    </a>
                    <Link v-if="selected.status === 'approved'" :href="route('staff.index', { q: selected.tracking_number })"
                          class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        <Landmark class="h-4 w-4" aria-hidden="true" /> Release at counter
                    </Link>
                </div>

                <p v-if="!(transitions[selected.status] ?? []).length" class="text-sm text-slate-500">No further actions for this status.</p>

                <template v-if="(transitions[selected.status] ?? []).length" #actions>
                    <button v-for="s in transitions[selected.status]" :key="s" type="button" @click="choose(selected, s)"
                            :disabled="busy || (s === 'approved' && needsAssessment(selected))"
                            :title="s === 'approved' && needsAssessment(selected) ? 'Assess the fee first' : undefined"
                            :class="['inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet disabled:cursor-not-allowed disabled:opacity-50', actionFor(s).cls]">
                        <component :is="actionFor(s).icon" v-if="actionFor(s).icon" class="h-4 w-4" aria-hidden="true" /> {{ actionFor(s).label }}
                    </button>
                </template>
            </ContextPanel>
        </template>

        <!-- Confirmation (with a reason where the resident needs one) -->
        <ConfirmDialog :open="!!dialog" :title="dialog ? `${actionFor(dialog.status).label} this request?` : ''"
                       :message="dialog ? `${dialog.doc.document_type} · ${dialog.doc.citizen}. ${confirmText[dialog.status] ?? ''}` : ''"
                       :confirm-label="dialog ? `Yes, ${actionFor(dialog.status).label.toLowerCase()}` : 'Confirm'"
                       :tone="dialog && ['rejected', 'revoked'].includes(dialog.status) ? 'danger' : 'default'"
                       :busy="busy" :disabled="reasonMissing" focus-field
                       @confirm="confirm" @cancel="dialog = null">
            <div v-if="dialog && needsReason.includes(dialog.status)">
                <label class="text-sm font-bold text-ns-ink" for="reason">Reason (shown to the resident)</label>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button v-for="c in reasonChips" :key="c" type="button" @click="remarks = c"
                            class="rounded-full bg-[#F1E8F6] px-3 py-1 text-xs font-semibold text-ns-ink hover:bg-[#E5D6EE]">{{ c }}</button>
                </div>
                <textarea id="reason" v-model="remarks" rows="3" placeholder="Explain briefly so the resident knows what to do next"
                          class="mt-2 w-full rounded-xl border-ns-mist text-sm focus:border-ns-violet focus:ring-ns-violet"></textarea>
            </div>
            <p v-if="error" role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                {{ error }}
                <Link v-if="dialog" :href="route('staff.documents.review', dialog.doc.id)" class="ml-1 font-bold underline">Open the file review</Link>
            </p>
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>