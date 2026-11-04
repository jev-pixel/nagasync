<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import GovBanner from '@/Components/GovBanner.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth?.user);
const ring = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
</script>

<template>
    <div class="min-h-screen">
        <a href="#main" class="sr-only z-[60] rounded-full bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

        <GovBanner />

        <header class="sticky top-0 z-30 border-b border-ns-mist bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 sm:px-6">
                <Link :href="route('home')" :class="['flex items-center gap-2 rounded-xl', ring]">
                    <BrandMark /><span class="text-lg font-extrabold">NagaSync</span>
                </Link>
                <nav class="ml-auto flex items-center gap-2 text-sm font-semibold" aria-label="Site">
                    <Link :href="route('explore.index')" :class="['rounded-xl px-4 py-2 hover:bg-ns-mist', ring]">Explore</Link>
                    <Link v-if="user" :href="route('dashboard')"
                          :class="['rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink active:scale-95', ring]">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" :class="['rounded-xl px-4 py-2 hover:bg-ns-mist', ring]">Log in</Link>
                        <Link :href="route('register')" :class="['rounded-xl bg-ns-navy px-4 py-2 text-white transition hover:bg-ns-ink active:scale-95', ring]">Register</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main id="main" class="mx-auto max-w-6xl px-4 pb-16 pt-4 sm:px-6"><slot /></main>

        <footer class="border-t border-ns-mist bg-white py-6 text-center text-xs text-slate-500">
            <p class="font-semibold text-slate-600">City of Naga · Centralized Governance Portal</p>
            <Link :href="route('privacy')" class="mt-1 inline-block underline">Privacy notice</Link>
        </footer>
    </div>
</template>
