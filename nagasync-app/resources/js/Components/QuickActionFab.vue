<script setup>
import { iconFor } from '@/icons';
import { Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const open = ref(false);

// Visibility is cosmetic; the route middleware is the real access control (see routes/web.php)
const allActions = [
    { label: 'Request a document', route: 'documents.index', icon: 'file', tone: 'bg-ns-orange/20', roles: ['citizen', 'barangay_staff', 'admin'] },
    { label: 'Pay a fee', route: 'payments.index', icon: 'wallet', tone: 'bg-ns-teal/25', roles: ['barangay_staff', 'admin'] },
    { label: 'Emergency alert page', route: 'emergency.index', icon: 'siren', tone: 'bg-ns-coral/15', roles: ['barangay_staff', 'admin'] },
    { label: 'Check market prices', route: 'market.index', icon: 'store', tone: 'bg-ns-violet/15', roles: ['citizen', 'admin'] },
];

const page = usePage();
const actions = computed(() => {
    const role = page.props.auth.user?.role;
    const verified = page.props.auth.resident_verified !== false;
    // Unverified citizens cannot request documents yet; the market page stays open to them
    return allActions
        .filter((a) => a.roles.includes(role))
        .filter((a) => verified || a.route !== 'documents.index');
});

// Roles with Emergency get the red call button in the mobile bottom bar instead (see SidebarNav)
const hasEmergency = computed(() => actions.value.some((a) => a.route === 'emergency.index'));

const onKey = (e) => e.key === 'Escape' && (open.value = false);
onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <!-- Roles with no quick actions (treasury, MDRRMO) get no button -->
    <div v-if="actions.length">
        <button v-if="open" type="button" class="fixed inset-0 z-[45] bg-ns-ink/30 md:bg-transparent" aria-label="Close quick actions" @click="open = false"></button>

        <div v-if="open" role="menu" aria-label="Quick actions"
             class="fixed inset-x-4 bottom-[calc(7rem+env(safe-area-inset-bottom))] z-50 rounded-3xl bg-white p-2 shadow-2xl md:inset-x-auto md:bottom-28 md:right-8 md:w-72">
            <Link v-for="a in actions" :key="a.route" :href="route(a.route)" role="menuitem" @click="open = false"
                  class="flex h-14 items-center gap-3 rounded-2xl px-3 text-sm font-semibold transition hover:bg-ns-mist active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                <span :class="['flex h-9 w-9 items-center justify-center rounded-xl', a.tone]">
                    <component :is="iconFor(a.icon)" class="h-5 w-5" aria-hidden="true" />
                </span>
                {{ a.label }}
            </Link>
        </div>

        <button type="button" data-testid="fab" :aria-expanded="open" aria-label="Quick actions" @click="open = !open"
                :class="{ 'hidden md:flex': hasEmergency }"
                class="fixed bottom-[calc(2rem+env(safe-area-inset-bottom))] left-1/2 z-50 flex h-14 w-14 -translate-x-1/2 items-center justify-center rounded-full bg-ns-orange text-ns-ink shadow-lg ring-4 ring-ns-canvas transition duration-150 hover:scale-105 hover:shadow-xl active:scale-95 focus-visible:outline-none focus-visible:ring-ns-violet motion-reduce:transition-none md:bottom-8 md:left-auto md:right-8 md:translate-x-0">
            <Plus :class="['h-7 w-7 transition-transform duration-200 motion-reduce:transition-none', open ? 'rotate-45' : '']" aria-hidden="true" />
        </button>
    </div>
</template>
