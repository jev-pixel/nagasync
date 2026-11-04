<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ title: String, subtitle: String, ctaLabel: String, ctaHref: String });
</script>

<template>
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ns-orange to-[#ff8a1f] p-6 text-ns-ink shadow-card sm:p-8">
        <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/25" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/15" aria-hidden="true"></div>
        <div class="relative max-w-md">
            <h1 class="text-3xl font-extrabold sm:text-4xl">{{ title }}</h1>
            <p class="mt-2 text-base font-medium leading-6 text-ns-ink/80">{{ subtitle }}</p>
            <Link v-if="ctaHref" :href="ctaHref"
                  class="mt-5 inline-flex h-11 items-center rounded-full bg-ns-ink px-6 text-sm font-semibold text-white transition hover:bg-ns-navy hover:shadow-lg active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-ink focus-visible:ring-offset-2 focus-visible:ring-offset-ns-orange motion-reduce:transition-none">
                {{ ctaLabel }}
            </Link>
        </div>
    </section>
</template>