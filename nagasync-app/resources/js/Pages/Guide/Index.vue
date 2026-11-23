<script setup>
// Place at: resources/js/Pages/Guide/Index.vue
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, BadgeCheck, CalendarDays, Check, ChevronDown, FileText, Hash, Link2, ListChecks, Maximize2,
    Newspaper, Search, ShieldAlert, ShieldCheck, Siren, Sparkles, Upload, UserCheck, Wallet, X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    news: { type: Array, default: () => [] },
    requirements: { type: [Object, Array], default: () => ({}) },
});

const page = usePage();
const verified = computed(() => page.props.auth.resident_verified);
const user = computed(() => page.props.auth.user);

// ---- Tabs (deep-linkable: /guide#guidelines, /guide#howto) --------------------
const tabs = [
    { key: 'news', label: 'News', icon: Newspaper },
    { key: 'guidelines', label: 'Guidelines', icon: ShieldCheck },
    { key: 'howto', label: 'How to request', icon: ListChecks },
];
const tab = ref('news');
onMounted(() => {
    const h = window.location.hash.slice(1);
    if (tabs.some((t) => t.key === h)) tab.value = h;
});
function setTab(key) {
    tab.value = key;
    window.history.replaceState(window.history.state, '', `#${key}`);
}

// ---- News ---------------------------------------------------------------------
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

// ---- Content ------------------------------------------------------------------
const steps = [
    { title: 'Get verified', text: 'Barangay staff confirm that you live in your registered barangay.' },
    { title: 'Choose and prepare', text: 'Pick the document you need and get its requirements ready.' },
    { title: 'Submit and upload', text: 'Send your request with the files. Anything you already hold in NagaSync can be linked.' },
    { title: 'Staff review', text: 'Staff check each file. If something is unclear, you are asked to re-submit it.' },
    { title: 'Approval and QR code', text: 'Once approved you get a verification QR code. For City Hall documents, pay the assessed fee at the counter.' },
    { title: 'Claim your document', text: 'Bring a valid ID and your tracking number or QR code.' },
];

const guidelines = [
    { icon: UserCheck, title: 'Use your real details', text: 'Use your real name and your actual barangay. Staff check this when verifying you.' },
    { icon: Upload, title: 'Upload clear files', text: 'Use clear, readable photos or PDFs. Blurry files will be sent back.' },
    { icon: Hash, title: 'Keep your tracking number', text: 'It is how you follow your request and claim your document.' },
    { icon: Wallet, title: 'Pay only the official way', text: 'Fees are shown before you pay. Pay only at the official counter or through NagaSync.' },
    { icon: Siren, title: 'In an emergency', text: 'Use the Emergency button. It works even before you are verified.', alert: true },
];

// ---- What each document needs (accordion + filter) -------------------------------
const openType = ref(null);
const q = ref('');
const entries = computed(() => Object.entries(props.requirements ?? {}));
const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return term ? entries.value.filter(([type]) => type.toLowerCase().includes(term)) : entries.value;
});
const requiredCount = (items) => items.filter((r) => r.is_required).length;
const slug = (t) => String(t).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
const toggle = (type) => (openType.value = openType.value === type ? null : type);
</script>

