<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps, } from 'vue';
defineProps({ valid: Boolean, status: String, trackingNumber: String, documentType: String,
              barangay: String, holder: String, issuedAt: String });
</script>

<template>
    <Head title="Document Verification" />
    <div class="flex min-h-screen items-center justify-center bg-gray-100 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow">
            <div :class="valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                 class="mb-4 rounded-lg p-3 text-center text-lg font-bold">
                {{ valid ? 'VALID DOCUMENT' : 'NOT VALID (' + status.toUpperCase() + ')' }}
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Document</dt><dd>{{ documentType }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Holder</dt><dd>{{ holder }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Barangay</dt><dd>{{ barangay }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Issued</dt><dd>{{ issuedAt ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Tracking No.</dt><dd class="font-mono">{{ trackingNumber }}</dd></div>
            </dl>
            <p class="mt-4 text-center text-xs text-gray-400">NagaSync live verification</p>
        </div>
    </div>
</template>