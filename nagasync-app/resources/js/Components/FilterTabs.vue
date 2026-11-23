<script setup>
/**
 * Filter chips for queues, with optional counts.
 *
 *   <FilterTabs v-model="status" :tabs="[{ key: 'all', label: 'All', count: 40 }, { key: 'pending', label: 'Pending', count: 12 }]" />
 */
defineProps({
    modelValue: { type: String, required: true },
    tabs: { type: Array, required: true }, // [{ key, label, count? }]
    label: { type: String, default: 'Filter' },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div role="group" :aria-label="label" class="flex flex-wrap gap-2">
        <button v-for="t in tabs" :key="t.key" type="button"
                :aria-pressed="modelValue === t.key"
                @click="emit('update:modelValue', t.key)"
                :class="['inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none',
                         modelValue === t.key ? 'bg-ns-ink text-white' : 'bg-white text-slate-600 ring-1 ring-ns-mist hover:bg-ns-mist']">
            {{ t.label }}
            <span v-if="t.count !== undefined"
                  :class="['rounded-full px-1.5 text-xs tabular-nums', modelValue === t.key ? 'bg-white/20' : 'bg-ns-mist']">{{ t.count }}</span>
        </button>
    </div>
</template>