<template>
    <Head title="Guide" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-lg font-semibold text-white md:text-xl md:text-gray-800">News &amp; Guide</h2></template>

        <div class="mx-auto max-w-3xl space-y-4">
            <!-- Verification status -->
            <div v-if="!verified" role="status" class="flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-amber-900 ring-1 ring-amber-200">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100"><ShieldAlert class="h-5 w-5" aria-hidden="true" /></span>
                <div class="min-w-0 text-sm">
                    <p class="font-bold">Your account is not verified yet</p>
                    <p class="mt-0.5 text-amber-900/80">
                        Requests, payments and voting open once barangay staff confirm you live in your barangay.
                        Until then you can read the news and guides below.
                    </p>
                    <p v-if="user?.email" class="mt-1 truncate text-xs text-amber-900/60">Registered as {{ user.email }}</p>
                </div>
            </div>
            <div v-else class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-3 pr-3.5 text-emerald-900 ring-1 ring-emerald-200">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100"><BadgeCheck class="h-5 w-5" aria-hidden="true" /></span>
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-bold">You are verified</p>
                    <p class="text-emerald-900/80">You can now request documents.</p>
                </div>
                <Link :href="route('documents.index')"
                      class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#F04A2B] px-3.5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#D93C1E] active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B] focus-visible:ring-offset-2">
                    Request <ArrowRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <!-- Segmented tabs: all three always visible, no side-scrolling -->
            <div class="sticky top-2 z-20 md:static" role="tablist" aria-label="Guide sections">
                <div class="grid grid-cols-3 gap-1 rounded-2xl bg-white/90 p-1 shadow-sm ring-1 ring-black/5 backdrop-blur">
                    <button v-for="t in tabs" :key="t.key" type="button" role="tab" :id="`tab-${t.key}`"
                            :aria-selected="tab === t.key" :aria-controls="`panel-${t.key}`" @click="setTab(t.key)"
                            class="flex flex-col items-center justify-center gap-0.5 rounded-xl px-2 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B] sm:flex-row sm:gap-2 sm:text-sm"
                            :class="tab === t.key ? 'bg-[#3D2470] text-white shadow' : 'text-[#3D2470]/70 hover:bg-[#F1E8F6]'">
                        <component :is="t.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span class="leading-tight">{{ t.label }}</span>
                    </button>
                </div>
            </div>

            <!-- NEWS -->
            <section v-if="tab === 'news'" id="panel-news" role="tabpanel" aria-labelledby="tab-news" class="space-y-3">
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
            </section>

            <!-- GUIDELINES -->
            <section v-else-if="tab === 'guidelines'" id="panel-guidelines" role="tabpanel" aria-labelledby="tab-guidelines" class="space-y-3">
                <h3 class="px-1 text-base font-extrabold text-[#3D2470]">Using NagaSync</h3>
                <ul class="space-y-2.5">
                    <li v-for="g in guidelines" :key="g.title"
                        class="flex items-start gap-3 rounded-2xl p-4 shadow-sm ring-1"
                        :class="g.alert ? 'bg-red-50 ring-red-200' : 'bg-white ring-black/5'">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                              :class="g.alert ? 'bg-red-100 text-red-600' : 'bg-[#F1E8F6] text-[#3D2470]'">
                            <component :is="g.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-bold" :class="g.alert ? 'text-red-800' : 'text-[#1B1340]'">{{ g.title }}</p>
                            <p class="mt-0.5 text-sm leading-relaxed" :class="g.alert ? 'text-red-900/80' : 'text-gray-600'">{{ g.text }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- HOW TO REQUEST -->
            <section v-else id="panel-howto" role="tabpanel" aria-labelledby="tab-howto" class="space-y-4">
                <!-- Steps timeline -->
                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:rounded-3xl sm:p-5">
                    <h3 class="text-base font-extrabold text-[#3D2470]">6 steps to your document</h3>
                    <ol class="mt-4">
                        <li v-for="(s, i) in steps" :key="s.title" class="relative pb-5 pl-12 last:pb-0">
                            <span v-if="i < steps.length - 1" class="absolute bottom-0 left-[15px] top-8 w-0.5 rounded bg-[#3D2470]/15" aria-hidden="true"></span>
                            <span class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white"
                                  :class="i === 0 && verified ? 'bg-emerald-500' : 'bg-[#3D2470]'">
                                <Check v-if="i === 0 && verified" class="h-4 w-4" aria-hidden="true" /><template v-else>{{ i + 1 }}</template>
                            </span>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-bold text-[#1B1340]">{{ s.title }}</p>
                                <span v-if="i === 0 && verified" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">Done</span>
                                <span v-else-if="i === 0" class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">You are here</span>
                            </div>
                            <p class="mt-0.5 text-sm leading-relaxed text-gray-600">{{ s.text }}</p>
                        </li>
                    </ol>
                </div>

                <!-- What each document needs -->
                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:rounded-3xl sm:p-5">
                    <h3 class="text-base font-extrabold text-[#3D2470]">What each document needs</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Tap a document to see what to prepare.</p>

                    <p v-if="!entries.length" class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-500">Requirement lists will appear here soon.</p>

                    <template v-else>
                        <div v-if="entries.length > 5" class="relative mt-3">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                            <input v-model="q" type="search" placeholder="Find a document" aria-label="Find a document"
                                   class="w-full rounded-xl border-gray-300 py-2.5 pl-9 pr-9 text-base focus:border-[#3D2470] focus:ring-[#3D2470]" />
                            <button v-if="q" type="button" aria-label="Clear" @click="q = ''"
                                    class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                                <X class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>

                        <p v-if="!filtered.length" class="mt-3 rounded-xl bg-gray-50 p-4 text-center text-sm text-gray-500">No document matches “{{ q }}”.</p>

                        <div class="mt-3 space-y-2.5">
                            <div v-for="[type, items] in filtered" :key="type"
                                 class="overflow-hidden rounded-2xl ring-1 transition-colors"
                                 :class="openType === type ? 'bg-[#F1E8F6]/50 ring-[#3D2470]/40' : 'bg-white ring-gray-200'">
                                <button type="button" :id="`req-btn-${slug(type)}`" :aria-expanded="openType === type" :aria-controls="`req-${slug(type)}`"
                                        @click="toggle(type)"
                                        class="flex w-full items-center gap-3 p-3.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#F04A2B]">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#F1E8F6] text-[#3D2470]">
                                        <FileText class="h-4 w-4" aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold leading-snug text-[#1B1340]">{{ type }}</span>
                                        <span class="block text-xs text-gray-500">
                                            {{ items.length }} {{ items.length === 1 ? 'requirement' : 'requirements' }}<template v-if="requiredCount(items) !== items.length"> · {{ requiredCount(items) }} required</template>
                                        </span>
                                    </span>
                                    <ChevronDown class="h-5 w-5 shrink-0 text-[#3D2470] transition-transform duration-200 motion-reduce:transition-none"
                                                 :class="openType === type ? 'rotate-180' : ''" aria-hidden="true" />
                                </button>

                                <!-- Smooth height animation without measuring -->
                                <div :id="`req-${slug(type)}`" role="region" :aria-labelledby="`req-btn-${slug(type)}`"
                                     class="grid transition-[grid-template-rows] duration-200 motion-reduce:transition-none"
                                     :class="openType === type ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                                     :inert="openType === type ? undefined : ''">
                                    <div class="overflow-hidden">
                                        <ul class="space-y-3 border-t border-[#3D2470]/10 px-3.5 py-3.5">
                                            <li v-for="r in items" :key="r.name" class="flex items-start gap-2.5">
                                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                                                      :class="r.is_required ? 'bg-[#3D2470] text-white' : 'bg-gray-200 text-gray-500'">
                                                    <Check class="h-3 w-3" aria-hidden="true" />
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-[#1B1340]">
                                                        {{ r.name }}
                                                        <span v-if="!r.is_required" class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">Optional</span>
                                                        <span v-if="r.kind === 'issued_document'"
                                                              class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                                            <Link2 class="h-3 w-3" aria-hidden="true" /> Link your approved NagaSync document
                                                        </span>
                                                    </p>
                                                    <p v-if="r.description" class="mt-0.5 text-sm leading-relaxed text-gray-500">{{ r.description }}</p>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Next action -->
                <Link v-if="verified" :href="route('documents.index')"
                      class="flex items-center justify-between gap-3 rounded-2xl bg-[#F04A2B] p-4 text-white shadow-sm transition hover:bg-[#D93C1E] active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B] focus-visible:ring-offset-2">
                    <span>
                        <span class="block font-bold">Ready? Start your request</span>
                        <span class="block text-sm text-white/85">Choose a document and upload your files.</span>
                    </span>
                    <ArrowRight class="h-5 w-5 shrink-0" aria-hidden="true" />
                </Link>
            </section>
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
/* Cards fade and rise in one after another when the News tab opens */
.news-in { animation: news-in 0.4s ease both; animation-delay: var(--d, 0ms); }
@keyframes news-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .news-in { animation: none; }
}
</style>