<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({ categories: Array, recent: Array });

const form = useForm({
    commodity: '', category: 'other', unit: 'kg', price: '', srp: '',
    recorded_on: new Date().toLocaleDateString('en-CA'), // local YYYY-MM-DD
});
const submit = () => form.post(route('staff.market.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('commodity', 'price', 'srp'),
});
const php = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Price Entry" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Market Price Entry</h2></template>

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <form @submit.prevent="submit" class="grid gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-3">
                <div class="md:col-span-2">
                    <input v-model="form.commodity" placeholder="Commodity (e.g. Rice (Well Milled))" class="w-full rounded border-gray-300" />
                    <p v-if="form.errors.commodity" class="text-sm text-red-600">{{ form.errors.commodity }}</p>
                </div>
                <select v-model="form.category" class="rounded border-gray-300 capitalize">
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
                <input v-model="form.unit" placeholder="Unit (kg, liter, piece)" class="rounded border-gray-300" />
                <div>
                    <input v-model="form.price" type="number" step="0.01" placeholder="Price (PHP)" class="w-full rounded border-gray-300" />
                    <p v-if="form.errors.price" class="text-sm text-red-600">{{ form.errors.price }}</p>
                </div>
                <input v-model="form.srp" type="number" step="0.01" placeholder="SRP (optional, official only)" class="rounded border-gray-300" />
                <div>
                    <input v-model="form.recorded_on" type="date" class="w-full rounded border-gray-300" />
                    <p v-if="form.errors.recorded_on" class="text-sm text-red-600">{{ form.errors.recorded_on }}</p>
                </div>
                <button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50 md:col-span-2">Save price</button>
                <p v-if="form.recentlySuccessful" class="self-center text-sm text-green-700">Saved.</p>
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left"><tr><th class="p-3">Date</th><th class="p-3">Commodity</th><th class="p-3 text-right">Price</th><th class="p-3 text-right">SRP</th></tr></thead>
                    <tbody>
                        <tr v-for="p in recent" :key="p.id" class="border-t">
                            <td class="p-3">{{ p.recorded_on }}</td>
                            <td class="p-3">{{ p.commodity }} <span class="text-gray-400">/ {{ p.unit }}</span></td>
                            <td class="p-3 text-right">{{ php(p.price) }}</td>
                            <td class="p-3 text-right">{{ p.srp !== null ? php(p.srp) : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>