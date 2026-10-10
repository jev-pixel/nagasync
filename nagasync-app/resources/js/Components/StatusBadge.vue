<script setup>
import { computed, defineProps } from 'vue';

// The single status -> colour map for the whole app.
const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: '' },
});

const styles = {
    // documents
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    revoked: 'bg-gray-200 text-gray-700',
    // payments
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    expired: 'bg-gray-200 text-gray-700',
    refunded: 'bg-blue-100 text-blue-800',
    // emergency
    active: 'bg-red-100 text-red-800',
    dispatched: 'bg-blue-100 text-blue-800',
    resolved: 'bg-green-100 text-green-800',
    false_alarm: 'bg-gray-200 text-gray-700',
    // polls and residents
    open: 'bg-green-100 text-green-800',
    closed: 'bg-gray-200 text-gray-700',
    upcoming: 'bg-blue-100 text-blue-800',
    verified: 'bg-green-100 text-green-800',
    unverified: 'bg-amber-100 text-amber-800',
};

const text = computed(() => {
    const raw = props.label || props.status.replace(/_/g, ' ');
    return raw.charAt(0).toUpperCase() + raw.slice(1);
});
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="styles[status] ?? 'bg-gray-100 text-gray-700'">
        {{ text }}
    </span>
</template>