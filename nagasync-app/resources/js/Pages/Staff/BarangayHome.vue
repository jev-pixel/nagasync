<script setup>
/**
 * Barangay desk: who is waiting to be verified, which requests need review, and what is ready
 * for the counter. Verify a resident from the right-hand panel without leaving the page.
 * Everything here is limited to the staff member's own barangay on the server.
 */
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ContextPanel from '@/Components/ContextPanel.vue';
import KpiCard from '@/Components/KpiCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useLiveData } from '@/utils/useLiveData';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, ChevronRight, MapPinOff, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    barangay: String,
    stats: Object,
    unverifiedResidents: Array,
    queue: Array,
});

useLiveData({ only: ['stats', 'unverifiedResidents', 'queue'], interval: 15000 });

// ---- Resident panel ----
const selectedId = ref(null);
const selected = computed(() => props.unverifiedResidents.find((r) => r.id === selectedId.value) ?? null);

const confirming = ref(false);
const busy = ref(false);
const error = ref('');

const verify = () => {
    router.patch(route('staff.residents.update', selected.value.id), { verified: true }, {
        preserveScroll: true,
        onStart: () => { busy.value = true; error.value = ''; },
        onFinish: () => { busy.value = false; },
        onSuccess: () => { confirming.value = false; selectedId.value = null; },
        onError: () => { error.value = 'Could not verify this resident. Open the residents list to try again.'; },
    });
};
</script>

<template>
    <Head title="Barangay Desk" />
    <AuthenticatedLayout>
        <template #header>Barangay Desk<template v-if="barangay"> · {{ barangay }}</template></template>

        <div class="mx-auto max-w-6xl space-y-6">
            <!-- No barangay: every number would be zero, so say why -->
            <div v-if="!barangay" role="alert" class="flex items-start gap-3 rounded-3xl bg-amber-50 p-5 text-amber-900 ring-1 ring-amber-200">
                <MapPinOff class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <p class="text-sm"><strong>Your account is not assigned to a barangay yet.</strong>
                    You will see residents and requests once an administrator assigns one.</p>
            </div>

            <!-- Key numbers: residents first, since verifying unlocks a citizen's account -->
            <section aria-label="Key numbers" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="Unverified residents" :value="stats.unverified" :tone="stats.unverified > 0 ? 'warning' : 'normal'"
                         hint="cannot use services until verified"
                         :href="route('staff.residents.index', { status: 'unverified' })" action="Verify residents" />
                <KpiCard label="Awaiting review" :value="stats.awaiting" :tone="stats.awaiting > 0 ? 'warning' : 'normal'"
                         hint="pending or being reviewed"
                         :href="route('staff.documents.index', { status: 'open' })" action="Review queue" />
                <KpiCard label="Ready for the counter" :value="stats.ready" hint="approved, waiting for payment and hand-over"
                         :href="route('staff.index')" action="Open counter" />
                <KpiCard label="Released today" :value="stats.releasedToday" hint="handed over to residents" />
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Residents waiting -->
                <section aria-label="Residents waiting for verification" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="flex items-center gap-2 font-bold text-ns-ink"><UserCheck class="h-4 w-4" aria-hidden="true" /> Waiting for verification</h2>
                        <p class="text-xs text-slate-500">Longest waiting first</p>
                    </div>

                    <ul v-if="unverifiedResidents.length" class="mt-3 divide-y">
                        <li v-for="r in unverifiedResidents" :key="r.id">
                            <button type="button" :aria-pressed="selectedId === r.id" @click="selectedId = r.id"
                                    :class="['flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                                             selectedId === r.id ? 'bg-ns-mist' : 'hover:bg-ns-canvas']">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ns-ink">{{ r.name }}</span>
                                    <span class="block truncate text-xs text-slate-500">registered {{ r.registered_human }}</span>
                                </span>
                                <ChevronRight class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                            </button>
                        </li>
                    </ul>
                    <div v-else class="mt-4 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-800">
                        <CheckCircle2 class="h-5 w-5 shrink-0" aria-hidden="true" />
                        <p class="text-sm font-semibold">Everyone in your barangay is verified.</p>
                    </div>

                    <Link v-if="stats.unverified > unverifiedResidents.length" :href="route('staff.residents.index', { status: 'unverified' })"
                          class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-ns-violet hover:underline">
                        See all {{ stats.unverified }} <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </section>

                <!-- Requests waiting -->
                <section aria-label="Requests waiting for review" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="font-bold text-ns-ink">Requests waiting for review</h2>
                        <p class="text-xs text-slate-500">Longest waiting first</p>
                    </div>

                    <ul v-if="queue.length" class="mt-3 divide-y">
                        <li v-for="d in queue" :key="d.id">
                            <Link :href="route('staff.documents.index', { q: d.tracking_number })"
                                  class="flex items-center justify-between gap-3 rounded-xl px-3 py-3 transition hover:bg-ns-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ns-ink">{{ d.document_type }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ d.citizen }} · {{ d.created_human }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <StatusBadge :status="d.status" />
                                    <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
                                </span>
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="mt-4 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-800">
                        <CheckCircle2 class="h-5 w-5 shrink-0" aria-hidden="true" />
                        <p class="text-sm font-semibold">No requests are waiting for review.</p>
                    </div>

                    <Link v-if="stats.awaiting > queue.length" :href="route('staff.documents.index', { status: 'open' })"
                          class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-ns-violet hover:underline">
                        See all {{ stats.awaiting }} <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </section>
            </div>
        </div>

        <!-- Verify a resident without leaving the desk -->
        <template #context>
            <ContextPanel v-if="selected" :title="selected.name" subtitle="Resident awaiting verification" @close="selectedId = null">
                <dl class="divide-y text-sm">
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Email</dt><dd class="break-all text-right font-semibold text-ns-ink">{{ selected.email }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Phone</dt><dd class="font-semibold text-ns-ink">{{ selected.phone || 'Not given' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">Registered</dt><dd class="font-semibold text-ns-ink">{{ selected.registered }}</dd></div>
                </dl>

                <p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">
                    Verify only after checking this person's ID and that they live in your barangay.
                    Verified residents can request documents and join polls.
                </p>

                <template #actions>
                    <button type="button" @click="confirming = true"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        <UserCheck class="h-4 w-4" aria-hidden="true" /> Verify resident
                    </button>
                    <Link :href="route('staff.residents.index', { status: 'unverified' })"
                          class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        Residents list
                    </Link>
                </template>
            </ContextPanel>
        </template>

        <ConfirmDialog :open="confirming && !!selected" title="Verify this resident?"
                       :message="selected ? `${selected.name} will be able to request documents and join polls. You can undo this later from the residents list.` : ''"
                       confirm-label="Yes, verify" :busy="busy" @confirm="verify" @cancel="confirming = false">
            <p v-if="error" role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>