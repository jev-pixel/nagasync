<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-surface px-4 py-10">
        <!-- Brand-colored glow, matching the app layout's header gradient -->
        <div class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-brand-200/60 via-brand-100/30 to-transparent"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-accent-400/15 blur-3xl"></div>

        <div class="relative z-10 flex w-full max-w-md flex-col items-center motion-safe:animate-fade-in">
            <Link href="/" aria-label="NagaSync home">
                <ApplicationLogo class="h-24 w-24 shadow-card" />
            </Link>

            <h1 class="mt-4 text-2xl font-bold text-brand-900">NagaSync</h1>
            <p class="text-sm text-gray-500">Your barangay, connected.</p>

            <div class="mt-6 w-full rounded-card bg-white px-6 py-6 shadow-card ring-1 ring-brand-100">
                <slot />
            </div>
        </div>
    </div>
</template>