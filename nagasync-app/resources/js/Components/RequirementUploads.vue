<script setup>
/**
 * Citizen-side requirements checklist.
 *
 * Usage inside Pages/Citizen/Documents/Index.vue (new request form):
 *
 *   <RequirementUploads
 *     :requirements="requirements[form.document_type] || []"
 *     v-model="form.files"
 *     :errors="form.errors"
 *   />
 *
 * with `files: {}` added to your useForm() defaults, and submit via
 * form.post(route('documents.store'), { forceFormData: true }).
 */
const props = defineProps({
    requirements: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

function pick(req, event) {
    const file = event.target.files[0];
    const next = { ...props.modelValue };
    if (file) next[req.id] = file;
    else delete next[req.id];
    emit('update:modelValue', next);
}
</script>

<template>
    <div v-if="requirements.length" class="space-y-3">
        <h3 class="text-sm font-bold text-ns-ink">Required documents</h3>
        <p class="text-xs text-slate-500">JPG, PNG or PDF, up to 5 MB each. Staff will verify these before your request is approved.</p>

        <div
            v-for="req in requirements"
            :key="req.id"
            class="rounded-xl border border-ns-mist bg-white p-3"
        >
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-ns-ink">
                        {{ req.name }}
                        <span v-if="req.is_required" class="text-ns-coral">*</span>
                        <span v-else class="text-xs font-normal text-slate-400">(optional)</span>
                    </p>
                    <p v-if="req.description" class="text-xs text-slate-500">{{ req.description }}</p>
                </div>
                <span v-if="modelValue[req.id]" class="shrink-0 rounded-md bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Attached</span>
            </div>

            <input
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ns-mist file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-ns-ink hover:file:bg-ns-mist/70"
                @change="pick(req, $event)"
            />
            <p v-if="modelValue[req.id]" class="mt-1 truncate text-xs text-slate-500">{{ modelValue[req.id].name }}</p>
            <p v-if="errors['files.' + req.id]" class="mt-1 text-xs text-ns-coral">{{ errors['files.' + req.id] }}</p>
        </div>
    </div>
</template>
