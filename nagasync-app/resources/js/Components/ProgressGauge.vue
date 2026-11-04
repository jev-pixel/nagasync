<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    percent: { type: Number, default: 0 },
    size: { type: Number, default: 88 },
    stroke: { type: Number, default: 8 },
    tone: { type: String, default: 'coral' },
    label: { type: String, default: 'Progress' },
    dark: Boolean,
});

const colors = { coral: '#e8445a', orange: '#ffa51f', teal: '#3ccfe0', violet: '#5b4bdb' };
const clamped = computed(() => Math.max(0, Math.min(100, Math.round(props.percent))));
const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

// Animate from empty on mount (a CSS transition; it is skipped under reduced motion)
const shown = ref(0);
onMounted(() => requestAnimationFrame(() => (shown.value = clamped.value)));
const offset = computed(() => circumference.value * (1 - shown.value / 100));
</script>

<template>
    <div class="relative inline-flex items-center justify-center" role="img" :aria-label="`${label}: ${clamped}%`"
         :style="{ width: size + 'px', height: size + 'px' }">
        <svg :width="size" :height="size" class="-rotate-90" aria-hidden="true">
            <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" :stroke-width="stroke"
                    :stroke="dark ? 'rgba(255,255,255,.15)' : '#eceffa'" />
            <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" :stroke-width="stroke" stroke-linecap="round"
                    :stroke="colors[tone]" :stroke-dasharray="circumference" :stroke-dashoffset="offset"
                    class="transition-[stroke-dashoffset] duration-700 motion-reduce:transition-none" />
        </svg>
        <span :class="['absolute text-lg font-bold', dark ? 'text-white' : 'text-ns-ink']">{{ clamped }}%</span>
    </div>
</template>