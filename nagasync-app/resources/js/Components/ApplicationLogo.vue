<script setup>
defineProps({
    alt: { type: String, default: 'myNaga' },
});
</script>

<template>
    <img
        src="/images/mynaga-logo.png"
        :alt="alt"
        class="rounded-2xl object-contain"
        draggable="false"
    />
</template>