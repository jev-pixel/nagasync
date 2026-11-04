<script setup>
import { iconFor } from '@/icons';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({ title: { type: String, default: 'Services' }, items: { type: Array, required: true } });

const track = ref(null);
const scrollByCard = (dir) => track.value?.scrollBy({ left: dir * 200, behavior: 'smooth' });

const tones = { orange: 'bg-ns-orange/20', teal: 'bg-ns-teal/25', coral: 'bg-ns-coral/15', violet: 'bg-ns-violet/15' };
const btn = 'flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-card transition hover:shadow-md active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
</script>

<template>
    <section :aria-label="title">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-bold">{{ title }}</h2>
            <div class="hidden gap-2 md:flex">
                <button type="button" :class="btn" aria-label="Scroll left" @click="scrollByCard(-1)"><ChevronLeft class="h-4 w-4" /></button>
                <button type="button" :class="btn" aria-label="Scroll right" @click="scrollByCard(1)"><ChevronRight class="h-4 w-4" /></button>
            </div>
        </div>

        <!-- Negative margins let the cards scroll edge to edge on small screens -->
        <div ref="track" data-testid="folder-carousel" tabindex="0" :aria-label="`${title}, scrollable`"
            class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto overflow-y-hidden overscroll-x-contain scroll-smooth rounded-2xl px-4 pb-3 pt-1 [-webkit-overflow-scrolling:touch] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:scroll-auto sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:mx-0 lg:px-0">
            <Link v-for="item in items" :key="item.href" :href="item.href" data-testid="folder-card"
                  class="group w-44 shrink-0 snap-start rounded-3xl bg-white p-4 shadow-card transition duration-150 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none">
                <span :class="['flex h-10 w-10 items-center justify-center rounded-2xl', tones[item.tone]]">
                    <component :is="iconFor(item.icon)" class="h-5 w-5" aria-hidden="true" />
                </span>
                <span class="mt-3 block text-sm font-bold">{{ item.title }}</span>
                <span class="block text-xs text-slate-500">{{ item.sub }}</span>
            </Link>
        </div>
    </section>
</template>