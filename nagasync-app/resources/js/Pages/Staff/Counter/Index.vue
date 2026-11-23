<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // swap for your NagaSync layout
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({ q: String, doc: Object, error: String })

const search = useForm({ q: props.q ?? '' })
const find = () => router.get(route('staff.index'), { q: search.q }, { preserveState: true })

const release = useForm({ official_receipt_no: '', amount_paid: props.doc?.fee?.total ?? 0, id_checked: false })
watch(() => props.doc?.id, () => {
  release.reset()
  release.amount_paid = props.doc?.fee?.total ?? 0
})
const submitRelease = () =>
  release.post(route('staff.release', props.doc.id), { preserveScroll: true })

const isFree = computed(() => Number(props.doc?.fee?.total ?? 0) <= 0)
const peso = (n) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n ?? 0)
</script>

<template>
  <Head title="Counter" />
  <AuthenticatedLayout>
    <div class="mx-auto max-w-3xl space-y-4 p-4">
      <h1 class="text-lg font-semibold">Counter</h1>
      <p class="text-sm text-gray-500">Release approved documents for your barangay or City Hall office. Free documents need no payment or receipt.</p>

      <form class="flex gap-2" @submit.prevent="find">
        <input v-model="search.q" autofocus placeholder="Scan the QR code or type the tracking number"
               class="w-full rounded-lg border px-3 py-2" />
        <button class="rounded-lg bg-indigo-700 px-4 py-2 text-white">Find</button>
      </form>

      <p v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

      <div v-if="doc" class="space-y-4 rounded-xl border bg-white p-4">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-semibold">{{ doc.document_type }}</p>
            <p class="text-sm text-gray-600">{{ doc.tracking_number }} · {{ doc.purpose }}</p>
            <p class="text-sm">{{ doc.citizen.name }}<span v-if="doc.citizen.barangay"> · {{ doc.citizen.barangay }}</span></p>
          </div>
          <span class="rounded-full px-3 py-1 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800': doc.state === 'ready',
                  'bg-amber-100 text-amber-800': doc.state === 'not_ready',
                  'bg-red-100 text-red-800': ['rejected', 'revoked', 'already_released'].includes(doc.state),
                }">
            {{ doc.state === 'ready' ? 'Approved: ready to release'
               : doc.state === 'already_released' ? 'Already released'
               : doc.state === 'rejected' ? 'Rejected'
               : doc.state === 'revoked' ? 'Revoked' : 'Not yet approved' }}
          </span>
        </div>

        <p v-if="doc.state === 'already_released'" class="text-sm text-red-700">
          Released {{ doc.released_at }} · OR {{ doc.receipt_no }}. Do not release again.
        </p>

        <ul class="divide-y text-sm">
          <li v-for="r in doc.requirements" :key="r.name" class="flex justify-between py-1.5">
            <span>{{ r.name }}<em v-if="r.linked" class="text-gray-500"> (linked {{ r.linked_tracking }}, {{ r.linked_status }})</em></span>
            <span :class="r.review_status === 'verified' ? 'text-green-700' : 'text-amber-700'">{{ r.review_status }}</span>
          </li>
        </ul>

        <table class="w-full text-sm">
          <tr v-for="l in doc.fee.lines" :key="l.label">
            <td class="py-1">{{ l.label }}</td><td class="py-1 text-right">{{ peso(l.amount) }}</td>
          </tr>
          <tr class="border-t font-semibold">
            <td class="py-1.5">Total due</td><td class="py-1.5 text-right">{{ peso(doc.fee.total) }}</td>
          </tr>
        </table>

        <form v-if="doc.state === 'ready'" class="space-y-3 border-t pt-3" @submit.prevent="submitRelease">
          <label class="flex items-center gap-2 text-sm">
            <input v-model="release.id_checked" type="checkbox" />
            I checked the citizen's valid ID against the name above.
          </label>
          <p v-if="isFree" class="rounded-lg bg-emerald-50 p-2 text-sm text-emerald-800">No fee for this document. Just confirm the ID and release.</p>
          <div v-else class="grid grid-cols-2 gap-3">
            <div>
              <input v-model="release.official_receipt_no" placeholder="Official receipt no." class="w-full rounded-lg border px-3 py-2" />
              <p v-if="release.errors.official_receipt_no" class="text-xs text-red-600">{{ release.errors.official_receipt_no }}</p>
            </div>
            <div>
              <input v-model="release.amount_paid" type="number" step="0.01" class="w-full rounded-lg border px-3 py-2" />
              <p v-if="release.errors.amount_paid" class="text-xs text-red-600">{{ release.errors.amount_paid }}</p>
            </div>
          </div>
          <button :disabled="release.processing || !release.id_checked"
                  class="rounded-lg bg-orange-600 px-4 py-2 text-white disabled:opacity-50">
            {{ isFree ? 'Release document' : 'Collect payment and release' }}
          </button>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>