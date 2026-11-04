<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { CATEGORY, TAG_LABEL, pesos } from '@/categories';
import { Head, Link } from '@inertiajs/vue3';
import { BadgeCheck, Clock, Globe, Mail, MapPin, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({ business: Object });

const web = (url) => (/^https?:\/\//i.test(url ?? '') ? url : null); // never render any other scheme as a link
const tel = computed(() => 'tel:' + (props.business.phone ?? '').replace(/[^\d+]/g, ''));
const map = computed(() => props.business.latitude !== null && props.business.longitude !== null
    ? `https://www.openstreetmap.org/?mlat=${props.business.latitude}&mlon=${props.business.longitude}#map=17/${props.business.latitude}/${props.business.longitude}`
    : null);
const row = 'flex items-start gap-3 py-3 text-sm';
const link = 'font-semibold text-ns-violet underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
</script>

<template>
    <Head :title="business.name" />

    <PublicLayout>
        <Link :href="route('explore.index')" class="mt-2 inline-flex h-11 items-center text-sm font-semibold text-ns-violet underline">← All businesses</Link>

        <article class="mt-2 overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card">
            <div class="aspect-[16/7] w-full overflow-hidden">
                <img v-if="business.cover_url" :src="business.cover_url" :alt="business.name" class="h-full w-full object-cover" />
                <div v-else :class="['flex h-full w-full items-center justify-center bg-gradient-to-br text-white', CATEGORY[business.category]?.tone]">
                    <component :is="CATEGORY[business.category]?.icon" class="h-16 w-16" aria-hidden="true" />
                </div>
            </div>

            <div class="grid gap-6 p-6 md:grid-cols-3 md:p-8">
                <div class="md:col-span-2">
                    <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                        <span class="rounded-full bg-ns-mist px-3 py-1">{{ CATEGORY[business.category]?.label }}</span>
                        <span v-if="business.price_range" class="font-bold text-ns-ink">{{ pesos(business.price_range) }}</span>
                        <span class="inline-flex items-center gap-1 text-emerald-700"><BadgeCheck class="h-4 w-4" aria-hidden="true" /> Reviewed listing</span>
                    </p>
                    <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">{{ business.name }}</h1>
                    <p v-if="business.tagline" class="mt-1 text-lg text-slate-600">{{ business.tagline }}</p>
                    <p v-if="business.description" class="mt-4 whitespace-pre-line leading-relaxed">{{ business.description }}</p>

                    <ul v-if="business.tags.length" class="mt-5 flex flex-wrap gap-2" aria-label="Features">
                        <li v-for="t in business.tags" :key="t" class="rounded-full bg-ns-teal/25 px-3 py-1 text-xs font-semibold">{{ TAG_LABEL[t] }}</li>
                    </ul>
                </div>

                <aside class="rounded-xl bg-ns-canvas p-4 md:self-start" aria-label="Details">
                    <ul class="divide-y divide-white">
                        <li :class="row"><MapPin class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>{{ business.address }}<span v-if="business.barangay">, {{ business.barangay }}</span>
                                <a v-if="map" :href="map" target="_blank" rel="noopener" :class="[link, 'mt-1 block']">Open in maps</a></span></li>
                        <li v-if="business.hours" :class="row"><Clock class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /><span>{{ business.hours }}</span></li>
                        <li v-if="business.phone" :class="row"><Phone class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /><a :href="tel" :class="link">{{ business.phone }}</a></li>
                        <li v-if="business.email" :class="row"><Mail class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /><a :href="'mailto:' + business.email" :class="[link, 'break-all']">{{ business.email }}</a></li>
                        <li v-if="web(business.website)" :class="row"><Globe class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <a :href="web(business.website)" target="_blank" rel="noopener nofollow" :class="[link, 'break-all']">Website</a></li>
                        <li v-if="web(business.facebook_url)" :class="row"><Globe class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            <a :href="web(business.facebook_url)" target="_blank" rel="noopener nofollow" :class="[link, 'break-all']">Facebook page</a></li>
                    </ul>
                </aside>
            </div>
        </article>
    </PublicLayout>
</template>