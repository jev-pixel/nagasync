<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CATEGORY, STATUS_STYLE } from '@/categories';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ businesses: Object, status: String, statuses: Array, canFeature: Boolean });

function review(b, status) {
    let note = null;
    if (status !== 'approved') {
        note = window.prompt('Reason (the owner will see this):');
        if (!note) return;
    }
    router.patch(route('staff.businesses.update', b.id), { status, note }, { preserveScroll: true });
}
const feature = (b) => router.patch(route('staff.businesses.update', b.id), { is_featured: !b.is_featured }, { preserveScroll: true });

const tab = (active) => ['inline-flex h-10 items-center rounded-full px-4 text-sm font-semibold capitalize focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet', active ? 'bg-ns-ink text-white' : 'bg-white shadow-card hover:shadow-md'];
const btn = 'inline-flex h-10 items-center rounded-full border border-ns-mist px-4 text-sm font-semibold transition hover:bg-ns-mist active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet';
</script>

<template>
    <Head title="Business reviews" />
    <AuthenticatedLayout>
        <template #header>Business reviews</template>

        <div class="space-y-4">
            <nav class="flex flex-wrap gap-2" aria-label="Status">
                <Link v-for="s in statuses" :key="s" :href="route('staff.businesses.index', { status: s })" :class="tab(s === status)">{{ s }}</Link>
            </nav>

            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                Approving lists the business publicly. Check that it is real, in your barangay, and that the details match the permit number. [Confirm the exact procedure with the LGU.]
            </div>

            <article v-for="b in businesses.data" :key="b.id" class="rounded-3xl bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold">{{ b.name }}
                            <span :class="[STATUS_STYLE[b.status], 'ml-2 rounded-full px-2 py-0.5 text-xs font-semibold capitalize']">{{ b.status }}</span>
                            <span v-if="b.is_featured" class="ml-1 rounded-full bg-ns-orange px-2 py-0.5 text-xs font-bold">Featured</span></h2>
                        <p class="text-sm text-slate-600">{{ CATEGORY[b.category]?.label }} · {{ b.barangay }} · {{ b.address }}</p>
                        <p class="text-xs text-slate-500">Owner: {{ b.owner }}<span v-if="b.owner_phone"> · {{ b.owner_phone }}</span> · Permit no.: {{ b.permit_no ?? 'not given' }} · {{ b.submitted }}</p>
                        <p v-if="b.description" class="mt-2 line-clamp-3 text-sm">{{ b.description }}</p>
                        <p v-if="b.review_note" class="mt-1 text-xs text-slate-500">Note: {{ b.review_note }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template v-if="b.status === 'pending'">
                            <button :class="btn" @click="review(b, 'approved')">Approve</button>
                            <button :class="[btn, 'text-red-700']" @click="review(b, 'rejected')">Reject</button>
                        </template>
                        <button v-if="b.status === 'approved'" :class="[btn, 'text-red-700']" @click="review(b, 'suspended')">Suspend</button>
                        <button v-if="b.status === 'suspended'" :class="btn" @click="review(b, 'approved')">Restore</button>
                        <button v-if="canFeature && b.status === 'approved'" :class="btn" @click="feature(b)">{{ b.is_featured ? 'Unfeature' : 'Feature' }}</button>
                        <Link v-if="b.status === 'approved'" :href="route('explore.show', b.slug)" :class="btn">View</Link>
                    </div>
                </div>
            </article>

            <p v-if="!businesses.data.length" class="rounded-3xl bg-white p-10 text-center text-slate-500 shadow-card">Nothing {{ status }}.</p>

            <div v-if="businesses.last_page > 1" class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Page {{ businesses.current_page }} of {{ businesses.last_page }}</span>
                <div class="space-x-2">
                    <Link v-if="businesses.prev_page_url" :href="businesses.prev_page_url" :class="btn">Previous</Link>
                    <Link v-if="businesses.next_page_url" :href="businesses.next_page_url" :class="btn">Next</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>