<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-xl border border-ns-mist bg-white px-5 py-2.5 text-sm font-bold text-ns-ink shadow-sm transition duration-150 ease-in-out hover:bg-ns-mist focus:outline-none focus:ring-2 focus:ring-ns-violet focus:ring-offset-2 active:scale-95 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
