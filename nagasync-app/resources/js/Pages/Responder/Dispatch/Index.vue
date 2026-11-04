<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, MapPin, Phone, ShieldAlert, Siren, Truck, WifiOff } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({ open: Array, recent: Array, transitions: Object });

const labels = { dispatched: 'Dispatch', resolved: 'Resolve', false_alarm: 'False alarm' };
const hints = {
    dispatched: 'Responders are on the way',
    resolved: 'The emergency has been handled',
    false_alarm: 'No emergency was found',
};

// Active alerts always come first
const alerts = computed(() => [...props.open].sort((a, b) => (a.status === 'active' ? 0 : 1) - (b.status === 'active' ? 0 : 1)));
const activeCount = computed(() => props.open.filter((a) => a.status === 'active').length);

const mapUrl = (a) => `https://www.openstreetmap.org/?mlat=${a.lat}&mlon=${a.lng}#map=17/${a.lat}/${a.lng}`;

// Dispatching is one tap (time-critical). Closing an alert asks first.
const needsConfirm = ['resolved', 'false_alarm'];
const dialog = ref(null); // { alert, status }
const busy = ref(null);

function send(alert, status) {
    router.patch(route('dispatch.update', alert.id), { status }, {
        preserveScroll: true,
        onStart: () => (busy.value = alert.id),
        onFinish: () => (busy.value = null),
        onSuccess: () => (dialog.value = null),
    });
}
function change(alert, status) {
    if (needsConfirm.includes(status)) dialog.value = { alert, status };
    else send(alert, status);
}

// Polling is the safety net; WebSockets (optional) make it instant
let poll;
const baseTitle = document.title;
const lastOk = ref(Date.now());
const now = ref(Date.now());
const stale = computed(() => now.value - lastOk.value > 20000); // no successful refresh for 20s

onMounted(() => {
    poll = setInterval(() => {
        now.value = Date.now();
        if (document.hidden) return;
        router.reload({
            only: ['open', 'recent'], preserveScroll: true, preserveState: true,
            onSuccess: () => (lastOk.value = Date.now()),
        });
    }, 5000);
});
onUnmounted(() => { clearInterval(poll); document.title = baseTitle; });

watch(activeCount, (n) => {
    document.title = n > 0 ? `(${n}) ACTIVE ALERTS` : baseTitle;
}, { immediate: true });

const closedTone = { resolved: 'bg-emerald-100 text-emerald-800', false_alarm: 'bg-gray-200 text-gray-700' };
</script>

