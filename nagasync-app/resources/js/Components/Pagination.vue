<script setup>
import { Link } from '@inertiajs/vue3';

// Pass Laravel's paginator `links` array: [{ url, label, active }, ...]
defineProps({ links: { type: Array, default: () => [] } });
</script>

<template>
    <nav v-if="links.length > 3" class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
        <template v-for="(l, i) in links" :key="i">
            <span v-if="!l.url" v-html="l.label" class="rounded-lg px-3 py-1.5 text-sm text-slate-400"></span>
            <Link v-else :href="l.url" v-html="l.label" preserve-scroll
                  class="rounded-lg px-3 py-1.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet"
                  :class="l.active ? 'bg-ns-navy text-white' : 'border border-ns-mist bg-white text-ns-ink hover:bg-ns-mist'" />
        </template>
    </nav>
</template>
