<script setup>
/**
 * One badge for every status in the system. Meaning is carried by the word AND the colour,
 * so it never relies on colour alone.
 *
 *   <StatusBadge status="pending" />            -> amber "Pending"
 *   <StatusBadge status="ready" label="Ready to release" />
 */
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: String,
});

const TONES = {
    amber: 'bg-amber-100 text-amber-900 ring-amber-300',
    blue: 'bg-sky-100 text-sky-900 ring-sky-300',
    green: 'bg-emerald-100 text-emerald-900 ring-emerald-300',
    red: 'bg-red-100 text-red-900 ring-red-300',
    gray: 'bg-slate-100 text-slate-700 ring-slate-300',
};
const DOTS = { amber: 'bg-amber-500', blue: 'bg-sky-500', green: 'bg-emerald-500', red: 'bg-red-500', gray: 'bg-slate-400' };

// Add new statuses here once, and every page picks them up
const MAP = {
    pending: 'amber', unverified: 'amber', submitted: 'amber', unpaid: 'amber',
    processing: 'blue', in_review: 'blue', dispatched: 'blue', assessed: 'blue',
    approved: 'green', ready: 'green', claimed: 'green', released: 'green', paid: 'green',
    verified: 'green', resolved: 'green', completed: 'green',
    rejected: 'red', failed: 'red', cancelled: 'red', active: 'red', overdue: 'red',
};

const tone = computed(() => MAP[props.status] ?? 'gray');
const text = computed(() => props.label ?? props.status.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase()));
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', TONES[tone]]">
        <span :class="['h-1.5 w-1.5 rounded-full', DOTS[tone]]" aria-hidden="true"></span>
        {{ text }}
    </span>
</template>
