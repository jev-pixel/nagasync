<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({ news: Array, requirements: Object });

const page = usePage();
const verified = computed(() => page.props.auth.resident_verified);
const user = computed(() => page.props.auth.user);

const tabs = [
    { key: 'news', label: 'News' },
    { key: 'guidelines', label: 'Guidelines' },
    { key: 'howto', label: 'How to request' },
];
const tab = ref('news');
const openType = ref(null);

const steps = [
    'Get verified: barangay staff confirm that you live in your registered barangay.',
    'Choose the document you need and prepare its requirements.',
    'Submit your request and upload the files. Anything you already hold in NagaSync can be linked.',
    'Staff review each file. If something is unclear, you are asked to re-submit it.',
    'Once approved you get a verification QR code. For City Hall documents, pay the assessed fee at the counter.',
    'Claim your document with a valid ID and your tracking number or QR code.',
];
const guidelines = [
    'Use your real name and your actual barangay. Staff check this when verifying you.',
    'Upload clear, readable photos or PDFs. Blurry files will be sent back.',
    'Keep your tracking number. It is how you follow your request and claim your document.',
    'Fees are shown before you pay. Pay only at the official counter or through NagaSync.',
    'In an emergency, use the Emergency button. It works even before you are verified.',
];
</script>

<template>
    <Head title="Guide" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">News &amp; Guide</h2></template>

        <div class="mx-auto max-w-3xl space-y-5">
            <div v-if="!verified" role="status" class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <p class="font-bold">Your account is not verified yet.</p>
                <p class="mt-1">
                    Document requests, payments and voting open once barangay staff confirm you are a resident of
                    your barangay. Until then you can read the news and guides below.
                    <span v-if="user?.email"> Registered as {{ user.email }}.</span>
                </p>
            </div>
            <div v-else class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200">
                <p class="font-bold">You are verified.</p>
                <p class="mt-1">You can now <Link :href="route('documents.index')" class="font-bold underline">request documents</Link>.</p>
            </div>

            <div class="flex flex-wrap gap-2" role="tablist">
                <button v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="tab === t.key" @click="tab = t.key"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold ring-1 transition"
                        :class="tab === t.key ? 'bg-[#3D2470] text-white ring-[#3D2470]' : 'bg-white text-[#3D2470] ring-[#3D2470]/30 hover:bg-[#F1E8F6]'">
                    {{ t.label }}
                </button>
            </div>

            <section v-if="tab === 'news'" class="space-y-3">
                <article v-for="n in news" :key="n.title" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <p class="text-xs text-gray-500">{{ n.date }}</p>
                    <h3 class="mt-1 text-lg font-bold text-[#1B1340]">{{ n.title }}</h3>
                    <p class="mt-1 text-sm text-gray-700">{{ n.body }}</p>
                </article>
            </section>

            <section v-else-if="tab === 'guidelines'" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <h3 class="text-lg font-bold text-[#1B1340]">Using NagaSync</h3>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-gray-700">
                    <li v-for="g in guidelines" :key="g">{{ g }}</li>
                </ul>
            </section>

            <section v-else class="space-y-4">
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h3 class="text-lg font-bold text-[#1B1340]">Steps</h3>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-700">
                        <li v-for="s in steps" :key="s">{{ s }}</li>
                    </ol>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h3 class="text-lg font-bold text-[#1B1340]">What each document needs</h3>
                    <p v-if="!Object.keys(requirements).length" class="mt-2 text-sm text-gray-500">Requirement lists will appear here soon.</p>
                    <div v-for="(items, type) in requirements" :key="type" class="mt-3 rounded-2xl border border-gray-200">
                        <button type="button" class="flex w-full items-center justify-between p-3 text-left text-sm font-bold text-[#3D2470]"
                                :aria-expanded="openType === type" @click="openType = openType === type ? null : type">
                            {{ type }} <span aria-hidden="true">{{ openType === type ? '−' : '+' }}</span>
                        </button>
                        <ul v-if="openType === type" class="space-y-2 border-t p-3 text-sm text-gray-700">
                            <li v-for="r in items" :key="r.name">
                                <span class="font-semibold">{{ r.name }}</span>
                                <span v-if="!r.is_required" class="text-xs text-gray-400"> (optional)</span>
                                <span v-if="r.kind === 'issued_document'" class="text-xs text-gray-500"> · link your approved NagaSync document</span>
                                <p v-if="r.description" class="text-xs text-gray-500">{{ r.description }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>