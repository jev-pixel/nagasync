<script setup>
import GovBanner from '@/Components/GovBanner.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    valid: Boolean,
    // City Hall document that is approved but not yet paid for and claimed at the counter
    awaitingClaim: { type: Boolean, default: false },
    status: String,
    trackingNumber: String,
    documentType: String,
    barangay: String,
    holder: String,
    issuedAt: String,
    releasedAt: { type: String, default: null },
});
</script>

<template>
    <Head title="Document Verification" />
    <div class="flex min-h-screen flex-col bg-ns-canvas">
        <GovBanner />

        <div class="flex flex-1 items-center justify-center p-4">
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-ns-mist bg-white shadow-card">
                <div class="bg-ns-navy px-6 py-4 text-center text-white">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-white/60">Official Document Verification</p>
                    <p class="text-lg font-extrabold">NagaSync</p>
                </div>

                <div class="p-6">
                    <div v-if="valid" class="mb-4 rounded-xl bg-green-100 p-3 text-center text-lg font-bold text-green-800">
                        VALID DOCUMENT
                    </div>
                    <div v-else-if="awaitingClaim" class="mb-4 rounded-xl bg-amber-100 p-3 text-center text-amber-900">
                        <p class="text-lg font-bold">APPROVED, NOT YET ISSUED</p>
                        <p class="mt-1 text-sm">
                            This request was approved but has not been claimed at City Hall yet. It becomes a valid document
                            only after the fee is paid and it is handed over at the counter.
                        </p>
                    </div>
                    <div v-else class="mb-4 rounded-xl bg-red-100 p-3 text-center text-lg font-bold text-red-800">
                        NOT VALID ({{ status.replace('_', ' ').toUpperCase() }})
                    </div>

                    <dl class="divide-y divide-ns-mist text-sm">
                        <div class="flex justify-between py-2"><dt class="text-slate-500">Document</dt><dd class="font-semibold">{{ documentType }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">Holder</dt><dd class="font-semibold">{{ holder }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">Barangay</dt><dd class="font-semibold">{{ barangay }}</dd></div>
                        <div v-if="awaitingClaim" class="flex justify-between py-2"><dt class="text-slate-500">Approved</dt><dd>{{ issuedAt ?? '-' }}</dd></div>
                        <div v-else class="flex justify-between py-2"><dt class="text-slate-500">Issued</dt><dd>{{ releasedAt ?? issuedAt ?? '-' }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">Tracking No.</dt><dd class="font-mono">{{ trackingNumber }}</dd></div>
                    </dl>
                    <p class="mt-4 text-center text-xs text-slate-400">NagaSync live verification</p>
                </div>
            </div>
        </div>
    </div>
</template>
