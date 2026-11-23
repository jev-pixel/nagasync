<script setup>
import DateStrip from '@/Components/DateStrip.vue';
import MetricCard from '@/Components/MetricCard.vue';
import ProgressGauge from '@/Components/ProgressGauge.vue';
import StatBarChart from '@/Components/StatBarChart.vue';
import { iconFor } from '@/icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, ChevronRight, Landmark, MapPin, Newspaper, Store } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    hero: Object, metrics: Array, gauges: Array, week: Array, rangeLabel: String, activityLabel: String,
    openPolls: { type: Array, default: () => [] },
    canVote: { type: Boolean, default: false },
    system: { type: Array, default: null },
    // Same items as the Guide page: { title, date?, tag?, image? }. The panel is hidden when empty.
    news: { type: Array, default: () => [] },
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

// Photos live in public/images/naga/. The header photo follows the role, so each office feels like its own space.
const ROLE_PHOTOS = {
    citizen: '/images/naga/plaza-monument.webp',
    barangay_staff: '/images/naga/city-hall.webp',
    city_staff: '/images/naga/city-hall.webp',
    treasury: '/images/naga/city-hall.webp',
    mdrrmo: '/images/naga/rotonda.webp',
    admin: '/images/naga/porta-mariae.webp',
};
const heroPhoto = computed(() => ROLE_PHOTOS[role.value] ?? '/images/naga/porta-mariae.webp');
const today = new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' });

// Latest news panel: the three newest stories, each with a thumbnail
const NEWS_FALLBACKS = ['/images/naga/plaza-monument.webp', '/images/naga/city-hall.webp', '/images/naga/rotonda.webp'];
const latestNews = computed(() => (props.news ?? []).slice(0, 3).map((n, i) => ({
    ...n,
    img: n.image ?? NEWS_FALLBACKS[i % NEWS_FALLBACKS.length],
    focus: n.focus ?? 'center',
})));
const NEWS_TAGS = { 'City news': 'bg-ns-orange text-ns-ink', Economy: 'bg-ns-teal text-ns-ink', Technology: 'bg-ns-violet text-white', Explainer: 'bg-ns-mist text-ns-navy' };
const newsTag = (t) => NEWS_TAGS[t] ?? 'bg-ns-navy text-white';

const toneBg = { orange: 'bg-ns-orange/20', teal: 'bg-ns-teal/25', coral: 'bg-ns-coral/15', violet: 'bg-ns-violet/15' };
const panel = 'rounded-2xl border border-ns-mist bg-white shadow-card';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout grid>
        <template #header>Dashboard</template>

        <!-- Main column -->
        <div class="col-span-12 flex flex-col gap-6 lg:col-span-8">
            <!-- Command header: city photo under a navy wash -->
            <section class="relative isolate overflow-hidden rounded-2xl bg-ns-navy p-6 text-white shadow-card sm:p-8">
                <img :src="heroPhoto" alt="" aria-hidden="true" fetchpriority="high"
                     class="absolute inset-0 -z-20 h-full w-full object-cover object-center" />
                <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ns-navy via-ns-navy/85 to-ns-navy/30" aria-hidden="true"></div>
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ns-navy/70 via-transparent to-transparent" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-10 -top-10 -z-10 h-44 w-44 rounded-full border-[18px] border-white/10" aria-hidden="true"></div>

                <div class="relative flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/30 backdrop-blur">
                        <Landmark class="h-6 w-6 text-ns-orange" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-widest">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-2.5 py-1 text-white/90 backdrop-blur">
                                <MapPin class="h-3 w-3 text-ns-orange" aria-hidden="true" /> {{ roleLabel }} · City of Naga
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-white/70">
                                <CalendarDays class="h-3 w-3" aria-hidden="true" /> {{ today }}
                            </span>
                        </div>
                        <h2 class="mt-3 text-2xl font-extrabold leading-tight sm:text-3xl">{{ hero.title }}</h2>
                        <p class="mt-1 max-w-xl text-sm text-white/85 sm:text-base">{{ hero.subtitle }}</p>
                        <Link v-if="hero.cta" :href="hero.cta.href"
                              class="mt-5 inline-flex h-11 items-center rounded-xl bg-ns-orange px-5 text-sm font-bold text-ns-ink shadow-card transition hover:brightness-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            {{ hero.cta.label }}
                        </Link>
                    </div>
                </div>
                <div class="absolute inset-x-0 bottom-0 flex h-1" aria-hidden="true">
                    <span class="flex-1 bg-ns-orange"></span><span class="flex-1 bg-ns-teal"></span><span class="flex-1 bg-ns-violet"></span>
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

            <!-- Latest news -->
            <section v-if="latestNews.length" :class="[panel, 'overflow-hidden']" aria-label="Latest news">
                <div class="flex items-center justify-between gap-2 px-5 pb-2 pt-4">
                    <h3 class="flex items-center gap-2 text-base font-bold">
                        <Newspaper class="h-4 w-4 text-ns-violet" aria-hidden="true" /> Latest news
                    </h3>
                    <Link :href="route('guide.index') + '#news'" class="text-sm font-semibold text-ns-violet hover:underline">See all ›</Link>
                </div>
                <ul class="divide-y divide-ns-mist">
                    <li v-for="n in latestNews" :key="n.title">
                        <Link :href="route('guide.index') + '#news'"
                              class="group flex items-center gap-3 px-5 py-3 transition hover:bg-ns-mist/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ns-violet">
                            <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-ns-mist">
                                <img :src="n.img" :style="{ objectPosition: n.focus }" alt="" aria-hidden="true" loading="lazy"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110 motion-reduce:transition-none" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span :class="['rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide', newsTag(n.tag)]">{{ n.tag ?? 'Update' }}</span>
                                    <span v-if="n.date" class="text-[11px] text-slate-500">{{ n.date }}</span>
                                </span>
                                <span class="mt-1 line-clamp-2 block text-sm font-semibold leading-snug">{{ n.title }}</span>
                            </span>
                            <ChevronRight class="h-4 w-4 shrink-0 opacity-40 transition group-hover:translate-x-0.5 group-hover:opacity-80" aria-hidden="true" />
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- City spotlight -->
            <section :class="[panel, 'overflow-hidden']" aria-label="Around Naga">
                <div class="relative isolate h-44">
                    <img src="/images/naga/plaza-monument.webp" alt="Naga plaza monument lit up at night" loading="lazy"
                         class="absolute inset-0 -z-10 h-full w-full object-cover" />
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ns-navy/90 via-ns-navy/20 to-transparent" aria-hidden="true"></div>
                    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-ns-orange">Around Naga</p>
                        <h3 class="text-base font-bold leading-tight">Pilgrim City, lit up at night</h3>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 p-4">
                    <p class="text-sm text-slate-600">Support your neighbors and browse local shops.</p>
                    <Link :href="route('explore.index')"
                          class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-ns-navy px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-ns-ink active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                        <Store class="h-4 w-4" aria-hidden="true" /> Explore
                    </Link>
                </div>
            </section>

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