<script setup>
/**
 * "What is it for?" + personal details step of a document request.
 *
 *   <PurposeStep
 *     :purposes="purposes[form.document_type] || []"
 *     :fields="personalFields[form.document_type] || []"
 *     v-model:purpose-key="form.purpose_key"
 *     v-model:info="form.personal_info"
 *     :errors="form.errors" />
 *
 * useForm defaults: purpose_key: null, personal_info: {}. When there are no purposes (the type uses
 * the free-text purpose box) the purpose section is hidden.
 */
defineProps({
    purposes: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const purposeKey = defineModel('purposeKey', { default: null });
const info = defineModel('info', { default: () => ({}) });

const peso = (n) => (n > 0 ? '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'Free');
const setField = (key, value) => (info.value = { ...info.value, [key]: value });

const box = 'mt-1 block w-full rounded-xl border border-ns-mist bg-white px-3 py-2 text-sm focus:border-ns-violet focus:outline-none focus:ring-2 focus:ring-ns-violet/30';
</script>

<template>
    <div class="space-y-5">
        <fieldset v-if="purposes.length">
            <legend class="text-sm font-bold text-ns-ink">What is it for?</legend>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <label v-for="p in purposes" :key="p.key"
                       :class="['flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3 text-sm transition',
                                purposeKey === p.key ? 'border-ns-violet bg-ns-violet/5' : 'border-ns-mist bg-white hover:bg-ns-mist']">
                    <span class="flex items-center gap-2">
                        <input v-model="purposeKey" type="radio" :value="p.key" class="text-ns-violet focus:ring-ns-violet" />
                        <span class="font-semibold">{{ p.label }}</span>
                    </span>
                    <span :class="['text-xs font-bold', p.fee > 0 ? 'text-ns-ink' : 'text-emerald-700']">{{ peso(p.fee) }}</span>
                </label>
            </div>
            <p v-if="errors.purpose_key" class="mt-1 text-xs text-ns-coral">{{ errors.purpose_key }}</p>
        </fieldset>

        <div v-if="fields.length" class="space-y-3">
            <h3 class="text-sm font-bold text-ns-ink">Your details</h3>
            <p class="text-xs text-slate-500">These are printed on the certificate. Staff compare them with your documents.</p>

            <div v-for="f in fields" :key="f.key">
                <label :for="'pi-' + f.key" class="block text-sm font-semibold text-ns-ink">
                    {{ f.label }}
                    <span v-if="f.required" class="text-ns-coral">*</span>
                    <span v-else class="text-xs font-normal text-slate-400">(optional)</span>
                </label>

                <select v-if="f.type === 'select'" :id="'pi-' + f.key" :value="info[f.key] ?? ''" :class="box"
                        @change="setField(f.key, $event.target.value)">
                    <option value="" disabled>Select…</option>
                    <option v-for="o in f.options" :key="o" :value="o">{{ o }}</option>
                </select>
                <input v-else :id="'pi-' + f.key" :type="f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : 'text'"
                       :min="f.type === 'number' ? 0 : undefined" :value="info[f.key] ?? ''" :class="box"
                       @input="setField(f.key, $event.target.value)" />
                <p v-if="errors['personal_info.' + f.key]" class="mt-1 text-xs text-ns-coral">{{ errors['personal_info.' + f.key] }}</p>
            </div>
        </div>
    </div>
</template>
