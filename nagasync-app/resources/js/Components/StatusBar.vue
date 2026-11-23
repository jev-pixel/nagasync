<script setup>
import { live } from '@/utils/useLiveData';
import { router, usePage } from '@inertiajs/vue3';
import { WifiOff } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

defineProps({ roleLabel: { type: String, default: '' } });

const page = usePage();
const user = computed(() => page.props.auth.user);

// A normal page visit also counts as fresh data
let off;
onMounted(() => { off = router.on('success', () => { live.lastOk = Date.now(); }); });
onUnmounted(() => off?.());

const isLive = computed(() => live.watchers > 0);
const stale = computed(() => isLive.value && live.now - live.lastOk > live.interval * 2 + 2000);
const updated = computed(() => new Date(live.lastOk).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));

const state = computed(() => {
    if (stale.value) return { dot: 'bg-amber-400', text: 'Connection lost. Numbers may be out of date.' };
    if (isLive.value) return { dot: 'bg-emerald-400 animate-pulse motion-reduce:animate-none', text: `Live · updated ${updated.value}` };
    return { dot: 'bg-white/40', text: `Loaded ${updated.value}` };
});
</script>

<template>
    <footer role="status" aria-live="polite"
            class="fixed inset-x-0 bottom-0 z-30 hidden h-8 items-center gap-4 border-t border-white/10 bg-ns-ink px-4 text-[11px] text-white/75 md:left-20 md:flex lg:left-60">
        <span class="flex items-center gap-2 tabular-nums" :class="stale ? 'font-semibold text-amber-300' : ''">
            <WifiOff v-if="stale" class="h-3.5 w-3.5" aria-hidden="true" />
            <span v-else :class="['h-2 w-2 rounded-full', state.dot]" aria-hidden="true"></span>
            {{ state.text }}
        </span>
        <span class="ml-auto truncate">{{ roleLabel }}<template v-if="user?.email"> · {{ user.email }}</template></span>
    </footer>
</template>