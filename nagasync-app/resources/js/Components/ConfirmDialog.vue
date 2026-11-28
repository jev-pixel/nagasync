<script setup>
/**
 * Confirm before anything hard to undo (reject, release, delete).
 * Focus starts on Cancel so Enter never confirms by accident. When the dialog asks for a reason,
 * pass focus-field so the cursor lands in the first text field instead.
 *
 *   <ConfirmDialog :open="confirming" title="Reject this request?" message="The resident will be notified."
 *                  confirm-label="Reject" tone="danger" :busy="form.processing" :disabled="!reason"
 *                  focus-field @confirm="reject" @cancel="confirming = false">
 *       <textarea v-model="reason" />        (optional extra content)
 *   </ConfirmDialog>
 */
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: String,
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    tone: { type: String, default: 'default' }, // default | danger
    busy: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false }, // e.g. a required reason is still empty
    focusField: { type: Boolean, default: false },
});
const emit = defineEmits(['confirm', 'cancel']);

const box = ref(null);
const cancelBtn = ref(null);
watch(() => props.open, async (v) => {
    if (!v) return;
    await nextTick();
    const field = props.focusField ? box.value?.querySelector('textarea, input') : null;
    (field ?? cancelBtn.value)?.focus();
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-[55] flex items-center justify-center px-4"
         role="alertdialog" aria-modal="true" :aria-label="title" @keydown.esc.stop="emit('cancel')">
        <button type="button" tabindex="-1" aria-label="Cancel" class="absolute inset-0 bg-ns-ink/60" @click="emit('cancel')"></button>
        <div ref="box" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-ns-ink">{{ title }}</h2>
            <p v-if="message" class="mt-2 text-sm text-slate-600">{{ message }}</p>
            <div v-if="$slots.default" class="mt-4"><slot /></div>
            <div class="mt-6 flex justify-end gap-2">
                <button ref="cancelBtn" type="button" :disabled="busy" @click="emit('cancel')"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-ns-mist hover:bg-ns-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet disabled:opacity-50">
                    {{ cancelLabel }}
                </button>
                <button type="button" :disabled="busy || disabled" @click="emit('confirm')"
                        :class="['rounded-xl px-4 py-2 text-sm font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50',
                                 tone === 'danger' ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : 'bg-ns-violet hover:opacity-90 focus-visible:ring-ns-violet']">
                    {{ busy ? 'Working…' : confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>