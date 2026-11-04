<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CATEGORY, STATUS_STYLE } from '@/categories';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({ businesses: Array, limit: Number });

const remove = (b) => confirm(`Delete "${b.name}"? This cannot be undone.`) && router.delete(route('businesses.destroy', b.id), { preserveScroll: true });
const btn = 'inline-flex h-11 items-center gap-2 rounded-full border border-ns-mist px-4 text-sm font-semibold transition hover:bg-ns-mist active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
const hint = {
    pending: 'Waiting for review by your barangay.',
    approved: 'Live in the directory.',
    rejected: 'Not approved. Edit the listing and it will be reviewed again.',
    suspended: 'Hidden by staff. Contact your barangay.',
};
</script>

<template>
    <Head title="My businesses" />
    <AuthenticatedLayout>
        <template #header>My businesses</template>

        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600">List your business in the local directory. You can add up to {{ limit }}. Every listing is reviewed before it goes live.</p>
                <Link :href="route('businesses.create')" class="inline-flex h-11 items-center gap-2 rounded-full bg-ns-orange px-5 text-sm font-semibold text-ns-ink shadow-card transition hover:shadow-lg active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <Plus class="h-4 w-4" aria-hidden="true" /> Add a business
                </Link>
            </div>

            <div v-for="b in businesses" :key="b.id" class="flex flex-wrap items-center gap-4 rounded-3xl bg-white p-4 shadow-card">
                <div class="h-16 w-24 shrink-0 overflow-hidden rounded-2xl">
                    <img v-if="b.cover_url" :src="b.cover_url" alt="" class="h-full w-full object-cover" />
                    <div v-else :class="['flex h-full w-full items-center justify-center bg-gradient-to-br text-white', CATEGORY[b.category]?.tone]">
                        <component :is="CATEGORY[b.category]?.icon" class="h-6 w-6" aria-hidden="true" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold">{{ b.name }}</p>
                    <p class="text-xs text-slate-500">{{ CATEGORY[b.category]?.label }} · {{ b.barangay }}</p>
                    <p class="mt-1 text-xs">
                        <span :class="[STATUS_STYLE[b.status], 'rounded-full px-2 py-0.5 font-semibold capitalize']">{{ b.status }}</span>
                        <span class="ml-2 text-slate-500">{{ hint[b.status] }}</span>
                    </p>
                    <p v-if="b.review_note" class="mt-1 text-xs text-red-700">Staff note: {{ b.review_note }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="b.status === 'approved'" :href="route('explore.show', b.slug)" :class="btn">View</Link>
                    <Link :href="route('businesses.edit', b.id)" :class="btn"><Pencil class="h-4 w-4" aria-hidden="true" /> Edit</Link>
                    <button type="button" :class="[btn, 'text-red-700']" @click="remove(b)"><Trash2 class="h-4 w-4" aria-hidden="true" /> Delete</button>
                </div>
            </div>

            <p v-if="!businesses.length" class="rounded-3xl bg-white p-10 text-center text-slate-500 shadow-card">You haven't listed a business yet.</p>
        </div>
    </AuthenticatedLayout>
</template>