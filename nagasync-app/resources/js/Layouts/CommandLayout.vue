<script setup>
/**
 * Command frame for staff roles (admin, city_staff, barangay_staff).
 * Zones: top command bar (5.4rem) | left nav | main workspace | right context panel | status bar (2rem)
 *
 * Pages render inside it unchanged:
 *   <CommandLayout>
 *     <template #header>Mission Control</template>   (optional, sub-row of the top bar)
 *     ...page content...
 *     <template #context> <ContextPanel v-if="selected" ... /> </template>
 *   </CommandLayout>
 *
 * Navigation comes from useNavigation() (navigation.js), so the staff sidebar always
 * matches the role lists you already maintain. The dark theme is scoped with
 * data-theme="command" on the wrapper only.
 */
import StatusBar from '@/Components/StatusBar.vue';
import { useNavigation } from '@/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, LogOut, MoreHorizontal, Search, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, provide, ref } from 'vue';

defineProps({ alertCount: { type: Number, default: 0 } });

const page = usePage();
const user = computed(() => page.props.auth.user);

const ROLE_LABELS = {
    admin: 'Administrator',
    city_staff: 'City Hall Clerk',
    barangay_staff: 'Barangay Staff',
};
const roleLabel = computed(() => ROLE_LABELS[user.value?.role] ?? 'Staff');

const { items, pinned, isActive } = useNavigation();

// A route missing from Ziggy (e.g. a renamed one) is skipped instead of crashing the whole frame
const routeExists = (name) => {
    try { return route().has(name); } catch { return false; }
};

const navItems = computed(() =>
    items.value
        .filter((i) => routeExists(i.route))
        .map((i) => ({ ...i, href: route(i.route), active: isActive(i) })),
);

// The few links pinned to the mobile bottom bar; everything else is reachable through search
const pinnedItems = computed(() =>
    pinned.value.map((p) => navItems.value.find((i) => i.key === p.key)).filter(Boolean),
);

const homeHref = computed(() => navItems.value.find((i) => i.key === 'dashboard')?.href ?? '/');

/* Panel reservation: ContextPanel flips this to true while it is mounted */
const panelOpen = ref(false);
provide('cc-panel-open', panelOpen);

/* Command palette (Ctrl/Cmd+K or "/"): search pages for now, records later */
const paletteOpen = ref(false);
const query = ref('');
const cursor = ref(0);
const input = ref(null);
const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    return navItems.value.filter((i) => !q || i.label.toLowerCase().includes(q));
});

const openPalette = async () => {
    paletteOpen.value = true;
    query.value = '';
    cursor.value = 0;
    await nextTick();
    input.value?.focus();
};
const closePalette = () => { paletteOpen.value = false; };
const move = (d) => {
    if (!results.value.length) return;
    cursor.value = (cursor.value + d + results.value.length) % results.value.length;
};
const go = () => {
    const hit = results.value[cursor.value];
    if (hit) { closePalette(); window.location.assign(hit.href); }
};

const onGlobalKey = (e) => {
    const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openPalette(); }
    else if (e.key === '/' && !typing && !paletteOpen.value) { e.preventDefault(); openPalette(); }
};
onMounted(() => window.addEventListener('keydown', onGlobalKey));
onUnmounted(() => window.removeEventListener('keydown', onGlobalKey));
</script>

