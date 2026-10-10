<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <section class="relative overflow-hidden rounded-card bg-gradient-to-br from-brand-600 to-brand-900 p-6 text-white shadow-card sm:p-8">
        <span class="pointer-events-none absolute -right-10 -top-12 h-48 w-48 rounded-full bg-white/10"></span>
        <span class="pointer-events-none absolute -bottom-16 right-20 h-40 w-40 rounded-full bg-white/5"></span>
        <div class="relative">
            <h1 class="text-2xl font-bold sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 max-w-md text-sm text-brand-100 sm:text-base">{{ subtitle }}</p>
            <div v-if="$slots.default" class="mt-5 flex flex-wrap items-center gap-3">
                <slot />
            </div>
        </div>
    </section>
</template>