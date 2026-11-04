<script setup>
defineProps({ days: Array, rangeLabel: String, label: String });

const dot = (n) => (n === 0 ? 'bg-transparent' : n < 3 ? 'bg-ns-teal' : n < 5 ? 'bg-ns-orange' : 'bg-ns-coral');
</script>

<template>
    <section class="rounded-2xl border border-ns-mist bg-white p-5 shadow-card" :aria-label="label">
        <div class="mb-3 flex items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold">{{ rangeLabel }}</h2>
            <span class="text-xs font-medium text-slate-500">{{ label }}</span>
        </div>
        <ol class="grid grid-cols-7 gap-1 text-center">
            <li v-for="d in days" :key="d.label + d.date" :aria-label="`${d.label} ${d.date}: ${d.count}`"
                :aria-current="d.today ? 'date' : undefined"
                :class="['flex flex-col items-center gap-1 rounded-xl py-2', d.today ? 'bg-ns-navy text-white shadow-md' : '']">
                <span :class="['text-xs font-semibold', d.today ? 'text-white/80' : 'text-slate-500']">{{ d.label }}</span>
                <span class="text-lg font-bold">{{ d.date }}</span>
                <span :class="['h-1.5 w-1.5 rounded-full', d.today ? 'bg-ns-orange' : dot(d.count)]"></span>
            </li>
        </ol>
    </section>
</template>
