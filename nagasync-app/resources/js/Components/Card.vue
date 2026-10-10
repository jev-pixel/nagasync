<script setup>
defineProps({
    as: { type: String, default: 'div' },
    padded: { type: Boolean, default: true },
    tone: { type: String, default: 'white' }, // white | tint | brand
});
</script>

<template>
    <component
        :is="as"
        class="rounded-card"
        :class="[
            tone === 'brand' ? 'bg-brand-600 text-white' : tone === 'tint' ? 'bg-brand-50' : 'bg-white shadow-card',
            padded ? 'p-5' : '',
        ]"
    >
        <slot />
    </component>
</template>