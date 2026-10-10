<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

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
    <div class="flex min-h-screen items-center justify-center bg-gray-100 p-4">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow">
            <p class="mb-4 rounded bg-yellow-100 p-2 text-center text-xs font-semibold text-yellow-800">
                SANDBOX: no real money is moved
            </p>
            <p class="font-semibold">{{ label }}</p>
            <p class="font-mono text-xs text-gray-500">{{ reference }}</p>
            <p class="my-4 text-3xl font-bold">{{ php(total) }}</p>
            <button @click="submit('paid')" :disabled="form.processing"
                    class="mb-2 w-full rounded bg-green-600 py-2 text-white disabled:opacity-50">Simulate successful payment</button>
            <button @click="submit('failed')" :disabled="form.processing"
                    class="w-full rounded bg-red-600 py-2 text-white disabled:opacity-50">Simulate failed payment</button>
        </div>
    </div>
</template>