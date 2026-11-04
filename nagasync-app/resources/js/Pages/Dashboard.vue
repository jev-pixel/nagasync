<script setup>
import DateStrip from '@/Components/DateStrip.vue';
import MetricCard from '@/Components/MetricCard.vue';
import ProgressGauge from '@/Components/ProgressGauge.vue';
import StatBarChart from '@/Components/StatBarChart.vue';
import { iconFor } from '@/icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, Landmark } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    hero: Object, metrics: Array, gauges: Array, week: Array, rangeLabel: String, activityLabel: String,
    openPolls: { type: Array, default: () => [] },
    canVote: { type: Boolean, default: false },
    system: { type: Array, default: null },
});

// Visibility is cosmetic; the route middleware is the real access control (see routes/web.php)
const allServices = [
    { title: 'Request Documents', sub: 'Request and track', href: route('documents.index', {}, false), icon: 'file', tone: 'orange', roles: ['citizen', 'barangay_staff', 'admin'] },
    { title: 'Payments', sub: 'Pay municipal fees', href: route('payments.index', {}, false), icon: 'wallet', tone: 'teal', roles: ['barangay_staff', 'admin'] },
    { title: 'Emergency', sub: 'Hold to send an alert', href: route('emergency.index', {}, false), icon: 'siren', tone: 'coral', roles: ['barangay_staff', 'admin'] },
    { title: 'Market Prices', sub: 'Today\'s prices', href: route('market.index', {}, false), icon: 'store', tone: 'violet', roles: ['citizen', 'admin'] },
    { title: 'Community Polls', sub: 'Have your say', href: route('polls.index', {}, false), icon: 'vote', tone: 'orange', roles: ['citizen', 'admin'] },
    { title: 'Explore Market', sub: 'Local businesses', href: route('explore.index', {}, false), icon: 'compass', tone: 'teal', roles: ['citizen', 'barangay_staff', 'admin'] },
    { title: 'Document Queues', sub: 'Review incoming requests', href: route('staff.documents.index', {}, false), icon: 'list', tone: 'violet', roles: ['barangay_staff', 'admin'] },
];

const ROLE_LABELS = {
    citizen: 'Citizen', barangay_staff: 'Barangay Staff', city_staff: 'City Hall Staff',
    treasury: 'Treasury Office', mdrrmo: 'MDRRMO Responder', admin: 'Administrator',
};

const role = computed(() => usePage().props.auth.user?.role);
const roleLabel = computed(() => ROLE_LABELS[role.value] ?? 'Account');
const services = computed(() => allServices.filter((s) => s.roles.includes(role.value)));
const canSeePolls = computed(() => ['citizen', 'admin'].includes(role.value));

// The controller sends the same cards to everyone. Drop the ones that link to a module this role
// cannot open (they would 403): path prefix -> roles allowed. Keep in sync with routes/web.php.
const MODULE_ROLES = {
    '/payments': ['barangay_staff', 'admin'],
    '/emergency': ['barangay_staff', 'admin'],
    '/polls': ['citizen', 'admin'],
    '/market': ['citizen', 'admin'],
    '/documents': ['citizen', 'barangay_staff', 'admin'],
};
const canOpen = (href) => {
    let path = '';
    try { path = new URL(href ?? '', window.location.origin).pathname; } catch { return true; }
    const hit = Object.entries(MODULE_ROLES).find(([prefix]) => path.startsWith(prefix));
    return !hit || hit[1].includes(role.value);
};
const visibleMetrics = computed(() => (props.metrics ?? []).filter((m) => canOpen(m.href)));
const visibleGauges = computed(() => props.gauges ?? []);

