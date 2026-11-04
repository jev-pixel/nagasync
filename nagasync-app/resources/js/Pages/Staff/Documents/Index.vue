<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Check, FileSearch, Inbox, Play, RotateCcw, Search, X } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({ documents: Object, transitions: Object });

// What each button does, in plain words, and how it looks
const actions = {
    processing: { label: 'Start processing', icon: Play, cls: 'bg-[#3D2470] text-white hover:bg-[#2E1B58]' },
    approved: { label: 'Approve', icon: Check, cls: 'bg-emerald-600 text-white hover:bg-emerald-700' },
    rejected: { label: 'Reject', icon: X, cls: 'bg-white text-red-600 ring-1 ring-red-300 hover:bg-red-50' },
    revoked: { label: 'Revoke', icon: Ban, cls: 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' },
    needs_resubmission: { label: 'Ask to resubmit', icon: RotateCcw, cls: 'bg-white text-amber-700 ring-1 ring-amber-300 hover:bg-amber-50' },
};
// Safe lookup: an unknown status can never crash the page
const actionFor = (s) => actions[s] ?? { label: label(s), icon: null, cls: 'bg-white text-gray-700 ring-1 ring-gray-300' };
const label = (s) => String(s ?? '').replace(/_/g, ' ');
const needsReason = ['rejected', 'revoked', 'needs_resubmission'];
const confirmText = {
    approved: 'The resident will see this document as approved and get a verification QR code.',
    rejected: 'The resident will see your reason. They can submit a new request.',
    revoked: 'The document will no longer be valid. The resident will see your reason.',
    needs_resubmission: 'The resident will see what to fix and can send the request again.',
};
const isOpen = (st) => ['pending', 'processing', 'needs_resubmission'].includes(st);
const reasonChips = ['Incomplete information', 'Cannot verify residency', 'Duplicate request', 'Blurry or missing attachment'];

const tone = {
    pending: 'bg-amber-100 text-amber-800', processing: 'bg-sky-100 text-sky-800',
    approved: 'bg-emerald-100 text-emerald-800', rejected: 'bg-red-100 text-red-800', revoked: 'bg-gray-200 text-gray-700',
    needs_resubmission: 'bg-orange-100 text-orange-800', released: 'bg-teal-100 text-teal-800',
};

// Filters work on the rows loaded on this page
const tabs = ['all', 'pending', 'processing', 'needs_resubmission', 'approved', 'released', 'rejected', 'revoked'];
const tab = ref('all');
const q = ref('');
const counts = computed(() => {
    const c = { all: props.documents.data.length };
    props.documents.data.forEach((d) => (c[d.status] = (c[d.status] ?? 0) + 1));
    return c;
});
const rows = computed(() => props.documents.data.filter((d) => {
    if (tab.value !== 'all' && d.status !== tab.value) return false;
    const s = q.value.trim().toLowerCase();
    return !s || [d.tracking_number, d.citizen, d.document_type, d.barangay].some((v) => String(v ?? '').toLowerCase().includes(s));
}));

// Confirmation dialog (replaces the browser prompt)
const dialog = ref(null); // { doc, status }
const remarks = ref('');
const busy = ref(null);
const reasonBox = ref(null);
const error = ref('');

function send(doc, status, text) {
    router.patch(route('staff.documents.update', doc.id), { status, remarks: text }, {
        preserveScroll: true,
        onStart: () => (busy.value = doc.id),
        onFinish: () => (busy.value = null),
        onSuccess: () => { dialog.value = null; error.value = ''; },
        onError: (errs) => { error.value = errs.status ?? errs.remarks ?? 'Could not update this request.'; },
    });
}
function choose(doc, status) {
    if (status === 'processing') return send(doc, status, null); // low-risk step, no confirmation needed
    remarks.value = '';
    error.value = '';
    dialog.value = { doc, status };
    nextTick(() => reasonBox.value?.focus());
}
const reasonMissing = computed(() => dialog.value && needsReason.includes(dialog.value.status) && !remarks.value.trim());
function confirm() {
    if (!dialog.value || reasonMissing.value) return;
    const { doc, status } = dialog.value;
    send(doc, status, needsReason.includes(status) ? remarks.value.trim() : null);
}
</script>

<template>
    <Head title="Document Queue" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Document Queue</h2></template>

        <div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
            <p class="rounded-2xl bg-[#F1E8F6] p-4 text-sm text-[#3D2470]">
                Review each request, then use the buttons on its card. <strong>Start processing</strong> tells the resident you are working on it.
                <strong>Approve</strong> issues the document.
            </p>

            <!-- Search + status filter -->
            <div class="space-y-3">
                <label class="relative block">
                    <span class="sr-only">Search requests</span>
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="q" placeholder="Search by tracking number, name, document or barangay"
                           class="w-full rounded-2xl border-gray-300 pl-10 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                </label>
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter by status">
                    <button v-for="t in tabs" :key="t" type="button" role="tab" :aria-selected="tab === t" @click="tab = t"
                            class="rounded-full px-4 py-1.5 text-sm font-semibold capitalize ring-1 transition"
                            :class="tab === t ? 'bg-[#3D2470] text-white ring-[#3D2470]' : 'bg-white text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#F1E8F6]'">
                        {{ label(t) }} <span class="opacity-70">({{ counts[t] ?? 0 }})</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500">Search and filters apply to the requests shown on this page.</p>
            </div>

            <!-- Requests -->
            <div v-if="rows.length" class="space-y-4">
                <article v-for="d in rows" :key="d.id" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-lg font-bold text-[#1B1340]">{{ d.document_type }}</p>
                            <p class="text-sm text-gray-600">For: {{ d.purpose }}</p>
                        </div>
                        <span :class="tone[d.status]" class="shrink-0 rounded-full px-3 py-1 text-xs font-bold capitalize">{{ label(d.status) }}</span>
                    </div>

                    <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                        <div><dt class="text-xs text-gray-500">Resident</dt><dd class="font-semibold">{{ d.citizen }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Barangay</dt><dd class="font-semibold">{{ d.barangay }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Tracking no.</dt><dd class="select-all font-mono text-xs">{{ d.tracking_number }}</dd></div>
                    </dl>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t pt-4">
                        <Link :href="route('staff.documents.review', d.id)"
                              class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-bold ring-1 transition"
                              :class="isOpen(d.status) ? 'bg-[#F1E8F6] text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#E5D6EE]' : 'bg-white text-gray-700 ring-gray-300 hover:bg-gray-50'">
                            <FileSearch class="h-4 w-4" /> {{ isOpen(d.status) ? 'Review files' : 'View files' }}
                        </Link>
                        <button v-for="s in (transitions[d.status] ?? [])" :key="s" type="button" @click="choose(d, s)" :disabled="busy === d.id"
                                class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="actionFor(s).cls">
                            <component :is="actionFor(s).icon" v-if="actionFor(s).icon" class="h-4 w-4" /> {{ actionFor(s).label }}
                        </button>
                        <span v-if="!transitions[d.status]?.length" class="text-sm text-gray-500">No further actions for this status.</span>
                    </div>
                    <p v-if="d.status === 'approved'" class="mt-2 text-xs text-gray-500">Releasing the document (payment and hand-over) is done at the City Hall counter.</p>
                    <p v-if="d.status === 'needs_resubmission' && d.remarks" class="mt-2 text-xs text-orange-700">Sent back: {{ d.remarks }}</p>
                </article>
            </div>

            <div v-else class="rounded-3xl border-2 border-dashed border-[#3D2470]/20 p-10 text-center">
                <Inbox class="mx-auto h-8 w-8 text-[#3D2470]/50" />
                <p class="mt-3 font-semibold text-[#3D2470]">{{ documents.data.length ? 'No requests match your search' : 'The queue is empty' }}</p>
                <p class="text-sm text-gray-500">{{ documents.data.length ? 'Try a different filter or search term.' : 'New requests from residents will appear here.' }}</p>
            </div>

            <Pagination :links="documents.links" />
        </div>

        <!-- Confirmation dialog -->
        <div v-if="dialog" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @keydown.esc="dialog = null">
            <div role="dialog" aria-modal="true" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-extrabold text-[#3D2470]">{{ actionFor(dialog.status).label }} this request?</h3>
                <p class="mt-1 text-sm font-semibold text-[#1B1340]">{{ dialog.doc.document_type }} · {{ dialog.doc.citizen }}</p>
                <p class="mt-2 text-sm text-gray-600">{{ confirmText[dialog.status] ?? '' }}</p>

                <div v-if="needsReason.includes(dialog.status)" class="mt-4">
                    <label class="text-sm font-bold text-[#1B1340]" for="reason">Reason (shown to the resident)</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button v-for="c in reasonChips" :key="c" type="button" @click="remarks = c"
                                class="rounded-full bg-[#F1E8F6] px-3 py-1 text-xs font-semibold text-[#3D2470] hover:bg-[#E5D6EE]">{{ c }}</button>
                    </div>
                    <textarea id="reason" ref="reasonBox" v-model="remarks" rows="3" placeholder="Explain briefly so the resident knows what to do next"
                              class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-[#3D2470] focus:ring-[#3D2470]"></textarea>
                </div>

                <p v-if="error" role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}
                    <Link :href="route('staff.documents.review', dialog.doc.id)" class="ml-1 font-bold underline">Open the file review</Link>
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="dialog = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="button" @click="confirm" :disabled="reasonMissing || busy === dialog.doc.id"
                            class="rounded-xl px-5 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-50"
                            :class="actionFor(dialog.status).cls">
                        {{ busy === dialog.doc.id ? 'Saving…' : `Yes, ${actionFor(dialog.status).label.toLowerCase()}` }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>