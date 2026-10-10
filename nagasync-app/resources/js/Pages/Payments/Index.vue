<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, defineProps } from 'vue';

const props = defineProps({ types: Array, fee: Number, payments: Array });

const form = useForm({ payment_type: '' });
const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const selected = computed(() => props.types.find((t) => t.key === form.payment_type));
const badge = {
    pending: 'bg-yellow-100 text-yellow-800', paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800', expired: 'bg-gray-200 text-gray-700', refunded: 'bg-blue-100 text-blue-800',
};
</script>

<template>
    <Head title="Payments" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Municipal Payments</h2></template>

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <form @submit.prevent="form.post(route('payments.store'))" class="space-y-3 rounded-lg bg-white p-5 shadow">
                <h3 class="font-semibold">Pay a fee</h3>
                <select v-model="form.payment_type" class="w-full rounded border-gray-300">
                    <option value="" disabled>Select payment type</option>
                    <option v-for="t in types" :key="t.key" :value="t.key">{{ t.label }}</option>
                </select>
                <p v-if="form.errors.payment_type" class="text-sm text-red-600">{{ form.errors.payment_type }}</p>

                <div v-if="selected" class="rounded bg-gray-50 p-3 text-sm">
                    <div class="flex justify-between"><span>Fee</span><span>{{ php(selected.amount) }}</span></div>
                    <div class="flex justify-between"><span>Convenience fee</span><span>{{ php(fee) }}</span></div>
                    <div class="mt-1 flex justify-between border-t pt-1 font-semibold"><span>Total</span><span>{{ php(selected.amount + fee) }}</span></div>
                </div>

                <button :disabled="form.processing || !form.payment_type" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Proceed to payment</button>
            </form>

            <div v-for="p in payments" :key="p.id" class="flex items-center justify-between rounded-lg bg-white p-5 shadow">
                <div>
                    <p class="font-semibold">{{ p.label }}</p>
                    <p class="font-mono text-sm text-gray-500">{{ p.reference_no }}</p>
                    <p v-if="p.paid_at" class="text-xs text-gray-400">Paid {{ p.paid_at }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold">{{ php(p.total) }}</p>
                    <span :class="badge[p.status]" class="rounded px-2 py-0.5 text-xs font-medium capitalize">{{ p.status }}</span>
                </div>
            </div>
            <p v-if="!payments.length" class="text-center text-gray-500">No payments yet.</p>
        </div>
    </AuthenticatedLayout>
</template>