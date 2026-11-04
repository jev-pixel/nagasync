<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import GovBanner from '@/Components/GovBanner.vue';
import QuickActionFab from '@/Components/QuickActionFab.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// grid = true lets a page place its own widgets in the 12-column grid (the Dashboard does).
// Every other page is wrapped in one full-width cell, so existing pages keep working unchanged.
defineProps({ grid: { type: Boolean, default: false } });

const ROLE_LABELS = {
    citizen: 'Citizen',
    barangay_staff: 'Barangay Staff',
    city_staff: 'City Hall Staff',
    treasury: 'Treasury Office',
    mdrrmo: 'MDRRMO Responder',
    admin: 'Administrator',
};

const user = computed(() => usePage().props.auth.user);
const initials = computed(() => user.value.name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());
const firstName = computed(() => user.value.name.split(' ')[0]);
const roleLabel = computed(() => ROLE_LABELS[user.value.role] ?? 'Account');

// Bikol greeting that follows the time of day
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Marhay na aga' : h < 18 ? 'Marhay na hapon' : 'Marhay na banggi';
});

// Home pages get the tall greeting hero; every other page gets a compact banner with a back arrow
const isHome = computed(() => route().current('dashboard') || route().current('guide.index'));
const back = () => window.history.back();

// Render only one header (and so only one search box) at a time
const query = '(max-width: 767px)';
const isMobile = ref(typeof window !== 'undefined' && window.matchMedia(query).matches);
let mql;
const sync = (e) => (isMobile.value = e.matches);
onMounted(() => { mql = window.matchMedia(query); isMobile.value = mql.matches; mql.addEventListener('change', sync); });
onUnmounted(() => mql?.removeEventListener('change', sync));
</script>

<template>
    <div class="min-h-screen">
        <a href="#main" class="sr-only z-[60] rounded-full bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

        <SidebarNav />

        <GovBanner class="md:ml-20" />

        <!-- Mobile: gradient banner with greeting (home) or back arrow + title, and the search bar -->
        <div v-if="isMobile" class="rounded-b-3xl bg-gradient-to-br from-ns-navy to-ns-violet px-4 pb-5 pt-4 text-white shadow-card">
            <div v-if="isHome" class="pb-5 pt-10">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-white/60">NagaSync · {{ roleLabel }}</p>
                <h1 class="mt-1 text-3xl font-extrabold leading-tight">{{ greeting }},<br />{{ firstName }}</h1>
            </div>
            <div v-else class="flex items-center gap-3 pb-4 pt-2">
                <button type="button" aria-label="Go back" @click="back"
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <ArrowLeft class="h-5 w-5" aria-hidden="true" />
                </button>
                <h1 class="min-w-0 flex-1 truncate text-2xl font-bold"><slot name="header" /></h1>
            </div>
            <GlobalSearch placeholder="What would you like to do today?" />
        </div>

        <!-- Desktop / tablet header -->
        <header v-else class="sticky top-0 z-30 border-b border-ns-mist bg-white/90 px-4 py-3 backdrop-blur sm:px-6 md:ml-20">
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">NagaSync · {{ roleLabel }}</p>
                    <div class="text-xl font-bold leading-tight"><slot name="header" /></div>
                </div>

                <GlobalSearch class="w-72" />

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button type="button" aria-label="Account menu"
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-ns-navy text-sm font-bold text-white shadow-card transition hover:scale-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet focus-visible:ring-offset-2 motion-reduce:transition-none">
                            {{ initials }}
                        </button>
                    </template>
                    <template #content>
                        <div class="border-b border-gray-100 px-4 py-2 text-xs text-gray-500">
                            <p class="font-semibold text-gray-700">{{ roleLabel }}</p>
                            {{ user.email }}
                        </div>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </header>

        <main id="main" class="ml-0 grid grid-cols-12 gap-6 p-4 pb-28 sm:p-6 sm:pb-28 md:ml-20 md:pb-6">
            <div v-if="!grid" class="col-span-12"><slot /></div>
            <slot v-else />
        </main>

        <QuickActionFab />
    </div>
</template>
