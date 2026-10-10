<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({ barangays: Array, polls: Array });

const form = useForm({ title: '', description: '', barangay_id: '', opens_at: '', closes_at: '', options: ['', ''] });

const addOption = () => form.options.length < 8 && form.options.push('');
const removeOption = (i) => form.options.length > 2 && form.options.splice(i, 1);
const submit = () => form.post(route('admin.polls.store'), { preserveScroll: true, onSuccess: () => form.reset() });
const publish = (p) => confirm('Publish this poll? Its options can no longer change.')
    && router.patch(route('admin.polls.publish', p.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Manage Polls" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Manage Polls</h2></template>

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <form @submit.prevent="submit" class="space-y-3 rounded-lg bg-white p-5 shadow">
                <h3 class="font-semibold">New poll</h3>
                <input v-model="form.title" placeholder="Question / title" class="w-full rounded border-gray-300" />
                <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
                <textarea v-model="form.description" rows="2" placeholder="Description (optional)" class="w-full rounded border-gray-300"></textarea>

                <div class="grid gap-3 md:grid-cols-3">
                    <select v-model="form.barangay_id" class="rounded border-gray-300">
                        <option value="">City-wide</option>
                        <option v-for="b in barangays" :key="b.id" :value="b.id">{{ b.name }} only</option>
                    </select>
                    <input v-model="form.opens_at" type="datetime-local" class="rounded border-gray-300" />
                    <input v-model="form.closes_at" type="datetime-local" class="rounded border-gray-300" />
                </div>
                <p v-if="form.errors.opens_at || form.errors.closes_at" class="text-sm text-red-600">
                    {{ form.errors.opens_at || form.errors.closes_at }}
                </p>

                <div class="space-y-2">
                    <div v-for="(o, i) in form.options" :key="i" class="flex gap-2">
                        <input v-model="form.options[i]" :placeholder="'Option ' + (i + 1)" class="w-full rounded border-gray-300" />
                        <button type="button" @click="removeOption(i)" class="rounded border px-3 text-gray-500">✕</button>
                    </div>
                    <button type="button" @click="addOption" class="text-sm text-indigo-600 underline">+ Add option</button>
                    <p v-if="form.errors.options" class="text-sm text-red-600">{{ form.errors.options }}</p>
                    <p v-for="(msg, key) in form.errors" :key="key" v-show="String(key).startsWith('options.')" class="text-sm text-red-600">{{ msg }}</p>
                </div>

                <button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Create as draft</button>
            </form>

            <div v-for="p in polls" :key="p.id" class="flex items-center justify-between rounded-lg bg-white p-4 shadow">
                <div>
                    <p class="font-semibold">{{ p.title }}</p>
                    <p class="text-xs text-gray-500">{{ p.scope }} · {{ p.opens_at }} to {{ p.closes_at }} · {{ p.participants }} voted</p>
                </div>
                <div class="text-sm">
                    <span v-if="p.closed" class="rounded bg-gray-200 px-2 py-0.5">Closed</span>
                    <span v-else-if="p.is_published" class="rounded bg-green-100 px-2 py-0.5 text-green-800">Published</span>
                    <button v-else @click="publish(p)" class="rounded border px-3 py-1 hover:bg-gray-100">Publish</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>