const toneBg = { orange: 'bg-ns-orange/20', teal: 'bg-ns-teal/25', coral: 'bg-ns-coral/15', violet: 'bg-ns-violet/15' };
const panel = 'rounded-2xl border border-ns-mist bg-white shadow-card';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout grid>
        <template #header>Dashboard</template>

        <!-- Main column -->
        <div class="col-span-12 flex flex-col gap-6 lg:col-span-8">
            <!-- Command header -->
            <section class="relative overflow-hidden rounded-2xl bg-ns-navy p-6 text-white shadow-card">
                <div class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full border-[18px] border-white/5" aria-hidden="true"></div>
                <div class="relative flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <Landmark class="h-6 w-6 text-ns-orange" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-white/60">{{ roleLabel }} · City of Naga</p>
                        <h2 class="mt-1 text-2xl font-extrabold leading-tight">{{ hero.title }}</h2>
                        <p class="mt-1 text-sm text-white/75">{{ hero.subtitle }}</p>
                        <Link v-if="hero.cta" :href="hero.cta.href"
                              class="mt-4 inline-flex h-11 items-center rounded-xl bg-ns-orange px-5 text-sm font-bold text-ns-ink transition hover:brightness-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            {{ hero.cta.label }}
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Service hub: every office reachable from one place -->
            <section v-if="services.length" :class="[panel, 'order-first p-4 md:order-none md:p-5']" aria-label="Service hub">
                <div class="mb-3 flex items-baseline justify-between">
                    <h3 class="text-base font-bold">Service hub</h3>
                    <span class="hidden text-xs text-slate-500 md:inline">{{ services.length }} services for you</span>
                    <Link :href="route('services.index')" class="text-sm font-semibold text-ns-violet md:hidden">See all ›</Link>
                </div>

                <!-- Mobile: icon tiles -->
                <div class="grid grid-cols-4 gap-2 md:hidden">
                    <Link v-for="s in services" :key="s.href" :href="s.href"
                          class="flex flex-col items-center gap-1.5 rounded-xl border border-ns-mist p-2 text-center transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        <span :class="['flex h-11 w-11 items-center justify-center rounded-xl', toneBg[s.tone]]">
                            <component :is="iconFor(s.icon)" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="text-[11px] font-semibold leading-tight">{{ s.title }}</span>
                    </Link>
                </div>

                <!-- Tablet / desktop: rows -->
                <div class="hidden gap-3 md:grid md:grid-cols-2 xl:grid-cols-3">
                    <Link v-for="s in services" :key="s.href" :href="s.href"
                          class="group flex items-center gap-3 rounded-xl border border-ns-mist p-3 transition hover:border-ns-violet/40 hover:shadow-md active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', toneBg[s.tone]]">
                            <component :is="iconFor(s.icon)" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ s.title }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ s.sub }}</span>
                        </span>
                        <ChevronRight class="h-4 w-4 opacity-40 transition group-hover:translate-x-0.5 group-hover:opacity-80" aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <DateStrip :days="week" :range-label="rangeLabel" :label="activityLabel" />

            <section aria-label="Key figures" class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <MetricCard v-for="m in visibleMetrics" :key="m.label" :metric="m" />
            </section>

            <section aria-label="Progress" class="grid gap-4 sm:grid-cols-2">
                <div v-for="(g, i) in visibleGauges" :key="g.label"
                     :class="['flex items-center justify-between gap-4 rounded-2xl p-5 shadow-card', i % 2 ? 'bg-ns-navy text-white' : 'border border-ns-mist bg-white']">
                    <div>
                        <h3 class="text-base font-bold">{{ g.label }}</h3>
                        <p :class="['text-xs', i % 2 ? 'text-white/70' : 'text-slate-500']">{{ g.sub }}</p>
                    </div>
                    <ProgressGauge :percent="g.percent" :tone="g.tone" :label="g.label" :dark="i % 2 === 1" />
                </div>
            </section>
        </div>

        <!-- Right panel -->
        <aside class="col-span-12 space-y-6 lg:col-span-4">
            <section v-if="openPolls.length && canSeePolls" :class="[panel, 'border-l-4 border-l-ns-violet p-5']">
                <h3 class="mb-2 text-base font-bold">Polls open for your vote</h3>
                <ul class="divide-y text-sm">
                    <li v-for="p in openPolls" :key="p.id" class="flex items-center justify-between gap-2 py-2">
                        <span>{{ p.title }} <span class="text-slate-400">· closes {{ p.closes_at }}</span></span>
                        <Link :href="route('polls.index')" class="font-semibold text-ns-violet underline">{{ canVote ? 'Vote' : 'View' }}</Link>
                    </li>
                </ul>
                <p v-if="!canVote" class="mt-2 text-xs text-amber-700">To vote, your barangay must first verify you as a resident.</p>
            </section>

            <StatBarChart :title="activityLabel" :bars="week" />

            <section v-if="system" :class="[panel, 'p-5']" aria-label="System status">
                <h3 class="mb-3 text-base font-bold">System status</h3>
                <ul class="divide-y text-sm">
                    <li v-for="s in system" :key="s.label" class="flex items-center justify-between gap-2 py-2">
                        <span class="flex items-center gap-2">
                            <span :class="['h-2.5 w-2.5 rounded-full', s.ok ? 'bg-emerald-500' : 'bg-ns-coral']" aria-hidden="true"></span>
                            {{ s.label }}
                        </span>
                        <span class="text-xs text-slate-500">{{ s.ok ? s.detail : 'Check: ' + s.detail }}</span>
                    </li>
                </ul>
            </section>
        </aside>
    </AuthenticatedLayout>
</template>
