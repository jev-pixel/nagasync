<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

defineProps({ barangays: Array, polls: Array });

const form = useForm({ title: '', description: '', barangay_id: '', opens_at: '', closes_at: '', image: null, options: ['', ''] });

const addOption = () => form.options.length < 8 && form.options.push('');
const removeOption = (i) => form.options.length > 2 && form.options.splice(i, 1);

// ---- Image picker with preview -------------------------------------------------------------
const fileInput = ref(null);
const preview = ref(null);

function pickImage(e) {
    const file = e.target.files[0] ?? null;
    form.image = file;
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = file ? URL.createObjectURL(file) : null;
}

function clearImage() {
    form.image = null;
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

onBeforeUnmount(() => preview.value && URL.revokeObjectURL(preview.value));

// A File in the form makes Inertia send multipart data automatically
const submit = () => form.post(route('admin.polls.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { form.reset(); clearImage(); },
});

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

                <!-- Optional image -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Image (optional)</label>
                    <div v-if="preview" class="relative mb-2 overflow-hidden rounded-lg border border-gray-200">
                        <img :src="preview" alt="Selected poll image" class="h-44 w-full object-cover" />
                        <button type="button" @click="clearImage"
                                class="absolute right-2 top-2 rounded bg-white/90 px-2 py-1 text-xs font-medium text-gray-700 shadow hover:bg-white">Remove</button>
                    </div>
                    <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" @change="pickImage"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, up to 3 MB. Wide images (16:9) look best.</p>
                    <p v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</p>
                </div>

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

            <div v-for="p in polls" :key="p.id" class="flex items-center justify-between gap-4 rounded-lg bg-white p-4 shadow">
                <div class="flex min-w-0 items-center gap-3">
                    <img v-if="p.image_url" :src="p.image_url" alt="" class="h-12 w-16 shrink-0 rounded object-cover" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ p.title }}</p>
                        <p class="text-xs text-gray-500">{{ p.scope }} · {{ p.opens_at }} to {{ p.closes_at }} · {{ p.participants }} voted</p>
                    </div>
                </div>
                <div class="shrink-0 text-sm">
                    <span v-if="p.closed" class="rounded bg-gray-200 px-2 py-0.5">Closed</span>
                    <span v-else-if="p.is_published" class="rounded bg-green-100 px-2 py-0.5 text-green-800">Published</span>
                    <button v-else @click="publish(p)" class="rounded border px-3 py-1 hover:bg-gray-100">Publish</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>