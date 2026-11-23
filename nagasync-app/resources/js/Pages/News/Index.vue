<script setup>
// Place at: resources/js/Pages/News/Index.vue
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, ChevronDown, Compass, ListChecks, Maximize2, Sparkles, Store, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    news: { type: Array, default: () => [] },
});

// Quick links under the page heading. Visibility is cosmetic; route middleware is the real access
// control (routes/web.php): Market Prices is citizen/admin only, Explore is open to everyone.
const role = computed(() => usePage().props.auth.user?.role);
const quickLinks = computed(() => [
    { key: 'market', title: 'Market Prices', sub: 'Today\'s prices', href: route('market.index'), icon: Store, tone: 'bg-ns-violet/15', roles: ['citizen', 'admin'] },
    { key: 'explore', title: 'Explore Market', sub: 'Local businesses', href: route('explore.index'), icon: Compass, tone: 'bg-ns-teal/25', roles: null },
].filter((l) => !l.roles || l.roles.includes(role.value)));

// Each item: { title, body, date?, tag?, image?, focus?, source? }. Items without an `image`
// get one of the city photos so every card still has a picture.
const FALLBACKS = ['/images/naga/plaza-monument.webp', '/images/naga/city-hall.webp', '/images/naga/rotonda.webp', '/images/naga/porta-mariae.webp'];
const items = computed(() => (props.news ?? []).map((n, i) => ({
    ...n,
    key: `${i}-${n.title}`,
    tag: n.tag ?? 'Update',
    img: n.image ?? FALLBACKS[i % FALLBACKS.length],
    focus: n.focus ?? 'center', // CSS object-position: keeps the photo part of a graphic in the thumbnail
    hasGraphic: !!n.image,
})));
const featured = computed(() => items.value[0] ?? null);
const rest = computed(() => items.value.slice(1));
const meta = (n) => [n.date, n.source].filter(Boolean).join(' · ');

const TAG_TONES = {
    'City news': 'bg-ns-orange text-ns-ink',
    Economy: 'bg-ns-teal text-ns-ink',
    Technology: 'bg-ns-violet text-white',
    Explainer: 'bg-white text-ns-navy',
    NagaSync: 'bg-ns-navy text-white',
};
const tagTone = (t) => TAG_TONES[t] ?? 'bg-ns-navy text-white';

const openNews = ref(null);
const toggleNews = (key) => (openNews.value = openNews.value === key ? null : key);
function imgFallback(e) {
    if (e.target.dataset.fb) return;
    e.target.dataset.fb = '1';
    e.target.src = '/images/naga/porta-mariae.webp';
}

// Full-graphic viewer: thumbnails crop the graphic, this shows all of it
const viewer = ref(null);
const closeBtn = ref(null);
const onKey = (e) => { if (e.key === 'Escape') viewer.value = null; };
watch(viewer, async (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
    if (v) { await nextTick(); closeBtn.value?.focus(); }
});
onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => { window.removeEventListener('keydown', onKey); document.body.style.overflow = ''; });
</script>

