<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GovBanner from '@/Components/GovBanner.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight, BadgeCheck, FileText, Landmark, MapPinned, QrCode, ScanSearch, ShieldCheck,
    ShoppingBasket, Siren, Store, UserRound, Users, Vote, Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

// Photos live in public/images/naga/
const img = {
    hero: '/images/naga/porta-mariae.webp',
    rotonda: '/images/naga/rotonda.webp',
    cityHall: '/images/naga/city-hall.webp',
    plaza: '/images/naga/plaza-monument.webp',
};

// One portal, organised by who it serves
const audiences = [
    {
        key: 'citizens', icon: UserRound, title: 'Citizens', tone: 'bg-ns-orange/20',
        blurb: 'Request documents, check prices and have a say, all from your phone.',
        items: [['Request Documents', FileText], ['Market Prices', ShoppingBasket], ['Community Polls', Vote], ['Explore Market', Store]],
    },
    {
        key: 'barangay', icon: Users, title: 'Barangay Staff', tone: 'bg-ns-teal/25',
        blurb: 'Process requests, receive payments and respond to emergencies in one place.',
        items: [['Request Documents', FileText], ['Payments', Wallet], ['Emergency', Siren], ['Document Queues', ShieldCheck]],
    },
    {
        key: 'city', icon: Landmark, title: 'City Hall & Administration', tone: 'bg-ns-violet/15',
        blurb: 'A city-wide view for oversight, treasury, dispatch and verification.',
        items: [['Command Center', Landmark], ['Treasury ledger', Wallet], ['Dispatch', Siren], ['Resident verification', Users]],
    },
];

const active = ref('citizens');
const current = computed(() => audiences.find((a) => a.key === active.value));

const stats = [
    { value: '24/7', label: 'Online services' },
    { value: '1', label: 'Portal for every office' },
    { value: 'QR', label: 'Verified documents' },
];

const steps = [
    { icon: FileText, title: 'Request', text: 'File a document request or service from your barangay, any time of day.' },
    { icon: ScanSearch, title: 'Track', text: 'Follow every request from start to finish, so nothing disappears into a queue.' },
    { icon: QrCode, title: 'Verify', text: 'Every document carries a QR code that anyone can scan to confirm it is real.' },
];

