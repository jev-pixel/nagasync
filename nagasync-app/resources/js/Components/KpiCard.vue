<script setup>
/**
 * Mission Control KPI card. Every card should lead somewhere: pass `href` and it becomes a link
 * to the filtered list. Only show numbers you really track.
 *
 *   <KpiCard label="Pending requests" :value="12" tone="warning" :href="route('staff.documents.index', { status: 'pending' })" action="Review queue" />
 */
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    hint: String,                                   // e.g. "3 new today"
    tone: { type: String, default: 'normal' },      // normal | warning | critical
    href: String,
    action: { type: String, default: 'View' },
});

const BAR = { normal: 'bg-emerald-500', warning: 'bg-amber-500', critical: 'bg-red-500' };
const TONE_TEXT = { normal: 'Normal', warning: 'Needs attention', critical: 'Urgent' };
const bar = computed(() => BAR[props.tone] ?? BAR.normal);
const toneText = computed(() => TONE_TEXT[props.tone] ?? TONE_TEXT.normal);
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href"
               :class="['group relative block overflow-hidden rounded-2xl border border-ns-mist bg-white p-4 shadow-sm',
                        href ? 'transition hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet' : '']">
        <span :class="['absolute inset-y-0 left-0 w-1', bar]" aria-hidden="true"></span>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-1 text-3xl font-extrabold tabular-nums text-ns-ink">{{ value }}</p>
        <p class="mt-1 min-h-[1rem] text-xs text-slate-500">
            <span class="sr-only">{{ toneText }}. </span>{{ hint }}
        </p>
        <p v-if="href" class="mt-3 flex items-center gap-1 text-xs font-semibold text-ns-violet">
            {{ action }}
            <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5 motion-reduce:transition-none" aria-hidden="true" />
        </p>
    </component>
</template>