<template>
    <Head title="News" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-lg font-semibold text-white md:text-xl md:text-gray-800">News</h2></template>

        <div class="mx-auto max-w-3xl space-y-3">
            <!-- Quick links: Market Prices and Explore Market -->
            <nav v-if="quickLinks.length" aria-label="Quick links"
                 :class="['grid gap-2.5', quickLinks.length > 1 ? 'grid-cols-2' : 'grid-cols-1']">
                <Link v-for="l in quickLinks" :key="l.key" :href="l.href"
                      class="group flex items-center gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition hover:ring-ns-violet/40 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-ns-navy', l.tone]">
                        <component :is="l.icon" class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-ns-ink">{{ l.title }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ l.sub }}</span>
                    </span>
                    <ArrowRight class="hidden h-4 w-4 shrink-0 text-ns-violet opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100 sm:block" aria-hidden="true" />
                </Link>
            </nav>

            <p v-if="!items.length" class="rounded-2xl border-2 border-dashed border-ns-violet/20 p-8 text-center text-sm text-gray-500">
                No news yet. Check back soon.
            </p>

            <!-- Featured story: full-bleed photo under a navy wash -->
            <article v-if="featured" class="news-in group relative isolate overflow-hidden rounded-3xl bg-ns-navy text-white shadow-card" style="--d: 0ms">
                <img :src="featured.img" :style="{ objectPosition: featured.focus }" alt="" aria-hidden="true" fetchpriority="high" @error="imgFallback"
                     class="absolute inset-0 -z-20 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105 motion-reduce:transition-none" />
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ns-navy via-ns-navy/75 to-ns-navy/5" aria-hidden="true"></div>
                <div class="absolute inset-x-0 bottom-0 flex h-1" aria-hidden="true">
                    <span class="flex-1 bg-ns-orange"></span><span class="flex-1 bg-ns-teal"></span><span class="flex-1 bg-ns-violet"></span>
                </div>

                <div class="flex min-h-[340px] flex-col justify-end gap-2.5 p-5 pb-6 sm:min-h-[380px] sm:p-7">
                    <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wide">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-ns-navy">
                            <Sparkles class="h-3 w-3 text-ns-orange" aria-hidden="true" /> Latest
                        </span>
                        <span :class="['rounded-full px-2.5 py-1', tagTone(featured.tag)]">{{ featured.tag }}</span>
                    </div>
                    <h3 class="text-2xl font-extrabold leading-tight sm:text-3xl">{{ featured.title }}</h3>
                    <p class="text-sm leading-relaxed text-white/85" :class="openNews === featured.key ? '' : 'line-clamp-3'">{{ featured.body }}</p>
                    <p v-if="meta(featured)" class="flex items-center gap-1.5 text-xs text-white/70">
                        <CalendarDays v-if="featured.date" class="h-3.5 w-3.5" aria-hidden="true" /> {{ meta(featured) }}
                    </p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <button type="button" :aria-expanded="openNews === featured.key" @click="toggleNews(featured.key)"
                                class="rounded-xl bg-white/15 px-3.5 py-2 text-sm font-bold ring-1 ring-white/30 backdrop-blur transition hover:bg-white/25 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            {{ openNews === featured.key ? 'Show less' : 'Read more' }}
                        </button>
                        <button v-if="featured.hasGraphic" type="button" @click="viewer = featured"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-ns-orange px-3.5 py-2 text-sm font-bold text-ns-ink shadow-card transition hover:brightness-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <Maximize2 class="h-4 w-4" aria-hidden="true" /> View graphic
                        </button>
                    </div>
                </div>
            </article>

            <!-- More stories: thumbnail + headline, tap to read -->
            <article v-for="(n, i) in rest" :key="n.key" class="news-in overflow-hidden rounded-2xl bg-white shadow-sm ring-1 transition-shadow"
                     :class="openNews === n.key ? 'shadow-card ring-ns-violet/40' : 'ring-black/5'" :style="{ '--d': `${(i + 1) * 70}ms` }">
                <button type="button" :id="`news-btn-${i}`" :aria-expanded="openNews === n.key" :aria-controls="`news-${i}`" @click="toggleNews(n.key)"
                        class="flex w-full items-stretch gap-3 p-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ns-orange">
                    <span class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-ns-mist sm:h-28 sm:w-28">
                        <img :src="n.img" :style="{ objectPosition: n.focus }" alt="" aria-hidden="true" loading="lazy" @error="imgFallback"
                             class="h-full w-full object-cover" />
                        <span v-if="n.hasGraphic" class="absolute bottom-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-black/55 text-white" aria-hidden="true">
                            <Maximize2 class="h-3 w-3" />
                        </span>
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
                        <span>
                            <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span :class="['rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide', tagTone(n.tag)]">{{ n.tag }}</span>
                                <span v-if="n.date" class="text-[11px] text-gray-500">{{ n.date }}</span>
                            </span>
                            <span class="mt-1.5 line-clamp-3 block font-bold leading-snug text-ns-ink">{{ n.title }}</span>
                        </span>
                        <span class="mt-1 flex items-center gap-1 text-xs font-semibold text-ns-violet">
                            {{ openNews === n.key ? 'Show less' : 'Read' }}
                            <ChevronDown class="h-3.5 w-3.5 transition-transform duration-200 motion-reduce:transition-none" :class="openNews === n.key ? 'rotate-180' : ''" aria-hidden="true" />
                        </span>
                    </span>
                </button>

                <div :id="`news-${i}`" role="region" :aria-labelledby="`news-btn-${i}`"
                     class="grid transition-[grid-template-rows] duration-200 motion-reduce:transition-none"
                     :class="openNews === n.key ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                     :inert="openNews === n.key ? undefined : ''">
                    <div class="overflow-hidden">
                        <div class="border-t border-ns-mist px-4 pb-4 pt-3">
                            <p class="text-sm leading-relaxed text-gray-600">{{ n.body }}</p>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <p v-if="n.source" class="text-xs text-gray-500">Source: {{ n.source }}</p>
                                <button v-if="n.hasGraphic" type="button" @click="viewer = n"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-ns-mist px-3 py-1.5 text-sm font-bold text-ns-violet transition hover:bg-ns-violet/15 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-orange">
                                    <Maximize2 class="h-4 w-4" aria-hidden="true" /> View graphic
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Cross-link to the Guide -->
            <Link :href="route('guide.index')"
                  class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 transition hover:ring-ns-violet/40 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ns-mist text-ns-violet">
                    <ListChecks class="h-5 w-5" aria-hidden="true" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block font-bold text-ns-ink">Need help requesting a document?</span>
                    <span class="block text-sm text-gray-500">Read the guidelines and the step-by-step guide.</span>
                </span>
                <ArrowRight class="h-5 w-5 shrink-0 text-ns-violet" aria-hidden="true" />
            </Link>
        </div>

        <!-- Full-graphic viewer -->
        <Teleport to="body">
            <div v-if="viewer" class="fixed inset-0 z-[70] flex flex-col items-center justify-center bg-ns-ink/90 p-4 backdrop-blur-sm"
                 role="dialog" aria-modal="true" :aria-label="viewer.title" @click.self="viewer = null">
                <button ref="closeBtn" type="button" aria-label="Close" @click="viewer = null"
                        class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30 transition hover:bg-white/25 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
                <img :src="viewer.img" :alt="viewer.title" class="max-h-[78vh] max-w-full rounded-2xl object-contain shadow-2xl" />
                <p class="mt-3 max-w-md text-center text-sm text-white/80">{{ viewer.title }}<template v-if="viewer.source"> · {{ viewer.source }}</template></p>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Cards fade and rise in one after another when the page opens */
.news-in { animation: news-in 0.4s ease both; animation-delay: var(--d, 0ms); }
@keyframes news-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .news-in { animation: none; }
}
</style>