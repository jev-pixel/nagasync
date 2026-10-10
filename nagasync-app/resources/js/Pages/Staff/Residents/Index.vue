<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { defineProps, ref } from 'vue';

const props = defineProps({ residents: Object, filters: Object });
const q = ref(props.filters.q ?? '');

const search = () => router.get(route('staff.residents.index'), { q: q.value }, { preserveState: true, replace: true });

function setVerified(r, verified) {
    const message = verified
        ? `Confirm you have checked a valid ID showing ${r.name}'s address in this barangay.`
        : `Remove resident verification for ${r.name}?`;
    if (confirm(message)) {
        router.patch(route('staff.residents.update', r.id), { verified }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Residents" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Resident Verification</h2></template>

        <div class="mx-auto max-w-6xl space-y-4 p-6">
            <div class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                Verify a resident only after checking a valid ID that shows an address in your barangay.
                Verified residents can vote in community polls. [Confirm the exact procedure with the LGU.]
            </div>

            <form @submit.prevent="search" class="flex gap-2">
                <input v-model="q" placeholder="Search name, email or phone" class="w-full rounded border-gray-300" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Search</button>
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr><th class="p-3">Name</th><th class="p-3">Contact</th><th class="p-3">Barangay</th>
                            <th class="p-3">Registered</th><th class="p-3">Status</th><th class="p-3"></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in residents.data" :key="r.id" class="border-t">
                            <td class="p-3 font-medium">{{ r.name }}</td>
                            <td class="p-3">{{ r.email }}<div class="text-xs text-gray-500">{{ r.phone }}</div></td>
                            <td class="p-3">{{ r.barangay }}</td>
                            <td class="p-3 text-gray-500">{{ r.registered }}</td>
                            <td class="p-3">
                                <span v-if="r.verified" class="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Verified</span>
                                <span v-else class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Unverified</span>
                                <div v-if="r.verified" class="text-xs text-gray-500">{{ r.verified_at }} by {{ r.verified_by ?? 'n/a' }}</div>
                            </td>
                            <td class="p-3 text-right">
                                <button v-if="!r.verified" @click="setVerified(r, true)" class="rounded border px-3 py-1 hover:bg-gray-100">Verify</button>
                                <button v-else @click="setVerified(r, false)" class="rounded border px-3 py-1 text-red-700 hover:bg-red-50">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!residents.data.length" class="p-6 text-center text-gray-500">No residents found.</p>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-600">
                <span>{{ residents.from ?? 0 }}–{{ residents.to ?? 0 }} of {{ residents.total }}</span>
                <div class="space-x-2">
                    <Link v-if="residents.prev_page_url" :href="residents.prev_page_url" class="rounded border px-3 py-1">Previous</Link>
                    <Link v-if="residents.next_page_url" :href="residents.next_page_url" class="rounded border px-3 py-1">Next</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>