<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BadgeCheck, Mail, Phone, Search, ShieldAlert, ShieldCheck, UserX, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ residents: Object, filters: Object });

// Search runs on the server, so it covers every resident, not just this page
const q = ref(props.filters.q ?? '');
const search = () => router.get(route('staff.residents.index'), { q: q.value }, { preserveState: true, replace: true });
const clear = () => { q.value = ''; search(); };

// Verified / unverified tabs only filter the residents shown on this page
const tab = ref('all');
const rows = computed(() => props.residents.data.filter((r) =>
    tab.value === 'all' || (tab.value === 'verified' ? r.verified : !r.verified)));
const count = computed(() => ({
    all: props.residents.data.length,
    unverified: props.residents.data.filter((r) => !r.verified).length,
    verified: props.residents.data.filter((r) => r.verified).length,
}));

const initials = (name = '') => name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');

// Confirmation dialog (replaces the browser confirm box)
const dialog = ref(null); // { resident, verified }
const idChecked = ref(false);
const busy = ref(false);

function ask(resident, verified) {
    idChecked.value = false;
    dialog.value = { resident, verified };
}
function confirm() {
    const { resident, verified } = dialog.value;
    if (verified && !idChecked.value) return;
    router.patch(route('staff.residents.update', resident.id), { verified }, {
        preserveScroll: true,
        onStart: () => (busy.value = true),
        onFinish: () => (busy.value = false),
        onSuccess: () => (dialog.value = null),
    });
}
</script>

<template>
    <Head title="Residents" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Resident Verification</h2></template>

        <div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
            <!-- Why verification matters -->
            <div class="flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0" />
                <p>
                    Verify a resident only after checking a <strong>valid ID that shows an address in your barangay</strong>.
                    Verified residents can vote in community polls.
                </p>
            </div>

            <!-- Search (server-side) -->
            <form @submit.prevent="search" class="flex gap-2">
                <label class="relative block flex-1">
                    <span class="sr-only">Search residents</span>
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="q" placeholder="Search name, email or phone"
                           class="w-full rounded-2xl border-gray-300 pl-10 pr-10 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                    <button v-if="q" type="button" @click="clear" aria-label="Clear search"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><X class="h-4 w-4" /></button>
                </label>
                <button class="rounded-2xl bg-[#F04A2B] px-6 py-2 font-bold text-white shadow transition hover:bg-[#D93C1E]">Search</button>
            </form>

            <!-- Filter tabs + count -->
            <div class="space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter by verification">
                        <button v-for="t in ['all', 'unverified', 'verified']" :key="t" type="button" role="tab" :aria-selected="tab === t" @click="tab = t"
                                class="rounded-full px-4 py-1.5 text-sm font-semibold capitalize ring-1 transition"
                                :class="tab === t ? 'bg-[#3D2470] text-white ring-[#3D2470]' : 'bg-white text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#F1E8F6]'">
                            {{ t }} <span class="opacity-70">({{ count[t] }})</span>
                        </button>
                    </div>
                    <span class="text-sm text-gray-600">Showing {{ residents.from ?? 0 }}–{{ residents.to ?? 0 }} of {{ residents.total }}</span>
                </div>
                <p class="text-xs text-gray-500">The tabs filter the residents on this page. Search covers all residents.</p>
            </div>

            <!-- Residents -->
            <div v-if="rows.length" class="space-y-3">
                <article v-for="r in rows" :key="r.id" class="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-extrabold"
                              :class="r.verified ? 'bg-emerald-100 text-emerald-700' : 'bg-[#F1E8F6] text-[#3D2470]'">{{ initials(r.name) }}</span>
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-bold text-[#1B1340]">
                                {{ r.name }}
                                <span v-if="r.verified" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                    <BadgeCheck class="h-3.5 w-3.5" /> Verified
                                </span>
                                <span v-else class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Unverified</span>
                            </p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-0.5 text-sm text-gray-600">
                                <span class="inline-flex items-center gap-1"><Mail class="h-3.5 w-3.5" /> {{ r.email }}</span>
                                <span v-if="r.phone" class="inline-flex items-center gap-1"><Phone class="h-3.5 w-3.5" /> {{ r.phone }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ r.barangay }} · Registered {{ r.registered }}</p>
                            <p v-if="r.verified" class="mt-0.5 text-xs text-emerald-700">Verified {{ r.verified_at }} by {{ r.verified_by ?? 'n/a' }}</p>
                        </div>
                    </div>

                    <button v-if="!r.verified" type="button" @click="ask(r, true)"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-emerald-700">
                        <ShieldCheck class="h-4 w-4" /> Verify
                    </button>
                    <button v-else type="button" @click="ask(r, false)"
                            class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-2.5 text-sm font-bold text-red-600 ring-1 ring-red-300 transition hover:bg-red-50">
                        <UserX class="h-4 w-4" /> Remove verification
                    </button>
                </article>
            </div>

            <div v-else class="rounded-3xl border-2 border-dashed border-[#3D2470]/20 p-10 text-center">
                <Users class="mx-auto h-8 w-8 text-[#3D2470]/50" />
                <p class="mt-3 font-semibold text-[#3D2470]">
                    {{ !residents.data.length ? (filters.q ? 'No residents match your search' : 'No residents found') : 'No residents match this filter' }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ !residents.data.length && filters.q ? 'Check the spelling or try a name, email or phone number.' : 'Try another tab or search term.' }}
                </p>
            </div>

            <Pagination :links="residents.links" />
        </div>

        <!-- Confirmation dialog -->
        <div v-if="dialog" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @keydown.esc="dialog = null">
            <div role="dialog" aria-modal="true" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                <template v-if="dialog.verified">
                    <h3 class="text-lg font-extrabold text-[#3D2470]">Verify {{ dialog.resident.name }}?</h3>
                    <p class="mt-2 text-sm text-gray-600">Verified residents can vote in community polls. Only continue after checking their ID in person.</p>
                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
                        <input v-model="idChecked" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-gray-300 text-[#3D2470] focus:ring-[#3D2470]" />
                        <span class="text-sm font-semibold text-amber-900">
                            I have checked a valid ID showing {{ dialog.resident.name }}'s address in this barangay.
                        </span>
                    </label>
                </template>
                <template v-else>
                    <h3 class="text-lg font-extrabold text-[#3D2470]">Remove verification for {{ dialog.resident.name }}?</h3>
                    <p class="mt-2 text-sm text-gray-600">They will be marked as unverified and may no longer be able to vote in community polls.</p>
                </template>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="dialog = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="button" @click="confirm" :disabled="busy || (dialog.verified && !idChecked)"
                            class="rounded-xl px-5 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                            :class="dialog.verified ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'">
                        {{ busy ? 'Saving…' : dialog.verified ? 'Verify resident' : 'Remove verification' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>