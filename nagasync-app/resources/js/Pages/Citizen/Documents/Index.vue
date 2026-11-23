<script setup>
import CameraCapture from '@/Components/CameraCapture.vue';
import PurposeStep from '@/Components/PurposeStep.vue';
import RequestCalendar from '@/Components/RequestCalendar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { dayKey, fromKey, marksFrom } from '@/utils/dateKey';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BadgeCheck, Building2, Check, ClipboardCheck, CreditCard, Download, FileText, Hourglass, Landmark, Link2, Paperclip, QrCode, Send, Upload, XCircle } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    // { barangay: [...types], city: [...types] }
    groups: { type: Object, default: () => ({ barangay: [], city: [] }) },
    documents: Array,
    // { 'Business Permit (New)': [{ id, name, description, is_required, kind, satisfied_by_type, allow_upload_fallback, max_age_days }], ... }
    requirements: { type: Object, default: () => ({}) },
    // { [requirementId]: [{ id, tracking_number, document_type, issued_at }] }  approved docs the resident can reuse
    linkable: { type: Object, default: () => ({}) },
    // { [requirementId]: { id, tracking_number, status } | null }  a matching request already in progress
    inProgress: { type: Object, default: () => ({}) },
    // Types where the business itself is the purpose, so "What is it for?" is skipped
    purposeless: { type: Array, default: () => [] },
    // { [type]: [{ key, label, fee, student }] }  purposes the citizen picks from (empty = free-text purpose)
    purposes: { type: Object, default: () => ({}) },
    // { [type]: [{ key, label, type, required, options }] }  details printed on the certificate
    personalFields: { type: Object, default: () => ({}) },
    // Online payment surcharge in pesos
    convenienceFee: { type: Number, default: 0 },
});

const MAX_BYTES = 5 * 1024 * 1024; // keep in sync with the 5 MB server rule

const tab = ref('barangay'); // 'barangay' | 'city'
const form = useForm({ document_type: '', purpose: '', purpose_key: null, personal_info: {}, files: {}, links: {} });
const justSubmitted = ref(false);
const submittedFor = ref(null); // city type to come back to after a prerequisite was requested
const resumeFor = ref(null); // city type the resident started from
const uploadKey = ref(0); // bump to clear the <input type="file"> elements
const fileErrors = ref({}); // client-side errors keyed by requirement id

const visibleTypes = computed(() => props.groups?.[tab.value] ?? []);

// ---- Request history calendar ----------------------------------------------
const selectedDate = ref(null); // 'YYYY-MM-DD' (local time) or null = show every request
// The day a request was submitted (falls back to last update if created_at is not sent)
const requestDate = (d) => d.created_at ?? d.updated_at ?? null;
// { 'YYYY-MM-DD': [status, ...] }, used by the calendar for its colored dots
const dateMarks = computed(() => marksFrom(props.documents ?? [], requestDate));
const shownDocuments = computed(() => (selectedDate.value
    ? (props.documents ?? []).filter((d) => dayKey(requestDate(d)) === selectedDate.value)
    : (props.documents ?? [])));
const selectedLabel = computed(() => (selectedDate.value
    ? fromKey(selectedDate.value).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
    : ''));

function setTab(t) {
    tab.value = t;
    form.document_type = '';
    resumeFor.value = null;
}

const submit = () => form.post(route('documents.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
        submittedFor.value = resumeFor.value;
        form.reset();
        resumeFor.value = null;
        fileErrors.value = {};
        uploadKey.value++;
        selectedDate.value = null; // make sure the new request is visible
        justSubmitted.value = true;
        setTimeout(() => (justSubmitted.value = false), 10000);
    },
});

// Requirements for whichever document type is selected
// Only the requirements that apply to the chosen purpose (purpose_keys null = every purpose)
const reqs = computed(() => (props.requirements?.[form.document_type] ?? [])
    .filter((r) => !r.purpose_keys?.length || (form.purpose_key && r.purpose_keys.includes(form.purpose_key))));
const purposeList = computed(() => props.purposes?.[form.document_type] ?? []);
const fieldList = computed(() => props.personalFields?.[form.document_type] ?? []);
const hasPurposeList = computed(() => purposeList.value.length > 0);
const missingInfo = computed(() => fieldList.value.filter((f) => f.required && !String(form.personal_info?.[f.key] ?? '').trim()));
const isIssued = (r) => r.kind === 'issued_document';
const isCamera = (r) => r.kind === 'camera';
const satisfied = (r) => !!(form.files[r.id] || form.links[r.id]);
const missingRequired = computed(() => reqs.value.filter((r) => r.is_required && !satisfied(r)));

const isCityType = (t) => (props.groups?.city ?? []).includes(t);
const isPurposeless = computed(() => props.purposeless.includes(form.document_type));
// City Hall documents cannot be approved without configured requirements,
// so do not let one be submitted until City Hall has set them up.
const cityNotConfigured = computed(() => isCityType(form.document_type) && !reqs.value.length);

