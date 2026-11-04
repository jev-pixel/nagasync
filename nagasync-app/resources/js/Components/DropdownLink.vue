<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm font-medium leading-5 text-ns-ink transition duration-150 ease-in-out hover:bg-ns-mist focus:bg-ns-mist focus:outline-none"
    >
        <slot />
    </Link>
</template>
