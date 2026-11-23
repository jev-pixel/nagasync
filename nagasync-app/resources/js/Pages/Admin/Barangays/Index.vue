<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Upload, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({ barangays: { type: Array, default: () => [] } });

const q = ref('');
const list = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t ? props.barangays.filter((b) => b.name.toLowerCase().includes(t)) : props.barangays;
});

const editing = ref(null);
const picked = ref(null); // object URL of the chosen image
const fileError = ref('');
const form = useForm({ captain_name: '', signature: null, remove_signature: false });

const revoke = () => { if (picked.value) URL.revokeObjectURL(picked.value); picked.value = null; };
onBeforeUnmount(revoke);

function edit(b) {
    revoke();
    fileError.value = '';
    form.clearErrors();
    form.captain_name = b.captain_name ?? '';
    form.signature = null;
    form.remove_signature = false;
    editing.value = b.id;
}
function cancel() { revoke(); editing.value = null; }

function pick(e) {
    const f = e.target.files[0];
    revoke();
    fileError.value = '';
    form.signature = null;
    if (!f) return;
    if (!['image/png', 'image/jpeg'].includes(f.type)) { fileError.value = 'Use a PNG or JPG image.'; e.target.value = ''; return; }
    if (f.size > 1024 * 1024) { fileError.value = 'The image must be 1 MB or smaller.'; e.target.value = ''; return; }
    form.signature = f;
    form.remove_signature = false;
    picked.value = URL.createObjectURL(f);
}

const save = (b) => form.post(route('admin.barangays.update', b.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: cancel,
});

const field = 'mt-1 w-full rounded-xl border-gray-300 focus:border-[#3D2470] focus:ring-[#3D2470]';
</script>

<template>
    <Head title="Barangay signatories" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-lg font-semibold text-white md:text-xl md:text-gray-800">Barangay signatories</h2></template>

        <div class="mx-auto max-w-4xl space-y-4 p-4 sm:p-6">
            <section class="rounded-3xl bg-[#F1E8F6] p-5 text-sm text-[#3D2470]">
                <h3 class="text-lg font-extrabold">Who signs the certificates</h3>
                <p class="mt-1">Each barangay's Punong Barangay name and signature are printed on its PDF certificates. A document keeps the signatory on file when it is approved, so changing the captain later does not alter documents already approved.</p>
                <ul class="mt-2 list-disc space-y-0.5 pl-5">
                    <li>Use a <strong>PNG with a transparent background</strong>, dark ink, about 600×200 px (at most 2500×1500 and 1 MB).</li>
                    <li>Upload only a signature the Punong Barangay has authorised for this use.</li>
                </ul>
            </section>

            <label class="block">
                <span class="sr-only">Search barangays</span>
                <input v-model="q" type="search" placeholder="Search barangay…" :class="field" />
            </label>

            <p v-if="!list.length" class="rounded-2xl bg-gray-50 p-4 text-center text-sm text-gray-500">No barangay matches.</p>

            <article v-for="b in list" :key="b.id" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-[#1B1340]">Barangay {{ b.name }}</p>
                        <p class="text-sm" :class="b.captain_name ? 'text-gray-600' : 'text-amber-700'">
                            {{ b.captain_name || 'No captain set' }}
                            <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-bold"
                                  :class="b.has_signature ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                                {{ b.has_signature ? 'Signature on file' : 'No signature' }}
                            </span>
                        </p>
                    </div>
                    <button v-if="editing !== b.id" type="button" @click="edit(b)"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm font-semibold text-[#3D2470] hover:bg-[#E8DAF1]">
                        <Pencil class="h-4 w-4" /> Edit
                    </button>
                </div>

                <div v-if="editing === b.id" class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                    <div>
                        <label :for="'cn-' + b.id" class="block text-sm font-semibold text-[#1B1340]">Punong Barangay (printed name)</label>
                        <input :id="'cn-' + b.id" v-model="form.captain_name" type="text" maxlength="120" placeholder="Hon. Juan D. Dela Cruz" :class="field" />
                        <p v-if="form.errors.captain_name" class="mt-1 text-sm text-red-600">{{ form.errors.captain_name }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-[#1B1340]">Signature image</p>
                        <div class="mt-2 flex flex-wrap items-center gap-4">
                            <div class="flex h-24 w-56 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-[repeating-conic-gradient(#f3f4f6_0%_25%,#fff_0%_50%)] bg-[length:16px_16px]">
                                <img v-if="picked" :src="picked" alt="New signature" class="max-h-full max-w-full object-contain" />
                                <img v-else-if="b.has_signature && !form.remove_signature"
                                     :src="route('admin.barangays.signature', b.id) + '?v=' + b.version" alt="Current signature" class="max-h-full max-w-full object-contain" />
                                <span v-else class="text-xs text-gray-400">No signature</span>
                            </div>
                            <div class="space-y-2">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm font-semibold text-[#3D2470] hover:bg-[#E8DAF1]">
                                    <Upload class="h-4 w-4" /> {{ b.has_signature ? 'Replace image' : 'Choose image' }}
                                    <input type="file" accept="image/png,image/jpeg" class="sr-only" @change="pick" />
                                </label>
                                <label v-if="b.has_signature && !picked" class="flex items-center gap-2 text-sm text-gray-600">
                                    <input v-model="form.remove_signature" type="checkbox" class="rounded border-gray-300 text-[#3D2470] focus:ring-[#3D2470]" />
                                    Remove the current signature
                                </label>
                            </div>
                        </div>
                        <p v-if="fileError" class="mt-1 text-sm text-red-600">{{ fileError }}</p>
                        <p v-if="form.errors.signature" class="mt-1 text-sm text-red-600">{{ form.errors.signature }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" :disabled="form.processing || !form.captain_name.trim()" @click="save(b)"
                                class="rounded-xl bg-[#3D2470] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E1B58] disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </button>
                        <button type="button" @click="cancel" class="inline-flex items-center gap-1 rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">
                            <X class="h-4 w-4" /> Cancel
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </AuthenticatedLayout>
</template>
