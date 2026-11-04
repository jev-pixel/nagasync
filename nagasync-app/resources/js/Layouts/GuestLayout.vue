<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GovBanner from '@/Components/GovBanner.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-ns-canvas">
        <GovBanner />

        <div class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="w-full max-w-md motion-safe:animate-fade-in">
                <div class="flex flex-col items-center text-center">
                    <Link href="/" aria-label="NagaSync home">
                        <ApplicationLogo class="h-20 w-20 rounded-2xl shadow-card" />
                    </Link>
                    <h1 class="mt-4 text-2xl font-extrabold text-ns-ink">NagaSync</h1>
                    <p class="text-sm text-slate-500">One portal for city and barangay services.</p>
                </div>

                <div class="mt-6 w-full overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card">
                    <div class="h-1 bg-gradient-to-r from-ns-orange via-ns-teal to-ns-violet" aria-hidden="true"></div>
                    <div class="px-6 py-6"><slot /></div>
                </div>

                <p class="mt-4 text-center text-xs text-slate-500">
                    <Link :href="route('privacy')" class="underline">Privacy notice</Link>
                </p>
            </div>
        </div>
    </div>
</template>
