<script setup>
import { iconFor } from '@/icons';
import { Link } from '@inertiajs/vue3';

defineProps({ metric: { type: Object, required: true } });

const tones = {
    orange: 'bg-ns-orange/20 text-orange-700',
    coral: 'bg-ns-coral/15 text-ns-coral',
    teal: 'bg-ns-teal/25 text-teal-700',
    violet: 'bg-ns-violet/15 text-ns-violet',
};
</script>

<template>
    <Link :href="metric.href"
          class="group flex flex-col gap-3 rounded-2xl border border-ns-mist bg-white p-4 shadow-card transition duration-150 hover:-translate-y-0.5 hover:border-ns-violet/40 hover:shadow-lg active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none">
        <span :class="['flex h-10 w-10 items-center justify-center rounded-xl', tones[metric.tone]]">
            <component :is="iconFor(metric.icon)" class="h-5 w-5" aria-hidden="true" />
        </span>
        <span>
            <span class="block text-2xl font-extrabold leading-tight">{{ metric.value }}</span>
            <span class="block text-xs font-medium text-slate-500">{{ metric.label }}</span>
        </span>
    </Link>
</template>
