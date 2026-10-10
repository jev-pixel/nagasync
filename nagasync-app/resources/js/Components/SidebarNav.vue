<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Icon from '@/Components/Icon.vue';
import { isActive, roleLabels } from '@/Composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({
    groups: { type: Array, required: true },
    user: { type: Object, required: true },
});

const initials = (name = '') =>
    name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');
</script>

<template>
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-gradient-to-b from-brand-800 via-brand-900 to-brand-900 text-brand-100 lg:flex">
        <Link :href="route('dashboard')" class="flex items-center gap-3 px-6 py-6">
            <ApplicationLogo class="h-8 w-8 fill-current text-white" />
            <span class="text-lg font-bold text-white">NagaSync</span>
        </Link>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-6" aria-label="Main">
            <Link
                :href="route('dashboard')"
                class="relative flex items-center gap-3 rounded-tile px-3 py-2.5 text-sm font-medium transition-colors"
                :class="isActive(['dashboard']) ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white'"
            >
                <span v-if="isActive(['dashboard'])" class="absolute inset-y-2 left-0 w-1 rounded-r bg-accent-500"></span>
                <Icon name="home" class="h-5 w-5" />
                Home
            </Link>

            <div v-for="g in groups" :key="g.label">
                <p class="px-3 pb-1 text-xs font-medium text-brand-300">{{ g.label }}</p>
                <Link
                    v-for="i in g.items"
                    :key="i.route"
                    :href="route(i.route)"
                    class="relative flex items-center gap-3 rounded-tile px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="isActive(i.match) ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white'"
                >
                    <span v-if="isActive(i.match)" class="absolute inset-y-2 left-0 w-1 rounded-r bg-accent-500"></span>
                    <Icon :name="i.icon" class="h-5 w-5" :class="i.danger ? 'text-red-300' : ''" />
                    {{ i.label }}
                </Link>
            </div>
        </nav>

        <div class="border-t border-white/10 p-3">
            <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-tile px-3 py-2 hover:bg-white/5">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                    {{ initials(user.name) }}
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-white">{{ user.name }}</span>
                    <span class="block text-xs text-brand-300">{{ roleLabels[user.role] ?? user.role }}</span>
                </span>
            </Link>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="mt-1 flex w-full items-center gap-3 rounded-tile px-3 py-2 text-sm hover:bg-white/5 hover:text-white"
            >
                <Icon name="logout" class="h-5 w-5" />
                Log out
            </Link>
        </div>
    </aside>
</template>