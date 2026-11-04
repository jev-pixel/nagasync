<script setup>
import { CATEGORY, pesos } from '@/categories';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';

defineProps({ business: { type: Object, required: true } });
</script>

<template>
    <Link :href="route('explore.show', business.slug)" data-testid="business-card"
          class="group flex h-full flex-col overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card transition duration-150 hover:-translate-y-0.5 hover:border-ns-violet/40 hover:shadow-lg active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet motion-reduce:transition-none">
        <div class="relative aspect-[16/10] w-full overflow-hidden">
            <img v-if="business.cover_url" :src="business.cover_url" alt="" loading="lazy"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none" />
            <div v-else :class="['flex h-full w-full items-center justify-center bg-gradient-to-br text-white', CATEGORY[business.category]?.tone]">
                <component :is="CATEGORY[business.category]?.icon" class="h-12 w-12" aria-hidden="true" />
            </div>
            <span v-if="business.featured" class="absolute left-3 top-3 rounded-lg bg-ns-orange px-2.5 py-1 text-xs font-bold text-ns-ink">Featured</span>
        </div>
        <div class="flex flex-1 flex-col gap-1 p-4">
            <h3 class="text-base font-bold leading-snug">{{ business.name }}</h3>
            <p v-if="business.tagline" class="line-clamp-2 text-sm text-slate-600">{{ business.tagline }}</p>
            <p class="mt-auto flex flex-wrap items-center gap-x-2 border-t border-ns-mist pt-2 text-xs font-medium text-slate-500">
                <span>{{ CATEGORY[business.category]?.label }}</span>
                <span v-if="business.barangay" class="inline-flex items-center gap-1"><MapPin class="h-3 w-3" aria-hidden="true" />{{ business.barangay }}</span>
                <span v-if="business.price_range" class="font-bold text-ns-ink" :aria-label="`Price level ${business.price_range} of 3`">{{ pesos(business.price_range) }}</span>
            </p>
        </div>
    </Link>
</template>