<template>
    <div data-theme="command" class="min-h-screen bg-ns-canvas text-ns-ink">
        <a href="#cc-main"
           class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-semibold">
            Skip to content
        </a>

        <!-- TOP COMMAND BAR: row 1 (3.5rem) + page title row (1.9rem) = 5.4rem -->
        <header class="fixed inset-x-0 top-0 z-40 h-[5.4rem] border-b border-white/10 bg-ns-ink text-white">
            <div class="flex h-14 items-center gap-3 px-4">
                <Link :href="homeHref" class="flex shrink-0 items-center gap-2 font-bold tracking-tight md:w-[4.5rem] lg:w-52">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ns-violet text-sm">N</span>
                    <span class="hidden lg:inline">NagaSync</span>
                </Link>

                <!-- Search is the visual centerpiece -->
                <button type="button" @click="openPalette" aria-label="Ask, search, or run a command"
                        class="mx-auto flex h-10 w-full max-w-2xl items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 text-left text-sm text-white/70 transition hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <Search class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span class="flex-1 truncate">Ask, search, or run a command</span>
                    <kbd class="hidden rounded border border-white/20 px-1.5 py-0.5 text-[10px] sm:inline">Ctrl K</kbd>
                </button>

                <button type="button" :aria-label="alertCount ? `${alertCount} alerts` : 'Alerts'"
                        class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white/80 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <Bell class="h-5 w-5" aria-hidden="true" />
                    <span v-if="alertCount" class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold tabular-nums">
                        {{ alertCount > 99 ? '99+' : alertCount }}
                    </span>
                </button>

                <div class="hidden shrink-0 text-right leading-tight sm:block">
                    <p class="max-w-[10rem] truncate text-sm font-semibold">{{ user?.name }}</p>
                    <p class="text-[11px] text-white/60">{{ roleLabel }}</p>
                </div>

                <Link :href="route('logout')" method="post" as="button" aria-label="Log out"
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white/80 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <LogOut class="h-5 w-5" aria-hidden="true" />
                </Link>
            </div>

            <div class="flex h-[1.9rem] items-center overflow-hidden border-t border-white/10 px-4 text-xs font-semibold uppercase tracking-wide text-white/70 md:pl-24 lg:pl-64">
                <slot name="header" />
            </div>
        </header>

        <!-- LEFT NAVIGATION: icons on md, labels on lg, bottom tabs below md -->
        <nav aria-label="Main"
             class="fixed inset-y-0 left-0 top-[5.4rem] bottom-8 z-30 hidden w-20 flex-col gap-1 overflow-y-auto border-r border-white/10 bg-ns-ink p-2 md:flex lg:w-60">
            <Link v-for="item in navItems" :key="item.key" :href="item.href"
                  :aria-current="item.active ? 'page' : undefined"
                  :class="['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet md:flex-col md:gap-1 md:px-1 md:text-[11px] lg:flex-row lg:gap-3 lg:px-3 lg:text-sm',
                           item.active ? 'bg-ns-violet text-white' : 'text-white/75 hover:bg-white/10 hover:text-white']">
                <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span class="truncate md:max-w-full md:text-center">{{ item.label }}</span>
            </Link>
        </nav>

        <nav aria-label="Main (mobile)"
             class="fixed inset-x-0 bottom-0 z-30 flex border-t border-white/10 bg-ns-ink md:hidden">
            <Link v-for="item in pinnedItems" :key="item.key" :href="item.href"
                  :aria-current="item.active ? 'page' : undefined"
                  :class="['flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium',
                           item.active ? 'text-white' : 'text-white/60']">
                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                {{ item.label }}
            </Link>
            <button type="button" @click="openPalette"
                    class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium text-white/60">
                <MoreHorizontal class="h-5 w-5" aria-hidden="true" />
                More
            </button>
        </nav>

        <!-- MAIN WORKSPACE: makes room for the context panel on xl -->
        <main id="cc-main" tabindex="-1"
              :class="['min-h-screen px-4 pb-24 pt-[6.9rem] transition-[padding] duration-200 focus:outline-none md:pb-14 md:pl-24 lg:pl-64 motion-reduce:transition-none',
                       panelOpen ? 'xl:pr-[27.5rem]' : 'xl:pr-6']">
            <slot />
        </main>

        <!-- RIGHT CONTEXT PANEL (pages mount <ContextPanel> here only while something is selected) -->
        <slot name="context" />

        <StatusBar :role-label="roleLabel" />

        <!-- COMMAND PALETTE -->
        <div v-if="paletteOpen" class="fixed inset-0 z-50 flex items-start justify-center px-4 pt-[15vh]" role="dialog" aria-modal="true" aria-label="Command palette">
            <button type="button" tabindex="-1" aria-label="Close search" class="absolute inset-0 bg-ns-ink/60" @click="closePalette"></button>
            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center gap-3 border-b border-ns-mist px-4">
                    <Search class="h-4 w-4 text-slate-500" aria-hidden="true" />
                    <input ref="input" v-model="query" type="text" placeholder="Search pages…" aria-label="Search"
                           class="h-12 flex-1 border-0 bg-transparent text-sm focus:outline-none focus:ring-0"
                           @input="cursor = 0"
                           @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                           @keydown.enter.prevent="go" @keydown.esc.stop="closePalette" />
                    <button type="button" aria-label="Close search" @click="closePalette"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-ns-mist">
                        <X class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <ul class="max-h-72 overflow-y-auto p-2" role="listbox">
                    <li v-for="(r, i) in results" :key="r.key" role="option" :aria-selected="i === cursor">
                        <Link :href="r.href" @click="closePalette"
                              :class="['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm', i === cursor ? 'bg-ns-mist font-semibold' : '']">
                            <component :is="r.icon" class="h-4 w-4 text-slate-500" aria-hidden="true" />
                            {{ r.label }}
                        </Link>
                    </li>
                    <li v-if="!results.length" class="px-3 py-6 text-center text-sm text-slate-500">No matching pages.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style>
/* Scope for the future dark theme: only elements inside the command frame are affected */
[data-theme="command"] { color-scheme: light; }
</style>