<script setup>
/**
 * Right-hand panel: inspect a record and act on it without leaving the page.
 * Use it inside the layout's #context slot, only while something is selected:
 *
 *   <template #context>
 *     <ContextPanel v-if="selected" :title="selected.document_type" :subtitle="selected.tracking_number" @close="selected = null">
 *       ...details...
 *       <template #actions> <button>Approve</button> </template>
 *     </ContextPanel>
 *   </template>
 *
 * xl screens: sits beside the page (the page makes room for it).
 * Smaller screens: slides over the page with a backdrop.
 */
import { X } from 'lucide-vue-next';
import { inject, onMounted, onUnmounted, ref } from 'vue';

defineProps({ title: { type: String, required: true }, subtitle: String });
const emit = defineEmits(['close']);

const panel = ref(null);
const previous = ref(null);
const reserve = inject('cc-panel-open', ref(false));

const onKey = (e) => e.key === 'Escape' && emit('close');

onMounted(() => {
    reserve.value = true;
    previous.value = document.activeElement; // return focus here when closed
    panel.value?.focus();
    window.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    reserve.value = false;
    window.removeEventListener('keydown', onKey);
    previous.value?.focus?.();
});
</script>

<template>
    <div>
        <!-- Backdrop on screens where the panel overlaps the page -->
        <button type="button" aria-label="Close details" tabindex="-1"
                class="fixed inset-0 z-30 bg-ns-ink/40 xl:hidden" @click="emit('close')"></button>

        <aside ref="panel" tabindex="-1" role="complementary" :aria-label="`Details: ${title}`"
               class="fixed bottom-8 right-0 top-[5.4rem] z-40 flex w-full max-w-md flex-col border-l border-ns-mist bg-white shadow-2xl focus:outline-none xl:max-w-[26rem] xl:shadow-none">
            <header class="flex items-start justify-between gap-3 border-b border-ns-mist px-5 py-4">
                <div class="min-w-0">
                    <h2 class="truncate text-base font-bold text-ns-ink">{{ title }}</h2>
                    <p v-if="subtitle" class="truncate font-mono text-xs text-slate-500">{{ subtitle }}</p>
                </div>
                <button type="button" aria-label="Close details" @click="emit('close')"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-ns-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </header>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4"><slot /></div>

            <footer v-if="$slots.actions" class="flex flex-wrap gap-2 border-t border-ns-mist bg-ns-canvas px-5 py-3">
                <slot name="actions" />
            </footer>
        </aside>
    </div>
</template>