<template>
    <Head title="Dispatch" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-800">Dispatch</h2>
                <span :class="activeCount ? 'bg-red-600 text-white' : 'bg-emerald-100 text-emerald-800'"
                      class="rounded-full px-3 py-1 text-xs font-bold">
                    {{ activeCount ? activeCount + ' active' : 'No active alerts' }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
            <!-- Connection warning -->
            <div v-if="stale" role="alert" class="flex items-center gap-3 rounded-2xl bg-amber-50 p-4 text-amber-900 ring-1 ring-amber-300">
                <WifiOff class="h-5 w-5 shrink-0" />
                <p class="text-sm font-semibold">This page has not updated for a while. Check your internet connection. New alerts may not appear until it reconnects.</p>
            </div>

            <!-- How it works -->
            <p class="flex items-start gap-2 rounded-2xl bg-[#F1E8F6] p-4 text-sm text-[#3D2470]">
                <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" />
                <span>New alerts are <strong>Active</strong>. Tap <strong>Dispatch</strong> when responders are on the way, then <strong>Resolve</strong> when it is handled, or <strong>False alarm</strong> if there was no emergency. This page refreshes by itself every few seconds.</span>
            </p>

            <!-- Open alerts -->
            <section class="space-y-4" aria-live="polite">
                <article v-for="a in alerts" :key="a.id"
                         class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1"
                         :class="a.status === 'active' ? 'ring-2 ring-red-500' : 'ring-sky-300'">
                    <div class="flex items-center justify-between px-5 py-2 text-xs font-extrabold uppercase tracking-wide"
                         :class="a.status === 'active' ? 'bg-red-600 text-white' : 'bg-sky-100 text-sky-800'">
                        <span class="flex items-center gap-2">
                            <component :is="a.status === 'active' ? Siren : Truck" class="h-4 w-4" />
                            {{ a.status === 'active' ? 'Active: needs a response' : 'Dispatched: responders on the way' }}
                        </span>
                        <span class="font-semibold normal-case tracking-normal">{{ a.at }}</span>
                    </div>

                    <div class="p-5">
                        <p class="text-xl font-extrabold capitalize text-[#1B1340]">{{ a.type }}</p>
                        <p class="text-sm font-semibold text-gray-700">{{ a.citizen }} · {{ a.barangay ?? 'No barangay' }}</p>

                        <p v-if="a.note" class="mt-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-800"><strong>Note:</strong> {{ a.note }}</p>

                        <!-- Contact + location, big and easy to tap -->
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a v-if="a.phone" :href="'tel:' + a.phone"
                               class="inline-flex items-center gap-2 rounded-2xl bg-[#3D2470] px-5 py-3 text-base font-bold text-white shadow transition hover:bg-[#2E1B58]">
                                <Phone class="h-5 w-5" /> Call {{ a.phone }}
                            </a>
                            <span v-else class="inline-flex items-center gap-2 rounded-2xl bg-gray-100 px-5 py-3 text-sm font-semibold text-gray-600">
                                <Phone class="h-4 w-4" /> No phone number
                            </span>

                            <a v-if="a.lat !== null" :href="mapUrl(a)" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-base font-bold text-[#3D2470] ring-1 ring-[#3D2470]/30 transition hover:bg-[#F1E8F6]">
                                <MapPin class="h-5 w-5" /> Open map
                                <span class="text-xs font-medium text-gray-500">(±{{ a.accuracy ?? '?' }} m)</span>
                            </a>
                        </div>
                        <p v-if="a.lat === null" class="mt-3 flex items-start gap-2 text-sm font-semibold text-amber-700">
                            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" /> Location not available. Use the barangay and phone number.
                        </p>

                        <p v-if="a.ack_by" class="mt-3 text-xs text-gray-500">Handled by {{ a.ack_by }}</p>

                        <!-- Status actions -->
                        <div v-if="transitions[a.status]?.length" class="mt-5 flex flex-wrap gap-2 border-t pt-4">
                            <button v-for="s in transitions[a.status]" :key="s" type="button" @click="change(a, s)" :disabled="busy === a.id"
                                    :title="hints[s]"
                                    class="inline-flex flex-col items-start rounded-2xl px-5 py-2.5 text-left text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="s === 'dispatched' ? 'bg-red-600 text-white hover:bg-red-700'
                                        : s === 'resolved' ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                        : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50'">
                                {{ busy === a.id ? 'Saving…' : labels[s] ?? s }}
                                <span class="text-[11px] font-medium opacity-80">{{ hints[s] }}</span>
                            </button>
                        </div>
                    </div>
                </article>

                <div v-if="!open.length" class="flex flex-col items-center gap-2 rounded-3xl bg-emerald-50 p-10 text-center text-emerald-800 ring-1 ring-emerald-200">
                    <CheckCircle2 class="h-8 w-8" />
                    <p class="font-extrabold">No open alerts</p>
                    <p class="text-sm">New emergencies will appear here automatically.</p>
                </div>
            </section>

            <!-- Recently closed -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">Recently closed</h3>
                <ul v-if="recent.length" class="divide-y rounded-3xl bg-white text-sm shadow-sm ring-1 ring-black/5">
                    <li v-for="a in recent" :key="a.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <span class="capitalize"><span class="font-semibold">{{ a.type }}</span> · {{ a.citizen }} · {{ a.barangay }}</span>
                        <span class="flex items-center gap-2 text-gray-500">
                            <span :class="closedTone[a.status]" class="rounded-full px-2.5 py-0.5 text-xs font-bold capitalize">{{ a.status.replace('_', ' ') }}</span>
                            {{ a.at }}
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">Nothing closed recently.</p>
            </section>
        </div>

        <!-- Confirm closing an alert -->
        <div v-if="dialog" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @keydown.esc="dialog = null">
            <div role="dialog" aria-modal="true" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-extrabold text-[#3D2470]">
                    {{ dialog.status === 'resolved' ? 'Mark this alert as resolved?' : 'Mark this alert as a false alarm?' }}
                </h3>
                <p class="mt-1 text-sm font-semibold capitalize text-[#1B1340]">{{ dialog.alert.type }} · {{ dialog.alert.citizen }}</p>
                <p class="mt-2 text-sm text-gray-600">
                    {{ dialog.status === 'resolved'
                        ? 'The alert will move to "Recently closed".'
                        : 'Use this only when no emergency was found. The alert will move to "Recently closed".' }}
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="dialog = null" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="button" @click="send(dialog.alert, dialog.status)" :disabled="busy === dialog.alert.id"
                            class="rounded-xl px-5 py-2 text-sm font-bold text-white disabled:opacity-50"
                            :class="dialog.status === 'resolved' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-700 hover:bg-gray-800'">
                        {{ busy === dialog.alert.id ? 'Saving…' : 'Yes, confirm' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>