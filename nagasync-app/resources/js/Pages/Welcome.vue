<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GovBanner from '@/Components/GovBanner.vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Landmark, ShieldCheck, ShoppingBasket, Siren, Store, UserRound, Users, Vote, Wallet } from 'lucide-vue-next';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

// One portal, organised by who it serves
const audiences = [
    {
        icon: UserRound, title: 'Citizens', tone: 'bg-ns-orange/20',
        items: [['Request Documents', FileText], ['Market Prices', ShoppingBasket], ['Community Polls', Vote], ['Explore Market', Store]],
    },
    {
        icon: Users, title: 'Barangay Staff', tone: 'bg-ns-teal/25',
        items: [['Request Documents', FileText], ['Payments', Wallet], ['Emergency', Siren], ['Document Queues', ShieldCheck]],
    },
    {
        icon: Landmark, title: 'City Hall & Administration', tone: 'bg-ns-violet/15',
        items: [['Command Center', Landmark], ['Treasury ledger', Wallet], ['Dispatch', Siren], ['Resident verification', Users]],
    },
];

const stats = [
    { value: '24/7', label: 'Online services' },
    { value: '1', label: 'Portal for every office' },
    { value: 'QR', label: 'Verified documents' },
];

const btnPrimary = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-ns-orange px-6 py-3.5 sm:w-auto text-base font-bold text-ns-ink shadow-card transition hover:brightness-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
const btnSecondary = 'inline-flex w-full items-center justify-center gap-2 rounded-xl border border-ns-mist bg-white px-6 py-3.5 sm:w-auto text-base font-bold text-ns-ink shadow-card transition hover:bg-ns-mist active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-ns-canvas font-sans text-ns-ink">
        <GovBanner />

        <!-- Top bar -->
        <header class="sticky top-0 z-30 border-b border-ns-mist bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-3" aria-label="NagaSync home">
                    <ApplicationLogo class="h-10 w-10 rounded-xl" />
                    <span class="hidden text-xl font-extrabold tracking-tight sm:inline">NagaSync</span>
                </Link>

                <nav class="flex items-center gap-1 text-sm font-semibold sm:gap-4">
                    <a href="#services" class="hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block">Services</a>
                    <a href="#about" class="hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block">About</a>
                    <Link :href="route('explore.index')" class="hidden rounded-xl px-3 py-2 hover:bg-ns-mist sm:block">Explore</Link>

                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                              class="rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink sm:px-5">Dashboard</Link>
                        <template v-else>
                            <Link :href="route('login')" class="whitespace-nowrap rounded-xl px-3 py-2 hover:bg-ns-mist">Log in</Link>
                            <Link v-if="canRegister" :href="route('register')"
                                  class="whitespace-nowrap rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink sm:px-5">Register</Link>
                        </template>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-ns-navy text-white">
            <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 sm:py-16 md:grid-cols-5 md:gap-12 md:py-24">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-ns-orange">Centralized Governance Portal</p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">
                        One portal for every<br class="hidden sm:block" /> Naga City service
                    </h1>
                    <p class="mt-4 max-w-lg text-base text-white/80 sm:mt-6 sm:text-lg">
                        Citizens, barangay staff, City Hall and responders work from one connected platform, so requests,
                        payments and alerts are handled faster and in the open.
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:mt-8 sm:flex-row sm:flex-wrap">
                        <template v-if="canLogin && !$page.props.auth.user">
                            <Link :href="route('login')" :class="btnPrimary">Log in</Link>
                            <Link v-if="canRegister" :href="route('register')" :class="btnSecondary">Create account</Link>
                        </template>
                        <Link v-else-if="$page.props.auth.user" :href="route('dashboard')" :class="btnPrimary">Go to dashboard</Link>
                        <Link :href="route('explore.index')"
                              class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/30 px-6 py-3.5 sm:w-auto text-base font-bold text-white transition hover:bg-white/10 active:scale-95">
                            <Store class="h-5 w-5" aria-hidden="true" /> Explore local businesses
                        </Link>
                    </div>
                </div>

                <!-- Hub card -->
                <div class="md:col-span-2">
                    <div class="rounded-2xl bg-white p-5 text-ns-ink shadow-xl sm:p-6">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo class="h-14 w-14 rounded-xl" />
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Command center</p>
                                <p class="text-lg font-extrabold">City of Naga</p>
                            </div>
                        </div>
                        <dl class="mt-5 grid grid-cols-3 divide-x divide-ns-mist text-center">
                            <div v-for="s in stats" :key="s.label" class="px-2">
                                <dt class="text-2xl font-extrabold text-ns-violet">{{ s.value }}</dt>
                                <dd class="mt-1 text-[11px] leading-tight text-slate-500">{{ s.label }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="flex h-1" aria-hidden="true">
                <span class="flex-1 bg-ns-orange"></span><span class="flex-1 bg-ns-teal"></span><span class="flex-1 bg-ns-violet"></span>
            </div>
        </section>

        <!-- Services by audience -->
        <section id="services" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-16">
            <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Services, organised by who you are</h2>
            <p class="mt-2 max-w-2xl text-slate-600">You only see what applies to you, so there is nothing to get lost in.</p>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <article v-for="a in audiences" :key="a.title" class="rounded-2xl border border-ns-mist bg-white p-5 shadow-card sm:p-6">
                    <span :class="['flex h-12 w-12 items-center justify-center rounded-xl', a.tone]">
                        <component :is="a.icon" class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <h3 class="mt-4 text-xl font-bold">{{ a.title }}</h3>
                    <ul class="mt-4 divide-y divide-ns-mist text-sm">
                        <li v-for="[label, icon] in a.items" :key="label" class="flex items-center gap-3 py-2.5">
                            <component :is="icon" class="h-4 w-4 text-slate-400" aria-hidden="true" /> {{ label }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- About -->
        <section id="about" class="border-y border-ns-mist bg-white">
            <div class="mx-auto grid max-w-6xl items-center gap-6 px-4 py-10 sm:gap-10 sm:px-6 sm:py-16 md:grid-cols-2">
                <h2 class="text-2xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                    Built for good governance, designed for Nagueños.
                </h2>
                <p class="text-base text-slate-600 sm:text-lg">
                    NagaSync connects residents, barangay staff, emergency responders and the treasury office so that
                    everyday services are faster, clearer and closer to home. Every document carries a QR code anyone can
                    verify, and every request can be tracked from start to finish.
                </p>
            </div>
        </section>

        <!-- Explore call-to-action -->
        <section>
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-16">
                <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-ns-navy p-6 text-white shadow-card sm:p-8 md:flex-row md:items-center">
                    <div class="max-w-xl">
                        <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Discover local businesses</h2>
                        <p class="mt-2 text-white/80">Browse shops, eateries and services registered in Naga City, and support your neighbors.</p>
                    </div>
                    <Link :href="route('explore.index')" :class="btnPrimary">
                        <Store class="h-5 w-5" aria-hidden="true" /> Explore businesses
                    </Link>
                </div>
            </div>
        </section>

        <footer class="border-t border-ns-mist bg-white py-8 text-center text-sm text-slate-500">
            <p class="font-semibold text-slate-600">City of Naga · Centralized Governance Portal</p>
            <Link :href="route('privacy')" class="mt-1 inline-block underline">Privacy notice</Link>
            <p class="mt-1">&copy; {{ new Date().getFullYear() }} NagaSync. Your barangay, connected.</p>
        </footer>
    </div>
</template>
