<script setup>
/**
 * Staff/admin review screen: Pages/Staff/Documents/Review.vue
 * Route: staff.documents.review. Staff open each uploaded file here, then verify or reject it.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import FeeAssessmentCard from './FeeAssessmentCard.vue';

const props = defineProps({
    request: Object,
    items: Array,
    can_approve: Boolean,
    needs_fee: { type: Boolean, default: false },
    missing: { type: Array, default: () => [] },
});

const page = usePage();

const rejecting = ref(null); // attachment id being rejected
const approving = ref(false);
const form = useForm({ remarks: '' });

function verify(attachment) {
    router.post(route('staff.attachments.verify', attachment.id), {}, { preserveScroll: true });
}

function startReject(attachment) {
    rejecting.value = attachment.id;
    form.reset();
    form.clearErrors();
}

function confirmReject(attachment) {
    form.post(route('staff.attachments.reject', attachment.id), {
        preserveScroll: true,
        onSuccess: () => (rejecting.value = null),
    });
}

function approve() {
    if (!confirm(`Approve ${props.request.document_type} (${props.request.tracking_number})?`)) return;
    router.patch(route('staff.documents.update', props.request.id), { status: 'approved' }, {
        preserveScroll: true,
        onStart: () => (approving.value = true),
        onFinish: () => (approving.value = false),
    });
}

const badge = {
    pending: 'bg-amber-100 text-amber-800',
    verified: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};

const peso = (n) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(n ?? 0));
const officeName = { bplo: 'BPLO', treasurer: 'Treasurer', mayor: "Mayor's Office", cswdo: 'CSWDO', lingkod: 'Lingkod Barangay' };
const infoRows = (r) => Object.entries(r.personal_info ?? {}).map(([k, v]) => [k.replace(/_/g, ' '), v]);
const fileUrl = (a) => route('attachments.show', a.id);
const isImage = (a) => a.mime_type?.startsWith('image/');
const isPdf = (a) => a.mime_type === 'application/pdf';
const open = ['pending', 'processing', 'needs_resubmission'];
// A linked document that is still valid is verified by the system; staff do not need to act on it
const systemVerified = (a) => a?.linked?.valid;
const statusText = (item) => {
    const a = item.attachment;
    if (!a) return 'not uploaded';
    if (a.linked && !a.linked.valid) return 'no longer valid';
    return a.review_status;
};
</script>

<template>
    <Head title="Review requirements" />

    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Review requirements</h2></template>

        <div class="mx-auto max-w-3xl space-y-4 p-4">
            <Link :href="route('staff.documents.index')" class="inline-block text-sm text-blue-700 underline">
                Back to requests
            </Link>

            <header>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">
                    {{ request.level === 'city' ? 'City Hall request' : 'Barangay request' }}<template v-if="request.office"> · {{ officeName[request.office] ?? request.office }}</template>
                </p>
                <h1 class="text-lg font-semibold text-gray-900">{{ request.document_type }}</h1>
                <p class="text-sm text-gray-500">
                    {{ request.tracking_number }} · {{ request.citizen }} · {{ request.barangay }}
                </p>
                <p v-if="request.purpose" class="text-sm text-gray-600">Purpose: {{ request.purpose }}</p>
                <dl v-if="infoRows(request).length" class="mt-3 grid gap-x-6 gap-y-1 rounded-lg bg-gray-50 p-3 text-sm sm:grid-cols-2">
                    <div v-for="[k, v] in infoRows(request)" :key="k">
                        <dt class="text-xs capitalize text-gray-500">{{ k }}</dt><dd class="font-medium text-gray-900">{{ v }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs uppercase tracking-wide text-gray-400">Status: {{ request.status.replace('_', ' ') }}</p>
            </header>

            <div
                class="rounded-lg border p-3 text-sm"
                :class="can_approve ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
            >
                <template v-if="can_approve">
                    All required items are verified<template v-if="request.fee_total != null"> and the fee is set ({{ peso(request.fee_total) }})</template>.
                    This request can now be approved.
                </template>
                <template v-else-if="missing.length">Still to verify: {{ missing.join(', ') }}</template>
                <template v-else-if="needs_fee">Requirements are verified. Enter the declared amount in the fee box below before approving.</template>
                <template v-else>This request is {{ request.status.replace('_', ' ') }} and cannot be approved right now.</template>
            </div>

            <p v-if="page.props.errors?.status" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                {{ page.props.errors.status }}
            </p>

            <FeeAssessmentCard :doc="request" />

            <ul class="space-y-3">
                <li v-for="item in items" :key="item.requirement_id" class="rounded-lg border border-gray-200 bg-white p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ item.name }}
                                <span v-if="!item.is_required" class="text-xs font-normal text-gray-400">(optional)</span>
                            </p>
                            <p v-if="item.description" class="text-xs text-gray-500">{{ item.description }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                            :class="item.attachment ? (item.attachment.linked && !item.attachment.linked.valid ? 'bg-red-100 text-red-800' : badge[item.attachment.review_status]) : 'bg-gray-100 text-gray-600'"
                        >
                            {{ statusText(item) }}
                        </span>
                    </div>

                    <template v-if="item.attachment">
                        <!-- Linked NagaSync document -->
                        <div v-if="item.attachment.linked" class="mt-3 rounded-md bg-gray-50 p-3 text-sm">
                            <p class="font-medium text-gray-800">
                                {{ item.attachment.linked.document_type }}
                                <span class="font-mono text-xs text-gray-500">{{ item.attachment.linked.tracking_number }}</span>
                            </p>
                            <p v-if="item.attachment.linked.valid" class="text-xs text-green-700">
                                System-verified: issued through NagaSync, approved and within its validity period.
                            </p>
                            <p v-else class="text-xs text-red-700">
                                This linked document is {{ item.attachment.linked.status }} or too old. The citizen must link a current one or upload a copy.
                            </p>
                            <a
                                v-if="item.attachment.linked.verify_url"
                                :href="item.attachment.linked.verify_url"
                                target="_blank"
                                rel="noopener"
                                class="mt-1 inline-block text-xs text-blue-700 underline"
                            >
                                Open QR verification page
                            </a>
                        </div>

                        <!-- Uploaded file: preview here, or open in a new tab -->
                        <div v-else class="mt-3">
                            <img
                                v-if="isImage(item.attachment)"
                                :src="fileUrl(item.attachment)"
                                :alt="item.name"
                                class="max-h-96 rounded border border-gray-200 object-contain"
                            />
                            <iframe
                                v-else-if="isPdf(item.attachment)"
                                :src="fileUrl(item.attachment)"
                                :title="item.name"
                                class="h-96 w-full rounded border border-gray-200"
                            ></iframe>
                            <a
                                :href="fileUrl(item.attachment)"
                                target="_blank"
                                rel="noopener"
                                class="mt-1 inline-block text-sm text-blue-700 underline"
                            >
                                Open {{ item.attachment.original_name }} in a new tab
                            </a>
                        </div>

                        <p v-if="item.attachment.remarks" class="mt-2 text-xs text-red-700">
                            Rejected: {{ item.attachment.remarks }}
                        </p>

                        <div
                            v-if="open.includes(request.status) && !systemVerified(item.attachment)"
                            class="mt-3 flex flex-wrap items-center gap-2"
                        >
                            <button
                                v-if="item.attachment.review_status !== 'verified' && !item.attachment.linked"
                                type="button"
                                class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700"
                                @click="verify(item.attachment)"
                            >
                                Verify
                            </button>
                            <button
                                v-if="item.attachment.review_status !== 'rejected'"
                                type="button"
                                class="rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-700 hover:bg-red-50"
                                @click="startReject(item.attachment)"
                            >
                                Reject
                            </button>
                        </div>

                        <form
                            v-if="rejecting === item.attachment.id"
                            class="mt-3 space-y-2"
                            @submit.prevent="confirmReject(item.attachment)"
                        >
                            <label class="block text-xs font-medium text-gray-700">Reason (shown to the citizen)</label>
                            <textarea
                                v-model="form.remarks"
                                rows="2"
                                class="w-full rounded-md border-gray-300 text-sm"
                                placeholder="e.g. Photo is blurry, please retake."
                            />
                            <p v-if="form.errors.remarks" class="text-xs text-red-600">{{ form.errors.remarks }}</p>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="form.processing" class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white">
                                    Confirm rejection
                                </button>
                                <button type="button" class="px-3 py-1.5 text-sm text-gray-600" @click="rejecting = null">Cancel</button>
                            </div>
                        </form>
                    </template>
                </li>
            </ul>

            <div v-if="can_approve" class="flex justify-end border-t pt-4">
                <button
                    type="button"
                    :disabled="approving"
                    class="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                    @click="approve"
                >
                    {{ approving ? 'Approving…' : 'Approve request' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>