<script setup>
/**
 * Resident verification inside the command frame. Pick a resident to open the right-hand panel:
 * verify (after an ID check) or remove verification, and download a PDF copy of any document
 * already issued to them, for when they ask for a copy at the barangay hall.
 * Search, the verified / unverified filter and the counts all run on the server.
 */
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ContextPanel from '@/Components/ContextPanel.vue';
import FilterTabs from '@/Components/FilterTabs.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronRight, Download, Search, ShieldAlert, ShieldCheck, UserX, Users, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ residents: Object, filters: Object, counts: Object });

// ---- Search and filter (server side) ----
const q = ref(props.filters.q ?? '');
const status = ref(props.filters.status || 'all');
const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.counts?.all ?? 0 },
    { key: 'unverified', label: 'Unverified', count: props.counts?.unverified ?? 0 },
    { key: 'verified', label: 'Verified', count: props.counts?.verified ?? 0 },
]);
const apply = () => router.get(route('staff.residents.index'), {
    status: status.value === 'all' ? undefined : status.value,
    q: q.value.trim() || undefined,
}, { preserveState: true, preserveScroll: true, replace: true });
const setStatus = (key) => { status.value = key; apply(); };
const clear = () => { q.value = ''; apply(); };

const initials = (name = '') => name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');

// ---- Selected resident (right-hand panel) ----
const selectedId = ref(null);
const selected = computed(() => props.residents.data.find((r) => r.id === selectedId.value) ?? null);
watch(() => props.residents.data, () => { if (selectedId.value && !selected.value) selectedId.value = null; });

// ---- Verify / remove verification ----
const dialog = ref(null); // 'verify' | 'remove'
const idChecked = ref(false);
const busy = ref(false);
const error = ref('');

const ask = (kind) => { idChecked.value = false; error.value = ''; dialog.value = kind; };
const confirm = () => {
    if (!selected.value || (dialog.value === 'verify' && !idChecked.value)) return;
    router.patch(route('staff.residents.update', selected.value.id), { verified: dialog.value === 'verify' }, {
        preserveScroll: true,
        onStart: () => { busy.value = true; error.value = ''; },
        onFinish: () => { busy.value = false; },
        onSuccess: () => { dialog.value = null; },
        onError: () => { error.value = 'Could not update this resident. Please try again.'; },
    });
};
</script>

