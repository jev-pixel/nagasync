<script setup>
import Card from '@/Components/Card.vue';
import Icon from '@/Components/Icon.vue';
import { defineProps, } from 'vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
    icon: { type: String, default: '' },
    tone: { type: String, default: 'default' }, // default | success | warning | danger | brand
});

const valueClass = {
    default: 'text-gray-900',
    success: 'text-green-600',
    warning: 'text-amber-600',
    danger: 'text-red-600',
    brand: 'text-brand-600',
};
</script>

<template>
    <Card>
        <div class="flex items-start justify-between gap-2">
            <p class="text-sm text-gray-500">{{ label }}</p>
            <Icon v-if="icon" :name="icon" class="h-5 w-5 text-brand-400" />
        </div>
        <p class="mt-1 text-2xl font-bold tabular-nums" :class="valueClass[tone]">{{ value }}</p>
        <p v-if="hint" class="mt-0.5 text-xs text-gray-500">{{ hint }}</p>
    </Card>
</template>