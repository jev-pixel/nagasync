<script setup>
import { useForm } from '@inertiajs/vue3';
import { Calculator } from 'lucide-vue-next';
import { computed } from 'vue';

// `doc` is the document request as the review page already has it. It needs:
// id, document_type, level, status, fee_total, fee_breakdown
const props = defineProps({ doc: { type: Object, required: true } });

const form = useForm({
    declared_amount: props.doc.fee_breakdown?.inputs?.declared_amount ?? '',
    adjustment: '',
    adjustment_reason: '',
});

const basisLabel = computed(() => ({
    'Business Permit (New)': 'Declared capital (₱)',
    'Business Permit (Renewal)': 'Gross sales last year (₱)',
    'Community Tax Certificate': 'Annual income (₱)',
}[props.doc.document_type] ?? 'Declared amount (₱)'));

const editable = computed(() => ['pending', 'processing', 'needs_resubmission'].includes(props.doc.status));
const peso = (n) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(n ?? 0));

const submit = () => form.post(route('staff.documents.assess', props.doc.id), { preserveScroll: true });
</script>

<template>
    <section v-if="doc.level === 'city'" class="rounded-2xl bg-white p-4 ring-1 ring-gray-200">
        <h3 class="flex items-center gap-2 font-bold text-[#3D2470]">
            <Calculator class="h-5 w-5" /> Fee assessment
        </h3>

        <div v-if="doc.fee_total != null" class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900">
            <p class="font-semibold">Assessed: {{ peso(doc.fee_total) }}</p>
            <ul v-if="doc.fee_breakdown?.lines?.length" class="mt-1 space-y-0.5">
                <li v-for="l in doc.fee_breakdown.lines" :key="l.label" class="flex justify-between gap-3">
                    <span>{{ l.label }}</span><span>{{ peso(l.amount) }}</span>
                </li>
            </ul>
        </div>
        <p v-else class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
            Not assessed yet. A City Hall document cannot be approved until its fee is assessed.
        </p>

        <form v-if="editable" class="mt-3 space-y-3" @submit.prevent="submit">
            <div>
                <label class="text-sm font-medium text-gray-700">{{ basisLabel }}</label>
                <input v-model="form.declared_amount" type="number" min="0" step="0.01"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                <p v-if="form.errors.declared_amount" class="mt-1 text-sm text-red-600">{{ form.errors.declared_amount }}</p>
            </div>

            <details class="text-sm">
                <summary class="cursor-pointer font-medium text-[#3D2470]">Add an adjustment (optional)</summary>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <input v-model="form.adjustment" type="number" step="0.01" placeholder="Amount (negative for a discount)"
                           class="rounded-xl border-gray-300 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                    <input v-model="form.adjustment_reason" placeholder="Reason (required)"
                           class="rounded-xl border-gray-300 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                </div>
                <p v-if="form.errors.adjustment" class="mt-1 text-sm text-red-600">{{ form.errors.adjustment }}</p>
                <p v-if="form.errors.adjustment_reason" class="mt-1 text-sm text-red-600">{{ form.errors.adjustment_reason }}</p>
            </details>

            <button :disabled="form.processing || form.declared_amount === ''"
                    class="rounded-xl bg-[#3D2470] px-4 py-2 text-sm font-bold text-white disabled:opacity-40">
                {{ doc.fee_total != null ? 'Reassess fee' : 'Assess fee' }}
            </button>
        </form>
    </section>
</template>