<template>
    <Head title="Residents" />
    <AuthenticatedLayout>
        <template #header>Resident Verification</template>

        <div class="mx-auto max-w-5xl space-y-5">
            <!-- Why verification matters -->
            <div class="flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <p>
                    Verify a resident only after checking a <strong>valid ID that shows an address in your barangay</strong>.
                    Verified residents can request documents and vote in community polls.
                </p>
            </div>

            <!-- Search + filter -->
            <div class="space-y-3">
                <form class="flex gap-2" @submit.prevent="apply">
                    <label class="relative block flex-1">
                        <span class="sr-only">Search residents</span>
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input v-model="q" placeholder="Search name, email or phone"
                               class="w-full rounded-xl border-ns-mist py-2.5 pl-10 pr-10 text-sm focus:border-ns-violet focus:ring-ns-violet" />
                        <button v-if="q" type="button" @click="clear" aria-label="Clear search"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"><X class="h-4 w-4" aria-hidden="true" /></button>
                    </label>
                    <button class="rounded-xl bg-ns-violet px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet focus-visible:ring-offset-2">Search</button>
                </form>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <FilterTabs :model-value="status" :tabs="tabs" label="Filter by verification" @update:model-value="setStatus" />
                    <span class="text-sm text-slate-500">Showing {{ residents.from ?? 0 }}–{{ residents.to ?? 0 }} of {{ residents.total }}</span>
                </div>
            </div>

            <!-- Residents -->
            <section v-if="residents.data.length" aria-label="Residents" class="rounded-3xl bg-white p-2 shadow-sm ring-1 ring-black/5">
                <ul class="divide-y">
                    <li v-for="r in residents.data" :key="r.id">
                        <button type="button" :aria-pressed="selectedId === r.id" @click="selectedId = r.id"
                                :class="['flex w-full items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                                         selectedId === r.id ? 'bg-ns-mist' : 'hover:bg-ns-canvas']">
                            <span class="flex min-w-0 items-center gap-3">
                                <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-extrabold', r.verified ? 'bg-emerald-100 text-emerald-700' : 'bg-[#F1E8F6] text-ns-ink']">{{ initials(r.name) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ns-ink">{{ r.name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ r.email }}<template v-if="r.phone"> · {{ r.phone }}</template></span>
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span v-if="r.documents.length" class="hidden text-xs text-slate-500 sm:inline">{{ r.documents.length }} issued</span>
                                <StatusBadge :status="r.verified ? 'verified' : 'unverified'" />
                                <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <div v-else class="rounded-3xl border-2 border-dashed border-ns-violet/20 p-10 text-center">
                <Users class="mx-auto h-8 w-8 text-ns-violet/50" aria-hidden="true" />
                <p class="mt-3 font-semibold text-ns-ink">{{ (filters.q || filters.status) ? 'No residents match your filters' : 'No residents found' }}</p>
                <p class="text-sm text-slate-500">{{ (filters.q || filters.status) ? 'Check the spelling, or try another filter.' : 'Registered residents will appear here.' }}</p>
            </div>

            <Pagination :links="residents.links" />
        </div>

        <!-- Resident details, verification and document copies -->
        <template #context>
            <ContextPanel v-if="selected" :title="selected.name" :subtitle="selected.barangay" @close="selectedId = null">
                <div class="space-y-1">
                    <StatusBadge :status="selected.verified ? 'verified' : 'unverified'" />
                    <p v-if="selected.verified" class="pt-1 text-xs text-emerald-700">Verified {{ selected.verified_at }} by {{ selected.verified_by ?? 'n/a' }}</p>
                </div>

                <dl class="divide-y text-sm">
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Email</dt><dd class="break-all text-right font-semibold text-ns-ink">{{ selected.email }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Phone</dt><dd class="font-semibold text-ns-ink">{{ selected.phone || 'Not given' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Registered</dt><dd class="font-semibold text-ns-ink">{{ selected.registered }}</dd></div>
                </dl>

                <!-- Copies of issued documents, for residents who ask at the barangay hall -->
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Issued documents</h3>
                    <ul v-if="selected.documents.length" class="space-y-2">
                        <li v-for="d in selected.documents" :key="d.id" class="rounded-2xl bg-ns-canvas p-3">
                            <p class="text-sm font-semibold text-ns-ink">{{ d.document_type }}</p>
                            <p class="text-xs text-slate-500"><span class="font-mono">{{ d.tracking_number }}</span> · released {{ d.released }}</p>
                            <a :href="route('documents.pdf', d.id)" target="_blank" rel="noopener"
                               class="mt-2 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                                <Download class="h-3.5 w-3.5" aria-hidden="true" /> Download PDF certificate
                            </a>
                        </li>
                    </ul>
                    <p v-else class="rounded-2xl bg-ns-canvas p-3 text-sm text-slate-500">No documents have been issued to this resident yet.</p>
                </div>

                <template #actions>
                    <button v-if="!selected.verified" type="button" @click="ask('verify')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <ShieldCheck class="h-4 w-4" aria-hidden="true" /> Verify resident
                    </button>
                    <button v-else type="button" @click="ask('remove')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-red-600 ring-1 ring-red-300 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                        <UserX class="h-4 w-4" aria-hidden="true" /> Remove verification
                    </button>
                </template>
            </ContextPanel>
        </template>

        <ConfirmDialog :open="!!dialog && !!selected"
                       :title="dialog === 'verify' ? `Verify ${selected?.name}?` : `Remove verification for ${selected?.name}?`"
                       :message="dialog === 'verify'
                           ? 'Verified residents can request documents and vote in community polls. Only continue after checking their ID in person.'
                           : 'They will be marked as unverified and may no longer be able to request documents or vote in community polls.'"
                       :confirm-label="dialog === 'verify' ? 'Verify resident' : 'Remove verification'"
                       :tone="dialog === 'verify' ? 'default' : 'danger'"
                       :busy="busy" :disabled="dialog === 'verify' && !idChecked"
                       @confirm="confirm" @cancel="dialog = null">
            <label v-if="dialog === 'verify'" class="flex cursor-pointer items-start gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
                <input v-model="idChecked" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-ns-violet focus:ring-ns-violet" />
                <span class="text-sm font-semibold text-amber-900">
                    I have checked a valid ID showing {{ selected?.name }}'s address in this barangay.
                </span>
            </label>
            <p v-if="error" role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>