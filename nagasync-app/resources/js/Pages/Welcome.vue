<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />

    <div class="relative flex min-h-screen flex-col bg-surface">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-brand-200/60 via-brand-100/30 to-transparent"></div>

        <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-3">
                <ApplicationLogo class="h-10 w-10" />
                <span class="text-lg font-bold text-brand-800">NagaSync</span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-3">
                <Link
                    :href="route('login')"
                    class="rounded-full px-4 py-2 text-sm font-medium text-brand-800 transition hover:bg-brand-50"
                >
                    Log in
                </Link>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="rounded-full bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                    Register
                </Link>
            </nav>
        </header>

        <main class="relative z-10 mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-6 text-center">
            <ApplicationLogo class="h-32 w-32 shadow-xl shadow-brand-900/10" />
            <h1 class="mt-8 text-4xl font-bold tracking-tight text-brand-800 sm:text-5xl">
                Welcome to NagaSync
            </h1>
            <p class="mt-4 max-w-xl text-lg text-gray-600">
                Your barangay services, rewards and community, all in one place.
            </p>
            <div class="mt-8 flex gap-3">
                <Link :href="route('register')" class="rounded-full bg-accent-500 px-6 py-3 font-semibold text-white shadow-lg shadow-accent-500/30 transition hover:bg-accent-600">
                    Get started
                </Link>
                <Link :href="route('login')" class="rounded-full bg-white px-6 py-3 font-semibold text-brand-800 ring-1 ring-brand-200 transition hover:bg-brand-50">
                    Log in
                </Link>
            </div>
        </main>
    </div>
</template>