const ring = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
const btnPrimary = `inline-flex w-full items-center justify-center gap-2 rounded-xl bg-ns-orange px-6 py-3.5 sm:w-auto text-base font-bold text-ns-ink shadow-card transition hover:brightness-105 active:scale-95 ${ring}`;
const btnSecondary = `inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/10 px-6 py-3.5 sm:w-auto text-base font-bold text-white backdrop-blur transition hover:bg-white/20 active:scale-95 ${ring}`;
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-ns-canvas font-sans text-ns-ink">
        <a href="#main" class="sr-only z-[60] rounded-full bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

        <GovBanner />

        <!-- Top bar -->
        <header class="sticky top-0 z-30 border-b border-ns-mist bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 sm:px-6">
                <Link :href="route('home')" :class="['flex items-center gap-3 rounded-xl', ring]" aria-label="NagaSync home">
                    <ApplicationLogo class="h-10 w-10 rounded-xl" />
                    <span class="hidden text-xl font-extrabold tracking-tight sm:inline">NagaSync</span>
                </Link>

                <nav class="flex items-center gap-1 text-sm font-semibold sm:gap-4" aria-label="Site">
                    <a href="#services" :class="['hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block', ring]">Services</a>
                    <a href="#about" :class="['hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block', ring]">About</a>
                    <Link :href="route('explore.index')" :class="['hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block', ring]">Explore</Link>

                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                              :class="['rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink sm:px-5', ring]">Dashboard</Link>
                        <template v-else>
                            <Link :href="route('login')" :class="['whitespace-nowrap rounded-xl px-3 py-2 hover:bg-ns-mist', ring]">Log in</Link>
                            <Link v-if="canRegister" :href="route('register')"
                                  :class="['whitespace-nowrap rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink sm:px-5', ring]">Register</Link>
                        </template>
                    </template>
                </nav>
            </div>
        </header>

        <main id="main">
            <!-- Hero: Porta Mariae at dusk -->
            <section class="relative isolate overflow-hidden bg-ns-navy text-white">
                <img :src="img.hero" alt="" aria-hidden="true" fetchpriority="high"
                     class="absolute inset-0 -z-20 h-full w-full object-cover object-center" />
                <!-- Navy wash keeps text readable and ties the photo to the brand colours -->
                <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ns-navy via-ns-navy/85 to-ns-navy/30 md:via-ns-navy/70" aria-hidden="true"></div>
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ns-navy/80 via-transparent to-transparent" aria-hidden="true"></div>

                <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-24 pt-12 sm:px-6 sm:pb-32 sm:pt-20 md:grid-cols-5 md:gap-12 md:pt-28">
                    <div class="md:col-span-3 motion-safe:animate-fade-in">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-ns-orange backdrop-blur">
                            <MapPinned class="h-3.5 w-3.5" aria-hidden="true" /> Pilgrim City of Naga
                        </p>
                        <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">
                            One portal for every<br class="hidden sm:block" />
                            <span class="text-ns-orange">Naga City</span> service
                        </h1>
                        <p class="mt-5 max-w-lg text-base text-white/85 sm:mt-6 sm:text-lg">
                            Citizens, barangay staff, City Hall and responders work from one connected platform, so requests,
                            payments and alerts are handled faster and in the open.
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:mt-9 sm:flex-row sm:flex-wrap">
                            <template v-if="canLogin && !$page.props.auth.user">
                                <Link :href="route('login')" :class="btnPrimary">Log in <ArrowRight class="h-5 w-5" aria-hidden="true" /></Link>
                                <Link v-if="canRegister" :href="route('register')" :class="btnSecondary">Create account</Link>
                            </template>
                            <Link v-else-if="$page.props.auth.user" :href="route('dashboard')" :class="btnPrimary">
                                Go to dashboard <ArrowRight class="h-5 w-5" aria-hidden="true" />
                            </Link>
                            <Link :href="route('explore.index')" :class="btnSecondary">
                                <Store class="h-5 w-5" aria-hidden="true" /> Explore local businesses
                            </Link>
                        </div>
                    </div>

                    <!-- Glass hub card -->
                    <div class="md:col-span-2">
                        <div class="rounded-3xl border border-white/20 bg-white/10 p-5 shadow-2xl backdrop-blur-md sm:p-6">
                            <div class="flex items-center gap-3">
                                <ApplicationLogo class="h-14 w-14 rounded-xl shadow-card" />
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-widest text-white/70">Command center</p>
                                    <p class="text-lg font-extrabold">City of Naga</p>
                                </div>
                            </div>

                            <ul class="mt-5 space-y-2 text-sm font-semibold">
                                <li v-for="[label, Icon] in [['Document requests', FileText], ['Payments & treasury', Wallet], ['Emergency dispatch', Siren]]" :key="label"
                                    class="flex items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5">
                                    <component :is="Icon" class="h-4 w-4 text-ns-orange" aria-hidden="true" />
                                    <span class="flex-1">{{ label }}</span>
                                    <span class="flex items-center gap-1.5 text-xs font-medium text-white/70">
                                        <span class="h-2 w-2 rounded-full bg-ns-teal"></span> Connected
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="flex h-1" aria-hidden="true">
                    <span class="flex-1 bg-ns-orange"></span><span class="flex-1 bg-ns-teal"></span><span class="flex-1 bg-ns-violet"></span>
                </div>
            </section>

            <!-- Stats strip overlapping the hero -->
            <section class="relative z-10 mx-auto -mt-12 max-w-6xl px-4 sm:-mt-14 sm:px-6" aria-label="At a glance">
                <dl class="grid grid-cols-3 divide-x divide-ns-mist rounded-2xl border border-ns-mist bg-white py-5 text-center shadow-card sm:py-6">
                    <div v-for="s in stats" :key="s.label" class="px-2 sm:px-4">
                        <dt class="text-2xl font-extrabold text-ns-violet sm:text-4xl">{{ s.value }}</dt>
                        <dd class="mt-1 text-[11px] leading-tight text-slate-500 sm:text-sm">{{ s.label }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Services by audience (tabs) -->
            <section id="services" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-12 sm:px-6 sm:py-20">
                <p class="text-xs font-semibold uppercase tracking-widest text-ns-violet">Services</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-4xl">Services, organised by who you are</h2>
                <p class="mt-2 max-w-2xl text-slate-600">You only see what applies to you, so there is nothing to get lost in.</p>

                <div class="mt-8 grid gap-6 md:grid-cols-5">
                    <div class="flex gap-2 overflow-x-auto md:col-span-2 md:flex-col md:overflow-visible" role="tablist" aria-label="Choose your role">
                        <button v-for="a in audiences" :key="a.key" type="button" role="tab"
                                :aria-selected="active === a.key" :aria-controls="`panel-${a.key}`"
                                :class="['flex shrink-0 items-center gap-3 rounded-2xl border p-3 text-left transition sm:p-4 md:shrink',
                                         active === a.key ? 'border-ns-navy bg-ns-navy text-white shadow-card' : 'border-ns-mist bg-white hover:bg-ns-mist', ring]"
                                @click="active = a.key">
                            <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', active === a.key ? 'bg-white/15' : a.tone]">
                                <component :is="a.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span>
                                <span class="block whitespace-nowrap font-bold md:whitespace-normal">{{ a.title }}</span>
                                <span :class="['hidden text-sm md:block', active === a.key ? 'text-white/75' : 'text-slate-500']">{{ a.blurb }}</span>
                            </span>
                        </button>
                    </div>

                    <div :id="`panel-${current.key}`" role="tabpanel" class="md:col-span-3">
                        <div :key="current.key" class="rounded-3xl border border-ns-mist bg-white p-5 shadow-card motion-safe:animate-fade-in sm:p-7">
                            <div class="flex items-center gap-3">
                                <span :class="['flex h-12 w-12 items-center justify-center rounded-xl', current.tone]">
                                    <component :is="current.icon" class="h-6 w-6" aria-hidden="true" />
                                </span>
                                <h3 class="text-xl font-bold">{{ current.title }}</h3>
                            </div>
                            <p class="mt-3 text-slate-600 md:hidden">{{ current.blurb }}</p>
                            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                                <li v-for="[label, Icon] in current.items" :key="label"
                                    class="flex items-center gap-3 rounded-2xl bg-ns-canvas px-4 py-4 text-sm font-semibold">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-card">
                                        <component :is="Icon" class="h-4 w-4 text-ns-violet" aria-hidden="true" />
                                    </span>
                                    {{ label }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About: City Hall + Rotonda photo mosaic -->
            <section id="about" class="scroll-mt-20 border-y border-ns-mist bg-white">
                <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 sm:py-20 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-ns-violet">About NagaSync</p>
                        <h2 class="mt-2 text-2xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                            Built for good governance, designed for Nagueños.
                        </h2>
                        <p class="mt-4 text-base text-slate-600 sm:text-lg">
                            NagaSync connects residents, barangay staff, emergency responders and the treasury office so that
                            everyday services are faster, clearer and closer to home. Every document carries a QR code anyone can
                            verify, and every request can be tracked from start to finish.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm font-semibold">
                            <li class="flex items-center gap-3"><BadgeCheck class="h-5 w-5 text-ns-teal" aria-hidden="true" /> Open and trackable requests</li>
                            <li class="flex items-center gap-3"><BadgeCheck class="h-5 w-5 text-ns-teal" aria-hidden="true" /> Verifiable, QR-coded documents</li>
                            <li class="flex items-center gap-3"><BadgeCheck class="h-5 w-5 text-ns-teal" aria-hidden="true" /> One connected platform for every office</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-5 gap-3 sm:gap-4">
                        <figure class="relative col-span-3 overflow-hidden rounded-3xl shadow-card">
                            <img :src="img.cityHall" alt="Naga City Hall entrance with its heritage marker" loading="lazy"
                                 class="h-full min-h-[18rem] w-full object-cover" />
                            <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ns-navy/90 to-transparent p-4 pt-10 text-sm font-bold text-white">
                                Naga City Hall
                            </figcaption>
                        </figure>
                        <div class="col-span-2 flex flex-col gap-3 sm:gap-4">
                            <figure class="relative min-h-[8rem] flex-1 overflow-hidden rounded-3xl shadow-card">
                                <!-- Image is taller than its frame and pinned to the top, so the photo's watermark strip is cropped out -->
                                <img :src="img.rotonda" alt="Aerial view of the Pilgrim City of Naga rotunda at night" loading="lazy"
                                     class="absolute inset-x-0 top-0 h-[118%] w-full object-cover object-top" />
                                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ns-navy/90 to-transparent p-3 pt-8 text-xs font-bold text-white">
                                    The Rotunda, by night
                                </figcaption>
                            </figure>
                            <div class="flex items-center gap-3 rounded-3xl bg-ns-navy p-4 text-white shadow-card">
                                <QrCode class="h-8 w-8 shrink-0 text-ns-orange" aria-hidden="true" />
                                <p class="text-xs font-semibold leading-snug">Every document, verifiable by QR</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-20">
                <p class="text-xs font-semibold uppercase tracking-widest text-ns-violet">How it works</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-4xl">From request to verified document</h2>
                <ol class="mt-8 grid gap-4 md:grid-cols-3">
                    <li v-for="(s, i) in steps" :key="s.title" class="relative rounded-3xl border border-ns-mist bg-white p-6 shadow-card">
                        <span class="absolute right-5 top-4 text-5xl font-extrabold text-ns-mist" aria-hidden="true">{{ i + 1 }}</span>
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-ns-orange/20">
                            <component :is="s.icon" class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 text-xl font-bold">{{ s.title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ s.text }}</p>
                    </li>
                </ol>
            </section>

            <!-- Explore call-to-action -->
            <section>
                <div class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 sm:pb-20">
                    <div class="relative isolate overflow-hidden rounded-3xl bg-ns-navy p-6 text-white shadow-card sm:p-10">
                        <img :src="img.plaza" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 -z-20 h-full w-full object-cover object-center" />
                        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ns-navy via-ns-navy/75 to-ns-navy/20" aria-hidden="true"></div>
                        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                            <div class="max-w-xl">
                                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Discover local businesses</h2>
                                <p class="mt-2 text-white/85">Browse shops, eateries and services registered in Naga City, and support your neighbors.</p>
                            </div>
                            <Link :href="route('explore.index')" :class="btnPrimary">
                                <Store class="h-5 w-5" aria-hidden="true" /> Explore businesses
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-ns-mist bg-white py-8 text-center text-sm text-slate-500">
            <p class="font-semibold text-slate-600">City of Naga · Centralized Governance Portal</p>
            <Link :href="route('privacy')" class="mt-1 inline-block underline">Privacy notice</Link>
            <p class="mt-1">&copy; {{ new Date().getFullYear() }} NagaSync. Your barangay, connected.</p>
        </footer>
    </div>
</template>