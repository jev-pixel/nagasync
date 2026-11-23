<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useNavigation } from '@/navigation';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, FileText, Landmark, LogOut, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

// { barangay: [{ type, requirements }], city_hall: [...] }. Sent by ServicesController, citizens only.
const props = defineProps({
    requestTypes: { type: Object, default: () => ({ barangay: [], city_hall: [] }) },
});

const { items, verified } = useNavigation();
const isCitizen = computed(() => usePage().props.auth.user?.role === 'citizen');

// Citizen mobile layout: the "Requests" list row is replaced by one button per kind of request.
const requestSections = computed(() => [
    { key: 'barangay', title: 'Barangay requests', icon: FileText, tone: 'bg-ns-orange/20', types: props.requestTypes?.barangay ?? [] },
    { key: 'city_hall', title: 'City Hall requests', icon: Landmark, tone: 'bg-ns-teal/25', types: props.requestTypes?.city_hall ?? [] },
].filter((sec) => sec.types.length));
const showRequestGrid = computed(() => isCitizen.value && verified.value && requestSections.value.length > 0);

// What each menu item is for, shown under its name
const DESC = {
    guide: 'News, guidelines and how to request',
    documents: 'Request and track barangay documents',
    queue: 'Review incoming document requests',
    counter: 'Collect payment and release documents',
    residents: 'Verify resident accounts',
    payments: 'Collect and review municipal fees',
    emergency: 'Send a GPS-tagged emergency alert',
    dispatch: 'Respond to emergency alerts',
    treasury: 'Payments ledger',
    market: 'Today\'s prices in the public market',
    prices: 'Enter market prices',
    polls: 'Have your say on local concerns',
    explore: 'Browse local businesses',
    mybiz: 'Manage business listings',
    bizreview: 'Review business listings',
    command: 'Live city-wide overview',
    'manage-polls': 'Create and publish polls',
};

const GROUPS = [
    { title: 'Requests', keys: ['documents', 'queue', 'counter', 'residents'] },
    { title: 'Payments & Safety', keys: ['payments', 'emergency', 'dispatch', 'treasury'] },
    { title: 'Community', keys: ['guide', 'market', 'polls', 'explore', 'mybiz', 'bizreview', 'prices'] },
    { title: 'Administration', keys: ['command', 'manage-polls'] },
];

// Only what the signed-in role can open: the list comes from the same role-filtered navigation
const groups = computed(() => {
    const used = new Set(['dashboard']);
    const out = GROUPS.map((g) => ({
        title: g.title,
        rows: g.keys.map((k) => { used.add(k); return items.value.find((i) => i.key === k); }).filter(Boolean),
    }));
    const rest = items.value.filter((i) => !used.has(i.key));
    if (rest.length) out.push({ title: 'Other', rows: rest });
    return out.filter((g) => g.rows.length);
});

const row = 'flex items-center gap-3 px-4 py-3 transition hover:bg-ns-mist active:bg-ns-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ns-violet';
</script>

<template>
    <Head title="Services" />

    <AuthenticatedLayout>
        <template #header>Services</template>

        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Citizen, mobile only: a button grid for each kind of request -->
            <div v-if="showRequestGrid" class="space-y-6 md:hidden">
                <section v-for="sec in requestSections" :key="sec.key" :aria-label="sec.title">
                    <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-widest text-slate-500">{{ sec.title }}</h2>
                    <div class="grid grid-cols-2 gap-2.5">
                        <Link v-for="t in sec.types" :key="t.type" :href="route('documents.index', { type: t.type })"
                              class="flex flex-col items-start gap-2 rounded-2xl border border-ns-mist bg-white p-3 text-left shadow-card transition active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                            <span :class="['flex h-11 w-11 items-center justify-center rounded-xl text-ns-navy', sec.tone]">
                                <component :is="sec.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span class="block text-sm font-semibold leading-snug">{{ t.type }}</span>
                            <span class="block text-xs text-slate-500">{{ t.requirements }} {{ t.requirements === 1 ? 'requirement' : 'requirements' }}</span>
                        </Link>
                    </div>
                </section>

                <Link :href="route('documents.index')"
                      class="flex items-center gap-3 rounded-2xl border border-ns-mist bg-white px-4 py-3 shadow-card transition active:bg-ns-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold">My requests</span>
                        <span class="block text-xs text-slate-500">Track and manage your documents</span>
                    </span>
                    <ChevronRight class="h-4 w-4 shrink-0 opacity-40" aria-hidden="true" />
                </Link>
            </div>

            <section v-for="g in groups" :key="g.title" :aria-label="g.title"
                     :class="showRequestGrid && g.title === 'Requests' ? 'hidden md:block' : ''">
                <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-widest text-slate-500">{{ g.title }}</h2>
                <ul class="divide-y divide-ns-mist overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card">
                    <li v-for="i in g.rows" :key="i.key">
                        <Link :href="route(i.route)" :class="row">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ns-violet/15">
                                <component :is="i.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">{{ i.label }}</span>
                                <span v-if="DESC[i.key]" class="block text-xs text-slate-500">{{ DESC[i.key] }}</span>
                            </span>
                            <ChevronRight class="h-4 w-4 shrink-0 opacity-40" aria-hidden="true" />
                        </Link>
                    </li>
                </ul>
            </section>

            <section aria-label="Account">
                <h2 class="mb-2 px-1 text-xs font-semibold uppercase tracking-widest text-slate-500">Account</h2>
                <ul class="divide-y divide-ns-mist overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card">
                    <li>
                        <Link :href="route('profile.edit')" :class="row">
                            <UserRound class="h-5 w-5 text-slate-500" aria-hidden="true" />
                            <span class="flex-1 text-sm font-semibold">Profile</span>
                            <ChevronRight class="h-4 w-4 opacity-40" aria-hidden="true" />
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('logout')" method="post" as="button" :class="[row, 'w-full text-left']">
                            <LogOut class="h-5 w-5 text-slate-500" aria-hidden="true" />
                            <span class="flex-1 text-sm font-semibold">Log out</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>