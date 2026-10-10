<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import BottomNav from '@/Components/BottomNav.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
import { useNavigation } from '@/Composables/useNavigation';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { groups } = useNavigation();

const initials = computed(() =>
    (user.value.name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0].toUpperCase())
        .join(''),
);
</script>

<template>
    <div class="relative min-h-screen bg-surface">
        <!-- Desktop: sidebar. Mobile: bottom tab bar (below). -->
        <SidebarNav :groups="groups" :user="user" />

        <!-- Soft page-wide gradient: every page that uses this layout inherits it. -->
        <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-96 bg-gradient-to-b from-brand-200/60 via-brand-100/30 to-transparent"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-96 bg-gradient-to-br from-transparent via-transparent to-accent-400/10"></div>

        <div class="relative z-10 lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-brand-100/70 bg-white/90 pt-[env(safe-area-inset-top)] backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                    <Link :href="route('dashboard')" class="shrink-0 lg:hidden" aria-label="Home">
                        <ApplicationLogo class="h-8 w-8 fill-current text-brand-700" />
                    </Link>

                    <div class="min-w-0 flex-1">
                        <slot name="header">
                            <h2 class="text-xl font-semibold text-gray-800">NagaSync</h2>
                        </slot>
                    </div>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-full p-1 pr-1 transition-colors hover:bg-brand-50 sm:pr-3"
                                aria-label="Account menu"
                            >
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                                    {{ initials }}
                                </span>
                                <span class="hidden max-w-[10rem] truncate text-sm font-medium text-gray-700 sm:block">{{ user.name }}</span>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log out</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Layout remounts on every Inertia visit, so this gives each page a short fade-in. -->
            <main class="pb-28 motion-safe:animate-fade-in lg:pb-10">
                <slot />
            </main>
        </div>

        <BottomNav :groups="groups" />
    </div>
</template>