<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import { useNavigation } from '@/navigation';
import { Link } from '@inertiajs/vue3';
import { Phone } from 'lucide-vue-next';
import { computed } from 'vue';

const { items, tabs, isActive } = useNavigation();

// Roles that can open /emergency get the red call button in the middle of the mobile bar
const hasEmergency = computed(() => items.value.some((i) => i.key === 'emergency'));

const ring = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-orange';

const railLink = (active) => [
    'relative z-10 ml-3 flex h-14 items-center justify-center transition-colors duration-150',
    ring, 'focus-visible:ring-offset-2 focus-visible:ring-offset-ns-navy',
    active ? 'ns-notch text-ns-ink' : 'mr-3 rounded-2xl text-white/70 hover:bg-white/10 hover:text-white active:bg-white/20',
];

const stripLink = (active) => [
    'flex h-14 w-16 flex-col items-center justify-center gap-0.5 rounded-xl text-[11px] font-semibold transition active:scale-95',
    ring, active ? 'text-ns-orange' : 'text-white/70 hover:text-white',
];
</script>

<template>
    <!-- Desktop / tablet: icon rail with the cut-out notch -->
    <nav data-testid="sidenav" aria-label="Primary" class="fixed inset-y-0 left-0 z-40 hidden w-20 flex-col bg-ns-navy md:flex">
        <Link :href="route('dashboard')" aria-label="NagaSync home"
              :class="['mx-auto mb-1 mt-4 rounded-2xl', ring]">
            <BrandMark />
        </Link>
        <ul class="scrollbar-none mt-2 flex flex-1 flex-col gap-2 overflow-y-auto py-6">
            <li v-for="item in items" :key="item.key">
                <Link :href="route(item.route)" :class="railLink(isActive(item))" :title="item.label"
                      :aria-label="item.label" :aria-current="isActive(item) ? 'page' : undefined">
                    <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
                </Link>
            </li>
        </ul>
    </nav>

    <!-- Mobile: Home, Services, [centre button], Activity, Profile -->
    <nav data-testid="bottom-nav" aria-label="Primary"
         class="fixed inset-x-0 bottom-0 z-40 flex h-[calc(4rem+env(safe-area-inset-bottom))] items-start justify-around bg-ns-navy px-2 pb-[env(safe-area-inset-bottom)] pt-1 text-white shadow-[0_-8px_24px_rgba(30,27,58,.15)] md:hidden">
        <Link v-for="t in tabs.left" :key="t.key" :href="route(t.route)" :class="stripLink(isActive(t))"
              :aria-current="isActive(t) ? 'page' : undefined">
            <component :is="t.icon" class="h-6 w-6" aria-hidden="true" />
            {{ t.label }}
        </Link>

        <!-- Gap for the centre button; carries its label when it is the Emergency button -->
        <span class="flex h-14 w-16 items-end justify-center text-[11px] font-semibold text-white/70">
            <template v-if="hasEmergency">Emergency</template>
        </span>

        <Link v-for="t in tabs.right" :key="t.key" :href="route(t.route)" :class="stripLink(isActive(t))"
              :aria-current="isActive(t) ? 'page' : undefined">
            <component :is="t.icon" class="h-6 w-6" aria-hidden="true" />
            {{ t.label }}
        </Link>
    </nav>

    <!-- Mobile centre button: red call button to Emergency (other roles keep the quick-action button) -->
    <Link v-if="hasEmergency" :href="route('emergency.index')" aria-label="Emergency" data-testid="emergency-fab"
          class="fixed bottom-[calc(1.75rem+env(safe-area-inset-bottom))] left-1/2 z-50 flex h-16 w-16 -translate-x-1/2 items-center justify-center rounded-full bg-red-600 text-white shadow-lg ring-4 ring-ns-canvas transition duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-ns-orange md:hidden">
        <Phone class="h-7 w-7" aria-hidden="true" />
    </Link>
</template>
