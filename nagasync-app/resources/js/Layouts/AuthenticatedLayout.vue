<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import GovBanner from '@/Components/GovBanner.vue';
import QuickActionFab from '@/Components/QuickActionFab.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
import CommandLayout from '@/Layouts/CommandLayout.vue';
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

// Roles that get the staff command frame. Everyone else keeps the citizen layout below, untouched.
const COMMAND_ROLES = ['admin', 'city_staff', 'barangay_staff'];

const user = computed(() => usePage().props.auth.user);
const isCommand = computed(() => COMMAND_ROLES.includes(user.value?.role));
const initials = computed(() => user.value.name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());
const firstName = computed(() => user.value.name.split(' ')[0]);
const roleLabel = computed(() => ROLE_LABELS[user.value.role] ?? 'Account');

// Bikol greeting that follows the time of day
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Marhay na aga' : h < 18 ? 'Marhay na hapon' : 'Marhay na banggi';
});

// Home pages get the greeting hero; every other page gets a compact banner with a back arrow
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
    <!-- STAFF: command frame (top command bar, role-aware nav, context panel, status bar).
         Pages keep using the same slots: default, #header and #context.
         The `grid` prop still works, so staff pages that place widgets in the 12-column grid are unchanged. -->
    <CommandLayout v-if="isCommand">
        <template #header><slot name="header" /></template>

        <div :class="grid ? 'grid grid-cols-12 gap-4 sm:gap-6' : ''">
            <div v-if="!grid" class="col-span-12"><slot /></div>
            <slot v-else />
        </div>

        <template #context><slot name="context" /></template>
    </CommandLayout>

    <!-- CITIZEN and other roles (treasury, mdrrmo): the existing layout, unchanged -->
    <div v-else class="min-h-screen">
        <a href="#main" class="sr-only z-[60] rounded-full bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

        <SidebarNav />

        <GovBanner class="md:ml-20" />

        <!-- Mobile: compact gradient banner.
             Home: greeting + search. Inner pages: back arrow + title, then the page's own
             `mobile-extra` slot (e.g. a calendar) or, if the page has none, the search bar. -->
        <div v-if="isMobile" class="relative isolate overflow-hidden rounded-b-2xl bg-ns-navy px-3 pb-3 pt-3 text-white shadow-card">
            <!-- Porta Mariae photo under a navy-to-violet wash (keeps the text readable) -->
            <img src="/images/naga/porta-mariae.webp" alt="" aria-hidden="true" fetchpriority="high"
                 class="absolute inset-0 -z-20 h-full w-full object-cover object-center" />
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-ns-navy/95 via-ns-navy/75 to-ns-violet/60" aria-hidden="true"></div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ns-navy/50 via-transparent to-transparent" aria-hidden="true"></div>

            <template v-if="isHome">
                <div class="pb-3 pt-6">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-white/75">NagaSync · {{ roleLabel }}</p>
                    <h1 class="mt-1 text-2xl font-extrabold leading-tight">{{ greeting }}, {{ firstName }}</h1>
                </div>
                <GlobalSearch placeholder="What would you like to do today?" />
            </template>

            <template v-else>
                <div class="flex items-center gap-2 pb-2">
                    <button type="button" aria-label="Go back" @click="back"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <ArrowLeft class="h-5 w-5" aria-hidden="true" />
                    </button>
                    <h1 class="min-w-0 flex-1 truncate text-lg font-bold"><slot name="header" /></h1>
                </div>
                <slot name="mobile-extra">
                    <GlobalSearch placeholder="What would you like to do today?" />
                </slot>
            </template>
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

        <main id="main" class="ml-0 grid grid-cols-12 gap-4 p-3 pb-28 sm:gap-6 sm:p-6 sm:pb-28 md:ml-20 md:pb-6">
            <div v-if="!grid" class="col-span-12"><slot /></div>
            <slot v-else />
        </main>

        <QuickActionFab />
    </div>
</template>