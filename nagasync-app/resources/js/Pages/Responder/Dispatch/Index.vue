<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({ open: Array, recent: Array, transitions: Object });

const labels = { dispatched: 'Dispatch', resolved: 'Resolve', false_alarm: 'False alarm' };
const activeCount = computed(() => props.open.filter((a) => a.status === 'active').length);

const mapUrl = (a) =>
    `https://www.openstreetmap.org/?mlat=${a.lat}&mlon=${a.lng}#map=17/${a.lat}/${a.lng}`;

function change(alert, status) {
    router.patch(route('dispatch.update', alert.id), { status }, { preserveScroll: true });
}

// Polling is the safety net; WebSockets (optional) make it instant
let poll;
const baseTitle = document.title;
onMounted(() => {
    poll = setInterval(() => {
        if (!document.hidden) router.reload({ only: ['open', 'recent'], preserveScroll: true, preserveState: true });
    }, 5000);
});
onUnmounted(() => { clearInterval(poll); document.title = baseTitle; });

import { defineProps, watch } from 'vue';
watch(activeCount, (n) => {
    document.title = n > 0 ? `(${n}) ACTIVE ALERTS` : baseTitle;
}, { immediate: true });
</script>

<template>
    <Head title="Dispatch" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Dispatch</h2>
                <span :class="activeCount ? 'bg-red-600 text-white' : 'bg-green-100 text-green-800'"
                      class="rounded-full px-3 py-1 text-xs font-semibold">
                    {{ activeCount ? activeCount + ' active' : 'No active alerts' }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 p-6">
            <div v-for="a in open" :key="a.id"
                 :class="a.status === 'active' ? 'border-red-500' : 'border-blue-400'"
                 class="rounded-lg border-l-4 bg-white p-5 shadow">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-bold capitalize">{{ a.type }} · {{ a.citizen }}</p>
                        <p class="text-sm text-gray-600">
                            {{ a.barangay ?? 'No barangay' }} · {{ a.at }} ·
                            <a v-if="a.phone" :href="'tel:' + a.phone" class="text-indigo-600 underline">{{ a.phone }}</a>
                            <span v-else>no phone</span>
                        </p>
                        <p v-if="a.note" class="mt-1 text-sm">Note: {{ a.note }}</p>
                        <p class="mt-1 text-sm">
                            <a v-if="a.lat !== null" :href="mapUrl(a)" target="_blank" rel="noopener" class="text-indigo-600 underline">
                                Open location map
                            </a>
                            <span v-if="a.lat !== null" class="text-gray-500"> (±{{ a.accuracy ?? '?' }} m)</span>
                            <span v-else class="font-medium text-amber-700">Location not available. Use barangay and phone.</span>
                        </p>
                        <p v-if="a.ack_by" class="text-xs text-gray-500">Handled by {{ a.ack_by }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button v-for="s in transitions[a.status]" :key="s" @click="change(a, s)"
                                class="rounded border px-3 py-1 text-sm hover:bg-gray-100">{{ labels[s] }}</button>
                    </div>
                </div>
            </div>
            <p v-if="!open.length" class="py-8 text-center text-gray-500">No open alerts.</p>

            <h3 class="pt-4 font-semibold text-gray-700">Recently closed</h3>
            <div v-for="a in recent" :key="a.id" class="flex justify-between rounded bg-white p-3 text-sm shadow">
                <span class="capitalize">{{ a.type }} · {{ a.citizen }} · {{ a.barangay }}</span>
                <span class="text-gray-500">{{ a.status.replace('_', ' ') }} · {{ a.at }}</span>
            </div>
        </div>
    </AuthenticatedLayout>
</template>