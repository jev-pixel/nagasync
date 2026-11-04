<script setup>
import BusinessCard from '@/Components/BusinessCard.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { CATEGORY, TAG_LABEL } from '@/categories';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps({
    businesses: Object, featured: { type: Array, default: () => [] }, filters: Object,
    categories: Array, tags: Array, barangays: Array,
});

const state = reactive({
    q: props.filters.q ?? '', category: props.filters.category ?? '',
    tag: props.filters.tag ?? '', barangay: props.filters.barangay ?? '',
});
const hasFilters = computed(() => Object.values(state).some(Boolean));

function apply(patch = {}) {
    Object.assign(state, patch);
    const query = Object.fromEntries(Object.entries(state).filter(([, v]) => v));
    router.get(route('explore.index'), query, { preserveState: true, preserveScroll: true, replace: true });
}
const clear = () => apply({ q: '', category: '', tag: '', barangay: '' });

const chip = (active) => [
    'inline-flex h-10 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-semibold transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet',
    active ? 'bg-ns-ink text-white' : 'bg-white text-ns-ink shadow-card hover:shadow-md',
];
const select = 'h-11 rounded-full border-0 bg-white px-4 text-sm shadow-card focus:ring-2 focus:ring-ns-violet';
</script>

<template>
    <Head title="Explore local businesses" />

    <PublicLayout>
        <section class="relative mt-2 overflow-hidden rounded-2xl bg-ns-navy p-6 text-white shadow-card sm:p-10">
            <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full border-[18px] border-white/5" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <h1 class="text-3xl font-extrabold sm:text-4xl">Discover local favourites</h1>
                <p class="mt-2 text-base font-medium text-white/80">Cafés, shops, services and places to stay, from local businesses near you.</p>
                <form class="mt-5 flex flex-col gap-2 sm:flex-row" role="search" @submit.prevent="apply()">
                    <label for="explore-q" class="sr-only">Search businesses</label>
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input id="explore-q" v-model="state.q" type="search" maxlength="80" placeholder="Search by name or what they offer"
                               class="h-12 w-full rounded-xl border-0 bg-white pl-11 pr-4 text-sm text-ns-ink shadow-card focus:ring-2 focus:ring-ns-orange" />
                    </div>
                    <button class="h-12 rounded-xl bg-ns-orange px-6 text-sm font-bold text-ns-ink transition hover:brightness-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Search</button>
                </form>
            </div>
        </section>

        <div class="scrollbar-none -mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="group" aria-label="Categories">
            <button type="button" :class="chip(!state.category)" :aria-pressed="!state.category" @click="apply({ category: '' })">All</button>
            <button v-for="c in categories" :key="c" type="button" :class="chip(state.category === c)" :aria-pressed="state.category === c"
                    @click="apply({ category: c })">
                <component :is="CATEGORY[c].icon" class="h-4 w-4" aria-hidden="true" /> {{ CATEGORY[c].label }}
            </button>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <label class="sr-only" for="f-barangay">Barangay</label>
            <select id="f-barangay" :class="select" :value="state.barangay" @change="apply({ barangay: $event.target.value })">
                <option value="">All barangays</option>
                <option v-for="b in barangays" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <label class="sr-only" for="f-tag">Feature</label>
            <select id="f-tag" :class="select" :value="state.tag" @change="apply({ tag: $event.target.value })">
                <option value="">Any feature</option>
                <option v-for="t in tags" :key="t" :value="t">{{ TAG_LABEL[t] }}</option>
            </select>
            <button v-if="hasFilters" type="button" class="h-11 rounded-full px-4 text-sm font-semibold text-ns-violet underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet" @click="clear">Clear filters</button>
        </div>

        <section v-if="featured.length" class="mt-8" aria-label="Featured">
            <h2 class="mb-3 text-lg font-bold">Featured</h2>
            <div class="scrollbar-none -mx-4 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto overscroll-x-contain px-4 pb-3 [-webkit-overflow-scrolling:touch] sm:mx-0 sm:px-0">
                <div v-for="b in featured" :key="b.slug" class="w-72 shrink-0 snap-start"><BusinessCard :business="b" /></div>
            </div>
        </section>

        <section class="mt-8" aria-label="Results">
            <h2 class="mb-3 text-lg font-bold">{{ hasFilters ? 'Results' : 'All businesses' }}
                <span class="text-sm font-medium text-slate-500">· {{ businesses.total }}</span></h2>

            <div v-if="businesses.data.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <BusinessCard v-for="b in businesses.data" :key="b.slug" :business="b" />
            </div>
            <div v-else class="rounded-2xl border border-ns-mist bg-white p-10 text-center shadow-card">
                <p class="font-semibold">Nothing matches yet.</p>
                <p class="mt-1 text-sm text-slate-500">Try a different word or clear the filters.</p>
            </div>

            <div v-if="businesses.last_page > 1" class="mt-6 flex items-center justify-between text-sm">
                <span class="text-slate-500">Page {{ businesses.current_page }} of {{ businesses.last_page }}</span>
                <div class="space-x-2">
                    <Link v-if="businesses.prev_page_url" :href="businesses.prev_page_url" preserve-scroll class="inline-flex h-11 items-center rounded-full bg-white px-5 font-semibold shadow-card">Previous</Link>
                    <Link v-if="businesses.next_page_url" :href="businesses.next_page_url" preserve-scroll class="inline-flex h-11 items-center rounded-full bg-white px-5 font-semibold shadow-card">Next</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>