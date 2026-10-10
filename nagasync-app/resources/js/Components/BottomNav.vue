<script setup>
import Icon from '@/Components/Icon.vue';
import { isActive } from '@/Composables/useNavigation';
import { Link } from '@inertiajs/vue3';
import { computed, defineProps, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({ groups: { type: Array, required: true } });

const moreOpen = ref(false);

// Emergency sits in the centre: it is the one action that must never be hard to find.
const tabs = [
    { label: 'Home', route: 'dashboard', icon: 'home', match: ['dashboard'] },
    { label: 'Documents', route: 'documents.index', icon: 'document', match: ['documents.*'] },
    { label: 'Emergency', route: 'emergency.index', icon: 'alert', match: ['emergency.*'], center: true },
    { label: 'Payments', route: 'payments.index', icon: 'card', match: ['payments.*'] },
];
const tabRoutes = tabs.map((t) => t.route);

// Everything that is not a tab (Market, Polls, staff tools...) lives in "More".
const moreGroups = computed(() =>
    props.groups
        .map((g) => ({ label: g.label, items: g.items.filter((i) => !tabRoutes.includes(i.route)) }))
        .filter((g) => g.items.length),
);
const moreActive = computed(
    () => moreGroups.value.some((g) => g.items.some((i) => isActive(i.match))) || route().current('profile.*'),
);

const onKey = (e) => e.key === 'Escape' && (moreOpen.value = false);
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-brand-100 bg-white/95 backdrop-blur lg:hidden" aria-label="Main">
        <ul class="mx-auto grid max-w-md grid-cols-5 items-end px-2 pb-[env(safe-area-inset-bottom)]">
            <li v-for="t in tabs" :key="t.route" class="flex justify-center">
                <Link
                    v-if="t.center"
                    :href="route(t.route)"
                    class="-mt-6 flex flex-col items-center gap-1 pb-2"
                >
                    <span class="relative flex h-14 w-14 items-center justify-center">
                        <span class="absolute inset-0 rounded-full bg-red-500/40 motion-safe:animate-ping"></span>
                        <span class="relative flex h-14 w-14 items-center justify-center rounded-full bg-red-600 text-white shadow-lg ring-4 ring-white transition-transform active:scale-95">
                            <Icon :name="t.icon" class="h-7 w-7" />
                        </span>
                    </span>
                    <span class="text-[11px] font-medium" :class="isActive(t.match) ? 'text-red-600' : 'text-gray-500'">{{ t.label }}</span>
                </Link>

                <Link
                    v-else
                    :href="route(t.route)"
                    class="flex min-h-[56px] w-full flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors"
                    :class="isActive(t.match) ? 'text-brand-600' : 'text-gray-500'"
                    :aria-current="isActive(t.match) ? 'page' : undefined"
                >
                    <Icon :name="t.icon" class="h-6 w-6" />
                    {{ t.label }}
                </Link>
            </li>

            <li class="flex justify-center">
                <button
                    type="button"
                    class="flex min-h-[56px] w-full flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors"
                    :class="moreActive ? 'text-brand-600' : 'text-gray-500'"
                    aria-haspopup="dialog"
                    @click="moreOpen = true"
                >
                    <Icon name="grid" class="h-6 w-6" />
                    More
                </button>
            </li>
        </ul>
    </nav>

    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="moreOpen" class="fixed inset-0 z-40 bg-brand-900/40 lg:hidden" @click="moreOpen = false"></div>
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="moreOpen"
                class="fixed inset-x-0 bottom-0 z-50 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="More"
            >
                <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-gray-200"></div>

                <div v-for="g in moreGroups" :key="g.label" class="mb-5">
                    <p class="mb-2 text-sm font-medium text-gray-500">{{ g.label }}</p>
                    <div class="grid grid-cols-3 gap-3">
                        <Link
                            v-for="i in g.items"
                            :key="i.route"
                            :href="route(i.route)"
                            class="flex flex-col items-center gap-2 rounded-tile bg-surface p-3 text-center text-xs font-medium text-gray-700 transition-transform active:scale-95"
                            @click="moreOpen = false"
                        >
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm">
                                <Icon :name="i.icon" class="h-5 w-5" />
                            </span>
                            {{ i.label }}
                        </Link>
                    </div>
                </div>

                <div class="space-y-1 border-t border-gray-100 pt-3">
                    <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-tile px-3 py-3 text-sm font-medium text-gray-700 active:bg-surface" @click="moreOpen = false">
                        <Icon name="user" class="h-5 w-5 text-gray-400" /> Profile
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-3 rounded-tile px-3 py-3 text-sm font-medium text-gray-700 active:bg-surface">
                        <Icon name="logout" class="h-5 w-5 text-gray-400" /> Log out
                    </Link>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>