const purposeOk = computed(() => hasPurposeList.value ? !!form.purpose_key : (isPurposeless.value || !!form.purpose.trim()));
const canSubmit = computed(() =>
    form.document_type
    && purposeOk.value
    && !missingInfo.value.length
    && !missingRequired.value.length
    && !cityNotConfigured.value
);

// Switching type invalidates anything already attached
watch(() => form.document_type, () => {
    form.purpose = '';
    form.purpose_key = null;
    form.personal_info = {};
    form.files = {};
    form.links = {};
    fileErrors.value = {};
    uploadKey.value++;
});

// Changing the purpose can remove requirements (e.g. no cedula for students): drop what no longer applies
watch(reqs, (list) => {
    const ids = new Set(list.map((r) => String(r.id)));
    form.files = Object.fromEntries(Object.entries(form.files).filter(([id]) => ids.has(String(id))));
    form.links = Object.fromEntries(Object.entries(form.links).filter(([id]) => ids.has(String(id))));
    uploadKey.value++;
});

function checkFile(file) {
    if (!file) return null;
    if (file.size > MAX_BYTES) return 'File is larger than 5 MB.';
    if (!/\.(jpe?g|png|pdf)$/i.test(file.name)) return 'Use a JPG, PNG or PDF file.';
    return null;
}

function pickFile(req, event) {
    const file = event.target.files[0];
    const next = { ...form.files };
    const err = checkFile(file);

    if (!file || err) {
        delete next[req.id];
        event.target.value = '';
    } else {
        next[req.id] = file;
        // a copy replaces any linked document for this requirement
        if (form.links[req.id]) {
            const links = { ...form.links };
            delete links[req.id];
            form.links = links;
        }
    }
    form.files = next;
    fileErrors.value = { ...fileErrors.value, [req.id]: err };
}

// Live camera photo for a requirement on the new-request form
function setCaptured(req, file) {
    const err = checkFile(file);
    fileErrors.value = { ...fileErrors.value, [req.id]: err };
    if (err) return;
    form.files = { ...form.files, [req.id]: file };
}
function clearCaptured(req) {
    const next = { ...form.files };
    delete next[req.id];
    form.files = next;
}

function toggleLink(req, docId) {
    const links = { ...form.links };
    if (links[req.id] === docId) {
        delete links[req.id];
    } else {
        links[req.id] = docId;
        const files = { ...form.files };
        delete files[req.id];
        form.files = files;
        uploadKey.value++;
    }
    form.links = links;
}

// "Request it now": jump to the form for the prerequisite, on the tab where that type lives
async function requestNow(fromType, prerequisiteType) {
    resumeFor.value = fromType;
    tab.value = isCityType(prerequisiteType) ? 'city' : 'barangay';
    form.document_type = prerequisiteType;
    await nextTick();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Plain-language hints. Order matters: most specific keywords first.
const hints = [
    { match: 'permit', text: 'Official permit from City Hall to operate your business.' },
    { match: 'business', text: 'Barangay endorsement for starting or renewing a small business.' },
    { match: 'tax', text: 'Your cedula: an ID-style certificate used for permits and legal documents.' },
    { match: 'indigency', text: 'Certifies that your household has a low income. Used for assistance and scholarships.' },
    { match: 'residency', text: 'Confirms that you live in the barangay. Used for school, bank and ID applications.' },
    { match: 'clearance', text: 'Proof that you have no pending issues in the barangay. Often needed for jobs or permits.' },
];
const hintFor = (t) => hints.find((h) => t.toLowerCase().includes(h.match))?.text ?? 'Official document.';

// What residents bring when CLAIMING an approved document (uploads above are what staff verify first).
const defaultBring = ['One valid government-issued ID', 'Your tracking number or the QR code shown after approval'];
const bring = [
    { match: 'permit', items: ['One valid ID', 'Your tracking number or QR code', 'Payment for the assessed fee (shown on your approved request)'] },
    { match: 'business', items: ['One valid ID', 'DTI or business name registration, if available', 'Payment for the fee, if any'] },
    { match: 'tax', items: ['One valid ID', 'Payment for the assessed fee (shown on your approved request)'] },
    { match: 'indigency', items: ['One valid ID', 'Proof of household income or a short interview with staff'] },
    { match: 'residency', items: ['One valid ID', 'Proof of address (utility bill or lease), if your ID has no address'] },
    { match: 'clearance', items: ['One valid ID with your address', 'Community tax certificate (cedula), if required', 'Payment for the fee, if any'] },
];
const bringFor = (t) => bring.find((b) => t.toLowerCase().includes(b.match))?.items ?? defaultBring;

// Prerequisites shown as badges on city document cards
const prerequisitesFor = (t) => (props.requirements?.[t] ?? []).filter((r) => isIssued(r) && r.is_required);

const purposeChips = ['Employment', 'School', 'Scholarship', 'Medical assistance', 'Bank / ID application', 'Business'];

const selected = computed(() => form.document_type);
const step = computed(() => (
    !form.document_type ? 1
        : !purposeOk.value || missingInfo.value.length ? 2
            : missingRequired.value.length ? 3 : 4
));

// Every request moves through these stages; we show where it is right now.
// City Hall documents have one more stage: claimed at the counter.
const stages = [
    { key: 'pending', label: 'Received' },
    { key: 'processing', label: 'Being reviewed' },
    { key: 'approved', label: 'Approved' },
    { key: 'released', label: 'Issued' },
];
const stagesFor = () => stages; // same state machine for Barangay and City Hall
const stageIndex = (s) => {
    if (s === 'needs_resubmission') return 1;
    if (s === 'released') return 3;
    return Math.max(0, stages.findIndex((x) => x.key === s));
};
const stageChecked = (d, i) => i < stageIndex(d.status) || (d.status === 'released' && i === 3);
const showTracker = (s) => ['pending', 'processing', 'needs_resubmission', 'approved', 'released'].includes(s);

const statusInfo = {
    pending: { tone: 'bg-amber-100 text-amber-800', text: 'We received your request and documents. Staff will review them soon.' },
    processing: { tone: 'bg-sky-100 text-sky-800', text: 'Staff are checking your documents now.' },
    needs_resubmission: { tone: 'bg-orange-100 text-orange-800', text: 'Staff need you to update one or more documents. See the list below.' },
    approved: { tone: 'bg-emerald-100 text-emerald-800', text: 'Approved! Pay the fee at the counter to get your document.' },
    released: { tone: 'bg-emerald-100 text-emerald-800', text: 'Issued. This document is now valid and can be verified by its QR code.' },
    rejected: { tone: 'bg-red-100 text-red-800', text: 'This request was not approved. See the remarks below, then submit a new one.' },
    revoked: { tone: 'bg-gray-200 text-gray-700', text: 'This document is no longer valid.' },
};
const statusText = (d) => {
    if (d.status === 'approved') {
        const where = d.level === 'city' ? 'City Hall' : 'your barangay hall';
        return `Approved! Go to ${where}, show your QR code, pay the fee shown below and claim your document. Your requirements are already checked.`;
    }
    return statusInfo[d.status]?.text;
};
const label = (s) => s.replace('_', ' ');

const peso = (n) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(n ?? 0));
const fmtDate = (v) => (v ? new Date(v).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '');

