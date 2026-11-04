<script setup>
import { computed } from 'vue';

const props = defineProps({ title: String, bars: { type: Array, required: true } });

const max = computed(() => Math.max(1, ...props.bars.map((b) => b.count)));
const todayIndex = computed(() => props.bars.findIndex((b) => b.today));
const summary = computed(() => props.bars.map((b) => `${b.label} ${b.count}`).join(', '));
</script>

<template>
    <section class="rounded-3xl bg-white p-5 shadow-card">
        <h3 class="text-base font-bold">{{ title }}</h3>
        <p class="mt-2 text-3xl font-extrabold">
            {{ bars[todayIndex]?.count ?? 0 }}<span class="ml-1 text-sm font-medium text-slate-500">today</span>
        </p>
        <div class="mt-4 flex h-40 items-end gap-2" role="img" :aria-label="`${title}: ${summary}`">
            <div v-for="(b, i) in bars" :key="b.label" class="flex h-full min-h-0 flex-1 flex-col items-center gap-2">
                <div class="flex min-h-0 w-full flex-1 items-end rounded-full bg-ns-mist" :title="`${b.label}: ${b.count}`">
                    <div :class="['w-full rounded-full transition-[height] duration-500 motion-reduce:transition-none', i === todayIndex ? 'bg-ns-orange' : 'bg-ns-teal']"
                         :style="{ height: Math.max(b.count ? 12 : 0, (b.count / max) * 100) + '%' }"></div>
                </div>
                <span :class="['text-[11px] font-semibold', i === todayIndex ? 'text-ns-ink' : 'text-slate-400']">{{ b.label.slice(0, 2).toUpperCase() }}</span>
            </div>
        </div>
    </section>
</template>