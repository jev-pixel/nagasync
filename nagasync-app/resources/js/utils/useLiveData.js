import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive } from 'vue';

/**
 * One place for "refresh this page's data every N seconds".
 * Replaces the hand-rolled setInterval blocks in CommandCenter, Dispatch and Emergency,
 * and feeds the StatusBar so staff can always see how fresh the numbers are.
 *
 *   const { stale } = useLiveData({ only: ['open', 'recent'], interval: 5000 });
 */
export const live = reactive({
    lastOk: Date.now(), // last successful refresh (ms)
    now: Date.now(),    // ticks once a second while any page is live
    interval: 10000,    // fastest interval requested by a mounted page
    watchers: 0,        // how many mounted components are polling
});

let ticker = null;

export function useLiveData({ only = null, interval = 10000 } = {}) {
    let timer = null;

    const refresh = () => {
        live.now = Date.now();
        if (document.hidden) return; // never poll in a background tab
        router.reload({
            ...(only ? { only } : {}),
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { live.lastOk = Date.now(); },
        });
    };

    // Coming back to the tab refreshes straight away instead of showing stale numbers for a few seconds
    const onVisible = () => { if (!document.hidden) refresh(); };

    onMounted(() => {
        live.watchers += 1;
        live.interval = interval;
        live.lastOk = Date.now();
        timer = setInterval(refresh, interval);
        ticker ??= setInterval(() => { live.now = Date.now(); }, 1000);
        document.addEventListener('visibilitychange', onVisible);
    });

    onUnmounted(() => {
        clearInterval(timer);
        document.removeEventListener('visibilitychange', onVisible);
        live.watchers = Math.max(0, live.watchers - 1);
        if (live.watchers === 0 && ticker) { clearInterval(ticker); ticker = null; }
    });

    const stale = computed(() => live.now - live.lastOk > interval * 2 + 2000);
    return { live, stale, refresh };
}