<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

const props = defineProps({ documents: Object, transitions: Object });

const labels = { processing: 'Start processing', approved: 'Approve', rejected: 'Reject', revoked: 'Revoke' };

function change(doc, status) {
    let remarks = null;
    if (['rejected', 'revoked'].includes(status)) {
        remarks = window.prompt('Reason (shown to the citizen):');
        if (remarks === null) return;
    }
    router.patch(route('staff.documents.update', doc.id), { status, remarks }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Document Queue" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Document Queue</h2></template>

        <div class="mx-auto max-w-6xl p-6">
            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr><th class="p-3">Tracking</th><th class="p-3">Citizen</th><th class="p-3">Type</th>
                            <th class="p-3">Barangay</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in documents.data" :key="d.id" class="border-t">
                            <td class="p-3 font-mono">{{ d.tracking_number }}</td>
                            <td class="p-3">{{ d.citizen }}</td>
                            <td class="p-3">{{ d.document_type }}<div class="text-xs text-gray-500">{{ d.purpose }}</div></td>
                            <td class="p-3">{{ d.barangay }}</td>
                            <td class="p-3 capitalize">{{ d.status }}</td>
                            <td class="space-x-2 p-3">
                                <button v-for="s in transitions[d.status]" :key="s" @click="change(d, s)"
                                        class="rounded border px-2 py-1 hover:bg-gray-100">{{ labels[s] }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!documents.data.length" class="mt-4 text-center text-gray-500">Queue is empty.</p>
        </div>
    </AuthenticatedLayout>
</template>