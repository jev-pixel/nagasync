<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps, } from 'vue';


defineProps({ types: Array, documents: Array });

const form = useForm({ document_type: '', purpose: '' });
const submit = () => form.post(route('documents.store'), {
    preserveScroll: true, onSuccess: () => form.reset(),
});
const badge = {
    pending: 'bg-yellow-100 text-yellow-800', processing: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800', rejected: 'bg-red-100 text-red-800',
    revoked: 'bg-gray-200 text-gray-700',
};
</script>

<template>
    <Head title="My Documents" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">My Documents</h2></template>

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <form @submit.prevent="submit" class="space-y-3 rounded-lg bg-white p-5 shadow">
                <h3 class="font-semibold">Request a document</h3>
                <select v-model="form.document_type" class="w-full rounded border-gray-300">
                    <option value="" disabled>Select document type</option>
                    <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                </select>
                <p v-if="form.errors.document_type" class="text-sm text-red-600">{{ form.errors.document_type }}</p>
                <input v-model="form.purpose" placeholder="Purpose (e.g. employment)" class="w-full rounded border-gray-300" />
                <p v-if="form.errors.purpose" class="text-sm text-red-600">{{ form.errors.purpose }}</p>
                <button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Submit</button>
            </form>

            <div v-for="d in documents" :key="d.id" class="flex items-start justify-between gap-4 rounded-lg bg-white p-5 shadow">
                <div>
                    <p class="font-semibold">{{ d.document_type }}</p>
                    <p class="text-sm text-gray-500">{{ d.tracking_number }} · {{ d.purpose }}</p>
                    <span :class="badge[d.status]" class="mt-2 inline-block rounded px-2 py-0.5 text-xs font-medium capitalize">{{ d.status }}</span>
                    <p v-if="d.remarks" class="mt-1 text-sm text-gray-600">Remarks: {{ d.remarks }}</p>
                    <a v-if="d.verify_url" :href="d.verify_url" target="_blank" class="mt-1 block text-sm text-indigo-600 underline">Open verification page</a>
                </div>
                <img v-if="d.status === 'approved'" :src="route('documents.qr', d.id)" alt="Verification QR"
     loading="lazy" class="h-32 w-32" />
            </div>
            <p v-if="!documents.length" class="text-center text-gray-500">No requests yet.</p>
        </div>
    </AuthenticatedLayout>
</template>