// ---- Per-request document checklist --------------------------------------
const attachmentStyle = {
    pending: 'bg-amber-100 text-amber-800',
    verified: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
};

// Pair each requirement of the request's type with what the citizen provided for it
function itemsFor(d) {
    const byReq = Object.fromEntries((d.attachments ?? []).map((a) => [a.document_requirement_id, a]));
    return (props.requirements?.[d.document_type] ?? []).map((r) => ({ req: r, att: byReq[r.id] ?? null }));
}

const paying = ref(null);
function payOnline(d) {
    // The server answers with a redirect to the payment checkout
    router.post(route('documents.pay', d.id), {}, { onStart: () => (paying.value = d.id), onFinish: () => (paying.value = null) });
}

const canUpload = (d) => ['pending', 'processing', 'needs_resubmission'].includes(d.status);
const needsInput = (d, att) => canUpload(d) && (!att || att.needs_action);
const uploadError = ref({}); // keyed by `${requestId}-${requirementId}`
const uploading = ref(null);

function uploadItem(d, req, event) {
    const file = event.target.files[0];
    if (file) uploadFile(d, req, file, event.target);
}

function uploadFile(d, req, file, input = null) {
    const key = `${d.id}-${req.id}`;
    const err = checkFile(file);
    if (err) {
        uploadError.value = { ...uploadError.value, [key]: err };
        if (input) input.value = '';
        return;
    }

    uploadError.value = { ...uploadError.value, [key]: null };
    uploading.value = key;
    router.post(route('documents.resubmit', [d.id, req.id]), { file }, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => (uploadError.value = { ...uploadError.value, [key]: errors.file ?? 'Upload failed. Please try again.' }),
        onFinish: () => {
            uploading.value = null;
            if (input) input.value = '';
        },
    });
}

function linkItem(d, req, docId) {
    const key = `${d.id}-${req.id}`;
    uploadError.value = { ...uploadError.value, [key]: null };
    uploading.value = key;
    router.post(route('documents.link', [d.id, req.id]), { document_id: docId }, {
        preserveScroll: true,
        onError: (errors) => (uploadError.value = { ...uploadError.value, [key]: errors.document_id ?? 'Could not link that document.' }),
        onFinish: () => (uploading.value = null),
    });
}
</script>

