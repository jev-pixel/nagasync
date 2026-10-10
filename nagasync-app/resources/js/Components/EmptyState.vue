<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
    title: { type: String, required: true },
    text: { type: String, default: '' },
    icon: { type: String, default: 'inbox' },
});
</script>

<template>
    <div class="flex flex-col items-center gap-2 px-6 py-10 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500">
            <Icon :name="icon" class="h-6 w-6" />
        </span>
        <p class="font-semibold text-gray-800">{{ title }}</p>
        <p v-if="text" class="max-w-xs text-sm text-gray-500">{{ text }}</p>
        <slot />
    </div>
</template>