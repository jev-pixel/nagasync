<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, FlaskConical, XCircle } from 'lucide-vue-next';

const props = defineProps({ reference: String, label: String, total: Number });
const form = useForm({ outcome: '' });
const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const submit = (outcome) => {
    form.outcome = outcome;
    form.post(route('payments.sandbox.complete', props.reference));
};
</script>

<template>
    <Head title="Sandbox Checkout" />
    <div class="flex min-h-screen items-center justify-center bg-[#F1E8F6] p-4">
        <div class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-xl">
            <div class="flex items-center justify-center gap-2 bg-amber-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-amber-800">
                <FlaskConical class="h-4 w-4" /> Test mode: no real money is moved
            </div>

            <div class="p-6">
                <p class="text-sm font-medium text-gray-500">You are paying for</p>
                <p class="text-lg font-extrabold text-[#3D2470]">{{ label }}</p>

                <p class="mt-5 text-sm font-medium text-gray-500">Total amount</p>
                <p class="text-4xl font-extrabold text-[#1B1340]">{{ php(total) }}</p>

                <p class="mt-4 text-xs text-gray-500">
                    Reference no.
                    <span class="select-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-700">{{ reference }}</span>
                </p>

                <div class="mt-6 space-y-3">
                    <p class="text-sm text-[#1B1340]/70">Choose what should happen with this test payment:</p>

                    <button @click="submit('paid')" :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3 font-bold text-white shadow transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <CheckCircle2 class="h-5 w-5" /> Simulate successful payment
                    </button>
                    <button @click="submit('failed')" :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white py-3 font-bold text-red-600 ring-1 ring-red-300 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                        <XCircle class="h-5 w-5" /> Simulate failed payment
                    </button>
                </div>

                <p v-if="form.processing" class="mt-4 text-center text-sm text-gray-500">Processing, please wait…</p>
            </div>
        </div>
    </div>
</template>