<template>
    <Head title="My Documents" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-lg font-semibold text-white md:text-xl md:text-gray-800">My Documents</h2></template>

        <!-- Mobile header: request history calendar (on desktop it sits above "My requests") -->
        <template #mobile-extra>
            <RequestCalendar v-model="selectedDate" :marks="dateMarks" />
        </template>

        <div class="mx-auto max-w-4xl space-y-8 p-4 sm:p-6">
            <!-- How it works -->
            <section class="rounded-3xl bg-[#F1E8F6] p-5 sm:p-6">
                <h3 class="text-lg font-extrabold text-[#3D2470]">How to request a document</h3>
                <ol class="mt-4 grid gap-4 sm:grid-cols-4">
                    <li v-for="(s, i) in [
                        { t: 'Choose', d: 'Barangay or City Hall document.' },
                        { t: 'Attach', d: 'Upload requirements or reuse your approved barangay documents.' },
                        { t: 'Staff check', d: 'Staff verify your uploads and confirm the fee before approving.' },
                        { t: 'Claim', d: 'Free documents are issued on approval. Otherwise show your QR code at the counter and pay.' },
                    ]" :key="s.t" class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#3D2470] text-sm font-bold text-white">{{ i + 1 }}</span>
                        <div>
                            <p class="font-bold text-[#3D2470]">{{ s.t }}</p>
                            <p class="text-sm text-[#1B1340]/70">{{ s.d }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- Success message -->
            <div v-if="justSubmitted" role="status" class="flex items-start gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-200">
                <BadgeCheck class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-bold">Request submitted!</p>
                    <p class="text-sm">Staff will check your documents. Follow the progress in "My requests" below and keep your tracking number.</p>
                    <p v-if="submittedFor" class="mt-1 text-sm font-semibold">
                        Once it is approved, go to City Hall documents and continue your {{ submittedFor }}.
                    </p>
                </div>
            </div>

            <!-- Request form -->
            <form @submit.prevent="submit" class="space-y-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-6">
                <!-- Level tabs -->
                <div class="grid grid-cols-2 gap-2 rounded-2xl bg-gray-100 p-1" role="tablist">
                    <button type="button" role="tab" :aria-selected="tab === 'barangay'" @click="setTab('barangay')"
                            class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-bold transition"
                            :class="tab === 'barangay' ? 'bg-white text-[#3D2470] shadow-sm' : 'text-gray-500 hover:text-[#3D2470]'">
                        <Landmark class="h-4 w-4" /> Barangay documents
                    </button>
                    <button type="button" role="tab" :aria-selected="tab === 'city'" @click="setTab('city')"
                            class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-bold transition"
                            :class="tab === 'city' ? 'bg-white text-[#3D2470] shadow-sm' : 'text-gray-500 hover:text-[#3D2470]'">
                        <Building2 class="h-4 w-4" /> City Hall documents
                    </button>
                </div>

                <!-- Banner when the resident came here to get a prerequisite -->
                <div v-if="resumeFor" class="flex items-start gap-3 rounded-2xl bg-sky-50 p-4 text-sm text-sky-900 ring-1 ring-sky-200">
                    <Link2 class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>
                        You are requesting this because your <strong>{{ resumeFor }}</strong> needs it.
                        After it is approved, go back to <strong>City Hall documents</strong> and link it, no re-upload needed.
                    </p>
                </div>

                <div>
                    <h3 class="text-lg font-extrabold text-[#3D2470]">
                        <span class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full text-sm text-white"
                              :class="step > 1 ? 'bg-emerald-500' : 'bg-[#F04A2B]'">
                            <Check v-if="step > 1" class="h-4 w-4" /><template v-else>1</template>
                        </span>
                        What document do you need?
                    </h3>
                    <p v-if="tab === 'city'" class="mt-1 text-sm text-gray-500">
                        City Hall documents often need barangay documents first. If you already have them approved here, you can reuse them.
                    </p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Document type">
                        <button v-for="t in visibleTypes" :key="t" type="button" role="radio" :aria-checked="selected === t"
                                @click="form.document_type = t"
                                class="flex items-start gap-3 rounded-2xl p-4 text-left ring-1 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B]"
                                :class="selected === t ? 'bg-[#F1E8F6] ring-2 ring-[#3D2470]' : 'bg-white ring-gray-200 hover:ring-[#3D2470]/40'">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F1E8F6] text-[#3D2470]">
                                <FileText class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-bold text-[#1B1340]">{{ t }}</span>
                                <span class="block text-sm text-gray-500">{{ hintFor(t) }}</span>
                                <span v-if="prerequisitesFor(t).length" class="mt-2 flex flex-wrap gap-1.5">
                                    <span v-for="p in prerequisitesFor(t)" :key="p.id"
                                          class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                        <Link2 class="h-3 w-3" /> Needs {{ p.satisfied_by_type }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </div>
                    <p v-if="form.errors.document_type" class="mt-2 text-sm text-red-600">{{ form.errors.document_type }}</p>
                </div>

                <!-- Purpose list (with fees) and the details printed on the certificate -->
                <div v-if="selected && (hasPurposeList || fieldList.length)">
                    <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">
                        <span class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full text-sm text-white"
                              :class="step > 2 ? 'bg-emerald-500' : step === 2 ? 'bg-[#F04A2B]' : 'bg-gray-300'">
                            <Check v-if="step > 2" class="h-4 w-4" /><template v-else>2</template>
                        </span>
                        {{ hasPurposeList ? 'Purpose and your details' : 'Your details' }}
                    </h3>
                    <PurposeStep :purposes="purposeList" :fields="fieldList"
                                 v-model:purpose-key="form.purpose_key" v-model:info="form.personal_info" :errors="form.errors" />
                </div>

                <!-- Free-text purpose: only for types with no purpose list. Skipped when the business itself is the purpose -->
                <div v-if="selected && !hasPurposeList && !isPurposeless">
                    <h4 class="text-sm font-bold text-[#3D2470]">What is it for?</h4>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button v-for="c in purposeChips" :key="c" type="button" @click="form.purpose = c"
                                class="rounded-full px-4 py-1.5 text-sm font-medium ring-1 transition"
                                :class="form.purpose === c ? 'bg-[#3D2470] text-white ring-[#3D2470]' : 'bg-white text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#F1E8F6]'">
                            {{ c }}
                        </button>
                    </div>
                    <input v-model="form.purpose" placeholder="Or type your own reason (e.g. job application at ABC Corp)"
                           class="mt-3 w-full rounded-xl border-gray-300 focus:border-[#3D2470] focus:ring-[#3D2470]" />
                    <p v-if="form.errors.purpose" class="mt-2 text-sm text-red-600">{{ form.errors.purpose }}</p>
                </div>

                <!-- Requirements -->
                <div v-if="selected">
                    <h3 class="text-lg font-extrabold text-[#3D2470]">
                        <span class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-full text-sm text-white"
                              :class="step > 3 ? 'bg-emerald-500' : step === 3 ? 'bg-[#F04A2B]' : 'bg-gray-300'">
                            <Check v-if="step > 3" class="h-4 w-4" /><template v-else>3</template>
                        </span>
                        Attach your requirements
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Staff will verify uploads before your request is approved. JPG, PNG or PDF, up to 5 MB each.</p>

                    <p v-if="!reqs.length" class="mt-3 rounded-2xl p-4 text-sm"
                       :class="cityNotConfigured ? 'bg-amber-50 text-amber-900' : 'bg-gray-50 text-gray-600'">
                        <template v-if="cityNotConfigured">
                            City Hall has not finished setting up the requirements for this document yet, so it cannot be requested online right now. Please check back soon.
                        </template>
                        <template v-else>No documents are required for this request. You can submit it right away.</template>
                    </p>

                    <ul v-else :key="uploadKey" class="mt-3 space-y-3">
                        <li v-for="r in reqs" :key="r.id" class="rounded-2xl p-4 ring-1"
                            :class="satisfied(r) ? 'bg-emerald-50 ring-emerald-200' : 'bg-white ring-gray-200'">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-[#1B1340]">
                                        {{ r.name }}
                                        <span v-if="r.is_required" class="text-[#F04A2B]" title="Required">*</span>
                                        <span v-else class="text-xs font-normal text-gray-400">(optional)</span>
                                    </p>
                                    <p v-if="r.description" class="text-sm text-gray-500">{{ r.description }}</p>
                                </div>
                                <span v-if="satisfied(r)" class="flex shrink-0 items-center gap-1 text-xs font-semibold text-emerald-700">
                                    <Check class="h-4 w-4" /> {{ form.links[r.id] ? 'Linked' : 'Attached' }}
                                </span>
                            </div>

                            <!-- Issued-document requirement: reuse an approved NagaSync document -->
                            <div v-if="isIssued(r)" class="mt-3 space-y-2">
                                <template v-if="linkable[r.id]?.length">
                                    <button v-for="doc in linkable[r.id]" :key="doc.id" type="button" @click="toggleLink(r, doc.id)"
                                            class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm ring-1 transition"
                                            :class="form.links[r.id] === doc.id ? 'bg-white ring-2 ring-emerald-500' : 'bg-white ring-gray-200 hover:ring-[#3D2470]/40'">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <BadgeCheck class="h-4 w-4 shrink-0 text-emerald-600" />
                                            <span class="min-w-0">
                                                <span class="block font-semibold text-[#1B1340]">Use my approved {{ doc.document_type }}</span>
                                                <span class="block truncate font-mono text-xs text-gray-500">{{ doc.tracking_number }} · issued {{ doc.issued_at }}</span>
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-xs font-semibold" :class="form.links[r.id] === doc.id ? 'text-emerald-700' : 'text-[#3D2470]'">
                                            {{ form.links[r.id] === doc.id ? 'Selected' : 'Select' }}
                                        </span>
                                    </button>
                                </template>

                                <p v-else-if="inProgress[r.id]" class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900 ring-1 ring-sky-200">
                                    Your {{ r.satisfied_by_type }} request <span class="font-mono">{{ inProgress[r.id].tracking_number }}</span>
                                    is still <strong>{{ label(inProgress[r.id].status) }}</strong>. Come back once it is approved.
                                </p>

                                <button v-else type="button" @click="requestNow(selected, r.satisfied_by_type)"
                                        class="inline-flex items-center gap-2 rounded-xl bg-[#F04A2B] px-3 py-2 text-sm font-bold text-white hover:bg-[#D93C1E]">
                                    <Send class="h-4 w-4" /> Request your {{ r.satisfied_by_type }} first
                                </button>
                            </div>

                            <!-- Upload (the only option for plain requirements, a fallback for issued ones) -->
                            <CameraCapture v-if="isCamera(r)" class="mt-3" :file="form.files[r.id] ?? null"
                                           :allow-fallback="r.allow_upload_fallback"
                                           @captured="setCaptured(r, $event)" @cleared="clearCaptured(r)" />

                            <label v-else-if="!isIssued(r) || r.allow_upload_fallback"
                                   class="mt-3 flex cursor-pointer flex-wrap items-center gap-3 text-sm">
                                <span class="inline-flex items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 font-semibold text-[#3D2470] hover:bg-[#E8DAF1]">
                                    <Paperclip class="h-4 w-4" />
                                    {{ form.files[r.id] ? 'Replace file' : isIssued(r) ? 'Or upload a copy instead' : 'Choose file' }}
                                </span>
                                <span v-if="form.files[r.id]" class="min-w-0 truncate text-gray-600">{{ form.files[r.id].name }}</span>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" @change="pickFile(r, $event)" />
                            </label>

                            <p v-if="fileErrors[r.id] || form.errors['files.' + r.id]" class="mt-2 text-sm text-red-600">
                                {{ fileErrors[r.id] || form.errors['files.' + r.id] }}
                            </p>
                        </li>
                    </ul>

                    <!-- What to bring when claiming (separate from the requirements above) -->
                    <div class="mt-4 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200">
                        <p class="flex items-center gap-2 font-bold text-amber-900">
                            <ClipboardCheck class="h-5 w-5" /> What to bring when you claim your {{ selected }}
                        </p>
                        <ul class="mt-2 space-y-1 text-sm text-amber-900">
                            <li v-for="item in bringFor(selected)" :key="item" class="flex items-start gap-2">
                                <Check class="mt-0.5 h-4 w-4 shrink-0" /> {{ item }}
                            </li>
                        </ul>
                        <p class="mt-2 text-xs text-amber-800/80">
                            <template v-if="isCityType(selected)">City Hall assesses the fee where it depends on your declared amount. The amount appears on your approved request, and you pay it at the counter when you claim.</template>
                            <template v-else>Free documents are issued as soon as they are approved. Otherwise the fee is shown on your request and paid at the counter.</template>
                        </p>
                        <p class="mt-2 text-xs text-amber-800/80">You only need these once your request is approved.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t pt-5">
                    <button :disabled="form.processing || !canSubmit"
                            class="inline-flex items-center gap-2 rounded-2xl bg-[#F04A2B] px-6 py-3 font-bold text-white shadow transition hover:bg-[#D93C1E] disabled:cursor-not-allowed disabled:opacity-40">
                        <Send class="h-4 w-4" />
                        {{ form.processing ? 'Submitting…' : 'Submit request' }}
                    </button>
                    <p v-if="!form.document_type" class="text-sm text-gray-500">Choose a document first.</p>
                    <p v-else-if="cityNotConfigured" class="text-sm text-amber-700">Requirements for this document are not set up yet.</p>
                    <p v-else-if="!purposeOk" class="text-sm text-gray-500">{{ hasPurposeList ? 'Choose what it is for.' : 'Add a purpose to continue.' }}</p>
                    <p v-else-if="missingInfo.length" class="text-sm text-gray-500">Fill in: {{ missingInfo.map((f) => f.label).join(', ') }}.</p>
                    <p v-else-if="missingRequired.length" class="text-sm text-gray-500">
                        Still needed: {{ missingRequired.map((r) => r.name).join(', ') }}.
                    </p>
                    <p v-else class="text-sm text-gray-500">
                        Ready to submit: <strong>{{ form.document_type }}</strong><template v-if="!isPurposeless && (form.purpose || form.purpose_key)"> for <strong>{{ hasPurposeList ? purposeList.find((p) => p.key === form.purpose_key)?.label : form.purpose }}</strong></template>.
                    </p>
                </div>
            </form>

            <!-- My requests -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">My requests</h3>

                <!-- Desktop: the calendar lives here (on mobile it is in the header) -->
                <RequestCalendar v-if="documents.length" v-model="selectedDate" :marks="dateMarks" variant="light" class="mb-3 hidden md:block" />

                <div v-if="selectedDate" class="mb-3 flex items-center justify-between gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm text-[#3D2470]">
                    <span>Showing requests from <strong>{{ selectedLabel }}</strong> ({{ shownDocuments.length }})</span>
                    <button type="button" @click="selectedDate = null" class="shrink-0 font-bold underline">Show all</button>
                </div>

                <div v-if="!documents.length" class="rounded-3xl border-2 border-dashed border-[#3D2470]/20 p-10 text-center">
                    <Hourglass class="mx-auto h-8 w-8 text-[#3D2470]/50" />
                    <p class="mt-3 font-semibold text-[#3D2470]">No requests yet</p>
                    <p class="text-sm text-gray-500">Once you submit a request above, you can follow it here.</p>
                </div>

                <div v-else class="space-y-4">
                    <p v-if="!shownDocuments.length" class="rounded-2xl bg-gray-50 p-4 text-center text-sm text-gray-500">No requests on this date.</p>
                    <article v-for="d in shownDocuments" :key="d.id" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#3D2470]/60">
                                    {{ d.level === 'city' ? 'City Hall' : 'Barangay' }}
                                </p>
                                <p class="text-lg font-bold text-[#1B1340]">{{ d.document_type }}</p>
                                <p v-if="d.purpose && !purposeless.includes(d.document_type)" class="text-sm text-gray-500">For: {{ d.purpose }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Tracking no. <span class="select-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-700">{{ d.tracking_number }}</span><span v-if="requestDate(d)" class="ml-1">· {{ fmtDate(requestDate(d)) }}</span>
                                </p>
                            </div>
                            <span :class="statusInfo[d.status]?.tone" class="shrink-0 rounded-full px-3 py-1 text-xs font-bold capitalize">{{ label(d.status) }}</span>
                        </div>

                        <!-- Progress tracker -->
                        <ol v-if="showTracker(d.status)" class="mt-5 flex items-center">
                            <li v-for="(s, i) in stagesFor(d)" :key="s.key" class="flex flex-1 items-center last:flex-none">
                                <span class="flex flex-col items-center gap-1">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                          :class="i <= stageIndex(d.status) ? 'bg-[#3D2470] text-white' : 'bg-gray-200 text-gray-500'">
                                        <Check v-if="stageChecked(d, i)" class="h-4 w-4" /><template v-else>{{ i + 1 }}</template>
                                    </span>
                                    <span class="text-[11px] font-medium text-gray-600">{{ s.label }}</span>
                                </span>
                                <span v-if="i < stagesFor(d).length - 1" class="mx-2 mb-5 h-0.5 flex-1"
                                      :class="i < stageIndex(d.status) ? 'bg-[#3D2470]' : 'bg-gray-200'"></span>
                            </li>
                        </ol>

                        <p class="mt-4 flex items-start gap-2 text-sm text-[#1B1340]/80">
                            <XCircle v-if="d.status === 'rejected'" class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                            {{ statusText(d) }}
                        </p>
                        <p v-if="d.remarks" class="mt-2 rounded-xl bg-gray-50 p-3 text-sm text-gray-700"><strong>Staff remarks:</strong> {{ d.remarks }}</p>

                        <!-- Requirement checklist with verification status -->
                        <ul v-if="itemsFor(d).length" class="mt-4 divide-y divide-gray-100 rounded-2xl ring-1 ring-gray-200">
                            <li v-for="{ req, att } in itemsFor(d)" :key="req.id" class="p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-[#1B1340]">{{ req.name }}</p>
                                        <p v-if="att?.linked" class="flex items-center gap-1 truncate text-xs text-gray-500">
                                            <Link2 class="h-3 w-3 shrink-0" /> Linked {{ att.linked.tracking_number }}
                                            <span v-if="att.linked.valid" class="font-semibold text-emerald-700">· system-verified</span>
                                        </p>
                                        <p v-else-if="att" class="truncate text-xs text-gray-500">{{ att.original_name }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold capitalize"
                                          :class="att ? (att.linked && !att.linked.valid ? 'bg-red-100 text-red-800' : attachmentStyle[att.review_status]) : 'bg-gray-100 text-gray-600'">
                                        {{ att ? (att.linked && !att.linked.valid ? 'no longer valid' : att.review_status) : 'not provided' }}
                                    </span>
                                </div>

                                <p v-if="att?.review_status === 'rejected' && att.reviewer_remarks" class="mt-2 rounded-xl bg-red-50 p-2.5 text-sm text-red-700">
                                    <strong>Why:</strong> {{ att.reviewer_remarks }}
                                </p>
                                <p v-if="att?.linked && !att.linked.valid" class="mt-2 rounded-xl bg-red-50 p-2.5 text-sm text-red-700">
                                    The linked {{ req.satisfied_by_type }} was revoked or is too old. Link a current one or upload a copy.
                                </p>

                                <!-- Provide or replace this item -->
                                <div v-if="needsInput(d, att)" class="mt-2 space-y-2">
                                    <template v-if="isIssued(req)">
                                        <button v-for="doc in linkable[req.id] ?? []" :key="doc.id" type="button"
                                                :disabled="uploading === `${d.id}-${req.id}`" @click="linkItem(d, req, doc.id)"
                                                class="flex w-full items-center gap-2 rounded-xl bg-white px-3 py-2 text-left text-sm ring-1 ring-gray-200 hover:ring-[#3D2470]/40">
                                            <BadgeCheck class="h-4 w-4 shrink-0 text-emerald-600" />
                                            <span class="min-w-0 truncate">Link my approved {{ doc.document_type }}
                                                <span class="font-mono text-xs text-gray-500">{{ doc.tracking_number }}</span></span>
                                        </button>
                                        <button v-if="!(linkable[req.id] ?? []).length && !inProgress[req.id]" type="button"
                                                @click="requestNow(d.document_type, req.satisfied_by_type)"
                                                class="inline-flex items-center gap-2 rounded-xl bg-[#F04A2B] px-3 py-2 text-sm font-bold text-white hover:bg-[#D93C1E]">
                                            <Send class="h-4 w-4" /> Request your {{ req.satisfied_by_type }} first
                                        </button>
                                        <p v-else-if="!(linkable[req.id] ?? []).length" class="text-sm text-sky-800">
                                            Your {{ req.satisfied_by_type }} is still being processed. Come back once it is approved.
                                        </p>
                                    </template>

                                    <CameraCapture v-if="isCamera(req)" :allow-fallback="req.allow_upload_fallback"
                                                   :busy="uploading === `${d.id}-${req.id}`" @captured="uploadFile(d, req, $event)" />

                                    <label v-else-if="!isIssued(req) || req.allow_upload_fallback"
                                           class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm font-semibold text-[#3D2470] hover:bg-[#E8DAF1]">
                                        <Upload class="h-4 w-4" />
                                        {{ uploading === `${d.id}-${req.id}` ? 'Uploading…' : att ? 'Upload a new file' : 'Upload file' }}
                                        <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only"
                                               :disabled="uploading === `${d.id}-${req.id}`" @change="uploadItem(d, req, $event)" />
                                    </label>
                                </div>
                                <p v-if="uploadError[`${d.id}-${req.id}`]" class="mt-1 text-sm text-red-600">{{ uploadError[`${d.id}-${req.id}`] }}</p>
                            </li>
                        </ul>

                        <!-- City Hall fee, assessed when staff approved the request -->
                        <div v-if="d.status === 'approved' && Number(d.fee_total) > 0"
                             class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-emerald-200">
                            <p class="font-bold text-[#1B1340]">Amount due at the counter: {{ peso(d.fee_total) }}</p>
                            <ul v-if="d.fee_breakdown?.lines?.length" class="mt-2 space-y-1 text-sm text-gray-600">
                                <li v-for="l in d.fee_breakdown.lines" :key="l.label" class="flex justify-between gap-3">
                                    <span>{{ l.label }}</span><span>{{ peso(l.amount) }}</span>
                                </li>
                            </ul>
                            <p class="mt-2 text-xs text-gray-500">Pay at the counter, or online below. The clerk will check these details against your QR code and tracking number.</p>
                            <button type="button" :disabled="paying === d.id" @click="payOnline(d)"
                                    class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[#3D2470] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E1B58] disabled:opacity-50">
                                <CreditCard class="h-4 w-4" /> {{ paying === d.id ? 'Opening checkout…' : 'Pay online' }}
                            </button>
                            <p v-if="convenienceFee > 0" class="mt-1 text-xs text-gray-500">Online payments add a {{ peso(convenienceFee) }} convenience fee. Your document is released as soon as the payment goes through.</p>
                        </div>

                        <div v-if="['approved', 'released'].includes(d.status) && d.verify_url" class="mt-4 flex flex-wrap items-center gap-4 rounded-2xl bg-emerald-50 p-4">
                            <img :src="route('documents.qr', d.id)" alt="Verification QR code" loading="lazy" class="h-28 w-28 rounded-lg bg-white p-1" />
                            <div class="text-sm text-emerald-900">
                                <p class="flex items-center gap-1.5 font-bold"><QrCode class="h-4 w-4" /> Your verification QR</p>
                                <p class="mt-1">{{ d.status === 'released' ? 'Valid. Let others scan this to confirm your document is genuine.' : 'Show this at the counter. It becomes valid once the document is released.' }}</p>
                                <p v-if="d.status === 'approved'" class="mt-2 font-semibold">Bring: {{ bringFor(d.document_type).join(' · ') }}</p>
                                <a v-if="d.verify_url" :href="d.verify_url" target="_blank" rel="noopener"
                                   class="mt-2 inline-block font-semibold text-[#3D2470] underline">Open verification page</a>
                            </div>
                        </div>

                        <!-- Claimed at the counter -->
                        <div v-if="d.status === 'released'" class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900">
                            <p class="flex items-center gap-1.5 font-bold"><BadgeCheck class="h-4 w-4" /> Issued</p>
                            <p class="mt-1">
                                Released on {{ fmtDate(d.released_at) }}<span v-if="d.official_receipt_no"> · OR no. {{ d.official_receipt_no }}</span>.
                                You can use it as a requirement for other requests.
                            </p>
                            <a v-if="d.level === 'barangay'" :href="route('documents.pdf', d.id)" target="_blank" rel="noopener"
                               class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                                <Download class="h-4 w-4" /> Download PDF certificate
                            </a>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>